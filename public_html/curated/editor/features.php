<?php
/**
 * Feature Item Data Editor & Duplicate Resolver
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

$feature_type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 7;
$mode            = isset($_GET['mode']) ? $_GET['mode'] : 'duplicates'; // 'duplicates' or 'grid'
$sort_by         = isset($_GET['sort']) ? $_GET['sort'] : 'geo';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	include(__DIR__."/features.inc.php");

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)$_POST['feature_item_id'];

        updateFeatureItem($id, ['status' => 0], $USER->user_id);
        $message = "Row #{$id} marked as deleted (status = 0).";

    } elseif ($action === 'update_names') {
        $id1 = (int)$_POST['id1'];
        $name1 = trim($_POST['name1']);
        $id2 = (int)$_POST['id2'];
        $name2 = trim($_POST['name2']);

        updateFeatureItem($id1, ['name' => $name1], $USER->user_id);
        updateFeatureItem($id2, ['name' => $name2], $USER->user_id);
        $message = "Updated names for rows #{$id1} and #{$id2}.";

    } elseif ($action === 'bulk_update') {
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            $stmt = "UPDATE feature_item SET name = ?, label = ?, gridref = ? WHERE feature_item_id = ?";
            $updated = 0;
            foreach ($_POST['items'] as $id => $fields) {
                updateFeatureItem((int)$id, [
                    'name'    => trim($fields['name']),
                    'label'   => trim($fields['label']),
                    'gridref' => trim($fields['gridref']),
                ], $USER->user_id);

                $updated++;
            }
            $message = "Successfully saved grid updates for {$updated} rows.";
        }
    }
}

###########################################

// --- Distance Helpers (Haversine Formula) ---
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    if (($lat1 == 0 && $lon1 == 0) || ($lat2 == 0 && $lon2 == 0)) {
        return null; // Return null if lat/long is missing or invalid
    }
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c; // returns distance in kilometers
}

// --- Nearest Neighbor Spatial Sort ---
function sortGeographically(array $rows) {
    if (empty($rows)) return [];

    $unvisited = [];
    $zeroCoords = [];

    // Separate records with zero/missing coordinates from valid coordinates
    foreach ($rows as $row) {
        if ((float)$row['wgs84_lat'] == 0.0 && (float)$row['wgs84_long'] == 0.0) {
            $zeroCoords[] = $row;
        } else {
            $unvisited[] = $row;
        }
    }

    if (empty($unvisited)) return $zeroCoords;

    // Pick start item: Southern-most (min lat), then Western-most (min lon)
    usort($unvisited, function($a, $b) {
        if ($a['wgs84_lat'] == $b['wgs84_lat']) {
            return $a['wgs84_long'] <=> $b['wgs84_long'];
        }
        return $a['wgs84_lat'] <=> $b['wgs84_lat'];
    });

    $ordered = [];
    $current = array_shift($unvisited);
    $ordered[] = $current;

    // Nearest Neighbor Greedy Path
    while (!empty($unvisited)) {
        $nearestIdx = null;
        $minDist = INF;

        foreach ($unvisited as $idx => $candidate) {
            $dist = haversineDistance(
                (float)$current['wgs84_lat'], (float)$current['wgs84_long'],
                (float)$candidate['wgs84_lat'], (float)$candidate['wgs84_long']
            );
            if ($dist < $minDist) {
                $minDist = $dist;
                $nearestIdx = $idx;
            }
        }

        $current = $unvisited[$nearestIdx];
        unset($unvisited[$nearestIdx]);
        $unvisited = array_values($unvisited);
        $ordered[] = $current;
    }

    // Append 0,0 items at the beginning or end
    return array_merge($zeroCoords, $ordered);
}

// --- Fetch & Process Data ---
$sql = "SELECT feature_item_id, name, label, gridimage_id, gridref, wgs84_lat, wgs84_long
        FROM feature_item
        WHERE status = 1 AND feature_type_id = ?";

if ($sort_by === 'lat') {
    $sql .= " ORDER BY wgs84_lat ASC";
} elseif ($sort_by === 'name') {
    $sql .= " ORDER BY name ASC";
} elseif ($sort_by === 'id') {
    $sql .= " ORDER BY feature_item_id ASC";
}

$rawRows = $db->getAll($sql, [$feature_type_id]);

if ($sort_by === 'geo') {
    $rows = sortGeographically($rawRows);
} else {
    $rows = $rawRows;
}
?>
    <style>
        .nav-bar { margin: 15px 0; padding: 12px; background: #fff; border: 1px solid #dee2e6; border-radius: 6px; display: flex; gap: 15px; align-items: center; }
        .nav-bar a { text-decoration: none; color: #0d6efd; font-weight: 500; }
        .nav-bar a.active { font-weight: bold; color: #000; border-bottom: 2px solid #0d6efd; }
        .alert { padding: 10px 15px; background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; border-radius: 4px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dee2e6; }
        th, td { padding: 8px 12px; border: 1px solid #eee; font-size: 14px; text-align: left; }
        th { background: #f1f3f5; position: sticky; top: 0; }
        tr.duplicate-warning { background-color: #fff3cd; }
        .dist-badge { display: inline-block; padding: 2px 6px; font-size: 11px; font-weight: bold; border-radius: 4px; background: #e9ecef; }
        .dist-badge.close { background: #ffc107; color: #000; }
        .dist-badge.exact { background: #dc3545; color: #fff; }
        input[type="text"] { width: 95%; padding: 4px 6px; border: 1px solid #ced4da; border-radius: 3px; }
        .btn { padding: 4px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-primary { background: #0d6efd; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn:hover { opacity: 0.85; }
        .actions-cell { display: flex; gap: 5px; align-items: center; }
    </style>

    <h2>Feature Item Dataset Editor</h2>
    <div style="font-size: 14px; color: #6c757d;">Editing Feature Type ID: <strong><?= $feature_type_id ?></strong></div>

    <div class="nav-bar">
        <strong>Mode:</strong>
        <a href="?type_id=<?= $feature_type_id ?>&mode=duplicates&sort=<?= $sort_by ?>" class="<?= $mode==='duplicates'?'active':'' ?>">Duplicate Resolution</a> | 
        <a href="?type_id=<?= $feature_type_id ?>&mode=grid&sort=<?= $sort_by ?>" class="<?= $mode==='grid'?'active':'' ?>">Grid / Spreadsheet View</a>
        
        <span style="margin-left: auto;">
            <strong>Sort By:</strong>
            <a href="?type_id=<?= $feature_type_id ?>&mode=<?= $mode ?>&sort=geo" class="<?= $sort_by==='geo'?'active':'' ?>">Geographic Path</a> | 
            <a href="?type_id=<?= $feature_type_id ?>&mode=<?= $mode ?>&sort=lat" class="<?= $sort_by==='lat'?'active':'' ?>">Latitude</a> | 
            <a href="?type_id=<?= $feature_type_id ?>&mode=<?= $mode ?>&sort=name" class="<?= $sort_by==='name'?'active':'' ?>">Name</a> | 
            <a href="?type_id=<?= $feature_type_id ?>&mode=<?= $mode ?>&sort=id" class="<?= $sort_by==='id'?'active':'' ?>">ID</a>
        </span>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($mode === 'duplicates'): ?>
        <!-- ================= DUPLICATE RESOLUTION MODE ================= -->
        <h2>Duplicate Finder</h2>
        <p>Items are sorted geographically along a spatial path. Distances between adjacent rows are calculated below.</p>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Label</th>
                    <th>GridRef</th>
                    <th>Lat / Long</th>
                    <th>Dist to Next</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = count($rows);
                for ($i = 0; $i < $count; $i++): 
                    $row = $rows[$i];
                    $nextRow = ($i + 1 < $count) ? $rows[$i + 1] : null;
                    
                    $dist = null;
                    if ($nextRow) {
                        $dist = haversineDistance(
                            (float)$row['wgs84_lat'], (float)$row['wgs84_long'],
                            (float)$nextRow['wgs84_lat'], (float)$nextRow['wgs84_long']
                        );
                    }

                    $isDuplicateCandidate = ($dist !== null && $dist < 0.2); // threshold: within 200m
                ?>
                <tr class="<?= $isDuplicateCandidate ? 'duplicate-warning' : '' ?>">
                    <td><strong><?= $row['feature_item_id'] ?></strong></td>
                    <td><?= htmlspecialchars($row['name'] ?: '') ?></td>
                    <td><?= htmlspecialchars($row['label'] ?: '') ?></td>
                    <td><code><?= htmlspecialchars($row['gridref'] ?: '') ?></code></td>
                    <td><?= sprintf("%.5f, %.5f", $row['wgs84_lat'], $row['wgs84_long']) ?></td>
                    <td>
                        <?php if ($dist !== null): ?>
                            <?php if ($dist === 0.0): ?>
                                <span class="dist-badge exact">0 m (Exact)</span>
                            <?php elseif ($dist < 1.0): ?>
                                <span class="dist-badge close"><?= round($dist * 1000) ?> m</span>
                            <?php else: ?>
                                <span class="dist-badge"><?= round($dist, 2) ?> km</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#aaa;">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                        <!-- Action 2: Delete Row -->
                        <form method="POST" onsubmit="return confirm('Delete item #<?= $row['feature_item_id'] ?>?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="feature_item_id" value="<?= $row['feature_item_id'] ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>

                        <!-- Action 3: Edit Pair Names if close to next item -->
                        <?php if ($nextRow && $isDuplicateCandidate): ?>
                            <button class="btn btn-secondary" onclick="toggleEditPair(<?= $i ?>)">Differentiate Names</button>
<? if ($dist > 0) { ?>
    <a href="compare.php?id1=<?= $row['feature_item_id'] ?>&id2=<?= $nextRow['feature_item_id'] ?>" 
       target="_blank" 
       class="btn btn-primary" 
       style="text-decoration: none; display: inline-block;">
       View Nearby Photos
    </a>
<? } ?>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Inline Pair Editing Form Row -->
                <?php if ($nextRow && $isDuplicateCandidate): ?>
                <tr id="edit-pair-<?= $i ?>" style="display:none; background: #fff8e6;">
                    <td colspan="7" style="padding: 12px;">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_names">
                            <input type="hidden" name="id1" value="<?= $row['feature_item_id'] ?>">
                            <input type="hidden" name="id2" value="<?= $nextRow['feature_item_id'] ?>">
                            
                            <strong>Quick Edit Pair Names to Differentiate:</strong>
                            <div style="display: flex; gap: 15px; margin-top: 8px;">
                                <div style="flex: 1;">
                                    <label>#<?= $row['feature_item_id'] ?> Name:</label>
                                    <input type="text" name="name1" value="<?= htmlspecialchars($row['name']) ?>">
                                </div>
                                <div style="flex: 1;">
                                    <label>#<?= $nextRow['feature_item_id'] ?> Name:</label>
                                    <input type="text" name="name2" value="<?= htmlspecialchars($nextRow['name']) ?>">
                                </div>
                                <div style="align-self: flex-end;">
                                    <button type="submit" class="btn btn-primary">Save Both Names</button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>

                <?php endfor; ?>
            </tbody>
        </table>

    <?php else: ?>
        <!-- ================= GRID / SPREADSHEET MODE ================= -->
        <h2>Grid / Spreadsheet View</h2>
        <form method="POST">
            <input type="hidden" name="action" value="bulk_update">
            
            <div style="margin-bottom: 10px; text-align: right;">
                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">Save All Changes</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Name</th>
                        <th>Label</th>
                        <th style="width: 140px;">GridRef</th>
                        <th style="width: 100px;">GridImage ID</th>
                        <th style="width: 160px;">Lat / Long</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): $id = $row['feature_item_id']; ?>
                    <tr>
                        <td><strong><?= $id ?></strong></td>
                        <td>
                            <input type="text" name="items[<?= $id ?>][name]" value="<?= htmlspecialchars($row['name']) ?>">
                        </td>
                        <td>
                            <input type="text" name="items[<?= $id ?>][label]" value="<?= htmlspecialchars($row['label']) ?>">
                        </td>
                        <td>
                            <input type="text" name="items[<?= $id ?>][gridref]" value="<?= htmlspecialchars($row['gridref']) ?>">
                        </td>
                        <td><?= $row['gridimage_id'] ?></td>
                        <td><?= sprintf("%.5f, %.5f", $row['wgs84_lat'], $row['wgs84_long']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top: 15px; text-align: right;">
                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">Save All Changes</button>
            </div>
        </form>
    <?php endif; ?>

    <script>
        function toggleEditPair(index) {
            const row = document.getElementById('edit-pair-' + index);
            if (row) {
                row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
            }
        }
    </script>

