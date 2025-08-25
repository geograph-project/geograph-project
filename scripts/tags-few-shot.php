<?php


//these are the arguments we expect
$param=array('execute'=>0,'batch'=>10, 'print'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

//it provides getLLMResponse - which interacts with small LLM on our one server
require_once "geograph/vectors.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

    // Select tags with a NULL classification, limited by batch size.
    $where = array();
    $where[] = "classification IS NULL";
    $where[] = "status=1";

	$order = "tag_id ASC"; //nice simple repeatable results

//$order = "RAND()"; //to get varied rsults for testing!

	$where[] = "prefix NOT in ('top','subject','type','bucket')";
//	$where[] = "canonical = 0"; //has special meaning in these 'offical' namespaces

	$where[] = "prefix != 'milestoneid'";  //- as would never realistically classify!

############################################

/**
 * Sends a batch of tags and few-shot examples to the LLM microservice.
 *
 * @param array $tags An array of tags to classify, where each element is ['tag_id' => int, 'tag' => string].
 * @return array An array of classified tags, or an empty array on failure.
 */
function classify_tags_batch($tags) {
    global $llm_api_url, $param;

    if (empty($tags)) {
        return [];
    }

    // Define the few-shot examples and classification rules.
    // This forms the core of the LLM prompt.
    $prompt_examples = [
        ["tag" => "London", "class" => "[named-place]"],
        ["tag" => "South Downs Way", "class" => "[named-path]"],
        ["tag" => "National Cycle Route 5", "class" => "[named-cyclepath]"],
        ["tag" => "St Peters Church", "class" => "[named-poi]"],
        ["tag" => "View to Nant Gwrtheyrn", "class" => "[related-to]"],
        ["tag" => "Lake District National Park", "class" => "[named-area]"],
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
    $prompt .= "- [named-place]: An actual, named settlement or place.\n";
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
//    $prompt .= "\nRespond with a single JSON array of objects, where each object contains the original 'tag' and its 'class'.\n";

    $prompt .= 'Your response must be a single JSON array, conforming to this schema: [ { "tag": "string", "class": "string" } ]. Do not include any other text, comments, or explanations before or after the JSON.'."\n";

//with some models eg Llama, its best to seperate the 'information' as and provide the actual task in a seperate prompt
// the can always be combined back if want a single prompt for specific model;

    $user = "Tags to classify:\n";

    foreach ($tags as $tag_item) {
        $user .= "- \"{$tag_item['tag']}\"\n";
    }

//    $user .= "\nJSON Response:\n"; -- didnt help all that much

if (!empty($param['print'])) {
	print "$prompt$user\n";
	exit;
}

//print_r($prompt);
print_r($user);
    //more advanced method, that sends the instrcutions as a system prompt.
    return callCloudflare($prompt, $user);


/*
Tjis was just a test on giving one tag, and asking for the reply directlt - rather than JSON (intendeed for gemma270m but didnt work

    $user = "\nNow, classify the following tag. Respond with just the classfication, nothing else.\n";
    foreach ($tags as $tag_item) {
        $user .= "- \"{$tag_item['tag']}\"\n";
        break;
    }

*/


print_r($prompt,$user);

    return getLLMResponse($prompt.$user);   //this is calling our self hosted gemma
}

// =======================================================================
// Main Script Logic
// =======================================================================
echo "Starting tag classification process in batches...\n";

// Use a loop to process tags in batches.
$offset = 0;
while (true) {
    echo "Fetching batch from offset {$offset}...\n";

	//note dont use tagtext from tag_stat, as here dont want some specific prefixes.
    $sql = "SELECT tag_id, IF(prefix NOT in ('','top','subject','type','bucket','category'), CONCAT(prefix,':',tag), tag) AS tagtext FROM tag WHERE ".implode(" AND ",$where)." ORDER BY $order LIMIT {$param['batch']} OFFSET {$offset}";
    $rs = $db->Execute($sql);

    // If no more records are found, break the loop.
    if ($rs->EOF) {
        break;
    }

    $tags_to_classify = [];
    while (!$rs->EOF) {
        $tags_to_classify[] = ['tag_id' => $rs->fields['tag_id'], 'tag' => preg_replace('/ s\b/','s',$rs->fields['tagtext'])]; //this is a fix applied by to_title_case, bcause the apos was replaced by space in some old tags!
        $rs->MoveNext();
    }

    // Classify the batch of tags with a single LLM API call.
    $classified_tags = classify_tags_batch($tags_to_classify);

    if (is_string($classified_tags))
	 $classified_tags = json_decode($classified_tags, TRUE);



    if (empty($classified_tags)) {
        echo "Failed to classify batch at offset {$offset}. Skipping.\n";
        // Optionally, you could log this for later reprocessing
        $offset += $param['batch'];
        continue;
    }

    $db->BeginTrans();
    $tags_processed_in_batch = 0;

    // Loop through the classified results and update the database.
    foreach ($classified_tags as $classified_item) {
        // Find the original tag_id based on the returned tag string.
        $original_tag_id = null;
        foreach ($tags_to_classify as $original_tag_item) {
            if ($original_tag_item['tag'] === $classified_item['tag']) {
                $original_tag_id = $original_tag_item['tag_id'];
                break;
            }
        }

        if ($original_tag_id) {
            $classification = trim($classified_item['class'],'[]');
            echo "Updating tag_id: {$original_tag_id} -> \"{$classified_item['tag']}\" with Classification: {$classification}\n";
            $update_sql = "UPDATE tag SET classification = ?, updated=updated WHERE tag_id = ?";
            $db->Execute($update_sql, [$classification, $original_tag_id]);
            $tags_processed_in_batch++;
        } else {
            error_log("Could not find original tag_id for classified tag: " . $classified_item['tag']);
        }
    }

    $db->CommitTrans();

    echo "Batch of {$tags_processed_in_batch} tags processed successfully.\n";

    // If we processed fewer tags than the batch size, we've reached the end.
    if ($tags_processed_in_batch < $param['batch']) {
        break;
    }

    // Increment the offset for the next batch.
//    $offset += $param['batch'];
}

echo "All tags have been classified. Script finished.\n";

// Close the database connection.
$db->Close();




function callCloudflare($prompt, $user = null) {
	global $CONF;

	$accountId = $CONF['CLOUDFLARE_ACCOUNT_ID'];
	$apiToken = $CONF['CLOUDFLARE_API_TOKEN'];

	$modelName = '@cf/meta/llama-3-8b-instruct'; // You can change this to another model.

	$modelName = '@cf/openai/gpt-oss-120b';


print "Using Model $modelName\n";

	// The API endpoint URL.
	$url = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$modelName}";

	// --- Step 2: Prepare the Request Payload ---
	//can send two step prompt for more advanced tasks
	if ($modelName == '@cf/openai/gpt-oss-120b') {
		$data = [
		    'input' => $prompt.($user ?? '')
		];

	//more expressive version for llama
	} elseif (!empty($user)) {

		$data = [
		    'messages' => [
			    // System message to set the model's behavior.
			    [
			        'role' => 'system',
			        'content' => $prompt
			    ],
			    // User message with the actual request.
			    [
			        'role' => 'user',
			        'content' => $user
			    ]
		    ],
		    'response_format' => [
		        'type' => 'json_object'
		    ]
		];
	// The API expects a JSON body with a 'prompt' field for text generation models.
	} else {
		$data = [
		    'prompt' => $prompt
		];
	}

	$payload = json_encode($data);

	// --- Step 3: Initialize and Configure cURL ---
	$ch = curl_init();

	// Set cURL options for a POST request.
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string instead of outputting it.
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

	// Set the required HTTP headers, including the Authorization header.
	$headers = [
	    'Authorization: Bearer ' . $apiToken,
	    'Content-Type: application/json',
	    'Content-Length: ' . strlen($payload)
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	// --- Step 4: Execute the cURL Request ---
	$response = curl_exec($ch);

	// Check for cURL errors.
	if (curl_errno($ch)) {
	    echo 'cURL Error: ' . curl_error($ch);
	} else {
	    // --- Step 5: Process the Response ---
	    // Decode the JSON response.
	    $responseData = json_decode($response, true);

	    // Check if the API call was successful.
	    if (isset($responseData['success']) && $responseData['success']) {

	//this is for decoding gpt-oss

    // Loop through the output array to find the correct text.
    if (isset($responseData['result']['output']) && is_array($responseData['result']['output'])) {
        foreach ($responseData['result']['output'] as $outputItem) {
            // Check for the nested content with a 'text' key.
            if (isset($outputItem['content']) && is_array($outputItem['content']) && !empty($outputItem['content'][0]['text']) && $outputItem['content'][0]['type'] == 'output_text') {
                $generatedText = $outputItem['content'][0]['text'];

return $generatedText;

                break; // Found the text, no need to continue the loop.
            }
        }
    }


	        // The generated text is in the 'result' -> 'response' field.
	        $generatedText = $responseData['result']['response'];

return $generatedText;

	        echo "<h2>Cloudflare Workers AI Response:</h2>";
	        echo "<pre>{$generatedText}</pre>";
	    } else {
	        // Handle API-specific errors.
	        echo "<h2>API Error:</h2>";
	        echo "<pre>" . print_r($responseData['errors'], true) . "</pre>";
	    }
	}

	// Close the cURL session.
	curl_close($ch);
}
