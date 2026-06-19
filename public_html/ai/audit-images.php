<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm('basic');


//	$smarty->display('_std_begin.tpl');

	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

######################################

$type = "subject";
$gridref = isset($_GET['gridref']) ? $_GET['gridref'] : '';

if (empty($gridref)) {
    die("Error: A grid reference (&amp;gridref=) is required.");
}

$user_id = (int)$USER->user_id;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $img_id = (int)($_POST['gridimage_id'] ?? 0);
    
    if ($img_id > 0) {
        $lat = 0.0;
        $long = 0.0;
        $distance = 'NULL';
        $status = 'skip';

        if ($action === 'placed') {
            $lat = (float)($_POST['new_lat'] ?? 0);
            $long = (float)($_POST['new_long'] ?? 0);
            $distance = (int)($_POST['distance'] ?? 0);
            $status = 'placed';
        } elseif ($action === 'ok') {
            $lat = (float)($_POST['orig_lat'] ?? 0);
            $long = (float)($_POST['orig_long'] ?? 0);
            $distance = 0;
            $status = 'ok';
        } elseif ($action === 'incorrect') {
            // Confirmed wrong, but location unknown
            $lat = 0.0;
            $long = 0.0;
            $distance = 'NULL';
            $status = 'incorrect';
        } elseif ($action === 'skip') {
            $lat = 0.0;
            $long = 0.0;
            $distance = 'NULL';
            $status = 'skip';
        }

        if (!empty($action)) {
            $insert_sql = "INSERT INTO gridimage_audit (gridimage_id, user_id, type, wgs84_lat, wgs84_long, distance, status)
                           VALUES (?, ?, ?, ?, ?, $distance, ?)";
            
            $db->Execute($insert_sql, [$img_id, $user_id, $type, $lat, $long, $status]);
            
            // Redirect smoothly to the next image in the queue
            header("Location: ?gridref=" . urlencode($gridref));
            exit;
        }
    }
}

######################################

// Fetch Next Image
$sql = "SELECT gi.gridimage_id, gi.user_id, gi.title, gi.grid_reference, gi.realname, gi.wgs84_lat, gi.wgs84_long
        FROM gridimage_search gi
        LEFT JOIN gridimage_audit a ON (a.gridimage_id = gi.gridimage_id AND a.type = ? AND a.user_id = ?)
        WHERE a.user_id IS NULL
        AND gi.grid_reference = ? 
        LIMIT 1";

$row = $db->getRow($sql, [$type, $user_id, $gridref]);

if (!$row) {
    echo "<!DOCTYPE html><html><head><title>Audit Complete</title></head><body>";
    echo "<p>No remaining images found for grid reference: " . htmlspecialchars($gridref) . "</p>";
    echo "</body></html>";
    exit;
}

$image = new GridImage();
$image->fastInit($row);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Image Location Audit &middot; Gridref <?php echo htmlspecialchars($gridref); ?></title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; background: #fdfdfd; }
        .audit-container { display: flex; gap: 20px; max-width: 1300px; margin: 0 auto; }
        .panel-media { flex: 1; max-width: 640px; }
        .panel-map { flex: 1; display: flex; flex-direction: column; }
        #map { width: 100%; height: 500px; border: 1px solid #ccc; border-radius: 4px; }
        .meta-info { margin-top: 10px; font-size: 0.9em; color: #555; }
        
        .action-bar { margin-top: 15px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        button { padding: 10px 16px; font-size: 14px; font-weight: bold; border-radius: 4px; border: 1px solid #ccc; cursor: pointer; }
        button:disabled { opacity: 0.4; cursor: not-allowed; }
        
        #btn-ok { background: #e2f0d9; border-color: #b4c6e7; color: #385723; }
        #btn-placed { background: #fff2cc; border-color: #ffd966; color: #7f6000; }
        #btn-incorrect { background: #fce4d6; border-color: #f8cbad; color: #c65911; }
        #btn-skip { background: #f2f2f2; color: #595959; }
        #btn-reset { background: #fff; border: 1px dashed #f44336; color: #f44336; display: none; }
        
        h3 { margin: 0 0 5px 0; }
    </style>
</head>
<body>

<div class="audit-container">
    <div class="panel-media">
        <h3>Image Auditing &mdash; ID: <?php echo (int)$row['gridimage_id']; ?></h3>
        <div class="image-wrapper"><?php print $image->getFull(); ?></div>
        <div class="meta-info">
            <strong>Title:</strong> <?php echo htmlspecialchars($row['title']); ?><br>
            <strong>Author:</strong> <?php echo htmlspecialchars($row['realname']); ?><br>
            <strong>Declared Grid Square:</strong> <?php echo htmlspecialchars($row['grid_reference']); ?>
        </div>
    </div>

    <div class="panel-map">
    	<p>Drag the pin to the <b>SUBJECT</b> of the photo.</p>
        <div id="map"></div>
        
        <form id="audit-form" method="POST" action="">
            <input type="hidden" name="gridimage_id" value="<?php echo (int)$row['gridimage_id']; ?>">
            <input type="hidden" name="orig_lat" value="<?php echo (float)$row['wgs84_lat']; ?>">
            <input type="hidden" name="orig_long" value="<?php echo (float)$row['wgs84_long']; ?>">
            <input type="hidden" id="new_lat" name="new_lat" value="">
            <input type="hidden" id="new_long" name="new_long" value="">
            <input type="hidden" id="distance" name="distance" value="">
            <input type="hidden" id="action" name="action" value="">

            <div class="action-bar">
                <button type="button" id="btn-ok" onclick="submitAudit('ok')">Looks OK</button>
                <button type="button" id="btn-placed" onclick="submitAudit('placed')" disabled>I&rsquo;ve Placed the Pin</button>
                <button type="button" id="btn-reset" onclick="resetMarker()">Reset Pin</button>
                
                <button type="button" id="btn-incorrect" title="Know the Pin location, 100% certain photo not taken there, but don&rsquo;t know the correct location" onclick="submitAudit('incorrect')">Incorrect Location</button>
                
                <button type="button" id="btn-skip" title="Don't reconcise the photo, or too subjective to accurately place" onclick="submitAudit('skip')">Don&rsquo;t know</button>
                <span id="distance-display" style="font-size:0.85em; color:#666;"></span>
            </div>

	    <p>If can't accurately locate the photo (within ~100m), then just use [Don't know]; unless absolutely sure it not taken near the pin, then use [Incorrect Location].

	    <p>If a wide angle, or long range short without a strict subject location, (ie the location is technically fine, even if not what you would of picked), use [Don't Know] 
	    unless you can say the pin is definitly <i>wrong</i>, them move the pin as needed.

	    <p>You may find just moving the pin a tiny amount (like 12m), when we come to analysis the data will take into account the resolution of the origial grid-reference, just place 
	    as best can on the available map.

        </form>
    </div>
</div>

<script>
    const origLat = <?php echo (float)$row['wgs84_lat']; ?>;
    const origLng = <?php echo (float)$row['wgs84_long']; ?>;
    const origLatLng = L.latLng(origLat, origLng);

    const map = L.map('map').setView([origLat, origLng], 17);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OSM' }).addTo(map);

    const marker = L.marker([origLat, origLng], { draggable: true }).addTo(map);

    const btnOk = document.getElementById('btn-ok');
    const btnPlaced = document.getElementById('btn-placed');
    const btnIncorrect = document.getElementById('btn-incorrect');
    const btnReset = document.getElementById('btn-reset');
    const distanceDisplay = document.getElementById('distance-display');

    marker.on('dragend', function () {
        const currentLatLng = marker.getLatLng();
        const distanceMeters = Math.round(origLatLng.distanceTo(currentLatLng));

        if (distanceMeters > 0) {
            btnOk.disabled = true;
            btnIncorrect.disabled = true; // Block assertion if they dragged it
            btnPlaced.disabled = false;
            btnReset.style.display = 'inline-block';
            
            document.getElementById('new_lat').value = currentLatLng.lat.toFixed(6);
            document.getElementById('new_long').value = currentLatLng.lng.toFixed(6);
            document.getElementById('distance').value = distanceMeters;
            distanceDisplay.innerText = `Moved: ${distanceMeters}m`;
        } else {
            resetMarker();
        }
    });

    function resetMarker() {
        marker.setLatLng(origLatLng);
        map.panTo(origLatLng);
        btnOk.disabled = false;
        btnIncorrect.disabled = false;
        btnPlaced.disabled = true;
        btnReset.style.display = 'none';
        distanceDisplay.innerText = '';
    }

    function submitAudit(actionType) {
        document.getElementById('action').value = actionType;
        document.getElementById('audit-form').submit();
    }
</script>
</body>
</html>
