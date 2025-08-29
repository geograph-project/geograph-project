<?

require_once('geograph/global.inc.php');
init_session();

//the gridimage_embedding is only on DEV instance for now!
$CONF['manticorert_host'] = "manticorert-worker-svc.dev.svc.cluster.local"; //test instance!

//        $db = GeographDatabaseConnection(false);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

        $rt = GeographSphinxConnection('manticorert',true);

                require_once('geograph/imagelistknn.class.php');
                $imagelist=new ImageListKNN;
                $imagelist->_setSph($rt); //need to force it to use RT backend


        require_once "geograph/locationselector.class.php";
        $location = new LocationSelector();


if (!empty($_GET['loc'])) {
        list($lat, $lng) = $location->extractLatLng($_GET['loc']);

        if (!empty($lat) && isset($lng)) { //lng COULD be e
                $_GET['lat'] = $lat;
                $_GET['lon'] = $lng;
        }
}

//$imagelist->knncols .= ",wgs84_lat,wgs84_long";
//we dont need then, instead want manticore to crate the distance!

$limit = 100;
if (!empty($_GET['limit']))
	$limit = intval($_GET['limit']);

####################################################

        if (!empty($_GET['lat']) && !empty($_GET['lon']) && !empty($_GET['dist'])) {
                $lat = $_GET['lat'];
                $lon = $_GET['lon'];
                $dist = $_GET['dist'];
                $label = !empty($_GET['label']) ? $_GET['label'] : null;

                print "These images are visually similar to the term <b>".htmlentities($label)."</b> and within ".round($dist/1000,1)."km of ".round($lat,6).", ".round($lon,6).".<br>";

                $imagelist->getImagesByLocation($lat, $lon, $dist, $label, $limit);

####################################################
//new vector search

        } elseif (!empty($_GET['lat']) && !empty($_GET['lon'])) {
                $lat = $_GET['lat'];
                $lon = $_GET['lon'];
                $label = $_GET['label'];

                print "These images are visually similar to the term <b>".htmlentities($label)."</b> and location ".round($lat,6).", ".round($lon,6).". (using experimental vector append met";

                $imagelist->getImagesByLocationVector($lat, $lon, $label, $limit, true); //true means add the GEO 'distance' column!
	}

print str_replace(' where ','<br>where ',preg_replace('/\((-?\d+\.\d+, )[\d,\. -]+\)/', '($1...)', $imagelist->sql)).";<hr>";

if (empty($imagelist->images))
	die("no results");

if (!empty($_GET['sort'])) {
	//fakr sorting by geodist - seems if use KNN, cant use ORDER BY in manticore! Even to reorder
	usort($imagelist->images, function($a,$b) {
		return (float)$a->distance - (float)$b->distance;
	});
}

$data = array();
foreach($imagelist->images as $idx => $image) {
            // Format: ['Label', distance_meters, vector_distance, dummy, rank]
	$data[] = array(''.($idx+1), intval($image->distance), floatval($image->k), 'D', count($imagelist->images)/($idx+1));
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>3D Scatter (Bubble) Chart - Fixed First Column</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            var data = new google.visualization.DataTable();
            // --- CHANGE HERE ---
            // The first column *must* be a string for Google Bubble Charts.
            // This will act as the "label" for each bubble, even if it's not displayed.
            data.addColumn('string', 'Bubble Label');
            // --- END CHANGE ---

            data.addColumn('number', 'Distance (meters)');      // Horizontal Axis (X-value)
            data.addColumn('number', 'Vector Distance (0-1)');  // Vertical Axis (Y-value)
            data.addColumn('string', 'Dummy Color'); // Column 3: Series/Color (dummy column)
            data.addColumn('number', 'Rank');                    // Bubble Size (Z-value)

            // Format: ['Label', distance_meters, vector_distance, dummy, rank]
            data.addRows(<? echo json_encode($data); ?>);

            // Determine the maximum distance in your data to add padding
            // This is crucial if your data changes dynamically
            let maxDistance = 0;
            data.getFilteredRows([{ column: 1, minValue: 0 }]).forEach(rowIdx => {
                maxDistance = Math.max(maxDistance, data.getValue(rowIdx, 1));
            });

            // Calculate a padded max value for the hAxis.
            // Since it's a logarithmic scale, adding a fixed number won't work well.
            // Instead, multiply by a factor. A factor like 1.2 to 1.5 is a good starting point.
            // Ensure the padded value is still reasonable for a log scale.
            const hAxisPaddingFactor = 1.2; // Increase by 20%
            const paddedMaxDistance = maxDistance * hAxisPaddingFactor;
            // If maxDistance is 100,000, paddedMaxDistance will be 120,000.


            var options = {
                title: 'Vector Distance vs. Distance (Meters) by Rank',
                hAxis: {
                    title: '<- closer -- Distance (meters)',
                    logScale: true,
                    format: '#,###',
                    minValue: 1,
                    gridlines: {
                        count: 6
                    },
                    viewWindow: {
                        min: 1, // Start from 1 for the logarithmic scale
                        max: paddedMaxDistance // Set the calculated padded maximum
                    }
                },
                vAxis: {
                    title: 'Vector Distance (0-1) -- better ->',
                    direction: -1,
                    gridlines: {
                        count: 5
                    }
                },
                bubble: {
                    opacity: 0.7,
                    sizeAxis: {
                        minValue: 1,
                        maxValue: 100,
                        minSize: 5,
                        maxSize: 30,
                        set: [
                            { value: 1, size: 30 },
                            { value: 100, size: 5 }
                        ]
                    },
                    textStyle: { // Optional: Hide labels inside bubbles if you don't want them
                        fontSize: 0 // Set font size to 0 to make labels invisible
                    }
                },
                legend: 'none'
            };

            var chart = new google.visualization.BubbleChart(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
    </script>
</head>
<body>
    <h1>Vector Distance Analysis [<? echo count($imagelist->images); ?> images]</h1>
    <div id="chart_div" style="width: 900px; height: 500px;"></div>
</body>
</html>
