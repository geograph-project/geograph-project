<?


require_once('geograph/global.inc.php');
ini_set('memory_limit', '128M');

$maxspan = 30;

customExpiresHeader(empty($_GET['long'])?3600*24:3600*24*6,true);

//https://github.com/LaurensRietveld/HeatMap/blob/master/googleMapUtility.php
require_once ('3rdparty/googleMapUtilityClass.php');

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

        if ($span > $maxspan) {
                $error = "Zoom in closer to the British Isles to see coverage details";
        } else {
                $conv = new Conversions;

if ($bounds[1] < 49)
	$bounds[1] = 49.0001;
if ($bounds[2] > 2.3)
	$bounds[2] = 2.2999;
if ($bounds[3] > 62)
	$bounds[3] = 61.9999;

                list($x1,$y1) = $conv->wgs84_to_internal(floatval($bounds[1]),floatval($bounds[0])); //bottom-left
                list($x2,$y2) = $conv->wgs84_to_internal(floatval($bounds[3]),floatval($bounds[2])); //top-rigth

//BODGE!
	if ($x1 == 0 && $bounds[0] < -9.5 && $bounds[0-9.5] > -15) { //wgs84_to_national as a small boundary for GB!
		$conv2 =  new ConversionsLatLong;

		list($e,$n) = $conv2->wgs84_to_osgb36(floatval($bounds[1]),floatval($bounds[0]));
		list($x1,$y1) = $conv2->national_to_internal($e,$n,1);
	}

	if ($x2 == 0 && $bounds[2] > 2.3 && $bounds[2] < 5.8) { //wgs84_to_national as a small boundary for GB!
		$conv2 =  new ConversionsLatLong;

		list($e,$n) = $conv2->wgs84_to_osgb36(floatval($bounds[3]),floatval($bounds[2]));
		list($x2,$y2) = $conv2->national_to_internal($e,$n,1);
	}

		if ($x1 > -100 && $x1 < 1000) {
	                //$rectangle = "'POLYGON(($x1 $y1,$x2 $y1,$x2 $y2,$x1 $y2,$x1 $y1))'";
        	        //$sql['wheres'][] = "CONTAINS( GeomFromText($rectangle), point_xy)";

	                $sql['wheres'][] = "x between $x1 AND $x2";
	                $sql['wheres'][] = "y between $y1 AND $y2";

		} else {
			$error = "Zoom in closer to the British Isles to see coverage details";
		}
        }


        $db = GeographDatabaseConnection(true);

        $sql['tables'] = array();

        if (!empty($_GET['user_id'])) {
                $sql['tables']['gi'] = 'gridimage_search gi';
                $sql['group'] = 'hectad';
                $sql['wheres'][] = "user_id = ".intval($_GET['user_id']);
		$sql['wheres'][] = "moderation_status = 'geograph'";

                $sql['columns'] = "CONCAT(SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-3),SUBSTRING(gi.grid_reference,LENGTH(gi.grid_reference)-1,1)) AS hectad,"
			."x,y,reference_index as ri, COUNT(DISTINCT IF(imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR)),grid_reference,null)) AS percent"; //technically its just number in hectad, not a percent!
        } else {
                $sql['tables']['hs'] = 'hectad_stat';

                $sql['columns'] = "x,y,reference_index as ri,   recentsquares/landsquares*100 AS percent";
                $sql['wheres'][] = "landsquares>0";
        }

        $query = sqlBitsToSelect($sql);

        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_GET['dd'])) {
	print_r($bounds);
	print_r($query);
	print_r($sql);
	print_r($error);
	exit;
}

	if (empty($error))
	        $rows = $db->getAll($query);


########################################################################

	$im = imagecreate(googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE);

	$bg = imagecolorallocate($im, 255, 255, 255);
	imagecolortransparent($im,$bg);
	$fg = imagecolorallocate($im, 255, 0, 0); //marker/red

	if (!empty($error)) { //todo!

		imagestring($im, 5, 0, 0, $error, $fg);

		header('Content-type: image/png');
		imagepng($im);
		exit;
	}




	if (!empty($rows)) {
		$colors = array();
		foreach (range(0,100) as $percent) {
			$cr = 255 - $percent;
			$cg = 255 - ($percent*$percent); if ($cg<0) $cg=0;

			$colors[$percent] = imagecolorallocate($im, $cr, $cg, 0);
		}


        foreach ($rows as $idx => $row) {
		$color = $colors[floor($row['percent'])];

                $x = $row['x'];
                $y = $row['y'];
		$ri = $row['ri'];

                //remove the internal origin
                $x -= $CONF['origins'][$ri][0];
                $y -= $CONF['origins'][$ri][1];

                $x = intval($x/10)*10;
                $y = intval($y/10)*10;

                $x += $CONF['origins'][$ri][0];
                $y += $CONF['origins'][$ri][1];

                $p1 = getPixCoord($x,$y,$ri); //getPixCoord gives us location of bottom left corner.
                $p2 = getPixCoord($x+10,$y+10,$ri);

		imagefilledrectangle($im, $p1->x,$p1->y, $p2->x,$p2->y, $color);
	}
	}

	imagesavealpha($im, true);
	header('Content-type: image/png');
	imagepng($im);


########################################################################


