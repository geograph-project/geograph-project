<?

require_once('geograph/global.inc.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/conversions.class.php');


init_session();


$conv = new Conversions();


	$z = intval($_GET['z']);
	

	$p = intval($_GET['p']);
	$x = ($p % 900);
	$y = ($p - $x) / 900;
	$x = 900 - $x;


$square=new GridSquare;

$grid_ok=$square->loadFromPosition($x, $y, true);
$g = $square->grid_reference;
	


$img=imagecreate(125,125);

$blue=imagecolorallocate ($img, 101,117,255);
imagefill($img,0,0,$blue);
$black=imagecolorallocate($img, 255,255,255);

imagestring($img, 2, 5, 5, "TILE:$z-$x-$y", $black);	
imagestring($img, 2, 20, 20, "G:$g", $black);	
imagestring($img, 2, 30, 30, "R:".$_GET['r'], $black);	
imagestring($img, 2, 30, 50, "P:".$p, $black);	

header("Content-Type: image/png");
imagepng($img);

?>