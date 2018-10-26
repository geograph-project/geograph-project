<?

if ($_GET['z'] < 7 && empty($_GET['gg'])) {
        require __DIR__."/tile-hectad.php";
        exit;

}elseif ($_GET['z'] < 10 && empty($_GET['gg'])) {
	require __DIR__."/tile-square.php";
	exit;
}

if (!empty($_GET['gg'])) {
	define('SPHINX_INDEX',"germany");
} else
	define('SPHINX_INDEX',"sample8");

//https://github.com/LaurensRietveld/HeatMap/blob/master/googleMapUtility.php
require_once ('3rdparty/googleMapUtility.php');

$g = new GoogleMapUtility();

$b = $g->getTileRect($_GET['x'], $_GET['y'], $_GET['z']);

##long,lat,long,lat

$xd = $b->width/128;
$yd = $b->height/128;
$bounds = array();
$bounds[] = $b->x-$xd;
$bounds[] = $b->y-$yd;
$bounds[] = $b->x+$b->width+$xd;
$bounds[] = $b->y+$b->height+$yd;

$_GET['olbounds'] = implode(",",$bounds);
$_GET['select'] = "wgs84_lat as lat,wgs84_long as lng";

if (empty($_GET['limit']))
	$_GET['limit'] = 1000;
if (empty($_GET['order']))
	$_GET['order'] = ($_GET['z'] > 8)?"sequence asc":"RAND()";

$_GET['long'] = 1;//long expires header

if (!empty($_GET['l'])) {
	$_GET['option'] = "max_matches=50000";
	$_GET['limit'] = 50000;
	ini_set('memory_limit', '128M');
}

if (!empty($_GET['user_id']))
	@$_GET['match'] .= " @user user".intval($_GET['user_id']);

if (!empty($_GET['6']) && $_GET['z'] > 10) {
	$_GET['where'] = 'scenti not in(1000000000,2000000000)'; //exclude 4fig GRs!

} elseif (empty($_GET['match']) && $_GET['z'] < 10) {
	//todo, later, can redirect this to tile-coverage.php (maybe specifing blue as the colour scheme!)

	$_GET['group'] = "grid_reference"; // one dot per square!
	$_GET['order'] = "id asc"; //rand doesnt work for group, could use sequence, but may as well just use id.
}

if (!function_exists('call_with_results')) { //hack that means this function is only added at RUNTIME, not COMPILE time.

//this is automatically called by "api-facetql.php"
function call_with_results($data) {

	global $g,$b;

########################################################################
########################################################################
########################################################################


	$im = imagecreate(GoogleMapUtility::TILE_SIZE,GoogleMapUtility::TILE_SIZE);

	// White background and blue text
	$bg = imagecolorallocate($im, 255, 255, 255);
	imagecolortransparent($im,$bg);
	$fg = imagecolorallocate($im, 0, 0, 255);

	if (empty($data) || !empty($data['meta']['error'])) {

		imagestring($im, 5, 0, 0, $data['meta']['error'], $fg);

		header('Content-type: image/png');
		imagepng($im);
		exit;
	}

	if (!empty($data['rows']))
	foreach ($data['rows'] as $row) {

		$lat = rad2deg($row['lat']);
		$lng = rad2deg($row['lng']);

		$p = $g->getOffsetPixelCoords($lat,$lng,$_GET['z'],$_GET['x'],$_GET['y']);

		imagefilledrectangle($im, $p->x-1,$p->y-1, $p->x+1,$p->y+1, $fg);
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
