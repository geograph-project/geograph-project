<?php

// Refactored and tidied script for tag classification using Cloudflare's Workers AI with the gpt-oss-120b model.
// This version removes redundant code and focuses solely on the specified model.

// Script parameters
$param = array('offset'=>0, 'batch' => 10, 'print' => false, 'provider'=>'cloudflare', 'loops'=>10, 'fatal'=>true);

chdir(__DIR__);
// Required files and database connection
require "./_scripts.inc.php";

require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

echo "Starting tag classification process in batches...\n";

// Query conditions for selecting tags to classify
$where = array();
$where[] = "classification IS NULL";
$where[] = "status=1";
$where[] = "prefix NOT in ('top','subject','type','bucket','milestoneid')";
$order = "tag_id ASC";

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

    if (!empty($param['print'])) {
        print "$prompt$user\n";
        exit;
    }

    return getLLMResponse($prompt, $user, $param['provider']);
}


// Main script logic: process tags in batches
$offset = $param['offset'];
$loops = 0;
while (true) {
    echo "Fetching batch $loops from offset {$offset}...\n";

    $sql = "SELECT tag_id, IF(prefix NOT in ('','top','subject','type','bucket','category'), CONCAT(prefix,':',tag), tag) AS tagtext FROM tag WHERE " . implode(" AND ", $where) . " ORDER BY {$order} LIMIT {$param['batch']} OFFSET {$offset}";
    $rs = $db->Execute($sql);

    if ($rs->EOF) {
        break;
    }

    $tags_to_classify = [];
    while (!$rs->EOF) {
        $tags_to_classify[] = ['tag_id' => $rs->fields['tag_id'], 'tag' => latin1_to_utf8(preg_replace('/ s\b/', 's', $rs->fields['tagtext']))];
        $rs->MoveNext();
    }

    $response = classify_tags_batch($tags_to_classify);

    $classified_tags = null;
    if (is_string($response)) {
        $classified_tags = json_decode($response, true);

	    if (empty($classified_tags)) {

print "FAILED JSON EXTRACT, trying line by line!\n";
var_dump($response);

		//the LLM may make 'typo' in creating JSON, but might still be SOME usable data
		$classified_tags = array();
		//try decoding line by line - some will fail! but still extract the valid json lines
		foreach(explode("\n",$response) as $line) {
			if (preg_match('/^\s*\{.+\},?\s*$/',$line)) {
				$d = json_decode(trim($line,' ,'), true);
				if (!empty($d) && is_array($d) && !empty($d['class'])) {
					$classified_tags[] = $d;
				}
			}
		}
print_r($classified_tags);
	    }
    }


    if (empty($classified_tags)) {
        echo "Failed to classify batch at offset {$offset}. Skipping.\n";

	if ($param['fatal']) {
		var_dump($response);
		exit;
	}

        $offset += $param['batch'];
        continue;
    }

    $db->BeginTrans();
    $tags_processed_in_batch = 0;

    foreach ($classified_tags as $classified_item) {
        $original_tag_id = null;
        foreach ($tags_to_classify as $original_tag_item) {
            if ($original_tag_item['tag'] === $classified_item['tag']) {
                $original_tag_id = $original_tag_item['tag_id'];
                break;
            }
        }

        if ($original_tag_id) {
            $classification = trim($classified_item['class'], '[]');
            echo "Updating tag_id: {$original_tag_id} -> \"{$classified_item['tag']}\" with Classification: {$classification}\n";
            $update_sql = "UPDATE tag SET classification = ?, updated=updated WHERE tag_id = ?";
            $db->Execute($update_sql, [$classification, $original_tag_id]);
            $tags_processed_in_batch++;
        } else {
            error_log("Could not find original tag_id for classified tag: " . $classified_item['tag']);
        }
    }

    $db->CommitTrans();

    echo "Batch $loops of {$tags_processed_in_batch} tags processed successfully.\n";

    //if ($tags_processed_in_batch < $param['batch']) { --this aborts if a few failed tags
    if (count($tags_to_classify) < $param['batch']) { //-- really just want to abort if the 'last' small batch
        break;
    }

    $loops++;
    if ($loops == $param['loops']) {
	break;
    }
    //this is was a mstake by code generator - should only increment offset on failure
//    $offset += $param['batch'];
}

echo "All tags have been classified. Script finished.\n";
$db->Close();


