<?

define('SPHINX_INDEX',"sample8");

//https://github.com/LaurensRietveld/HeatMap/blob/master/googleMapUtility.php
require_once ('3rdparty/googleMapUtilityClass.php');

$g = new googleMapUtilityClass($_GET['x'], $_GET['y'], $_GET['z']);

$tilerect = $b = $g->getTileRect();

##long,lat,long,lat

$xd = $b->width/16; //overshoot by 16th of a square!
$yd = $b->height/16;
$bounds = array();
$bounds[] = $b->x-$xd;
$bounds[] = $b->y-$yd;
$bounds[] = $b->x+$b->width+$xd;
$bounds[] = $b->y+$b->height+$yd;

$_GET['olbounds'] = implode(",",$bounds);
$_GET['select'] = "id,hash,wgs84_lat*1000 as lat,wgs84_long*1000 as lng,sequence as s";
if (empty($_GET['limit']))
	$_GET['limit'] = 35;

$_GET['order'] = "sequence asc";

$_GET['long'] = 1;//long expires header

if (!empty($_GET['l'])) {
	//$_GET['option'] = "max_matches=500";
	$_GET['limit'] = 100;
}

$_GET['where'] = array();
if (!empty($_GET['6']) && $_GET['z'] > 10) {
	$_GET['where'][] = 'scenti not in(1000000000,2000000000)';
}

if (!empty($_GET['mgeo'])) {
	$_GET['where'][] = "land > 0";
	$_GET['where'][] = "status = 'geograph'";

} elseif (!empty($_GET['land'])) {
        $_GET['where'][] = "land > 5";
}

########################################################################

if (!empty($_GET['gbt'])) {
	if (!empty($_GET['m'])) {
		$_GET['select'] = "id,hash,avg(wgs84_lat*1000) as lat,avg(wgs84_long*1000) as lng,sequence as s";
	}

        $divlat = deg2rad($tilerect->height/floatval($_GET['gbt']));
        $divlng = deg2rad($tilerect->width/floatval($_GET['gbt']));

        $tile1 = "floor( wgs84_lat / $divlat )";
        $tile2 = "floor( wgs84_long / $divlng )";

        $_GET['select'] .= ", ($tile1*100) + $tile2 AS tile";

	$_GET['group'] = 'tile';
	$_GET['limit'] = ceil($_GET['gbt']*$_GET['gbt'])*2;

	$_GET['within'] = $_GET['order'];

	if (!empty($_GET['m']) && $_GET['m'] == 1) {
		$_GET['order'] = "wgs84_long ASC ,wgs84_lat DESC";
	}
}



########################################################################

if (!empty($_GET['dd'])) {
	print "<pre>";
	print_r($tilerect);
	print_r($bounds);
	print_r($_GET);
	exit;
}



//this is automatically called by "api-facetql.php"
function call_with_results($data) {

	global $g,$b;

########################################################################
########################################################################
########################################################################

	//setup

	$im = imagecreatetruecolor(googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE);

	$bg = imagecolorallocate($im, 101,117,255);
	if (!empty($_GET['t']))
	        imagecolortransparent($im,$bg);

	imagefilledrectangle($im, 0,0, googleMapUtilityClass::TILE_SIZE,googleMapUtilityClass::TILE_SIZE, $bg);

	if (empty($data) || !empty($data['meta']['error'])) {

		$fg = imagecolorallocate($im, 0, 0, 255);

		imagestring($im, 5, 0, 0, $data['meta']['error'], $fg);

		header('Content-type: image/png');
		imagepng($im);
		exit;
	}

	#############################
	//dedup

	if (!empty($data['rows']) && (count($data['rows']) > 10) && !empty($_GET['div'])) {
		global $tilerect;

		//remove close duplicates, data is still in squence order at this point
                $t = array();

		if ($_GET['z'] > 15) {
			$_GET['div'] = $_GET['div']*1.7;
		}

		$divlat = $tilerect->height/floatval($_GET['div']);
		$divlng = $tilerect->width/floatval($_GET['div']);

                foreach ($data['rows'] as $idx => $row) {
			$lat = rad2deg($row['lat']/1000);
			$lng = rad2deg($row['lng']/1000);

		        //OLD! :  $k = round($row['lat']/1000,4).":".round($row['lng']/1000,4);

                        $k = intval($lat/$divlat).":".intval($lng/$divlng);
                        if (isset($t[$k]))
                                unset($data['rows'][$idx]);
                        $t[$k]=1;
                }
	}

	#############################
        //reverse the order (so low sequence drawn last - on top!)
			function cmp(&$a, &$b) {
				if ($a['s'] == $b['s']) {
					return 0;
				}
			  	return ($a['s'] > $b['s']) ? -1 : 1;
			}
	usort($data['rows'], 'cmp');


	#############################
	//thumbs!

	global $divlat,$divlng; //gridsize in RADIANS!
	$halflat = rad2deg($divlat)*0.5;
	$halflng = rad2deg($divlng)*0.5;

if (!empty($_GET['credits'])) {
	$db = GeographDatabaseConnection(false);

	$sql = "INSERT INTO photomap_credit SET x = ".intval($_GET['x']).", y = ".intval($_GET['y']).", z = ".intval($_GET['z']);
}

	$filesystem = new FileSystem();

	if (!empty($data['rows']))
	foreach ($data['rows'] as $row) {

		$path = getGeographPath($row['id'],$row['hash'],'small');


		if ($filesystem->file_exists($path, true)) {
			if (!empty($_GET['credits'])) {
				$db->Execute("$sql, gridimage_id = {$row['id']}");
			}

			if (!empty($_GET['m']) && $_GET['m'] == 2) {

		                $minlat = rad2deg( floor($row['lat']/1000/$divlat) * $divlat);
		                $minlng = rad2deg( floor($row['lng']/1000/$divlng) * $divlng);

				$p = $g->getOffsetPixelCoords($minlat+$halflat,$minlng+$halflng);

			} else {
				$lat = rad2deg($row['lat']/1000);
				$lng = rad2deg($row['lng']/1000);

				$p = $g->getOffsetPixelCoords($lat,$lng);
			}


			//imagefilledrectangle($im, $p->x-1,$p->y-1, $p->x+1,$p->y+1, $fg);

			$photo = $filesystem->imagecreatefromjpeg($path);

			$widthS = imagesx($photo);	$heightS = imagesy($photo);
			$widthD = intval($widthS*0.6);	$heigthD = intval($heightS*0.6);
			$widthD2 = intval($widthD/2);	$heigthD2 = intval($heigthD/2);

			imagecopyresampled($im, $photo,
				$p->x - $widthD2,	$p->y - $heigthD2,	0,	0,
				$widthD, 	$heigthD, 	$widthS,	$heightS);

			/*
				bool imagecopyresampled ( resource $dst_image , resource $src_image , 
					int $dst_x , int $dst_y , int $src_x , int $src_y , 
					int $dst_w , int $dst_h , int $src_w , int $src_h )
			*/

	                imagedestroy($photo);
		}
	}


	#############################

	//imagesavealpha($im, true);
	header('Content-type: image/png');
	imagepng($im);

########################################################################
########################################################################
########################################################################

	exit;

}

include("../api-facetql.php");


function getGeographPath($gridimage_id,$hash,$size ='small') {

       $yz=sprintf("%02d", floor($gridimage_id/1000000));
       $ab=sprintf("%02d", floor(($gridimage_id%1000000)/10000));
       $cd=sprintf("%02d", floor(($gridimage_id%10000)/100));
       $abcdef=sprintf("%06d", $gridimage_id);

        if ($yz == '00') {
                $fullpath=$_SERVER['DOCUMENT_ROOT']."/photos/$ab/$cd/{$abcdef}_{$hash}";
        } else {
                $fullpath=$_SERVER['DOCUMENT_ROOT']."/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}";
        }

       switch($size) {
                case 'orig': return "{$fullpath}_original.jpg"; break;
               case 'full': return "$fullpath.jpg"; break;
               case 'med': return "{$fullpath}_213x160.jpg"; break;
               case 'small':  return "{$fullpath}_120x120.jpg";
               default: return "{$fullpath}_{$size}.jpg"; //this is a custom version for geogridfs
       }
}

