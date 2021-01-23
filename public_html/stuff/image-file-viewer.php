<?

if (empty($_GET['id'])) {
	die("please");
}

require_once('geograph/global.inc.php');

init_session();

if ($_SERVER['HTTP_HOST'] != 'staging.geograph.org.uk' && !$USER->hasPerm("admin"))
	die("404");


$id = intval($_GET['id']);

                        $image = new GridImage($id);
                        $image->getThumbnail(120,120,true);

$_GET['large'] = 1;  //make sure t responsive.
$tag = $image->getFull(true,true); //as used on photo page  - calls getSize etc

$postfix = empty($_GET['v'])?'':("&v=".intval($_GET['v']));


$sizes = array(40,60,120,213,'full');
$res = array();

if ($image->original_width>10) {
	$greatest = max($image->original_width,$image->original_height);
	if ($image->original_width > 640) {
		$size = 640;
                $thumbnail = $image->_getOriginalpath(true,true,"_{$size}x{$size}");

		//this can geniunely not exist, so check it existance first!
		if (basename($thumbnail) != "error.jpg") {
			$sizes[] = 640;
			//add it here, as it used by more sizes page
			$res["640x640"] = $thumbnail;
		}
	}

	if ($image->original_width > 800)
		$sizes[] = 800;
	if ($image->original_width > 1024)
		$sizes[] = 1024;
	if ($image->original_width > 1600)
		$sizes[] = 1600;
	$sizes[] = 'original';

	//this adds all the ones referenced by the photo page. 
	if (preg_match_all('/(https?:[\w\/\.]+?_)([a-z0-9]+)\.jpg/',$tag,$ms)) {
		foreach($ms[0] as $idx => $zero) {
			$res[$ms[2][$idx]] = "{$ms[1][$idx]}{$ms[2][$idx]}.jpg";
		}
	}
}
//$stylefix ='';
//$style = " style=\"max-width:640px;max-height:640px\"";
//$style = " style=\"max-width:320px;max-height:320px\"";
$style = " style=\"zoom:10%\""; $stylefix = "@10%";

print "<base target=_rsult>";
print "<table border=1 cellpadding=4 cellspacing=0>";
print "<tr><td>Size";
print "<th>Normal Image</th>";
print "<th>Stamped</th>";
print "<th>Download</th>";
foreach ($sizes as $size) {
	print "<tr>";
	print "<td>$size</td>";

	if ($size == 40) {
		//$url = http://staging.t0.geograph.org.uk/tile/tiny.php?id=197573
		$url = "{$CONF['TILE_HOST']}/tile/tiny.php?id={$image->gridimage_id}&hash=".$image->_getAntiLeechHash();
                print "<td><img src=$url></td>";
		print "<td>n/a";
		print "<td>n/a";

	} elseif ($size == 60) {
                print "<td>".$image->getSquareThumbnail(60,60)."</td>";
		print "<td>n/a";
		print "<td>n/a";

	} elseif ($size == 120) {
		print "<td>".$image->getThumbnail(120,120)."</td>";
		print "<td>n/a";
		print "<td>n/a";

	} elseif ($size == 213) {
		print "<td>".$image->getThumbnail(213,160)."</td>";
		print "<td>n/a";
		print "<td>n/a";

	} elseif ($size == 'full') {
		//normal
		//print "<td>$tag</td>";
		preg_match('/src="(https?:[\w\/\.]+?_\w+\.jpg)/',$tag,$m);

		$url = $m[1];
		print "<td><img src=$url $style>$stylefix</td>";

		//stamp
		$url = "{$CONF['TILE_HOST']}/stamp.php?id={$image->gridimage_id}&gravity=SouthEast&hash=".$image->_getAntiLeechHash();
		print "<td><img src=$url $style>$stylefix</td>";

		//download
		$url = "/reuse.php?id={$image->gridimage_id}&amp;download=".$image->_getAntiLeechHash();
		print "<td><img src=$url $style>$stylefix</td>";

	} elseif(is_numeric($size)) {
		//normal
		print "<td>";
		if (!empty($res["{$size}x{$size}"])) {
			//$midurl = $this->getImageFromOriginal($size,$size,true);
			$url = $res["{$size}x{$size}"];
			print "<img src=$url $style>$stylefix";
		} else print "n/a";
		print "</td>";
		if ($size != 640) {
			//stamp
			$url = "{$CONF['TILE_HOST']}/stamp.php?id={$image->gridimage_id}&gravity=SouthEast&hash=".$image->_getAntiLeechHash()."&large=$size";
			print "<td><img src=$url $style>$stylefix</td>";

			//download
			$url = "/reuse.php?id={$image->gridimage_id}&amp;download=".$image->_getAntiLeechHash()."&amp;size={$size}".$postfix;
			print "<td><img src=$url $style>$stylefix</td>";
		} else {
			print "<td>n/a";
			print "<td>n/a";
		}

	} elseif($size == 'original') {
		//normal
		print "<td>";
		if (!empty($res["original"])) {
			$url = $res["original"];
			print "<img src=$url $style>$stylefix";
		} else print "n/a";
		print "</td>";

		//stamp
		$url = "{$CONF['TILE_HOST']}/stamp.php?id={$image->gridimage_id}&gravity=SouthEast&hash=".$image->_getAntiLeechHash()."&large=1";
		print "<td><img src=$url $style>$stylefix</td>";

		//download
		$url = "/reuse.php?id={$image->gridimage_id}&amp;download=".$image->_getAntiLeechHash()."&amp;size=original".$postfix;
		print "<td><img src=$url $style>$stylefix</td>";
	} else {
		print "huh?";
	}

	print "</tr>\n";
}

print "<tr><td>";
print "<td>Served from S3, via CloudFront";
print "<td>Served from Apache/PHP, via CloudFront";
print "<td>Served from Apache/PHP directly";
print "</table>";

if ($image->gridimage_id == 197577) {
	$url = "https://media.geograph.org.uk//files/20f07591c6fcb220ffe637cda29bb3f6/17-original.jpg";
	print "<p>Just for comparsion, old image saved by previous version of ImageMagick, just to see if the stamped version above, still has the same issue";
	print "<img src=$url $style>";
}
