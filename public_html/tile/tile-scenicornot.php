<?

if ($_GET['z'] < 7) {
//         require __DIR__."/tile-coverage-hectad.php";
         exit;
}

if ($_GET['z'] > 11) {
//         require __DIR__."/tile-coverage-large.php";
         exit;
}


require_once('geograph/global.inc.php');
ini_set('memory_limit', '128M');

$maxspan = 10;

customExpiresHeader(3600*24*30);

//https://github.com/LaurensRietveld/HeatMap/blob/master/googleMapUtility.php
require_once ('3rdparty/googleMapUtilityClass.php');
require_once ('geograph/tile.inc.php');

$g = new googleMapUtilityClass($_GET['x'], $_GET['y'], $_GET['z']);

$b = $g->getTileRect();

##long,lat,long,lat

$xd = $b->width/8;
$yd = $b->height/8;
$bounds = array();
$bounds[] = $b->x-$xd;
$bounds[] = $b->y-$yd;
$bounds[] = $b->x+$b->width+$xd;
$bounds[] = $b->y+$b->height+$yd;

########################################################################

	//from public_html/stuff/squares.json.php


$sql = array();
$sql['wheres'] = array();

        #### example: -10.559026590196122,46.59604915850878,7.514135843906623,54.84589681367314

        $span = max($bounds[2] - $bounds[0],$bounds[3] - $bounds[1]);

        $conv = new Conversions;

        if ($span > $maxspan) {
                $error = "Zoom in closer to the British Isles to see coverage details";

        } elseif (true) {
		//todo, could also do a wgs84 bbox filter, so could use a index in scenic_votes directly

		//$sql['wheres'][] = sprintf('s.wgs84_lat  BETWEEN %.6f AND %.6f', $bounds[1], $bounds[3]);
		//$sql['wheres'][] = sprintf('s.wgs84_long BETWEEN %.6f AND %.6f', $bounds[0], $bounds[2]);
			//lat/long are DECIMAL which doesnt index well!

		//worth creating a new spatial index on scenic_votes as it more selective than gi
// ALTER TABLE scenic_votes ADD point_ll POINT NOT NULL;
// UPDATE scenic_votes SET point_ll = GeomFromText(CONCAT('POINT(',wgs84_long,' ',wgs84_lat,')'));
// ALTER TABLE scenic_votes ADD SPATIAL INDEX(point_ll);

		list($west,$south,$east,$north) = $bounds;
		$rectangle = "'POLYGON(($west $south,$east $south,$east $north,$west $north,$west $south))'";
		$sql['wheres'][] = "CONTAINS(GeomFromText($rectangle),s.point_ll)";

        } else {
                $conv = new Conversions;

                list($x1,$y1) = $conv->wgs84_to_internal(floatval($bounds[1]),floatval($bounds[0])); //bottom-left
                list($x2,$y2) = $conv->wgs84_to_internal(floatval($bounds[3]),floatval($bounds[2])); //top-rigth

//BODGE!
	if ($x2 == 0 && $bounds[2] > 2.3 && $bounds[2] < 4) { //wgs84_to_national as a small boundary for GB!
		$conv2 =  new ConversionsLatLong;

		list($e,$n) = $conv2->wgs84_to_osgb36(floatval($bounds[3]),floatval($bounds[2]));
		list($x2,$y2) = $conv2->national_to_internal($e,$n,1);
	}

		if ($x1 > -100 && $x1 < 1000) {
	                $rectangle = "'POLYGON(($x1 $y1,$x2 $y1,$x2 $y2,$x1 $y2,$x1 $y1))'";
        	        $sql['wheres'][] = "CONTAINS( GeomFromText($rectangle), point_xy)";

		} else {
			$error = "Zoom in closer to the British Isles to see coverage details";
		}
        }


        $db = GeographDatabaseConnection(true);

        $sql['tables'] = array();

                $sql['tables']['gs'] = 'gridimage_search';
                $sql['tables']['s'] = 'inner join scenic_votes s using (gridimage_id)';

                $sql['columns'] = "x,y,reference_index as ri, average";

        $query = sqlBitsToSelect($sql);

        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_GET['dd'])) {
	print_r($bounds);
	print_r($query);
	print_r($sql);
	exit;
}

	if (empty($error))
	        $rows = $db->getAll($query);


########################################################################

	header('Access-Control-Allow-Origin: *'); //needed for Google Earth, in general Leafet etc use std <img> that ignore CORS

	$im = imagecreate(googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE);

	$bg = imagecolorallocate($im, 255, 255, 255);
	imagecolortransparent($im,$bg);
	$fg = imagecolorallocate($im, 255, 0, 0); //marker/red
	$land = imagecolorallocate($im, 117,255,101); //land/green!

        $colours = getStaticColorKey($im);

	if (!empty($error)) { //todo!

		imagestring($im, 5, 0, 0, $error, $fg);

		header('Content-type: image/png');
		imagepng($im);
		exit;
	}

	if (!empty($rows))
        foreach ($rows as $idx => $row) {
		$color = $colours[intval($row['average'])]; //$land;

                $p1 = getPixCoord($row['x'],$row['y'],$row['ri']); //getPixCoord gives us location of bottom left corner.
                $p2 = getPixCoord($row['x']+1,$row['y']+1,$row['ri']);

		imagefilledrectangle($im, $p1->x,$p1->y, $p2->x,$p2->y, $color);
	}

	imagesavealpha($im, true);
	header('Content-type: image/png');
	imagepng($im);




