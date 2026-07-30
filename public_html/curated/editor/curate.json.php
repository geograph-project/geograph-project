<?php
/**
 * curate.json.php - JSON API endpoint for spatial curation tool
 */

require_once('geograph/global.inc.php');
init_session();

// Fallback definition for outputJSON if not already defined in Geograph functions
if (!function_exists('outputJSON')) {
    function outputJSON(&$data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}

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
        $sql = "SELECT c.curated_id, c.gridimage_id, c.active, c.feature, c.region, c.caption, c.user_id,
                       gi.title, gi.hash, gi.wgs84_lat, gi.wgs84_long
                FROM curated c
                INNER JOIN gridimage_search gi USING (gridimage_id)
                WHERE c.label = ?
                ORDER BY c.updated DESC";
        $rows = $db->getAll($sql, array($label));

        $curated_list = array();
        foreach ($rows as $row) {
            $curated_list[] = array(
                'id' => intval($row['gridimage_id']),
                'active' => intval($row['active']),
                'feature' => latin1_to_utf8($row['feature']),
                'region' => latin1_to_utf8($row['region']),
                'caption' => latin1_to_utf8($row['caption']),
                'user_id' => intval($row['user_id']),
                'title' => latin1_to_utf8($row['title']),
                'hash' => $row['hash'],
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
        $region = trim($_POST['region'] ?? '');
        $caption = trim($_POST['caption'] ?? '');

        if ($gridimage_id <= 0) {
            $data['error'] = 'Invalid gridimage_id.';
            break;
        }

        // Check if row already exists for this label and image
        $existing = $db->getRow("SELECT curated_id, user_id, active FROM curated WHERE label = ? AND gridimage_id = ?", array($label, $gridimage_id));

        if ($existing) {
            // Update existing row
            // Enforce security scoping check by updating user_id to current user who approves/modifies it
            $sql = "UPDATE curated
                    SET active = ?, feature = ?, region = ?, caption = ?, user_id = ?
                    WHERE label = ? AND gridimage_id = ?";
            $db->execute($sql, array($active, $feature, $region, $caption, $USER->user_id, $label, $gridimage_id));
            $data['status'] = 'updated';
            $data['curated_id'] = intval($existing['curated_id']);
        } else {
            // Insert new row
            $sql = "INSERT INTO curated (user_id, feature, label, region, gridimage_id, caption, created, active)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            $db->execute($sql, array($USER->user_id, $feature, $label, $region, $gridimage_id, $caption, $active));
            $data['status'] = 'inserted';
            $data['curated_id'] = intval($db->Insert_ID());
        }
        break;

    case 'get_proximity_suggestions':
        $lat = floatval($_GET['lat'] ?? 0);
        $lng = floatval($_GET['lng'] ?? 0);

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
                       (ABS(gi.wgs84_lat - ?) + ABS(gi.wgs84_long - ?)) AS dist
                FROM curated c
                INNER JOIN gridimage_search gi USING (gridimage_id)
                WHERE c.label = ? AND c.active = 2 AND c.feature != ''
                  AND gi.wgs84_lat BETWEEN ? AND ?
                  AND gi.wgs84_long BETWEEN ? AND ?
                ORDER BY dist ASC
                LIMIT 10";
        $rows = $db->getAll($sql, array($lat, $lng, $label, $lat_min, $lat_max, $lng_min, $lng_max));

        $suggestions = array();
        foreach ($rows as $row) {
            $suggestions[] = array(
                'feature' => latin1_to_utf8($row['feature']),
                'lat' => floatval($row['wgs84_lat']),
                'lng' => floatval($row['wgs84_long']),
                'dist' => floatval($row['dist'])
            );
        }
        $data['suggestions'] = $suggestions;
        break;

    case 'get_unassigned_queue':
        $sql = "SELECT c.curated_id, c.gridimage_id, c.active, gi.title, gi.hash, gi.wgs84_lat, gi.wgs84_long
                FROM curated c
                INNER JOIN gridimage_search gi USING (gridimage_id)
                WHERE c.label = ? AND c.active IN (1, 2) AND c.feature = ''
                ORDER BY c.curated_id ASC";
        $rows = $db->getAll($sql, array($label));

        $queue = array();
        foreach ($rows as $row) {
            $queue[] = array(
                'id' => intval($row['gridimage_id']),
                'active' => intval($row['active']),
                'title' => latin1_to_utf8($row['title']),
                'hash' => $row['hash'],
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
