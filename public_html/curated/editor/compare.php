<?php
/**
 * Compare Nearby Photos for Two Feature Items
 *
 * Takes two feature_item_ids, fetches nearby images via ImageList (0.5km radius),
 * deduplicates shared images by assigning each image to its closest feature,
 * and renders a two-column side-by-side view with thumbnail links.
 */

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic");
customNoCacheHeader();

//adodb object :)
$db = GeographDatabaseConnection(true);

$smarty->display('_std_begin.tpl');

###########################################

$id1 = isset($_GET['id1']) ? (int)$_GET['id1'] : 0;
$id2 = isset($_GET['id2']) ? (int)$_GET['id2'] : 0;

if (!$id1 || !$id2) {
    die("Invalid or missing feature IDs.");
}

// --- Fetch both feature records ---
$featuresRaw = $db->getAll("SELECT feature_item_id, name, label, gridref, wgs84_lat, wgs84_long
                        FROM feature_item
                        WHERE feature_item_id IN (?, ?)", [$id1, $id2]);

$features = [];
foreach ($featuresRaw as $f) {
    $features[$f['feature_item_id']] = $f;
}

if (!isset($features[$id1]) || !isset($features[$id2])) {
    die("One or both feature items could not be found.");
}

$f1 = $features[$id1];
$f2 = $features[$id2];

// --- Haversine Distance Helper (km) ---
function calcDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
}

// --- Fetch nearby images for Feature 1 ---
$imagelist1 = new ImageList();
$imagelist1->_setDb($db);
$imagelist1->buildSimpleQuery("{$f1['wgs84_lat']},{$f1['wgs84_long']}", 0.50);

// --- Fetch nearby images for Feature 2 ---
$imagelist2 = new ImageList();
$imagelist2->_setDb($db);
$imagelist2->buildSimpleQuery("{$f2['wgs84_lat']},{$f2['wgs84_long']}", 0.50);

// --- Deduplicate and assign to the closest feature ---
$rawPool = [];

// Index Feature 1 candidates
if (!empty($imagelist1->images)) {
    foreach ($imagelist1->images as $img) {
        $rawPool[$img->gridimage_id] = [
            'image' => $img,
            'sourceList' => $imagelist1 // retain reference for getThumbnailLink
        ];
    }
}

// Index Feature 2 candidates
if (!empty($imagelist2->images)) {
    foreach ($imagelist2->images as $img) {
        if (!isset($rawPool[$img->gridimage_id])) {
            $rawPool[$img->gridimage_id] = [
                'image' => $img,
                'sourceList' => $imagelist2
            ];
        }
    }
}

$feature1Images = [];
$feature2Images = [];

// Calculate distance to both coordinates and assign to nearest
foreach ($rawPool as $item) {
    $img = $item['image'];
    $imgLat = (float)$img->wgs84_lat;
    $imgLon = (float)$img->wgs84_long;

    $dist1 = calcDistance($imgLat, $imgLon, (float)$f1['wgs84_lat'], (float)$f1['wgs84_long']);
    $dist2 = calcDistance($imgLat, $imgLon, (float)$f2['wgs84_lat'], (float)$f2['wgs84_long']);

    if ($dist1 <= $dist2) {
        $feature1Images[] = [
            'data' => $item,
            'dist' => $dist1
        ];
    } else {
        $feature2Images[] = [
            'data' => $item,
            'dist' => $dist2
        ];
    }
}

// Sort each column by proximity to feature center
usort($feature1Images, fn($a, $b) => $a['dist'] <=> $b['dist']);
usort($feature2Images, fn($a, $b) => $a['dist'] <=> $b['dist']);

// Distance between the two features themselves
$featureDistance = calcDistance((float)$f1['wgs84_lat'], (float)$f1['wgs84_long'], (float)$f2['wgs84_lat'], (float)$f2['wgs84_long']);
?>

    <title>Photo Comparison: #<?= $f1['feature_item_id'] ?> vs #<?= $f2['feature_item_id'] ?></title>
    <style>
        .header { background: #fff; padding: 15px; border: 1px solid #dee2e6; border-radius: 6px; margin-bottom: 20px; }
        .comparison-grid { display: flex; gap: 20px; }
        .column { flex: 1; background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; }
        .column-header { border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 15px; }
        .image-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; }
        .photo-card { border: 1px solid #e9ecef; border-radius: 4px; padding: 6px; text-align: center; background: #fafafa; }
        .photo-card img { max-width: 100%; height: auto; border-radius: 3px; display: block; margin: 0 auto; }
        .photo-meta { font-size: 11px; color: #6c757d; margin-top: 4px; }
        .dist-tag { font-weight: bold; color: #0d6efd; }
    </style>

    <div class="header">
        <a href="javascript:history.back()">&laquo; Back to Editor</a>
        <h2 style="margin: 10px 0 5px 0;">Side-by-Side Photo Comparison</h2>
        <div>Distance between feature markers: <strong><?= round($featureDistance * 1000) ?> meters</strong></div>
    </div>

    <p>This page just shows the images nearest each feature, they aren't nessarily images of the feature itself. But in theory should have some at the start at least</p>

    <div class="comparison-grid">
        <!-- Feature 1 Column -->
        <div class="column">
            <div class="column-header">
                <h3>#<?= $f1['feature_item_id'] ?>: <?= htmlspecialchars($f1['name'] ?: 'Unnamed') ?></h3>
                <div style="font-size: 13px; color: #6c757d;">
                    GridRef: <code><?= htmlspecialchars($f1['gridref']) ?></code> | 
                    <?= sprintf("%.5f, %.5f", $f1['wgs84_lat'], $f1['wgs84_long']) ?>
                </div>
                <div style="font-size: 12px; margin-top: 4px; color: #0d6efd;">
                    Assigned Photos: <strong><?= count($feature1Images) ?></strong>
                </div>
            </div>

            <div class="image-gallery">
                <?php if (empty($feature1Images)): ?>
                    <p style="color: #888; grid-column: 1/-1;">No photos closest to this feature within 500m.</p>
                <?php else: ?>
                    <?php foreach ($feature1Images as $entry): 
                        $img = $entry['data']['image'];
                        $list = $entry['data']['sourceList'];
                    ?>
                        <div class="photo-card">
			    <?= htmlentities2($img->title) ?><br>
                            <?= $list->getThumbnailLink($img) ?>
                            <div class="photo-meta">
                                #<?= $img->gridimage_id ?><br>
                                <span class="dist-tag"><?= round($entry['dist'] * 1000) ?>m away</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Feature 2 Column -->
        <div class="column">
            <div class="column-header">
                <h3>#<?= $f2['feature_item_id'] ?>: <?= htmlspecialchars($f2['name'] ?: 'Unnamed') ?></h3>
                <div style="font-size: 13px; color: #6c757d;">
                    GridRef: <code><?= htmlspecialchars($f2['gridref']) ?></code> | 
                    <?= sprintf("%.5f, %.5f", $f2['wgs84_lat'], $f2['wgs84_long']) ?>
                </div>
                <div style="font-size: 12px; margin-top: 4px; color: #0d6efd;">
                    Assigned Photos: <strong><?= count($feature2Images) ?></strong>
                </div>
            </div>

            <div class="image-gallery">
                <?php if (empty($feature2Images)): ?>
                    <p style="color: #888; grid-column: 1/-1;">No photos closest to this feature within 500m.</p>
                <?php else: ?>
                    <?php foreach ($feature2Images as $entry): 
                        $img = $entry['data']['image'];
                        $list = $entry['data']['sourceList'];
                    ?>
                        <div class="photo-card">
			    <?= htmlentities2($img->title) ?><br>
                            <?= $list->getThumbnailLink($img) ?>
                            <div class="photo-meta">
                                #<?= $img->gridimage_id ?><br>
                                <span class="dist-tag"><?= round($entry['dist'] * 1000) ?>m away</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

