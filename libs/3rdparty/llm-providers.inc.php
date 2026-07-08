<?php

//NOTE: This code was hardcoded to use openai/gpt-oss-120b
//... but then openai/gpt-oss-safeguard-20b was released, which we wanted to try!
//... but then the number of models released exploded, so has grown a lot to add support range of models and providers

######################################

// general calling function
//... also NO check is made to see if the model is available by a provider (the caller, has to know which provider(s) to use for which)
// but note in particular should use 'open' for 'gpt-oss-safeguard-20b' but will use backup via bedrock if getting rate-limited.

function getLLMResponse($prompt, $user, $provider = 'cloudflare', $model = 'gpt-oss-120b', $max_tokens = 4096) {

        if ($provider=='cloudflare') {
                return callCloudflare($prompt, $user, $model);

        } elseif ($provider=='open' || $provider=='openrouter') {
                return callOpenRouter($prompt, $user, $max_tokens, $model);

        } elseif ($provider=='aws') {
		return bedrockChat($prompt, $user, $model, $region = 'eu-west-1', $tier = 'flex'); //$CONF['s3_region']

        } elseif ($provider=='lmstudio') {
		return callLMStudio($prompt, $user, $model);

        } elseif ($provider=='nvidia') {
		return callNvidia($prompt, $user, $model, $max_tokens);

        } elseif ($provider=='moondream' && $model == 'moondream/moondream3-preview') {
		//extract from our 'standard' image format!
		$userText = $user[0]['text'] ?? null;
		$image_url = $user[1]['image_url']['url'] ?? null;
		if (empty($userText) || empty($image_url))
			die("moondream only supports image prompt currently\n");
		//technically probably could ask a text query, wihtout image, but ignore that for now!
		return queryMoondream($image_url, "$prompt\n$userText");

        } elseif ($provider=='local') {
                return getLLMLocalResponse($prompt, $user, /* $model = */ 'gemma270m'); //for now, doesnt support other models anyway!
        } else {
		die("unknown provider\n");
	}
}

######################################

//we have an experimental API hosting a local (small) LLM

function getLLMLocalResponse($inputText, $user = false, $model = 'gemma270m') {
    global $CONF;
    $apiUrl = $CONF['embed_api'].'/generate';

	//our API does not support seperate prompts
	if (!empty($user))
		$inputText .= $user;

    // The data to send in the request body as a JSON string
    $postData = json_encode(['text' => $inputText, "model" => $model]);
    if ($postData === false) {
        error_log('getLLMResponse: Failed to JSON encode postData.');
        return [];
    }

static $ch;

if (empty($ch)) {

    // Initialize a cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
}

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);

    // Execute the cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $decoded_response = [];

    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log('getLLMResponse: cURL error: ' . curl_error($ch));
    } elseif ($http_code !== 200) {
        // Handle non-200 HTTP responses from the embedding API
        error_log('getLLMResponse: API returned HTTP status ' . $http_code . ': ' . $response);
    } else {
        // Decode the JSON response
        $decoded_response = json_decode($response, true);

        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('getLLMResponse: JSON decode error: ' . json_last_error_msg() . ' for response: ' . $response);
        }
    }

    // Close the cURL session
//    curl_close($ch); -- keep it as now stored statically for reuse
    return $decoded_response;
}

######################################

/**
 * Calls the Cloudflare Workers AI API to run an LLM.
 *
 * @param string $prompt The system prompt.
 * @param string $user The user's input.
 * @return string The generated text from the LLM.
 */
function callCloudflare($prompt, $user = null, $model = 'gpt-oss-120b') {
    global $CONF;
    $accountId = $CONF['cloudflare_account_id'];
    $apiToken = $CONF['cloudflare_api_token'];
    $modelName = '@cf/openai/'.$model; //currently assumes openai models!
	if (!defined('QUIET'))
	    print "Using Model $modelName (via Cloudflare)\n";
    $url = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$modelName}";

    $data = ['input' => $prompt . ($user ?? '')];

    $payload = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $headers = [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
    } else {
        $responseData = json_decode($response, true);
        if (isset($responseData['success']) && $responseData['success']) {
            if (isset($responseData['result']['output']) && is_array($responseData['result']['output'])) {
                foreach ($responseData['result']['output'] as $outputItem) {
                    if (isset($outputItem['content']) && is_array($outputItem['content']) && !empty($outputItem['content'][0]['text']) && $outputItem['content'][0]['type'] == 'output_text') {
                        $generatedText = $outputItem['content'][0]['text'];
                        curl_close($ch);
                        return $generatedText;
                    }
                }
            }
        } else {
            error_log("API Error: " . print_r($responseData['errors'], true));
        }
    }
    curl_close($ch);
    return null;
}

######################################

/**
 * Calls the OpenRouter API to run an LLM.
 *
 * @param string $prompt The system prompt.
 * @param string $user The user's input.
 * @return string The generated text from the LLM.
 */
function callOpenRouter($prompt, $user = null, $maxTokens = 2048, $model = 'gpt-oss-120b') {
    global $CONF;
    $apiKey = $CONF['openrouter_api_key'];

    if (strpos($model,'/') === FALSE) {
	    $modelName = 'openai/'.$model; //currently assumes openai models!
    } else {
	    $modelName = $model;
    }

	if (preg_match('/#(\w+)$/',$model, $m)) {
		$reasoning = $m[1];
		$modelName = preg_replace('/#(\w+)$/','',$modelName);
	}

    if (!defined('QUIET'))
        print "Using Model $modelName (via OpenRouter)\n";
    $url = "https://openrouter.ai/api/v1/chat/completions";

    $messages = [
        ['role' => 'system', 'content' => $prompt]
    ];
    if ($user) {
        $messages[] = ['role' => 'user', 'content' => $user];
    }

//"model": "@preset/tag-classifer", - has the above system prompt, but also defines the model!

    $data = [
        'model' => $modelName,
        'service_tier' => 'flex',
        'messages' => $messages,
        'temperature' => 0.2, // Add optional parameters as needed
        'max_tokens' => $maxTokens,
        'provider' => [
           'sort' => 'price', //prioritize low prices, and not apply any load balancing
        ],
    ];
	//we can manually specify other models now!
    if (strpos($modelName,'openai/') === 0) {
        $data['provider']['max_price'] = ["prompt" => 0.2, "completion" => 0.49]; //optimized for current prices for gpt-oss-120b,
    }
    if (!empty($reasoning)) {
        $data['reasoning'] = array(
		'effort'=>$reasoning,
		//'enabled'=> $reasoning != 'none'
	);
    }

    $payload = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

//print_r($response);

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    } else {
        $responseData = json_decode($response, true);

	if (!empty($responseData['error']['code']) && $responseData['error']['code'] != 200) {
		//if ($responseData['error']['code'] == 429 && ... in fact might as well 'redirect' on any error...
		if ($modelName == "openai/gpt-oss-safeguard-20b") { //&& provider==open :)
			//we only have some models enabled on bedrock! But groq seems to have throtted on openrouter
			return bedrockChat($prompt, $user, $modelName);
		}
		if ($responseData['error']['code'] == 402) { //insuffient credits
			die("No Credits Left. Aborting\n"); //stop more loops!
		}
		sleep(5); //to otherwise slow down loops!
	}

        if (isset($responseData['choices'][0]['message']['reasoning'])) {
		$GLOBALS['reasoning'] = $responseData['choices'][0]['message']['reasoning'];
	}

        // OpenRouter's API response structure is different
        if (isset($responseData['choices'][0]['message']['content'])) {
            $generatedText = $responseData['choices'][0]['message']['content'];
            curl_close($ch);
            return $generatedText;
        } else {
            error_log("API Error: " . print_r($responseData, true));
            curl_close($ch);
            return null;
        }
    }
}

//make this a generial function for calling OpenRouter API (without input) - chat/completions has its own function above!
function callOpenRouterKey($url = "https://openrouter.ai/api/v1/key") {
    global $CONF;
    $apiKey = $CONF['openrouter_api_key'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        'Authorization: Bearer ' . $apiKey,
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    } else {
        return json_decode($response, true);
    }
}

######################################

/**
 * Generates a unified embedding vector via OpenRouter's embeddings API.
 * Supports text-only, image-only, or combined multimodal inputs (using EmbeddingGamini 2 syntax!).
 *
 * @param array|string $input Configuration array or plain string.
 * @param string $model The OpenRouter model identifier.
 * @param int $dimensions The target dimensions for the vector.
 * @return array The raw float array containing the vector.
 * @throws Exception If the cURL request fails or returns an error response.
 */
function getEmbeddingViaOpenRouter($input, $model = 'google/gemini-embedding-2', $dimensions = 3072): array
{
    global $CONF;
    $apiKey = $CONF['openrouter_api_key'] ?? '';

    if (empty($apiKey)) {
        throw new Exception("OpenRouter API key is missing from configuration.");
    }

    $structuredInput = [];

    // Case 1: Plain string input
    if (is_string($input)) {
        $structuredInput[] = [
            'type' => 'text',
            'text' => 'task: sentence similarity | query: ' . trim($input)
        ];
    }
    // Case 2: Structured array input
    elseif (is_array($input)) {
        $type = $input['type'] ?? 'document';
	if (!empty($input['query'])) $type='query';

        // 1. Process Text Context depending on the task intent
        if ($type === 'query') {
            //if no query, assumes must be providing image as the query!
            $queryText = !empty($input['query']) ? trim($input['query']) : "Find the most relevant match for this asset";
            if (!empty($queryText)) {
                $structuredInput[] = [
                    'type' => 'text',
                    'text' => 'task: search result | query: ' . trim($queryText)
                ];
            }
        } else { // Defaults to 'document' ingestion style
            $title = $input['title'] ?? 'none';
            $text = $input['text'] ?? null;

            $compiledText = "title: " . trim($title);
            if (!empty($text)) {
                $compiledText .= " | text: " . trim($text);
            }

            $structuredInput[] = [
                'type' => 'text',
                'text' => $compiledText
            ];
        }

        // 2. Process and append the image if it exists
        if (!empty($input['image'])) {
            $structuredInput[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $input['image']
                ]
            ];
        }
    }

    if (empty($structuredInput)) {
        throw new Exception("The provided input yielded an empty payload.");
    }

    // Double brackets around $structuredInput forces OpenRouter to pass
    // the text and image components together as ONE single item to embed.
    $payloadData = [
        'model' => $model,
        'dimensions' => $dimensions,
        'input' => [['content' => $structuredInput]]
    ];

    $payload = json_encode($payloadData);

    // Prepare and execute the cURL request
    $ch = curl_init('https://openrouter.ai/api/v1/embeddings');

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $errorMsg = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error occurred: " . $errorMsg);
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if ($statusCode !== 200) {
        $apiError = $responseData['error']['message'] ?? 'Unknown OpenRouter Error';
        throw new Exception("OpenRouter API returned HTTP {$statusCode}: {$apiError}");
    }

    // Dig into OpenAI style data arrays to pluck out the float vector
    if (!isset($responseData['data'][0]['embedding'])) {
        throw new Exception("Failed to retrieve embedding vector from response structure.");
    }

    return $responseData['data'][0]['embedding'];
}

######################################

function bedrockChat($systemPrompt, $userPrompt, $modelId, $region = 'eu-west-1', $tier = 'flex') {

	//openai.gpt-oss-safeguard-20b
    if (strpos($modelId,'/') === FALSE) {
	    $modelId = 'openai.'.$modelId; //currently assumes openai models!
    } else {
	    $modelId = str_replace('/','.',$modelId);
    }

	if (!defined('QUIET'))
	    print "Using Model $modelId (via bedrock-runtime.$region)\n";

    $filesystem = new FileSystem(); //requied to get the STS token
    $host = "bedrock-runtime.$region.amazonaws.com";
    $method = "POST";
    $uri = "/model/$modelId/converse";

    // Build the payload
    $payload = [
        "system" => [["text" => $systemPrompt]],
        "messages" => [
            [
                "role" => "user",
                "content" => [["text" => $userPrompt]]
            ]
        ],
        "inferenceConfig" => [
            "maxTokens" => 2048*2,
            "temperature" => 0.2
        ]
    ];

    // Add the Service Tier parameter
    // Valid values: 'flex', 'priority', 'default'
    if ($tier !== 'default') {
        $payload["serviceTier"] = ["type" => $tier];
    }

    $data = json_encode($payload);

    $amzHeaders = [
        "X-Amz-Security-Token" => S3::$securityToken,
        "Content-Type" => "application/json"
    ];

    $otherHeaders = [
        "Host" => $host,
        "Date" => gmdate('D, d M Y H:i:s T'),
    ];

    // Sign the request
    $newHeaders = $filesystem->__getSignatureV4(
        $amzHeaders,
        $otherHeaders,
        $method,
        $uri,
        $data,
        'bedrock'
    );

    $headers = [];
    foreach (array_merge($amzHeaders, $otherHeaders, $newHeaders) as $k => $v) {
        $headers[] = "$k: $v";
    }

    $ch = curl_init("https://$host$uri");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    curl_setopt($ch, CURLOPT_TIMEOUT, 180); // particully as 'flex', could be slow!

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
print_r($response);

        throw new Exception("Bedrock Error ($httpCode): " . $response);
    }

    $result = json_decode($response, true);

// Extracting the reply and reasoning
    // Note: 'reasoning' is returned if the model supports "Thinking/Reasoning" blocks
    $reply = $result['output']['message']['content'][0]['text'] ?? '';
    
    // Look for reasoning content if available (specific to "Thinking" models)
    $reasoning = null;
    foreach ($result['output']['message']['content'] as $part) {
	// Check for the actual response text (might not of been at 0)
        if (isset($part['text'])) {
            $reply = $part['text'];
        }
        if (isset($part['reasoningContent']['text'])) {
            $reasoning = $part['reasoningContent']['text'];
            break;
        }
    }

if (empty($reply)) {
	print str_repeat('-',80)."\n";
	print $response."\n";
	print str_repeat('-',80)."\n";
}

	//this is our standard reply format...
    $GLOBALS['reasoning'] = $reasoning;
    return $reply;


    return [
        'reply' => $reply,
        'reasoning' => $reasoning
    ];

    return json_decode($response, true);
}

######################################

function callNvidia($prompt, $user = null, $modelName = 'google/diffusiongemma-26b-a4b-it', $max_tokens = 4096) {
    $url = "https://integrate.api.nvidia.com/v1/chat/completions";

    global $CONF;
    echo "Using $modelName at {$url}\n";

    // Build the messages array for the OpenAI-compatible API
    $messages = [
        ['role' => 'system', 'content' => $prompt],
        ['role' => 'user', 'content' => $user]
    ];

    $payload = json_encode([
        'model' => $modelName,
        'messages' => $messages,
	'max_tokens' => $max_tokens,
        'temperature' => 0.2, // You can adjust this value
        'stream' => false
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $headers = [
        'Authorization: Bearer '.$CONF['nvidia_api_key'],
        'Accept: application/json',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    $responseData = json_decode($response, true);
    curl_close($ch);

	if (!empty($responseData['status']) && $responseData['status'] == 429) {
		print("Error: ".$responseData['title']."\n\n");
		return $responseData['status'];
	}

    // Check for errors in the API response
    if (isset($responseData['error'])) {
        error_log("API Error: " . $responseData['error']['message']);
        return null;
    }

if (empty($responseData['choices'][0]['message']['content'])) {
    print str_repeat('-',80)."\n";
    print_r($response); print "\n";
    print str_repeat('-',80)."\n";
}


    // Extract the generated text from the OpenAI-compatible response format
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    }

    return null;
}

######################################

/**
 * Queries an image with a specific text question using the Moondream API.
 * - the usage is very differnt to using multimodal LLM
 *
 * @param string $imagePathOrUrl Local file path, web URL, or base64 data URI of the image.
 * @param string $question       The question you want to ask about the image.
 * @return string                The generated answer from the model.
 * @throws Exception             If the image cannot be processed or the API request fails.
 */
function queryMoondream(string $imagePathOrUrl, string $question): string
{
	global $CONF;

    $apiUrl = 'https://api.moondream.ai/v1/query';
    $apiKey = $CONF['moondream_api_key'];
    $imageData = '';

    // 1. Handle image input types and convert to Base64 if necessary
    if (strpos($imagePathOrUrl, 'data:image/') === 0) {
        // Already a base64 data URI
        $imageData = $imagePathOrUrl;

    } elseif (filter_var($imagePathOrUrl, FILTER_VALIDATE_URL)) {
        ini_set('user_agent', 'Internal Request');

        // It's a web URL. Fetch the content and convert to base64.
        $imageContent = @file_get_contents($imagePathOrUrl);
        if ($imageContent === false) {
            throw new Exception("Failed to fetch image from URL: $imagePathOrUrl");
        }

        // Try to guess the mime type from the URL extension (fallback to jpeg)
        $extension = strtolower(pathinfo(parse_url($imagePathOrUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        $mimeType = in_array($extension, ['png', 'gif', 'webp']) ? "image/{$extension}" : 'image/jpeg';

        $imageData = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);

    } elseif (file_exists($imagePathOrUrl)) {
        // It's a local file. Read and convert to base64.
        $imageContent = @file_get_contents($imagePathOrUrl);
        if ($imageContent === false) {
            throw new Exception("Failed to read local file: $imagePathOrUrl");
        }
        $mimeType = mime_content_type($imagePathOrUrl) ?: 'image/jpeg';
        $imageData = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);

    } else {
        throw new Exception("Invalid image source provided. Must be a valid local path, URL, or Base64 data URI.");
    }

    // 2. Prepare payload for the query endpoint
    $payload = json_encode([
        'image_url' => $imageData,
        'question'  => $question,
        'stream'    => false
    ]);

    // 3. Initialize and configure cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Moondream-Auth: ' . $apiKey
    ]);

    // Execute the request
    $response = curl_exec($ch);

    // Check for cURL connection errors
    if (curl_errno($ch)) {
        $errorMsg = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: " . $errorMsg);
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Decode the response
    $responseData = json_decode($response, true);

    // 4. Handle API response and return answer
    if ($statusCode !== 200) {
        $errorMessage = $responseData['error'] ?? 'Unknown API Error';
        throw new Exception("Moondream API Error (Status $statusCode): " . $errorMessage);
    }

    if (!isset($responseData['answer'])) {
        throw new Exception("Invalid API response structure. 'answer' field is missing.");
    }

    return $responseData['answer'];
}

######################################


/**
 * Calls the local LM Studio API to run an LLM.
 *
 * @param string $prompt The system prompt.
 * @param string $user The user's input.
 * @return string The generated text from the LLM, or null on failure.
 */
function callLMStudio($prompt, $user = null, $modelName = 'gpt-oss-120b') {
    // LM Studio's default API endpoint
    $url = "http://localhost:1234/v1/chat/completions";
    // The model name is a local identifier, typically found in LM Studio.
    // Replace 'gpt-oss-120b' with the actual model name you've loaded in LM Studio.

    echo "Using Local Model via LM Studio at {$url}\n";

    // Build the messages array for the OpenAI-compatible API
    $messages = [
        ['role' => 'system', 'content' => $prompt],
        ['role' => 'user', 'content' => $user]
    ];

    $payload = json_encode([
        'model' => $modelName,
        'messages' => $messages,
        'temperature' => 0.7, // You can adjust this value
        'stream' => false // Set to true for streaming responses
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $headers = [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    $responseData = json_decode($response, true);
    curl_close($ch);

    // Check for errors in the API response
    if (isset($responseData['error'])) {
        error_log("API Error: " . $responseData['error']['message']);
        return null;
    }

    // Extract the generated text from the OpenAI-compatible response format
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    }

    return null;
}

######################################

/**
 * Sends a batch of tags and few-shot examples to the LLM microservice.
 *
 * @param array $tags An array of tags to classify, where each element is ['tag_id' => int, 'tag' => string].
 * @return array An array of classified tags, or an empty array on failure.
 */
function classify_tags_batch($tags, $provider = 'open', $print = false) {

    if (empty($tags)) {
        return [];
    }

    //we've moved the prompt library into the database (for better version tracking)
    $prompt = get_ai_prompt("tag-classification");

//if ($param['provider']=='open')
//	$prompt .= 'Provide your reasoning in a brief, one-sentence summary.\n\n';
// (was an experiment to see if could shorten the reasoning)

    $user = "Tags to classify:\n";
    foreach ($tags as $tag_item) {
        $user .= "- \"{$tag_item['tag']}\"\n";
    }

    if (!empty($print)) {
        print "$prompt\n$user\n";
        exit;
    }

    return getLLMResponse($prompt, $user, $provider);
}

################################

function classify_query_batch($queries, $provider = 'open', $print = false, $model = 'gpt-oss-120b') {

    if (empty($queries)) {
        return [];
    }

	//we've moved the prompt library into the database (for better version tracking)
    $prompt = get_ai_prompt("classify-query");

    $user = "Queries to classify:\n";
    foreach ($queries as $item) {
        $user .= "- ".json_encode($item['query'])."\n";
    }

    if (!empty($print)) {
        print "$prompt$user\n";
        exit;
    }

    return getLLMResponse($prompt, $user, $provider, $model);
}

################################

/**
 * Fetches content from the r.jina.ai API for a given URL, handling
 * HTTP errors and API-specific JSON error messages gracefully.
 *
 * @param string $targetUrl The URL of the page to extract content from (e.g., 'https://www.google.com').
 * @return array Returns an associative array with keys 'success' (bool), 'content' (string), or 'error' (string).
 */
function fetchJinaContent(string $targetUrl): array
{
    // 1. Build the full Jina API endpoint URL
    //$jinaApiUrl = "https://r.jina.ai/" . urlencode($targetUrl);
    $jinaApiUrl = "https://r.jina.ai/" . $targetUrl;

    // 2. Initialize cURL session
    $ch = curl_init();

    // 3. Set cURL options
    curl_setopt($ch, CURLOPT_URL, $jinaApiUrl);
    // Return the transfer as a string instead of outputting it directly
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Allow redirects (important for robust fetching)
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Set a reasonable timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // Optionally: Set user-agent to mimic a browser
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP JinaAI Client/1.0');

    // 4. Execute the cURL request and get the response body
    $responseBody = curl_exec($ch);

    // 5. Check for cURL connection errors (e.g., DNS failure, timeout)
    if (curl_errno($ch)) {
        $errorMessage = 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'error' => $errorMessage
        ];
    }

    // 6. Get the HTTP status code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // 7. Close the cURL session
    curl_close($ch);

    // 8. Handle successful response (200 OK)
    if ($httpCode === 200) {
        return [
            'success' => true,
            'content' => $responseBody
        ];
    }

    // 9. Handle non-200 HTTP status codes (errors)
    
    // Attempt to decode the response body as JSON (Jina returns JSON errors)
    $errorData = json_decode($responseBody, true);

    if (json_last_error() === JSON_ERROR_NONE && 
        isset($errorData['message']) && 
        isset($errorData['code'])) {
        
        // This is a structured Jina API error (e.g., 400 ParamValidationError)
        $errorMessage = sprintf(
            "Jina API Error (HTTP %d, Code %d): %s",
            $httpCode,
            $errorData['code'],
            $errorData['message']
        );
        
        return [
            'success' => false,
            'error' => $errorMessage,
            // You can return the full error data for debugging if needed
            'api_data' => $errorData
        ];
    }

    // Fallback for non-200 status codes that don't match the expected JSON format
    $errorMessage = sprintf("HTTP Error %d: The API returned an unexpected non-200 status code.", $httpCode);
    
    return [
        'success' => false,
        'error' => $errorMessage,
        'response_body' => $responseBody
    ];
}

#####################

function get_ai_prompt($prompt_name) {
	global $db;
	if (empty($db))
		$db = GeographDatabaseConnection(true);
	//todo, this is where could insert example if needed!
	return $db->getOne("SELECT content FROM ai_prompt WHERE active=1 AND prompt_name = ".$db->Quote($prompt_name));
}

function save_user_prompt($prompt_name, $user, $example = '') {
	global $db;
	if (empty($db) || !empty($db->readonly))
		$db = GeographDatabaseConnection(false);
	$row = $db->getRow("SELECT * FROM ai_prompt WHERE active=1 AND prompt_name = ".$db->Quote($prompt_name));
	$column = null;
	$i = 1;
	while(!empty($row['user'.$i]))
		$i++;
	if (array_key_exists('user'.$i, $row)) { //isset wont 'see' null!
		if (is_array($user))
			$user = json_encode($user);

		$updates = array();
		$updates['user'.$i] = $user;
		if (!empty($example) && empty($row['example'])) {
			if (is_array($example))
	                        $example = json_encode($example);
			$updates['example'] = $example;
		}

		$update = "UPDATE ai_prompt SET `".implode('` = ?,`',array_keys($updates))."` = ? WHERE active=1 AND prompt_name = ".$db->Quote($prompt_name);
		$db->Execute($update, array_values($updates));
		print "Saved to user$i on $prompt_name.\n";
	}
}
