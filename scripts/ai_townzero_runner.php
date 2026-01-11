<?php
/**
 * Zero-Shot Classification Runner for Geograph Towns
 */

$param = array('debug' => true, 'execute' => false, 'limit_per_town' => 5);

chdir(__DIR__);
require "./_scripts.inc.php";
require_once('geograph/conversions.class.php');
require_once('geograph/vectors.inc.php');
require_once('3rdparty/vector.class.php');

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$conv = new Conversions;

// 1. Prepare our Labels from the JSON set
$labels_json = '{
  "scenery_natural": ["Mountain Landscape", "Forest and Woodland", "River and Stream", "Coastal and Harbor", "Moorland and Heath", "Open Fields and Pasture", "Canal and Waterway"],
  "architecture_built": ["Terraced Housing", "Semi-detached Housing", "Detached House", "Apartment Block", "Modern Glass Office", "Grand Stone Building", "Retail and Shopfront", "Industrial Warehouse", "Historic Ruins", "Churches and Steeples", "Thatched Cottage", "Skyscraper"],
  "transport_infrastructure": ["Road and Highway", "Railway and Train Track", "Bridge and Viaduct", "Bus Stop and Shelter", "Traffic Lights", "Pedestrian Crossing", "Parking Lot", "Power Lines and Pylons"],
  "urban_features": ["Public Park", "Town Square or Plaza", "Street Art and Graffiti", "Construction Site", "Statue and Monument", "Outdoor Dining", "Nightlife Scene", "Market Stall"],
  "objects_details": ["Signage and Maps", "Telephone Box", "Post Box", "Public Bench", "Trash Can and Bin", "Information Board", "Dry Stone Wall"],
  "composition_metadata": ["Panoramic View", "Aerial Photography", "Macro and Close-up", "Interior Shot", "Night Photography", "Overcast and Gloomy", "Sunny and Bright"]
}';

$label_buckets = json_decode($labels_json, true);
$candidate_vectors = [];

print "Pre-fetching Label Embeddings...\n";
foreach ($label_buckets as $bucket => $labels) {
    foreach ($labels as $label) {
        // We wrap the label to improve CLIP performance
        $prompt = "A Geograph photo of $label";
        $vec_array = getTextEmbeddingWrapper($prompt, 'clip', true);
        $candidate_vectors[$label] = new EmbeddingVector($vec_array, true);
    }
}

// 2. Define Towns
$towns = array('East Grinstead', 'Fort William/An Gearasdan', 'Abergavenny/Y Fenni', 'Bicester');

foreach ($towns as $town) {
    $town_name = explode('/', $town)[0];
    print "\nProcessing $town_name...\n";

    // Get Bounding Box
    $mbr = $db->getRow("SELECT mbr_xmin, mbr_ymin, mbr_xmax, mbr_ymax FROM os_open_places WHERE name1 = " . $db->Quote($town_name) . " LIMIT 1");
    
    if (!$mbr) continue;

    // Fetch all image embeddings within this town's box
    // Note: Using FORCE INDEX if performance lags as discussed earlier
    $sql = "SELECT gridimage_id, embeddings 
            FROM gridimage_embedding 
            INNER JOIN gb_images USING (gridimage_id) 
            WHERE model = 'clip' AND type='image'
            AND nateastings BETWEEN {$mbr['mbr_xmin']} AND {$mbr['mbr_xmax']}
            AND natnorthings BETWEEN {$mbr['mbr_ymin']} AND {$mbr['mbr_ymax']}
            LIMIT " . $param['limit_per_town'];
    $images = $db->Execute($sql);

    while ($row = $images->FetchRow()) {
        $id = $row['gridimage_id'];
        
        try {
            // Initialize image vector
            $imgVector = new EmbeddingVector($row['embeddings'], true);

            // Perform KNN against our 50 labels
            // We want the top 3 matches for each image
            $matches = EmbeddingVector::knn($imgVector, $candidate_vectors, 5);

            foreach ($matches as $label => $distance) {
                $score = 1 - $distance;

                // Prepare insert for gridimage_label
                $insert = [
                    'gridimage_id' => $id,
                    'model' => 'clipzero',
                    'label' => $label,
                    'score' => $score,
                    'user_agent' => 'GeminiZeroShot/1.0'
                ];

                if ($param['debug']) {
                    printf("  IMG %d: %-25s Score: %.4f\n", $id, $label, $score);
                }

                if ($param['execute']) {
                    // Use Replace to avoid duplicates if re-running
                    $db->Replace('gridimage_label', $insert, ['gridimage_id', 'model', 'label'], true);
                }
            }
        } catch (Exception $e) {
            print "  Error processing ID $id: " . $e->getMessage() . "\n";
        }
    }
}

print "\nFinished. " . date('r') . "\n";
