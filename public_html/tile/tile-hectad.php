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

$_GET['limit'] = 1000;

if (!empty($_GET['l'])) {
	if ($_GET['z'] <6) {
		$_GET['limit'] = 4000;
		$_GET['option'] = "max_matches=4000";
	}
}

$_GET['mid'] = 1;//long expires header


if (!empty($_GET['user_id']))
	@$_GET['match'] .= " @user user".intval($_GET['user_id']);

if (!empty($_GET['6']) && $_GET['z'] > 10)
	$_GET['where'] = 'scenti not in(1000000000,2000000000)'; //exclude 4fig GRs!


$_GET['order'] = "id asc"; //rand doesnt work for group, could use sequence, but may as well just use id.


$_GET['select'] = "scenti,x,y,count(*) as images"; //using x,y avoids having to convert the lat/lng to xy, and minimises rounding errors!
$_GET['group'] = "hectad";


  $conv = new Conversions;

if (!empty($_GET['dde'])) {
	print_r($_GET);
	exit;
}


//this is automatically called by "api-facetql.php"
function call_with_results($data) {

	global $g,$b,$conv,$CONF;

########################################################################
########################################################################
########################################################################

	header('Access-Control-Allow-Origin: *'); //needed for Google Earth, in general Leafet etc use std <img> that ignore CORS

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
		$reference_index = $row['scenti'][0];

		$x = $row['x'];
		$y = $row['y'];

		if ($x == 4294967205) $x = -91; //manticore stores x,y in uint column, which overflows. only used for Rockall

                //remove the internal origin
                $x -= $CONF['origins'][$reference_index][0];
                $y -= $CONF['origins'][$reference_index][1];

		$x = intval($x/10)*10;
		$y = intval($y/10)*10;

                $x += $CONF['origins'][$reference_index][0];
                $y += $CONF['origins'][$reference_index][1];


                $p1 = getPixCoord($x,$y,$reference_index); //getPixCoord gives us location of bottom left corner.
                $p2 = getPixCoord($x+10,$y+10,$reference_index);

		$v = $row['images']/3; //by 3 as hectads have lots of images! (scaling function was designed for single squares!)

		$a = max(60 - log($v*2)*log($v*2),0);
		$color = imagecolorallocatealpha($im, 0, 0, 255, intval($a));

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
