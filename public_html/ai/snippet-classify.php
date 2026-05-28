<?php



require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic"); //mainly just as anti-scraping

        $db = GeographDatabaseConnection(false);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$message = '';
$user_id = $USER->user_id;
if (!empty($_GET['verify'])) {
    $user_id += 1000000;
}


// 1. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['classification'])) {
    $submitted_snippet_id = (int)$_POST['item_id'];
    $classification = $_POST['classification'];

    // Prepare variables for insertion using ADOdb's AutoExecute or normal Execute
    // Table schema assumed: snippet_classification (snippet_id, classification, created_at)
    $insertData = array(
        'snippet_id' => $submitted_snippet_id,
        'classification' => $classification,
	'user_id' => $user_id,
    );
    
    // Using ADOdb AutoExecute for a clean, injection-safe INSERT
    $result = $db->AutoExecute('snippet_classification', $insertData, 'INSERT');
    
    if ($result) {
        $message = '<div class="alert alert-success">Classification updated successfully!</div>';
        // In production, you'd likely redirect to the next snippet ID here
    } else {
        $message = '<div class="alert alert-danger">Error saving classification: ' . htmlspecialchars($db->ErrorMsg()) . '</div>';
    }
}

// 2. Fetch Shared Description Data

$rand = floor(rand(1,$db->getOne("SELECT MAX(snippet_id) FROM snippet")-1000));

$query_sd = "SELECT s.snippet_id, title, comment
 FROM snippet s
 INNER JOIN snippet_centroids USING (snippet_id)
 LEFT JOIN snippet_classification c USING (snippet_id)
 WHERE c.snippet_id IS NULL AND enabled=1 AND images> 5

and s.snippet_id > $rand

 LIMIT 1";

// 2. Fetch Shared Description Data (Prioritizing least classified, excluding current user)

//by default, start with unknowns (unlikly all 25k will get done, so really this is finding 0's)
$order = "classification_count ASC";
$having = '';
$roll = rand(1, 7);

if (!empty($_GET['verify'])) {
    //look for items to review...
	// the difference between Single-Cluster and Area is a very fine line, ignore that for now
    $having = "having count(distinct replace(c.classification,'Single-Cluster','Area')) > 1 or sum(c.classification in ('Skip','Other'))>0";
    $order = "s.snippet_id ASC"; //might as well go though in consistent order

} elseif ($roll < 3) {
    // Prioritize exactly 1 classification, to kick-start doubles
    $order = "(COUNT(c.snippet_id) = 1) DESC";
} elseif ($roll == 5) {
    // hunt for the most-classified items to test user calibration
    $order = "classification_count DESC";
}


$query_sd = "SELECT s.snippet_id, s.title, s.comment, COUNT(c.snippet_id) as classification_count
             FROM snippet s
             INNER JOIN snippet_centroids USING (snippet_id)
             -- Left join to find out if THIS specific user has already classified it
             LEFT JOIN snippet_classification u_done 
                ON s.snippet_id = u_done.snippet_id AND u_done.user_id = ?
             -- Left join to count TOTAL classifications from everyone
             LEFT JOIN snippet_classification c 
                ON s.snippet_id = c.snippet_id
             WHERE u_done.snippet_id IS NULL 
               AND s.enabled = 1 
               AND images > 5
             GROUP BY s.snippet_id, s.title, s.comment
		$having
             ORDER BY $order,  s.snippet_id > $rand DESC, s.snippet_id ASC
             LIMIT 1";


$rs_sd = $db->Execute($query_sd, [$user_id]);

if (!$rs_sd || $rs_sd->EOF) {
    die("Snippet not found.");
}

$snippet_id = $rs_sd->fields['snippet_id'];
$title = $rs_sd->fields['title'];
$comment = $rs_sd->fields['comment'];

// 3. Fetch Grid Images Data for Map Coordinates
$query_map = "SELECT gridimage_id, title, wgs84_lat, wgs84_long 
              FROM gridimage_search 
              INNER JOIN gridimage_snippet USING (gridimage_id) 
              WHERE snippet_id = ? LIMIT 10000";
$rs_map = $db->Execute($query_map, array($snippet_id));

$map_points = array();
while ($rs_map && !$rs_map->EOF) {
    $map_points[] = array(
        'id'   => $rs_map->fields['gridimage_id'],
        'title'=> latin1_to_utf8($rs_map->fields['title']),
        'lat'  => (float)$rs_map->fields['wgs84_lat'],
        'lng'  => (float)$rs_map->fields['wgs84_long']
    );
    $rs_map->MoveNext();
}

// Convert PHP array to a clean JSON string for Leaflet consumption
$json_points = json_encode($map_points);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classification Interface - Item <?php echo $snippet_id; ?></title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    
    <style>
        :root {
            --primary-color: #2b6cb0;
            --hover-color: #2c5282;
            --skip-color: #4a5568;
            --skip-hover-color: #2d3748;
            --bg-color: #f7fafc;
            --card-bg: #ffffff;
            --text-color: #2d3748;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

/* When the screen is 1400px or wider, sit side-by-side */
@media (max-width: 1400px) {
    body {
        flex-direction: column;
    }
}

        .container {
            width: 100%;
            max-width: 800px;
            background: var(--card-bg);
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .shared-description {
            margin-bottom: 24px;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 16px;
        }

        .shared-description h1 {
            font-size: 1.5rem;
            margin-top: 0;
            margin-bottom: 8px;
            color: #1a202c;
        }

        .shared-description p {
            font-size: 1rem;
            line-height: 1.6;
            margin: 0;
        }

        .classification-form {
            margin-bottom: 24px;
        }

        .button-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }

        .btn {
            padding: 12px 16px;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
            background-color: var(--primary-color);
            color: white;
        }

        .btn:hover {
            background-color: var(--hover-color);
        }

        .btn-skip {
            background-color: var(--skip-color);
        }

        .btn-skip:hover {
            background-color: var(--skip-hover-color);
        }

        #map {
            height: 450px;
            width: 100%;
            border-radius: 6px;
            border: 1px solid #cbd5e0;
            z-index: 1;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-weight: 500;
        }
        .alert-success { background-color: #c6f6d5; color: #22543d; }
        .alert-danger { background-color: #fed7d7; color: #742a2a; }
    </style>
</head>
<body>

    <div class="container">
        
        <?php echo $message; ?>

        <section class="shared-description">
            <h1><?php echo htmlentities2($title); ?></h1>
            <p><?php echo htmlentities2($comment); ?></p>
        </section>

        <form method="POST" class="classification-form">
            <input type="hidden" name="item_id" value="<?php echo $snippet_id; ?>">
            
            <div class="button-group">
                <button type="submit" name="classification" value="Single-Cluster" class="btn">Single-Cluster</button>
                <button type="submit" name="classification" value="Multi-cluster" class="btn">Multi-cluster</button>
                <button type="submit" name="classification" value="Area" class="btn">Area</button>
                <button type="submit" name="classification" value="Linear" class="btn">Linear</button>
                <button type="submit" name="classification" value="Locationless" class="btn">Locationless</button>
                <button type="submit" name="classification" value="One-Off" class="btn">One-Off</button>
                <button type="submit" name="classification" value="Other" class="btn">Other</button>
                <button type="submit" name="classification" value="Skip" class="btn btn-skip">Skip</button>
            </div>
        </form>

        <div id="map"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        // Data injected securely from ADOdb layer
        const pointsData = <?php echo $json_points; ?>;

        // Initialize map instance
        const map = L.map('map');

        // Basic OSM Tile Layer configuration (using &copy; HTML entity for attribution)
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        if (pointsData.length > 0) {
            const markersGroup = [];

            // Plot each image row using a CircleMarker
            pointsData.forEach(point => {
                const marker = L.circleMarker([point.lat, point.lng], {
                    radius: 8,
                    fillColor: "#e53e3e", // red accent filling
                    color: "#fff",       // white border outline
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                })
                .addTo(map)
                .bindPopup(`<b>ID:</b> ${point.id}<br><b>Title:</b> ${point.title}`);
                
                markersGroup.push(marker);
            });

            // Automatically scale and center map bounds to wrap around database points nicely
            const featureGroup = L.featureGroup(markersGroup);
            map.fitBounds(featureGroup.getBounds().pad(0.1));
        } else {
            // Fallback view if no coordinates are returned
            map.setView([52.409, -1.513], 13);
        }
    </script>

<table style="padding:20px">
  <thead>
    <tr>
      <th>Category</th>
      <th>Fast Definition</th>
      <th>Key Visual Clue</th>
      <th>Examples</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Single-Cluster</strong></td>
      <td>Single major location. All points sit in one blob. In general the images would depict the specific subject.</td>
      <td>One tight cluster (allows a few distant views/outliers).</td>
      <td>A specific church; a historic estate house + its gardens.</td>
    </tr>
    <tr>
      <td><strong>Multi-Cluster</strong></td>
      <td>Multiple distinct pockets of points separated by wide gaps.</td>
      <td>Several separate tight clusters on the map.</td>
      <td>Churches in Norfolk; Martello Towers; Birch Woodlands.</td>
    </tr>
    <tr>
      <td><strong>Linear</strong></td>
      <td>Points track along a clear pathway or network.</td>
      <td>A distinct winding line or branching route.</td>
      <td>Canals, rivers, active or abandoned railways, long-distance trails.</td>
    </tr>
    <tr>
      <td><strong>Area</strong></td>
      <td>Points are scattered randomly inside a specific boundary. In general any photo within the area could count.</td>
      <td>A loose, random dot-matrix filling a set area.</td>
      <td>A National Park, nature reserve, specific town, county, or beach.</td>
    </tr>
    <tr>
      <td><strong>Locationless</strong></td>
      <td>Universal or general subject themes. Points appear anywhere on the map.</td>
      <td>Total random scatter across the whole country, subjects that don't have fixed location.</td>
      <td>Cormorant (the bird); Birch Trees (generic species); Sunsets.</td>
    </tr>
    <tr>
      <td><strong>One-Off</strong></td>
      <td>One-off events or highly personal tracking tags.</td>
      <td>Low reuse value; often restricted to one time or one user.</td>
      <td>Pickering Steam Rally 2026; Ribblehead Viaduct Walk 26th July 2009, My Evening Stroll.</td>
    </tr>
  </tbody>

 <tr>
  <td colspan=3>
Single Cluster, is differnated from Area by the distribution. i.e. cluster would have a general 'center' or focus, rather than anything distributed over the area.
</td>

</table>

</body>
</html>
