<?

if ($_GET['z'] < 7) {
         require __DIR__."/tile-coverage-hectad.php";
         exit;
}

if ($_GET['z'] > 11) {
         require __DIR__."/tile-coverage-large.php";
         exit;
}


require_once('geograph/global.inc.php');
ini_set('memory_limit', '128M');

$maxspan = 10;

customExpiresHeader(empty($_GET['user_id'])?3600*24*3:3600*6,true);

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

        if (!empty($_GET['user_id'])) {
                $sql['tables']['gi'] = 'gridimage_search';
                $sql['group'] = 'grid_reference';
                $sql['wheres'][] = "user_id = ".intval($_GET['user_id']);
		$sql['wheres'][] = "moderation_status = 'geograph'";
                $sql['columns'] = "x,y,reference_index as ri,   count(*) as g,SUM(imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR))) as r";
        } else {
                $sql['tables']['gs'] = 'gridsquare';

                $sql['columns'] = "x,y,reference_index as ri,   has_geographs as g, has_recent as r";
                $sql['wheres'][] = "percent_land > 0 ";
        }

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

	$im = imagecreate(googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE);

	$bg = imagecolorallocate($im, 255, 255, 255);
	imagecolortransparent($im,$bg);
	$fg = imagecolorallocate($im, 255, 0, 0); //marker/red

if (!empty($_GET['test'])) {
//#FF00FF
	//$supp = imagecolorallocate($im, 255,0,255); //tpoint/purple (no longer used for non-geo squares!)
	//$supp = imagecolorallocate($im, 255,204,255); //tpoint/pink (no longer used for non-geo squares!)
        $supp = imagecolorallocate($im, 1,50,32);

} else {
	$supp = imagecolorallocate($im, 255,136,0); //supp/organge
}

	$land = imagecolorallocate($im, 117,255,101); //land/green!

	if (!empty($error)) { //todo!

		imagestring($im, 5, 0, 0, $error, $fg);

		header('Content-type: image/png');
		imagepng($im);
		exit;
	}

	if (!empty($rows))
        foreach ($rows as $idx => $row) {
		$color = $row['r']?$fg:($row['g']?$supp:$land);

                $p1 = getPixCoord($row['x'],$row['y'],$row['ri']); //getPixCoord gives us location of bottom left corner.
                $p2 = getPixCoord($row['x']+1,$row['y']+1,$row['ri']);

		imagefilledrectangle($im, $p1->x,$p1->y, $p2->x,$p2->y, $color);
	}

	imagesavealpha($im, true);
	header('Content-type: image/png');
	imagepng($im);


########################################################################
