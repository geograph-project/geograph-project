<?

if (empty($_GET['query'])) {
	header("HTTP/1.0 204 No Content");
	die();
}


$criteria = array(); //this is the $critera/filter array for imagelists3vector.class

########################################################################
// Tile setup

//https://github.com/LaurensRietveld/HeatMap/blob/master/googleMapUtility.php
require_once ('3rdparty/googleMapUtilityClass.php');
require_once ('geograph/tile.inc.php');

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

//$_GET['olbounds'] = implode(",",$bounds);
$criteria['bbox'] =  implode(",",$bounds);

//not perfect, but good for a quick check!
$coverage = "-13.688451,49.863788,1.795260,60.860395";

if (!doBoundingBoxesIntersect($coverage,$criteria['bbox'])) {
	header("HTTP/1.0 204 No Content");
        die();
}

########################################################################
//vector setup

require_once('geograph/global.inc.php');

                $filesystem = new FileSystem(); //sets up configuation automagically
                //the vector lip needs S3 class setup already!

		//note, if switich to imagelistknn, will have to make sure getKNNResults returns the lat/long!
                require_once('geograph/imagelists3vector.class.php');
                $imagelist=new ImageListS3Vector;

//note max isnt supported by s3vectors, we post filter!
if (!empty($_GET['max']))
	$criteria['max'] = floatval($_GET['max']);

if (!empty($_GET['query']))
	$criteria['label'] = $_GET['query'];

if (!empty($_GET['user_id']))
	$criteria['user_id'] = intval($_GET['user_id']);

########################################################################
// query function
// if ($imagelist->getImagesByLocationVector($lat, $lon, $label)) {
  //                      $imagelist->outputThumbs($thumbw, $thumbh);

//actully using getImagesByCriteria will get use actual image object (which is wasteful, we can use s3vector metadata directly!
$results = $imagelist->getRawVectorsByCriteria($criteria, 100, true);

########################################################################
//render tile

	$im =  imagecreatetruecolor(googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE);

	if (empty($results)) { // || empty($results['vectors'])) { -- should only be for errors, not no results

		// White background and blue text
		$bg = imagecolorallocate($im, 255, 255, 255);
		imagecolortransparent($im,$bg);
		//$fg = imagecolorallocate($im, 0, 0, 255);

		//imagestring($im, 5, 0, 0, $data['meta']['error'], $fg);

		header('Content-type: image/png');
		imagepng($im);
		exit;
	}

	imagealphablending($im, false);
	//fill with completely trasparent! (so get something with 127 alpha)
	$fg = imagecolorallocatealpha($im, 0, 0, 255, 127);
	imagefilledrectangle($im, 0,0, googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE, $fg);

	$highlight = imagecolorallocatealpha($im, 255, 255, 255, 20);

	if (!empty($results['vectors'])) {
		$decay = (count($results['vectors']) > 1000)?25:15;

/*
    [vectors] => Array
        (
            [0] => Array
                (
                    [key] => 1714625
                    [metadata] => Array
                        (
                            [taken] => 20100214
                            [slat] => 52.831476
                            [gridref] => SH5628
                            [user_id] => 40351
                            [slng] => -4.12437
                        )

                    [distance] => 0.74801713228226
                )*/

		foreach ($results['vectors'] as $row) {
			if (!empty($criteria['max']) && $row['distance'] > $criteria['max'])
				continue;

			$p = $g->getOffsetPixelCoords($row['metadata']['slat'], $row['metadata']['slng']);

			//php/gd doesnt doesnt have a 'blend alphas' (eg if 20% alpha in square add 20% to make same colour at 40%, so do it manually!
			for($x=$p->x-3; $x<=$p->x+3; $x++) {
				for($y=$p->y-3; $y<=$p->y+3; $y++) {
					$d = sqrt(pow($p->y-$y,2) + pow($p->x-$x,2));
					imageaddalpha($im, $x, $y, -110+($decay*$d));
				}
			}
			//if ($row['natgrlen'] > $nopoint) {
				//draw a tiny dot to present coverage in busy areas
				imagesetpixel($im, $p->x, $p->y, $highlight);
			//}
		}
	}

	imagesavealpha($im, true);
	header('Content-type: image/png');
	imagepng($im);

########################################################################

