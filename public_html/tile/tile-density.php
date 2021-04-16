<?

if (!empty($_GET['gg'])) {
	define('SPHINX_INDEX',"germany");
} elseif (!empty($_GET['is'])) {
	define('SPHINX_INDEX',"islands");
} else {
	//these only support Brit+Isle currentyl!
	if ($_GET['z'] < 7) {
	        require __DIR__."/tile-hectad.php";
        	exit;

	} elseif ($_GET['z'] < 10) {
	        require __DIR__."/tile-square.php";
	        exit;
	}

	//not really needed as the default!
	define('SPHINX_INDEX',"sample8");
}

//https://github.com/LaurensRietveld/HeatMap/blob/master/googleMapUtility.php
require_once ('3rdparty/googleMapUtilityClass.php');

$g = new googleMapUtilityClass($_GET['x'], $_GET['y'], $_GET['z']);

$b = $g->getTileRect();

##long,lat,long,lat

$xd = $b->width/128;
$yd = $b->height/128;
$bounds = array();
$bounds[] = $b->x-$xd;
$bounds[] = $b->y-$yd;
$bounds[] = $b->x+$b->width+$xd;
$bounds[] = $b->y+$b->height+$yd;

$_GET['olbounds'] = implode(",",$bounds);
$_GET['select'] = "wgs84_lat*1000 as lat,wgs84_long*1000 as lng,natgrlen";

if (empty($_GET['limit']))
	$_GET['limit'] = 1000;
if (empty($_GET['order']))
	$_GET['order'] = ($_GET['z'] > 8)?"sequence asc":"RAND()";

//$_GET['long'] = 1;//long expires header
$_GET['mid'] = 1;//mid expires header

if (!empty($_GET['l'])) {
	$_GET['option'] = "max_matches=50000";
	$_GET['limit'] = 50000;
	ini_set('memory_limit', '128M');
}

if (!empty($_GET['user_id']))
	@$_GET['match'] .= " @user user".intval($_GET['user_id']);

if (!empty($_GET['6']) && $_GET['z'] > 10) {
	$_GET['where'] = 'scenti not in(1000000000,2000000000)'; //exclude 4fig GRs!
	//todo, this could use natgrlen attribute instead now!

} elseif (empty($_GET['match']) && $_GET['z'] < 10) {
	//todo, later, can redirect this to tile-coverage.php (maybe specifing blue as the colour scheme!)

	$_GET['group'] = "grid_reference"; // one dot per square!
	$_GET['order'] = "id asc"; //rand doesnt work for group, could use sequence, but may as well just use id.
}

$nopoint = 2;
if ($_GET['z'] > 16) {
	$nopoint = 8;
} elseif ($_GET['z'] > 13) {
	$nopoint = 6;
} elseif ($_GET['z'] > 10) {
	$nopoint = 4;
}

if (!function_exists('call_with_results')) { //hack that means this function is only added at RUNTIME, not COMPILE time.

//this is automatically called by "api-facetql.php"
function call_with_results($data) {

	global $g,$b,$nopoint;

########################################################################
########################################################################
########################################################################


	$im =  imagecreatetruecolor(googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE);

	if (empty($data) || !empty($data['meta']['error'])) {

		// White background and blue text
		$bg = imagecolorallocate($im, 255, 255, 255);
		imagecolortransparent($im,$bg);
		$fg = imagecolorallocate($im, 0, 0, 255);

		imagestring($im, 5, 0, 0, $data['meta']['error'], $fg);

		header('Content-type: image/png');
		imagepng($im);
		exit;
	}

	imagealphablending($im, false);
	//fill with completely trasparent! (so get something with 127 alpha)
	$fg = imagecolorallocatealpha($im, 0, 0, 255, 127);
	imagefilledrectangle($im, 0,0, googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE, $fg);

	$highlight = imagecolorallocatealpha($im, 255, 255, 255, 20);

	if (!empty($data['rows'])) {
		$decay = (count($data['rows']) > 1000)?25:15;

		foreach ($data['rows'] as $row) {

			$lat = rad2deg($row['lat']/1000);
			$lng = rad2deg($row['lng']/1000);

			$p = $g->getOffsetPixelCoords($lat,$lng);

			//php/gd doesnt doesnt have a 'blend alphas' (eg if 20% alpha in square add 20% to make same colour at 40%, so do it manually!
			for($x=$p->x-3; $x<=$p->x+3; $x++) {
				for($y=$p->y-3; $y<=$p->y+3; $y++) {
					$d = sqrt(pow($p->y-$y,2) + pow($p->x-$x,2));
					imageaddalpha($im, $x, $y, -110+($decay*$d));
				}
			}
			if ($row['natgrlen'] > $nopoint) {
				//draw a tiny dot to present coverage in busy areas
				imagesetpixel($im, $p->x, $p->y, $highlight);
			}
		}
	}

	imagesavealpha($im, true);
	header('Content-type: image/png');
	imagepng($im);


########################################################################
########################################################################
########################################################################

	exit;

}

}


include("../api-facetql.php");


function imageaddalpha(&$im, $x, $y, $delta) {

        if ($x<0 || $x > googleMapUtilityClass::TILE_SIZE)
                return;
        if ($y<0 || $y > googleMapUtilityClass::TILE_SIZE)
                return;

	$rgba = imagecolorat($im, $x, $y);

		$r = ($rgba >> 16) & 0xFF;
		$g = ($rgba >> 8) & 0xFF;
		$b = $rgba & 0xFF;
		$a = ($rgba & 0x7F000000) >> 24;

	$a+=round($delta);
	if ($a<=0) $a = 0;
	$color = imagecolorallocatealpha($im, $r, $g, $b, $a);

	imagesetpixel($im, $x, $y, $color);
}

