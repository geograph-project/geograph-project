<?


require_once('geograph/global.inc.php');
ini_set('memory_limit', '128M');

$maxspan = 10;

customExpiresHeader(empty($_GET['long'])?3600*24:3600*24*6,true);
customCacheControl(filemtime(__FILE__),$_SERVER['QUERY_STRING']);

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

		$images = $db->getOne("SELECT 1 FROM user_gridsquare WHERE ".implode(' AND ',$sql['wheres'])." AND user_id = ".intval($_GET['user_id']));
		if (empty($images)) {
			//redirect - rather than just 'fallback', so that the image can actully be served from varnish cache!

			//https://t0.geograph.org.uk/tile/tile-score.php?z=8&x=125&y=76&v=2&user_id=104377&q
			header("Location: /tile/tile-score.php?z={$_GET['z']}&x={$_GET['x']}&y={$_GET['y']}",true,302);
			exit;
		}

                $sql['tables']['gs'] = 'gridsquare g ';
		$sql['tables']['ug'] = 'left join user_gridsquare ug on (ug.grid_reference = g.grid_reference and ug.user_id = '.intval($_GET['user_id']).')';

                $sql['columns'] = "g.x,g.y,g.reference_index as ri, (g.has_geographs+g.has_recent+(g.max_ftf>2)+(g.max_ftf>4)+(g.imagecount>20)+(g.imagecount>50)+(coalesce(ug.imagecount,0)>0)+(coalesce(ug.max_ftf,0)>0)) as s";
                $sql['wheres'][] = "percent_land > 0 ";
		$sql['wheres'][0] = str_replace(' point_xy',' g.point_xy',$sql['wheres'][0]);
        } else {
                $sql['tables']['gs'] = 'gridsquare';

                $sql['columns'] = "x,y,reference_index as ri,    (has_geographs+has_recent+(max_ftf>2)+(max_ftf>4)+(imagecount>20)+(imagecount>50)) as s";
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

        header('Access-Control-Allow-Origin: *'); //needed for Google Earth, in general Leafet etc use std <img> that ignore CORS

	$im = imagecreate(googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE);

	$bg = imagecolorallocate($im, 255, 255, 255);
	imagecolortransparent($im,$bg);
	$fg = imagecolorallocate($im, 255, 0, 0); //marker/red
	$supp = imagecolorallocate($im, 236,206,64); //supp/organge
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
		$color = $row['s']?$colours[$row['s']]:$land;

                $p1 = getPixCoord($row['x'],$row['y'],$row['ri']); //getPixCoord gives us location of bottom left corner.
                $p2 = getPixCoord($row['x']+1,$row['y']+1,$row['ri']);

		imagefilledrectangle($im, $p1->x,$p1->y, $p2->x,$p2->y, $color);
	}

	imagesavealpha($im, true);
	header('Content-type: image/png');
	imagepng($im);


########################################################################


////////////////////////////////////

function getStaticColorKey(&$img) {
        $colour=array();

        for ($p=1; $p<=10; $p++) {
                switch (true) {
                        case $p == 1: $r=255; $g=255; $b=0; break;
                        case $p == 2: $r=255; $g=196; $b=0; break;
                        case $p == 3: $r=255; $g=132; $b=0; break;
                        case $p == 4: $r=255; $g=64; $b=0; break;
                        case $p == 5: $r=225; $g=0; $b=0; break;
                        case $p == 6: $r=200; $g=0; $b=0; break;
                        case $p == 7: $r=168; $g=0; $b=0; break;
                        case $p == 8: $r=136; $g=0; $b=0; break;
                        case $p == 9: $r=112; $g=0; $b=0; break;
                        case $p ==10: $r=80; $g=0; $b=0; break;
                }
                $colour[$p]=imagecolorallocate($img, $r,$g,$b);
        }
        return $colour;
}
