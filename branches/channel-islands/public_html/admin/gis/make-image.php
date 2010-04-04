<?

ini_set("display_errors",1);

require_once('geograph/global.inc.php');
init_session();

$conv = new ConversionsLatLong;



$h = fopen("Channel Islands.kml",'r');


print "go";
while (!feof($h)) {
	$l = fgets($h);
	#print $l;
	if (preg_match('/<coordinates>([\d\., -]+)</',$l,$m)) {
		$e = array();
		$n = array();
		foreach(explode(' ',$m[1]) as $b) {
			if (trim($b)) {
				list($long,$lat,$dummy) = explode(',',$b,3);
				
				list($east,$north) = $conv->wgs84_to_utm($lat,$long,$zone=30);
				
				$e[] = $east;
				$n[] = $north;
				
			}
		}
		
		$ee[] = $e;
		$nn[] = $n;
		
		if ($e1) {
			$e1 = min($e1,min($e));
		} else {
			$e1 = min($e);
		}
		if ($n1) {
			$n1 = min($n1,min($n));
		} else {
			$n1 = min($n);
		}
		if ($e2) {
			$e2 = max($e2,max($e));
		} else {
			$e2 = max($e);
		}
		if ($n2) {
			$n2 = max($n2,max($n));
		} else {
			$n2 = max($n);
		}
		#break;
	}
}

print "done >> ($e1,$e2,$n1,$n2)\n";

$e1 = floor($e1/10000)*10000; //clamp to hectads
$e2 = ceil($e2/10000)*10000;

$n1 = floor($n1/10000)*10000;
$n2 = ceil($n2/10000)*10000;

print "done >> ($e1,$e2,$n1,$n2)\n";

##################

$w = max($e2 - $e1,$n2 - $n1)/100;
$h = $w;

print "H/W = ($w,$h)";

##################


$ow = $e2 - $e1;
$oh = $n2 - $n1;




if ($ow > $oh) {
        $bot = floor(($ow - $oh)/2000) * 1000;
        $top = ($ow - $oh) - $bot;
        $n1 -= $bot;
        $n2 += $top;
} else if ($oh > $ow) {
        $bot = floor(($oh - $ow)/2000) * 1000;
        $top = ($oh - $ow) - $bot;
        $e1 -= $bot;
        $e2 += $top;
}

print "final >> ($e1,$e2,$n1,$n2)\n";


#print "$bot,$top,".($ow - $oh).",".($top - ($bot * 1000));

$ow = $e2 - $e1;
$oh = $n2 - $n1;


$re = $w / $ow;
$rn = $h / $oh;

#echo "$e1,$n1 $e2,$n2<BR>";

##################

$imgh = imagecreate($w,$h);

$backcolor = imagecolorallocate($imgh,255,255,255);
$coastcolor = imagecolorallocate($imgh,0,0,0);

$thiscolor = $coastcolor;

imagefill($imgh,0,0,$backcolor);

##################

foreach ($ee as $shape => $e) {
	$n = $nn[$shape];
	
	$polygon = array();
	
	foreach ($e as $point => $east) {
		$north = $n[$point];
	
		$xy = cc($east,$north);

		$polygon[] = $xy[0];
		$polygon[] = $xy[1];

	}
	if ($ccc = count($polygon)) {
		imagefilledpolygon ( $imgh, $polygon, $ccc/2, $thiscolor);
		$polygon = array();
		print "added $ccc verticies \n";
	}

}

##################

#header("Content-type: image/png");
imagePNG($imgh,"output.png");


function cc($e,$n) {
        global $e1,$re,$n1,$rn,$h;
        $x = ($e - $e1) * $re;
        $y = $h - (($n - $n1) * $rn);
        return array ($x,$y);
}
