<?

 define('SPHINX_INDEX',"scenic");

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
$_GET['select'] = "wgs84_lat as lat,wgs84_long as lng,scenic as s";
if (empty($_GET['limit']))
	$_GET['limit'] = 1000;
if (empty($_GET['order']))
	$_GET['order'] = "sequence ASC";
$_GET['filter']['status'] = "4";

$_GET['long'] = 1;//long expires header

if (!empty($_GET['l'])) {
	$_GET['option'] = "max_matches=50000";
	$_GET['limit'] = 50000;
}

$pxsize = 0;
if ($_GET['z'] > 10)
	$pxsize=1;
if ($_GET['z'] > 13)
	$pxsize=2;

if (isset($_GET['group'])) {
	$sizee = intval($_GET['group']);
	$columns = array('max','min','avg');
	$func = 'avg';
        if (!empty($_GET['column']) && in_array($_GET['column'],$columns))
                $func = $_GET['column'];

	$_GET['select'] = "wgs84_lat as lat,wgs84_long as lng,$func(scenic) as s,CEIL(wgs84_lat*$sizee) as l1,CEIL(wgs84_long*$sizee) as l2";
	$_GET['group'] = "l1,l2";
}


//this is automatically called by "sample5nql.php"
function call_with_results($data) {

	global $g,$b,$pxsize;

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

customExpiresHeader(30,true);

		header('Content-type: image/png');
		imagepng($im);
		exit;
	}

        for ($p=1; $p<=10; $p++) {
                switch (true) {
                        case $p ==10: $rr=255; $gg=255; $bb=0; break;
                        case $p == 9: $rr=255; $gg=196; $bb=0; break;
                        case $p == 8: $rr=255; $gg=132; $bb=0; break;
                        case $p == 7: $rr=255; $gg=64; $bb=0; break;
                        case $p == 6: $rr=225; $gg=0; $bb=0; break;
                        case $p == 5: $rr=200; $gg=0; $bb=0; break;
                        case $p == 4: $rr=168; $gg=0; $bb=0; break;
                        case $p == 3: $rr=136; $gg=0; $bb=0; break;
                        case $p == 2: $rr=112; $gg=0; $bb=0; break;
                        default: $rr=80; $gg=0; $bb=0; break;
                }
	        $colours[$p] = ImageColorAllocate($im, $rr, $gg, $bb);
	}
	if (!empty($_GET['uniq'])) {
		$total =0;
		foreach ($data['rows'] as $row)
			$total += $row['s'];
		$avg = ceil($total/count($data['rows']));
	}
	foreach ($data['rows'] as $row) {
		if (!empty($_GET['uniq']) && $avg == ceil($row['s']) ) {
			continue;
		}
		$lat = rad2deg($row['lat']);
		$lng = rad2deg($row['lng']);

		$p = $g->getOffsetPixelCoords($lat,$lng,$_GET['z'],$_GET['x'],$_GET['y']);

		$colur = $colours[ceil($row['s'])];
		if (!empty($_GET['text'])) {
			imagestring($im, intval($_GET['text']), $p->x-3, $p->y-3, ceil($row['s']), $colur);
		} else
			imagefilledrectangle($im, $p->x-$pxsize,$p->y-$pxsize, $p->x+$pxsize,$p->y+$pxsize, $colur);
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
