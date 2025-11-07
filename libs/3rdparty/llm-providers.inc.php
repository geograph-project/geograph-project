<?php

//NOTE: This code was hardcoded to use openai/gpt-oss-120b
//... but then openai/gpt-oss-safeguard-20b was released, which we want to try!
//... so for now, the model override, is basic, and assumes openai for now!
//... also no check is made to see if the model is available by each provider, (but know OpenRouter has the new model already)


######################################

function getLLMResponse($prompt, $user, $provider = 'cloudflare', $model = 'gpt-oss-120b') {

        if ($provider=='cloudflare') {
                return callCloudflare($prompt, $user, $model);

        } elseif ($provider=='open') {
                return callOpenRouter($prompt, $user, /* $max_tokens = */ 2048*2, $model);

        } elseif ($provider=='lmstudio') {
		return callLMStudio($prompt, $user, $model);

        } else {
                return getLLMLocalResponse($prompt, $user, /* $model = */ 'gemma270m'); //for now, doesnt support other models anyway!
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
    $accountId = $CONF['CLOUDFLARE_ACCOUNT_ID'];
    $apiToken = $CONF['CLOUDFLARE_API_TOKEN'];
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
    $apiKey = $CONF['OPENROUTER_API_KEY'];

    $modelName = 'openai/'.$model; //currently assumes openai models!

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
        'messages' => $messages,
        'temperature' => 0.2, // Add optional parameters as needed
        'max_tokens' => $maxTokens,
        'provider' => [
           'sort' => 'price', //prioritize low prices, and not apply any load balancing
	   'max_price' => ["prompt" => 0.2, "completion" => 0.49], //optimized for current prices for gpt-oss-120b,
        ],
    ];
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

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    } else {
        $responseData = json_decode($response, true);

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

function callOpenRouterKey() {
    global $CONF;
    $apiKey = $CONF['OPENROUTER_API_KEY'];

    $url = "https://openrouter.ai/api/v1/key";

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

    // Define few-shot examples and classification rules for the LLM prompt.
    $prompt_examples = [
        ["tag" => "London", "class" => "[named-place]"],
        ["tag" => "South Downs Way", "class" => "[named-path]"],
        ["tag" => "National Cycle Route 5", "class" => "[named-cyclepath]"],
        ["tag" => "St Peters Church", "class" => "[named-poi]"],
        ["tag" => "View to Nant Gwrtheyrn", "class" => "[related-to]"],
        ["tag" => "Lake District National Park", "class" => "[named-area]"],
        ["tag" => "York Street (Belfast)", "class" => "[named-feature]"],
        ["tag" => "place:Frinkley Lane", "class" => "[named-feature]"],
        ["tag" => "Meredith", "class" => "[named-person]"],
        ["tag" => "Bayliss Engine", "class" => "[branded-object]"],
        ["tag" => "Art-Deco", "class" => "[architectural-style]"],
        ["tag" => "petrifying well", "class" => "[geographical]"],
        ["tag" => "farm", "class" => "[geographical]"],
        ["tag" => "hotel", "class" => "[geographical]"],
        ["tag" => "national park", "class" => "[geographical]"],
        ["tag" => "greenspace", "class" => "[geographical]"],
        ["tag" => "Steam Engine", "class" => "[object]"],
        ["tag" => "cart", "class" => "[object]"],
        ["tag" => "flower", "class" => "[object]"],
        ["tag" => "autumn", "class" => "[temporal]"],
        ["tag" => "snowscene", "class" => "[temporal]"],
        ["tag" => "sunset", "class" => "[temporal]"],
        ["tag" => "1978", "class" => "[date]"],
        ["tag" => "nudists", "class" => "[unsafe]"],
        ["tag" => "close", "class" => "[ambiguous]"],
        ["tag" => "Bunn s Lane footpath", "class" => "[typo]"],
        ["tag" => "fly tipping", "class" => "[other]"],
    ];

    // Build the prompt string for the LLM.
    $prompt = "You are a professional tag classifier. Your task is to classify a list of given tags into one of the following categories:\n\n";
    $prompt .= "- [unsafe]: Potentially sensitive, or not suitable for children and/or specifically adult themed (This category overrules others).\n";
    $prompt .= "- [named-place]: An actual, named settlement or place (not named road, even if in a specific place).\n";
    $prompt .= "- [named-feature]: A named feature like a river, lake, road, or hill.\n";
    $prompt .= "- [named-path]: A specific, named walking path.\n";
    $prompt .= "- [named-cyclepath]: A numbered or named cycle route.\n";
    $prompt .= "- [named-area]: A named area, national park, SSNI or similar.\n";
    $prompt .= "- [named-poi]: A specific named building, company, or point of interest (e.g., 'St Peters Church', **not** general 'farm' or 'hotel').\n";
    $prompt .= "- [named-person]: The tag refers to a specific person, e.g. architect.\n";
    $prompt .= "- [architectural-style]: The tag refers to a specifically named architectural style or period.\n";
    $prompt .= "- [related-to]: A tag that notes a **named** place/feature but is not the place itself.\n";
    $prompt .= "- [event]: The tag relates to a specific event (e.g. 'Geograph Meetup').\n";
    $prompt .= "- [geographical]: A general geographical term or feature that is not a named place.\n";
    $prompt .= "- [branded-object]: A specicaly named branded object, that mentions a company or similar brand.\n";
    $prompt .= "- [object]: A movable object, or item not tied to specific location.\n";
    $prompt .= "- [date]: The tag appears to be a specific date or year.\n";
    $prompt .= "- [temporal]: The tag relates to a time, period, season or weather condition. When the photo taken, other than a specific date.\n";
    $prompt .= "- [ambiguous]: Could mean different things depending on context (tag alone does not indentify what it represents).\n";
    $prompt .= "- [typo]: Looks like the tag contains a typo or spelling mistake (This category overrules others).\n";
    $prompt .= "- [other]: Anything else that doesn't fit the above categories.\n\n";
    $prompt .= "The capitalization of the tag is not definitive, not all named entities are capitalized.\n\n";
    $prompt .= "Here are some examples of tag classifications:\n";
    foreach ($prompt_examples as $example) {
        $prompt .= "Tag: \"{$example['tag']}\", class: \"{$example['class']}\"\n";
    }

    $prompt .= "You can ignore a 'place:' prefix on a tag, and still try to classify as you otherwise would. The user may not been very precise, and included the prefix on different types of tag.\n";

//if ($param['provider']=='open')
//	$prompt .= 'Provide your reasoning in a brief, one-sentence summary.\n\n';

//Todo (need testing!) but perhaps could try JSON Lines, to be less fragile in decoding.
//"Your response must be a series of JSON objects, one per line, conforming to this schema: { "tag": "string", "class": "string" }. Do not include any other text, comments, or explanations before or after the JSON."


    $prompt .= 'Your response must be a single JSON array, conforming to this schema: [ { "tag": "string", "class": "string" } ].
Do not include any other text, comments, or explanations before or after the JSON.'."\n";
    $user = "Tags to classify:\n";
    foreach ($tags as $tag_item) {
        $user .= "- \"{$tag_item['tag']}\"\n";
    }

    if (!empty($print)) {
        print "$prompt$user\n";
        exit;
    }

    return getLLMResponse($prompt, $user, $provider);
}

################################


function classify_query_batch($queries, $provider = 'open', $print = false) {

    if (empty($queries)) {
        return [];
    }

$prompt = <<<'EOD'
You are a professional classifier. Your task is to classify a list of given search queries with one or more of the following labels:

- [branded]: specifically mentions geograph project by name.
- [navigational]: they likly looking for a webpage, not content directly, example 'geograph search' is looking for search page, rather than content from geograph.
- [location]: appears to be looking for a specific location, rather than actual photos (eg "wembley stadium postcode" or "directions to ...").

- [named-place]: they are likly looking for a specific named place (ie singular place that that could be identified on a map).
- [named-feature]: A named natural feature like a river, lake, or hill.
- [named-area]: they are likly looking for a specific named area (like a county, island, national park, or similar, could be informal area/region like "southern england").
- [named-poi]: they are looking (for images of) a specific named point of interest (that is not a settlement), eg a castle, church or specific road.
- [named-path]: A specific, named walking path.
- [named-cyclepath]: A numbered or named cycle route.

- [something]: they are looking for something (could be that looking for something at a place (like "loch aslaich bothy" is looking for "bothy" at "loch aslaich") - or just looking for soemthing in general (for example "bridges" is an item).
- [item-at-location]: looking for a specific something in a specific place.

- [named-person]: looking for photos related to a specific named person like an artitect.
- [photographer]: looking for photos taken by a specific named contributor/photographer.
- [event]: photos related to specific event like a social gathering, meet, or other named event.
- [temporal]: looking for images on some data based criteral (like a specific year, or season, or historic/older images).
- [weather]: looking for images in speciifc weather condistions (eg snow, fog, rain etc)
- [mapped-feature]: looking for a something what likly is marked on maps (eg looking for cliffs in general).
- [architectural-style]: refers to a specifically named architectural style or period.

- [unsafe]: Potentially sensitive, or not suitable for children and/or specifically adult themed. Might provide misleading results (because the actual images have already been moderated, so dont have any unsafe images, but the query could still provide unsafe results.
- [extra-words]: contains word(s) that don't contribute to meaning (ie the site is specifically listing photos, so in query like "photos of llandudno", the  "photos" and "of" are extra words, and hence wouldnt actulyl be needed to by the search engine).
- [ambiguous]: Could mean different things depending on context (query alone does not indentify what it really looking for).
- [typo]: Looks like the query contains a typo or spelling mistake.
- [other]: Anything else that doesn't fit the above categories.

Here are some examples of labels:
[
	{"query":"geograph", "labels":["branded","navigational"]},
	{"query":"forest lodge windsor great park", "labels":["named-poi","named-area"]},
	{"query":"forest lodge windsor", "labels":["named-poi","named-place"]},
	{"query":"geograph uk", "labels":["branded","navigational"]},
	{"query":"jeremy beadle grave", "labels":["named-person","specific-item"]},
	{"query":"east gate piece hall", "labels":["named-poi","named-place"]},
	{"query":"frogs end farm hargrave", "labels":["named-poi","named-place"]},
	{"query":"river witham", "labels":["named-feature"]},
	{"query":"aldi galashiels", "labels":["named-poi","named-place"]},
	{"query":"photos by ben brooksbank", "labels":["photographer","extra-words"]},
	{"query":"rivers beginning with a", "labels":["navigational","other"]},
        {"query":"limestone outcrop", "labels":["specific-item"]},
	{"query":"house of gray dundee", "labels":["named-poi","named-place"]},
	{"query":"ore stone", "labels":["specific-item"]},
	{"query":"great wall of deerness", "labels":["named-poi","named-place"]},
	{"query":"gate 4 principality stadium", "labels":["named-poi","named-place","specific-item"]},
	{"query":"strangers gate norwich", "labels":["named-poi","named-place","item-at-location"]},
	{"query":"peakirk wildlife park", "labels":["named-poi","named-place"]},
	{"query":"bridleway", "labels":["something","map-feature"]},
	{"query":"capel egryn", "labels":["named-poi"]},
	{"query":"winter photos", "labels":["temporal","extra-words"]},
	{"query":"captains pool kidderminster", "labels":["named-feature","named-place"]},
	{"query":"birchen clough bridge car park", "labels":["named-poi","named-place"]}
]

Your response must be a single JSON array, conforming to this schema:
{
  "type":"array",
  "items":{
    "type":"object",
    "properties":{
      "query": {"type":"string", "description":"The search query"},
      "labels": {"type":"array", "minItems":1, "items":{"type":"string"}, "description":"A list of labels associated with the query"}
    },
    "required":[
      "query",
      "labels"
    ]
  }
}

Do not include any other text, comments, or explanations before or after the JSON.

EOD;

    $user = "Queries to classify:\n";
    foreach ($queries as $item) {
        $user .= "- \"{$item['query']}\"\n";
    }

    if (!empty($print)) {
        print "$prompt$user\n";
        exit;
    }

    return getLLMResponse($prompt, $user, $provider);
}


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

