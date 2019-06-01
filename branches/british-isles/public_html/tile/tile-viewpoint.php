<?


if ($_GET['z'] < 10 && empty($_GET['match'])) {
	header("HTTP/1.0 204 No Content");
	die();

} elseif ($_GET['z'] < 10) {
	header("HTTP/1.0 204 No Content");
	die();
}


define('SPHINX_INDEX',"viewpoint");

//https://github.com/LaurensRietveld/HeatMap/blob/master/googleMapUtility.php
require_once ('3rdparty/googleMapUtilityClass.php');

$g = new googleMapUtilityClass($_GET['x'], $_GET['y'], $_GET['z']);

$b = $g->getTileRect();

##long,lat,long,lat

$xd = $b->width/128;
$yd = $b->height/128;

if ((!isset($_GET['j']) || !empty($_GET['j'])) && $_GET['z'] > 16) { //temp bodge, for lines!
	$xd = $b->width*2; //need extra, to draw lines from 'off tile'
	$yd = $b->height*2;
}


$bounds = array();
$bounds[] = $b->x-$xd;
$bounds[] = $b->y-$yd;
$bounds[] = $b->x+$b->width+$xd;
$bounds[] = $b->y+$b->height+$yd;

$_GET['olbounds'] = implode(",",$bounds);
$_GET['select'] = "vlat*1000 as lat,vlong*1000 as lng,vgrlen,direction";

if ((!isset($_GET['j']) || !empty($_GET['j'])) && $_GET['z'] > 16) //temp bodge, for lines!
	$_GET['select'] .= ",distance,wgs84_lat*1000 as slt,wgs84_long*1000 as slng";

if (empty($_GET['limit']))
	$_GET['limit'] = 1000;
if (empty($_GET['order']))
	$_GET['order'] = ($_GET['z'] > 8)?"sequence asc":"RAND()";

//$_GET['long'] = 1;//long expires header
//$_GET['mid'] = 1;//mid expires header

if (!empty($_GET['l'])) {
	$_GET['option'] = "max_matches=50000";
	$_GET['limit'] = 50000;
	ini_set('memory_limit', '128M');
}

if (!empty($_GET['user_id']))
	@$_GET['match'] .= " @user user".intval($_GET['user_id']);

if (!empty($_GET['6']) && $_GET['z'] > 10) {
	//$_GET['where'] = 'vcenti not in(1000000000,2000000000)'; //exclude 4fig GRs!
        $_GET['where'] = 'vgrlen>4';
        //todo, this could use natgrlen attribute instead now!
        //in fact for viewpoint, NEED to use vgrlen, as viewpoint columns set eastings/northing even on 4figs!
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
	$fg = imagecolorallocatealpha($im, 148, 0, 211, 127);  //purple, https://rubblewebs.co.uk/imagemagick/display_example.php?example=53
	imagefilledrectangle($im, 0,0, googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE, $fg);

	$line = imagecolorallocate($im, 255, 0, 30); //marker/red
	$arrow = imagecolorallocatealpha($im, 148, 0, 211, 30);
	//$highlight = imagecolorallocatealpha($im, 230, 230, 230, 20);
	$highlight = imagecolorallocatealpha($im, 0, 0, 0, 20);

	$decay = (count($data['rows']) > 1000)?25:15;

	if (!empty($data['rows']))
	foreach ($data['rows'] as $row) {

		$lat = rad2deg($row['lat']/1000);
		$lng = rad2deg($row['lng']/1000);

		$p = $g->getOffsetPixelCoords($lat,$lng);

		if ($row['direction'] > -1) {
			$points = array_merge(
				array($p->x,$p->y),
				projectpoint($p->x,$p->y,8,$row['direction']-27),
				projectpoint($p->x,$p->y,4,$row['direction']),
				projectpoint($p->x,$p->y,8,$row['direction']+27)
			);
				//array($p->x,$p->y); -dont need to close the polygone!

			imagefilledpolygon($im, $points, 4, $arrow);

		} else {
			//php/gd doesnt doesnt have a 'blend alphas' (eg if 20% alpha in square add 20% to make same colour at 40%, so do it manually!
			for($x=$p->x-3; $x<=$p->x+3; $x++) {
				for($y=$p->y-3; $y<=$p->y+3; $y++) {
					$d = sqrt(pow($p->y-$y,2) + pow($p->x-$x,2));
					imageaddalpha($im, $x, $y, -110+($decay*$d));
				}
			}
		}
		if (!empty($row['distance']) && $row['distance']>2 && !empty($row['slt'])) {
			$slat = rad2deg($row['slt']/1000);
	                $slng = rad2deg($row['slng']/1000);

			$p2 = $g->getOffsetPixelCoords($slat,$slng);
			imageline($im, $p->x, $p->y, $p2->x, $p2->y, $line);
		}
		if ($row['vgrlen'] > $nopoint) {
			//draw a tiny dot to present coverage in busy areas
			//imagesetpixel($im, $p->x, $p->y, $highlight);
			imagefilledrectangle($im, $p->x-1,$p->y-1, $p->x+1,$p->y+1, $highlight);
		} else { //if(!empty($_GET['pp'])) {
			imagefilledrectangle($im, $p->x-1,$p->y-1, $p->x+1,$p->y+1, $arrow);
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

function projectpoint($x,$y,$d,$a) {//x/y/distance/angle
	$a = deg2rad($a);
	$xx = sin($a)*$d;
	$yy = cos($a)*$d;
	return array(round($x+$xx),round($y-$yy));     //minus, because images use top/left origin, e/n use bottom left, and $a is relative to north. 
}



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

