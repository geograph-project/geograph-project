<?php
/**
 * Command-line script to verify embeddings in the database.
 */

$param = array();
$param['limit'] = 10;
$param['v2'] = false;

require_once(dirname(__FILE__) . '/_scripts.inc.php');
require_once('geograph/vectors.inc.php');

###############################################

if (!empty($param['v2'])) {
	$CONF['embed_api'] = 'http://python-embed13.dev.svc.cluster.local:8000';
}
print "API: {$CONF['embed_api']}\n";

###############################################

 $db = GeographDatabaseConnection();

$sql = "
    SELECT
        ge.gridimage_id,
        gi.user_id,
        ge.type,
        ge.embeddings,
        gi.title,
        gi.seq_no,
        gs.grid_reference
    FROM
        gridimage_embedding ge
    JOIN
        gridimage gi ON ge.gridimage_id = gi.gridimage_id
    JOIN
        gridsquare gs ON gi.gridsquare_id = gs.gridsquare_id
    LIMIT {$param['limit']}
";

$result = $db->GetAll($sql);

###############################################

$total_checked = 0;
$mismatches = 0;

// Helper function to compare two float arrays
function compare_embeddings($a, $b) {
    if (count($a) !== count($b)) {
        return false;
    }
    for ($i = 0; $i < count($a); $i++) {
        if (abs($a[$i] - $b[$i]) > 0.00001) {
            return false;
        }
    }
    return true;
}

foreach ($result as $row) {
    echo "Processing gridimage_id: {$row['gridimage_id']}\n";

    $db_embedding_unpacked = unpack('f*', $row['embeddings']);
    $db_embedding = array_values($db_embedding_unpacked);

    if ($row['type'] == 'title') {
        echo "  Type: title\n";
        $api_embedding = getTextEmbedding($row['title']);
    } elseif ($row['type'] == 'image') {
        //note, we use 'check_exists=false' - we know the thumbnail should exist (as the image is in gridimage_embedding!)
	// ... we DONT bother setting size for getAIThumbnail
	$image = new GridImage();
	$image->fastInit($row);
        echo "  Type: image\n";
        $api_embedding = getImageEmbedding($image, true, false); //use_ai_thumb because we know it exists, and dont even need to check.
    } else {
        echo "  Unknown type: {$row['type']}\n";
        continue;
    }

    if (empty($api_embedding)) {
        echo "  Error: API returned no embedding\n";
        continue;
    }

    if (compare_embeddings($db_embedding, $api_embedding)) {
        echo "  Result: Embeddings match!\n";
    } else {
        echo "  Result: Embeddings DO NOT match.\n";
        echo "  DB Embedding:  " . implode(', ', array_slice($db_embedding, 0, 5)) . "...\n";
        echo "  API Embedding: " . implode(', ', array_slice($api_embedding, 0, 5)) . "...\n";
        $mismatches++;
    }
    $total_checked++;
}

echo "\n--- Verification Summary ---\n";
echo "Total embeddings checked: $total_checked\n";
echo "Matches: " . ($total_checked - $mismatches) . "\n";
echo "Mismatches: $mismatches\n";

if (!empty($stat)) {
	print_r($stat);
	foreach ($stat as $key => $data) {
		printf('%40s %7d %.3f'."\n", $key, $data['count'], $data['total']/$data['count']);
	}
}
