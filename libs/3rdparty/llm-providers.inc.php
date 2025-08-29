
######################################

function getLLMResponse($prompt, $user, $provider = 'cloudflare') {

        if ($provider=='cloudflare') {
                return callCloudflare($prompt, $user);

        } elseif ($provider=='open') {
                return callOpenRouter($prompt, $user, $max_tokens = 2048*2);

        } elseif ($provider=='lmstudio') {
		return callLMStudio($prompt, $user);

        } else {
                return getLLMLocalResponse($prompt, $user, $model = 'gemma270m');
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
function callCloudflare($prompt, $user = null) {
    global $CONF;
    $accountId = $CONF['CLOUDFLARE_ACCOUNT_ID'];
    $apiToken = $CONF['CLOUDFLARE_API_TOKEN'];
    $modelName = '@cf/openai/gpt-oss-120b';
    print "Using Model $modelName\n";
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
function callOpenRouter($prompt, $user = null, $maxTokens = 2048) {
    global $CONF;
    $apiKey = $CONF['OPENROUTER_API_KEY'];

    $modelName = 'openai/gpt-oss-120b';

    print "Using Model $modelName\n";
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

######################################

/**
 * Calls the local LM Studio API to run an LLM.
 *
 * @param string $prompt The system prompt.
 * @param string $user The user's input.
 * @return string The generated text from the LLM, or null on failure.
 */
function callLMStudio($prompt, $user = null) {
    // LM Studio's default API endpoint
    $url = "http://localhost:1234/v1/chat/completions";
    // The model name is a local identifier, typically found in LM Studio.
    // Replace 'gpt-oss-120b' with the actual model name you've loaded in LM Studio.
    $modelName = 'gpt-oss-120b';

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


