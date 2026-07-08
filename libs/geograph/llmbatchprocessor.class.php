<?php

//THis is a simplified data processer, optimzed for image pompts, but can do simple text prompts.
//  Wraps our getLLMResponse (which might get ported into the is library!), as well as uses get_ai_prompt/save_user_prompt for working with our prompt library
// Assumes simple single-turn process, using a library SYSTEM prompt, and a user prompt formed from databsae row (by callback)
// also assumes the prompt is specifically requesting JSON response which it automatically decodes.

/**
 * LLMBatchProcessor
 * * A structured pipeline utility optimized for running single-turn batch
 * processing jobs against LLMs (Text and Vision models).
 * * It manages common execution tasks out of the box:
 * - Progress state tracking, sharding pagination, and directional iteration.
 * - Automated Base64 image fetching and proxy UA injection.
 * - Rate-limit mitigation (429 handling with exponential backoff).
 * - Markdown JSON response cleaning and array normalization.
 * * Process Blueprint requirements:
 * 1. System Prompt -> Loaded from local library via $promptName.
 * 2. Query Callback -> Provides the targeted queue subset of data.
 * 3. Format Callback -> Converts a database row into a structured User Prompt string.
 * 4. Save Callback -> Receives decoded JSON response payload for persistence.
 */

class LLMBatchProcessor {
    protected $db;
    protected $params;
    protected $promptName;
    protected $internalModelName;
    protected $systemPrompt;

    // Callbacks
    protected $queryCallback;
    protected $formatPromptCallback;
    protected $saveDataCallback;

    public function __construct($db, array $params, string $promptName, bool $imagePrompt = false, string $internalModelName = '') {
        $this->db = $db;
        $this->params = array_merge([
            'print' => true, 'provider' => 'open',
            'direction' => false, 'restart' => false, 'shard' => false,
            'reason' => false, 'ai_model' => '',
            'save' => false, 'encode' => false, 'sleep' => false,
            'max_tokens' => 2048
        ], $params);

        $this->promptName = $promptName;
        $this->internalModelName = $internalModelName ?: $promptName; //used for labeller_progress table, seperate variable, as could be different to the prompt name!
        $this->params['image_prompt'] = $imagePrompt;

	//it seems nvidia uses vllm, which can technicall accept a http URL, BUT with no control over the user-agent, seems often blocked by cloudflare
	// .. force encode mode, so that we fetch the image and bundle it into the request
	if ($this->params['provider'] == 'nvidia' && $this->params['image_prompt'])
		$this->params['encode'] = true;

	//also for nvidia, there is strict rate limit, so make sure enable OUR sleep based rate limiting
	if ($this->params['provider'] == 'nvidia')
		$this->params['sleep'] = true;


        // Append shard data if applicable to isolate the tracking identifier
        if (!empty($this->params['shard']) && preg_match('/^(\d+)\/(\d+)$/', $this->params['shard'])) {
            $this->internalModelName .= $this->params['shard'];
        }

        // Fetch the system prompt once
        $this->systemPrompt = get_ai_prompt($this->promptName);
    }

    public function setQueryCallback(callable $callback) {
        $this->queryCallback = $callback;
    }

    public function setFormatPromptCallback(callable $callback) {
        $this->formatPromptCallback = $callback;
    }

    public function setSaveDataCallback(callable $callback) {
        $this->saveDataCallback = $callback;
    }

    /**
     * Builds the final dynamic WHERE array incorporating tracking logic
     */
    protected function buildWhereClause($last_id) {
        $where = [];
        if (!empty($this->params['shard']) && preg_match('/^(\d+)\/(\d+)$/', $this->params['shard'], $m)) {
            $where[] = "gi.gridimage_id MOD {$m[2]} = {$m[1]}";
        }

        if ($this->params['direction'] == 'forward') {
            if (!empty($last_id)) $where['last'] = "gi.gridimage_id > " . intval($last_id);
        }
        if ($this->params['direction'] == 'backward') {
            if (!empty($last_id)) $where['last'] = "gi.gridimage_id < " . intval($last_id);
        }

        return $where ?: [1];
    }

    /**
     * Executes the pipeline
     */
    public function run(?callable $sqlBuilder = null) {
        // Fallback to the setter callback if none is passed directly to run()
        $sqlBuilder = $sqlBuilder ?: $this->queryCallback;

        if (!$sqlBuilder) {
            throw new Exception("No SQL query callback has been defined.");
        }

        $color = "\033[32m";
        $yellow = "\033[33m";
        $white = "\033[0m";

        $last_id = null;
        // Only attempt tracking load if progress tracking features are activated via direction parameters
        if (empty($this->params['restart']) && !empty($this->params['direction'])) {
            $last_id = $this->db->getOne("SELECT last_id FROM labeller_progress WHERE model = ? AND direction = ?", [
                $this->internalModelName, $this->params['direction']
            ]);
        }

        $whereClauses = $this->buildWhereClause($last_id);
        $order = ($this->params['direction'] == 'forward') ? "gridimage_id ASC" : "gridimage_id DESC";

        // Invoke user's SQL builder to get custom query
        $sql = $sqlBuilder($whereClauses, $order, $this->params);

        print "-- WHERE " . implode(" AND ", $whereClauses) . "\n";
        if ($this->params['print']) {
            print "{$this->systemPrompt}\n\n";
        }

        if (!empty($this->params['explain'])) {
		print "$sql;\n";
		$rows = $this->db->getAll("EXPLAIN $sql");
		print implode("\t",array_keys($rows[0]))."\n";
		foreach($rows as $row)
			print implode("\t",array_values($row))."\n";
		exit;
	}

        $rs = $this->db->Execute($sql);
        if (!$rs || $rs->EOF) {
	    print "-- No Rows to Process\n";
            if ($this->params['print']) print "$sql;\n";
            return;
        }

        $fetched_id = null;
        $sleep = 1;
        $path = '';

        while (!$rs->EOF) {

            // 1. First Callback: Format Text Context
            $userTextContent = call_user_func($this->formatPromptCallback, $rs->fields);

            // If the callback returns null, skip processing entirely for this record
            if ($userTextContent === null) {
                print "Skipping row: " . ($rs->fields['gridimage_id'] ?? $rs->fields['snippet_id'] ?? 'unknown') . " (skipped by callback)\n";
                $rs->MoveNext();
                continue;
            }

            // 2. Core Image Handling Logic
            if ($this->params['image_prompt']) {
                $currentRowId = $rs->fields['gridimage_id'];

                if ($fetched_id != $currentRowId) {
                    $image = new GridImage;
                    $image->fastInit($rs->fields);
                    $path = $image->getSquareThumbnail(224, 224, 'fullpath');

                    if (basename($path) == 'error.jpg') {
                        print_r($rs->fields);
                        print "Failed fetching image thumbnail\n";
                        exit;
                    }

                    if ($this->params['encode']) {
                        ini_set("user_agent", "Internal Request");
                        $imageData = file_get_contents($path);
                        print "Fetched " . strlen($imageData) . " bytes for image $path\n";
                        $base64Image = base64_encode($imageData);
                        $path = "data:image/jpeg;base64,{$base64Image}";
                    }
                    $fetched_id = $currentRowId;
                }

            // Bundle Text with Image for Multi-modal LLM request format
                $userPromptPayload = [
                    ['type' => 'text', 'text' => $userTextContent],
                    ['type' => 'image_url', 'image_url' => ['url' => $path]]
                ];

                if (empty($this->params['encode'])) {
                    print "path: {$path}\n";
                } else {
                    print "ID: {$currentRowId}\n";
                }

            } else {
            //otherwise just send the text content
                $userPromptPayload = $userTextContent;
                if (!empty($rs->fields['gridimage_id'])) {
                    print "ID: {$rs->fields['gridimage_id']}\n";
                }
            }

            if ($this->params['save']) {
                save_user_prompt($this->promptName, $userPromptPayload);
            }
            if ($this->params['print']) {
                print_r($userPromptPayload); print "\n\n"; exit;
            }

            // 3. Fire the LLM request
            $response = getLLMResponse($this->systemPrompt, $userPromptPayload, $this->params['provider'], $this->params['ai_model'], $this->params['max_tokens']);
            print "RESPONSE: $yellow$response$white\n";

            if ($this->params['reason'] && !empty($GLOBALS['reasoning'])) {
                print "Reasoning: $color{$GLOBALS['reasoning']}$white\n";
            }

            if (!empty($this->params['sleep'])) {
                if (is_numeric($response) && (int)$response === 429) {
                    $sleep *= 2;
                    print "Sleeping for $sleep\n";
                    sleep($sleep);
                    continue;  //calling continue, WITHOUT MoveNext, redoes same row!
                } elseif ($sleep > 1.2) {
                    $sleep *= 0.9;
                }
            }

            $json = json_decode(trim($response, "`json \t\n\r"), true);

            // 5. Second Callback: Process results & Save to DB
            call_user_func($this->saveDataCallback, $json ?? $response, $rs->fields, $this->params);

            // Update local tracking variables safely if parsing sequentially
            if ($this->params['direction'] == 'forward') {
                $last_id = max($last_id, $rs->fields['gridimage_id']);
            } elseif ($this->params['direction'] == 'backward') {
                $last_id = min($last_id, $rs->fields['gridimage_id']);
            }

            print "\n\n" . str_repeat('~', 80) . "\n\n";
            $rs->MoveNext();

            if (!empty($this->params['sleep']) && !$rs->EOF) {
                print "Sleeping for $sleep\n";
                sleep($sleep);
            }
        }

        // 6. Finalize tracking persistency state out of loop if direction tracking is on
        if (!empty($last_id) && !empty($this->params['direction'])) {
            $this->db->Execute(
                "INSERT INTO labeller_progress (model, last_id, direction) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE last_id = VALUES(last_id)",
                [$this->internalModelName, $last_id, $this->params['direction']]
            );
        }
    }
}
