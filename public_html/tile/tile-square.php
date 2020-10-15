<?

if (!empty($_GET['gg'])) {
	define('SPHINX_INDEX',"germany");
} elseif (!empty($_GET['is'])) {
	define('SPHINX_INDEX',"islands");
} else
	define('SPHINX_INDEX',"sample8");

require_once('geograph/global.inc.php');

//https://github.com/LaurensRietveld/HeatMap/blob/master/googleMapUtility.php
require_once ('3rdparty/googleMapUtilityClass.php');

$g = new googleMapUtilityClass($_GET['x'], $_GET['y'], $_GET['z']);

$b = $g->getTileRect();

##long,lat,long,lat

$xd = $b->width/64;
$yd = $b->height/64;
$bounds = array();
$bounds[] = $b->x-$xd;
$bounds[] = $b->y-$yd;
$bounds[] = $b->x+$b->width+$xd;
$bounds[] = $b->y+$b->height+$yd;

$_GET['olbounds'] = implode(",",$bounds);
$_GET['select'] = "wgs84_lat*1000 as lat,wgs84_long*1000 as lng";

if (empty($_GET['limit']))
	$_GET['limit'] = 1000;
if (empty($_GET['order']))
	$_GET['order'] = ($_GET['z'] > 8)?"sequence asc":"RAND()";

$_GET['mid'] = 1;//long expires header

if (!empty($_GET['l'])) {
	if ($_GET['z'] == 9) {
		$_GET['option'] = "max_matches=5000";
	        $_GET['limit'] = 5000;

	} elseif ($_GET['z'] < 9) {
		$_GET['option'] = "max_matches=50000";
		$_GET['limit'] = 50000;
		ini_set('memory_limit', '128M');
	}
}

if (!empty($_GET['user_id']))
	@$_GET['match'] .= " @user user".intval($_GET['user_id']);

if (!empty($_GET['6']) && $_GET['z'] > 10)
	$_GET['where'] = 'scenti not in(1000000000,2000000000)'; //exclude 4fig GRs!


$_GET['select'] = "avg(wgs84_lat*1000) as lat,avg(wgs84_long*1000) as lng,count(*) as images";
$_GET['group'] = "grid_reference"; // one dot per square!
$_GET['order'] = "id asc"; //rand doesnt work for group, could use sequence, but may as well just use id.


$_GET['select'] = "x,y,count(*) as images"; //using x,y avoids having to convert the lat/lng to xy, and minimises rounding errors!
$_GET['group'] = "x,y"; // one dot per square! x/y being simple ints is slightly more effient


  $conv = new Conversions;

if (!empty($_GET['dde'])) {
	print_r($_GET);
	exit;
}


//this is automatically called by "api-facetql.php"
function call_with_results($data) {

	global $g,$b,$conv;

########################################################################
########################################################################
########################################################################


	$im = imagecreatetruecolor(googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE);

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


	if (!empty($data['rows']))
	foreach ($data['rows'] as $row) {

		if (!empty($row['lat'])) {
			$lat = rad2deg($row['lat']/1000);
			$lng = rad2deg($row['lng']/1000);

			//alas using wgs84_to_internal, doesnt give refernce_index
			list($e,$n,$ri) = $conv->wgs84_to_national($lat,$lng);
			list($x,$y) =  $conv->national_to_internal($e,$n,$ri,false);
		} else {
			$x = $row['x'];
			$y = $row['y'];

	                if ($x == 4294967205) $x = -91; //manticore stores x,y in uint column, which overflows. only used for Rockall
		}

                $p1 = getPixCoord($x,$y,$ri); //getPixCoord gives us location of bottom left corner.
                $p2 = getPixCoord($x+1,$y+1,$ri);

		$v = $row['images'];

		$a = max(60 - log($v*2)*log($v*2),0);
		$color = imagecolorallocatealpha($im, 0, 0, 255, $a);

		imagefilledrectangle($im, $p1->x,$p1->y, $p2->x,$p2->y, $color);
	}

	imagesavealpha($im, true);
	header('Content-type: image/png');
	imagepng($im);


########################################################################
########################################################################
########################################################################

	exit;

}



include("../api-facetql.php");
