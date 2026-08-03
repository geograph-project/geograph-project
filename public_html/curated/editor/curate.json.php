<?php
/**
 * curate.json.php - JSON API endpoint for spatial curation tool
 */

require_once('geograph/global.inc.php');
init_session();

$data = array();
customNoCacheHeader();

// Enforce permission checks matching frontend main workstation
try {
    if (empty($USER->user_id)) {
        header('HTTP/1.0 401 Unauthorized');
        $data['error'] = 'You must be logged in to access this API.';
        outputJSON($data);
        exit;
    }
    $USER->mustHavePerm("basic");
} catch (Exception $e) {
    header('HTTP/1.0 403 Forbidden');
    $data['error'] = 'You do not have permission to access this resource: ' . $e->getMessage();
    outputJSON($data);
    exit;
}

$db = GeographDatabaseConnection(true);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$label = $_GET['label'] ?? $_POST['label'] ?? '';

if (empty($label)) {
    $data['error'] = 'Label parameter is required.';
    outputJSON($data);
    exit;
}

switch ($action) {
    case 'get_curation_summary':
        // Get all curation items for this label along with image metadata
        $sql = "SELECT c.curated_id, c.gridimage_id, c.active, c.feature, c.region, c.caption, c.user_id as curated_user_id,
                       gi.user_id, gi.realname, gi.grid_reference, gi.title, gi.wgs84_lat, gi.wgs84_long
                FROM curated c
                INNER JOIN gridimage_search gi USING (gridimage_id)
                WHERE c.label = ?
                ORDER BY c.updated DESC";
        $rows = $db->getAll($sql, array($label));

        $curated_list = array();
        foreach ($rows as $row) {
	    $image = new GridImage();
	    $image->fastInit($row);

            $curated_list[] = array(
                'id' => intval($row['gridimage_id']),
                'active' => intval($row['active']),
                'feature' => latin1_to_utf8($row['feature']),
                'region' => latin1_to_utf8($row['region']),
                'caption' => latin1_to_utf8($row['caption']),
                'user_id' => intval($row['curated_user_id']),
                'title' => latin1_to_utf8($row['title']),
                'realname' => latin1_to_utf8($row['realname']),
                'hash' => $image->_getAntiLeechHash(),
                'lat' => floatval($row['wgs84_lat']),
                'lng' => floatval($row['wgs84_long'])
            );
        }
        $data['curated'] = $curated_list;
        break;

    case 'save_state':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.0 405 Method Not Allowed');
            $data['error'] = 'POST method required for save_state.';
            break;
        }

        $gridimage_id = intval($_POST['gridimage_id'] ?? 0);
        $active = intval($_POST['active'] ?? 1);
        $feature = trim($_POST['feature'] ?? '');

        if ($gridimage_id <= 0) {
            $data['error'] = 'Invalid gridimage_id.';
            break;
        }

        // Check if row already exists for this label and image
        $existing = $db->getRow("SELECT curated_id, user_id, active, feature FROM curated WHERE label = ? AND gridimage_id = ?", array($label, $gridimage_id));

        if ($existing) {
            // Update existing row
		if (empty($feature)) //dont remove it, if not provided in request
			$feature = $existing['feature'];
            $sql = "UPDATE curated
                    SET active = ?, feature = ?, user_id = ?
                    WHERE label = ? AND gridimage_id = ?";
            $db->execute($sql, array($active, $feature, $USER->user_id, $label, $gridimage_id));
            $data['status'] = 'updated';
            $data['curated_id'] = intval($existing['curated_id']);
        } else {
            // Insert new row
            $sql = "INSERT INTO curated (user_id, feature, label, gridimage_id, created, active)
                    VALUES (?, ?, ?, ?, NOW(), ?)";
            $db->execute($sql, array($USER->user_id, $feature, $label, $gridimage_id, $active));
            $data['status'] = 'inserted';
            $data['curated_id'] = intval($db->Insert_ID());
        }
        break;

    case 'get_proximity_suggestions':
        $lat = floatval($_GET['lat'] ?? 0);
        $lng = floatval($_GET['lng'] ?? 0);
        $feature_type_id = isset($_GET['feature_type_id']) ? intval($_GET['feature_type_id']) : 0;

        if ($lat == 0 && $lng == 0) {
            $data['suggestions'] = array();
            break;
        }

        // Bounding box of approx 2km (latitude is ~111km per deg, longitude is ~70km per deg at 51N)
        $lat_delta = 0.018;
        $lng_delta = 0.028;

        $lat_min = $lat - $lat_delta;
        $lat_max = $lat + $lat_delta;
        $lng_min = $lng - $lng_delta;
        $lng_max = $lng + $lng_delta;

        $sql = "SELECT DISTINCT c.feature, gi.wgs84_lat, gi.wgs84_long,
		    ST_Distance_Sphere(
			        POINT(gi.wgs84_long, gi.wgs84_lat),
			        POINT(?, ?)
		    ) AS dist
                FROM curated c
                INNER JOIN gridimage_search gi USING (gridimage_id)
                WHERE c.label = ? AND c.active = 2 AND c.feature != ''
                  AND gi.wgs84_lat BETWEEN ? AND ?
                  AND gi.wgs84_long BETWEEN ? AND ?
                ORDER BY dist ASC
                LIMIT 10";
        $rows = $db->getAll($sql, array($lng, $lat, $label, $lat_min, $lat_max, $lng_min, $lng_max));

        $suggestions = array();
        foreach ($rows as $row) {
            $suggestions[] = array(
                'feature' => latin1_to_utf8($row['feature']),
                'lat' => floatval($row['wgs84_lat']),
                'lng' => floatval($row['wgs84_long']),
                'dist' => floatval($row['dist'])
            );
        }

        if ($feature_type_id > 0) {
            $sql_fi = "SELECT DISTINCT name AS feature, wgs84_lat, wgs84_long,
			ST_Distance_Sphere(
				    POINT(wgs84_long, wgs84_lat),
				    POINT(?, ?)
			) AS dist
                    FROM feature_item
                    WHERE feature_type_id = ? AND name != '' AND status > 0
                      AND wgs84_lat BETWEEN ? AND ?
                      AND wgs84_long BETWEEN ? AND ?
                    ORDER BY dist ASC
                    LIMIT 10";
            $rows_fi = $db->getAll($sql_fi, array($lng, $lat, $feature_type_id, $lat_min, $lat_max, $lng_min, $lng_max));
            foreach ($rows_fi as $row) {
                $suggestions[] = array(
                    'feature' => latin1_to_utf8($row['feature']),
                    'lat' => floatval($row['wgs84_lat']),
                    'lng' => floatval($row['wgs84_long']),
                    'dist' => floatval($row['dist'])
                );
            }

            // Remove duplicate feature names, keeping the closer one
            $temp = array();
            foreach ($suggestions as $s) {
                $name_key = strtolower(trim($s['feature']));
                if (!isset($temp[$name_key]) || $s['dist'] < $temp[$name_key]['dist']) {
                    $temp[$name_key] = $s;
                }
            }

            $suggestions = array_values($temp);

            // Sort by distance
            usort($suggestions, function($a, $b) {
                return $a['dist'] <=> $b['dist'];
            });

            // Limit to 10 suggestions
            $suggestions = array_slice($suggestions, 0, 10);
        }

        $data['suggestions'] = $suggestions;
        break;

    case 'get_features':
        $feature_type_id = intval($_GET['feature_type_id'] ?? 0);
        if ($feature_type_id <= 0) {
            $data['features'] = array();
            break;
        }

        // Left join with gridimage_search to obtain hash and title of selected image
        $sql = "SELECT f.feature_item_id, f.name, f.wgs84_lat, f.wgs84_long, f.gridimage_id, gi.title, gi.user_id
                FROM feature_item f
                LEFT JOIN gridimage_search gi USING (gridimage_id)
                WHERE f.feature_type_id = ? AND f.status > 0";
        $rows = $db->getAll($sql, array($feature_type_id));

        $features = array();
        foreach ($rows as $row) {
    	    $image = null;
	        if ($row['gridimage_id']) {
                $image = new GridImage();
                $image->fastInit($row);
	        }
            $features[] = array(
                'id' => intval($row['feature_item_id']),
                'name' => latin1_to_utf8($row['name'] ?: ''),
                'lat' => floatval($row['wgs84_lat']),
                'lng' => floatval($row['wgs84_long']),
                'gridimage_id' => $row['gridimage_id'] ? intval($row['gridimage_id']) : null,
                'hash' => $image?$image->_getAntiLeechHash():null,
                'title' => $row['title'] ? latin1_to_utf8($row['title']) : null
            );
        }
        $data['features'] = $features;
        break;

    case 'get_nearby_curated_images':
        $lat = floatval($_GET['lat'] ?? 0);
        $lng = floatval($_GET['lng'] ?? 0);

        if ($lat == 0 && $lng == 0) {
            $data['images'] = array();
            break;
        }

        // Bounding box of approx 1km (latitude is ~111km per deg, longitude is ~70km per deg at 51N)
        $lat_delta = 0.009;
        $lng_delta = 0.014;

        $lat_min = $lat - $lat_delta;
        $lat_max = $lat + $lat_delta;
        $lng_min = $lng - $lng_delta;
        $lng_max = $lng + $lng_delta;

        $sql = "SELECT DISTINCT gi.gridimage_id, gi.title, gi.wgs84_lat, gi.wgs84_long, gi.user_id, c.active,
		    ST_Distance_Sphere(
			        POINT(gi.wgs84_long, gi.wgs84_lat),
			        POINT(?, ?)
		    ) AS dist
                FROM curated c
                INNER JOIN gridimage_search gi USING (gridimage_id)
                WHERE c.label = ? AND c.active IN (1, 2)
                  AND gi.wgs84_lat BETWEEN ? AND ?
                  AND gi.wgs84_long BETWEEN ? AND ?
                HAVING dist <= 1000
                ORDER BY dist ASC
                LIMIT 50";
        $rows = $db->getAll($sql, array($lng, $lat, $label, $lat_min, $lat_max, $lng_min, $lng_max));

        $images = array();
        foreach ($rows as $row) {
            $image = new GridImage();
            $image->fastInit($row);
            $images[] = array(
                'id' => intval($row['gridimage_id']),
                'title' => latin1_to_utf8($row['title']),
                'lat' => floatval($row['wgs84_lat']),
                'lng' => floatval($row['wgs84_long']),
                'hash' => $image->_getAntiLeechHash(),
                'active' => intval($row['active']),
                'dist' => floatval($row['dist'])
            );
        }
        $data['images'] = $images;
        break;

    case 'insert_feature':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.0 405 Method Not Allowed');
            $data['error'] = 'POST method required for insert_feature.';
            break;
        }

        $feature_type_id = intval($_POST['feature_type_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $lat = floatval($_POST['wgs84_lat'] ?? 0);
        $lng = floatval($_POST['wgs84_long'] ?? 0);
        $gridimage_id = isset($_POST['gridimage_id']) && $_POST['gridimage_id'] !== '' && $_POST['gridimage_id'] !== 'null' ? intval($_POST['gridimage_id']) : null;

        if ($feature_type_id <= 0) {
            $data['error'] = 'Invalid feature_type_id.';
            break;
        }
        if ($name === '' && empty($gridimage_id)) {
            $data['error'] = 'Please enter a name or select an image.';
            break;
        }

        $sql = "INSERT INTO feature_item (feature_type_id, name, wgs84_lat, wgs84_long, gridimage_id, status, user_id, point_ll)
                VALUES (?, ?, ?, ?, ?, 1, ?, POINT(0,0))";
        $db->execute($sql, array($feature_type_id, $name, $lat, $lng, $gridimage_id, $USER->user_id));
        $data['status'] = 'inserted';
        $data['feature_item_id'] = intval($db->Insert_ID());
        break;

    case 'update_feature':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.0 405 Method Not Allowed');
            $data['error'] = 'POST method required for update_feature.';
            break;
        }

        $feature_item_id = intval($_POST['feature_item_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $gridimage_id = isset($_POST['gridimage_id']) && $_POST['gridimage_id'] !== '' && $_POST['gridimage_id'] !== 'null' ? intval($_POST['gridimage_id']) : null;

        if ($feature_item_id <= 0) {
            $data['error'] = 'Invalid feature_item_id.';
            break;
        }

        $sql = "UPDATE feature_item
                SET name = ?, gridimage_id = ?, user_id = ?
                WHERE feature_item_id = ? AND (user_id = ? OR 1=1)";
        $db->execute($sql, array($name, $gridimage_id, $USER->user_id, $feature_item_id, $USER->user_id));
        $data['status'] = 'updated';
        break;

    case 'update_feature_coords':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.0 405 Method Not Allowed');
            $data['error'] = 'POST method required for update_feature_coords.';
            break;
        }

        $feature_item_id = intval($_POST['feature_item_id'] ?? 0);
        $lat = floatval($_POST['wgs84_lat'] ?? 0);
        $lng = floatval($_POST['wgs84_long'] ?? 0);

        if ($feature_item_id <= 0) {
            $data['error'] = 'Invalid feature_item_id.';
            break;
        }

        $sql = "UPDATE feature_item
                SET wgs84_lat = ?, wgs84_long = ?, user_id = ?
                WHERE feature_item_id = ? AND (user_id = ? OR 1=1)";
        $db->execute($sql, array($lat, $lng, $USER->user_id, $feature_item_id, $USER->user_id));
        $data['status'] = 'updated';
        break;

    case 'get_unassigned_queue':
        $sql = "SELECT c.curated_id, c.gridimage_id, c.active, gi.user_id, gi.title, gi.wgs84_lat, gi.wgs84_long
                FROM curated c
                INNER JOIN gridimage_search gi USING (gridimage_id)
                WHERE c.label = ? AND c.active IN (1, 2) AND c.feature = ''
                ORDER BY c.curated_id ASC";
        $rows = $db->getAll($sql, array($label));

        $queue = array();
        foreach ($rows as $row) {
            $image = new GridImage();
            $image->fastInit($row);
            $queue[] = array(
                'id' => intval($row['gridimage_id']),
                'active' => intval($row['active']),
                'title' => latin1_to_utf8($row['title']),
                'hash' => $image->_getAntiLeechHash(),
                'lat' => floatval($row['wgs84_lat']),
                'lng' => floatval($row['wgs84_long'])
            );
        }
        $data['queue'] = $queue;
        break;

    case 'get_report':
        $sql = "SELECT COUNT(*) AS total_curated, feature
                FROM curated
                WHERE label = ? AND active = 2
                GROUP BY feature
                ORDER BY total_curated DESC";
        $rows = $db->getAll($sql, array($label));

        $report = array();
        foreach ($rows as $row) {
            $report[] = array(
                'feature' => latin1_to_utf8($row['feature'] ?: '[Unassigned]'),
                'total_curated' => intval($row['total_curated'])
            );
        }
        $data['report'] = $report;
        break;

    default:
        $data['error'] = 'Invalid action requested.';
        break;
}

outputJSON($data);
