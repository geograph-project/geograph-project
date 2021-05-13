<?

if (empty($_GET['id'])) {
	die("please specify id");
}

require_once('geograph/global.inc.php');

//to set the image rotation
print '<link rel="stylesheet" type="text/css" title="Monitor" href="'.smarty_modifier_revision("/templates/basic/css/basic.css").'" media="screen" />';

init_session();

if ($_SERVER['HTTP_HOST'] != 'staging.geograph.org.uk' && !$USER->hasPerm("admin") && !$USER->hasPerm('forum'))
	die("404");


$id = intval($_GET['id']);

                        $image = new GridImage($id);

if (!$image || !$image->isValid())
	die("invalid image");

  //                      $image->getThumbnail(120,120,true);


$filesystem = new FileSystem();

###################################

if (isset($_POST['fixed'])) {
	$db = GeographDatabaseConnection(false);

	$r = $db->getRow("SELECT * FROM image_report_form WHERE gridimage_id = {$id} and status != 'fixed' ORDER BY report_id DESC");

	if (!empty($_POST['report_id']) && $_POST['report_id'] == $r['report_id']) {
		$status = $_POST['fixed']?'fixed':'deleted';
		$db->Execute("UPDATE image_report_form SET status = '$status' WHERE status NOT in ('fixed','deleted') and gridimage_id = $id"); //need to close all 'old' reports too
		print "Updates = ".$db->Affected_Rows()."\n";
	}
}

###################################

if (!empty($_POST['recreate']) || !empty($_POST['delete']) || !empty($_POST['clearcache'])) {

	$seconds = 10;
	$extra = '';

	if (!empty($GLOBALS['DSN_READ']) && $GLOBALS['DSN'] != $GLOBALS['DSN_READ']) {

	        $db=NewADOConnection($GLOBALS['DSN_READ']);

        	if (!empty($db)) {
                	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	                $row = $db->getRow("SHOW SLAVE STATUS");
        	        if (!empty($row)) { //its empty if we actully connected to master!
                	        if (is_null($row['Seconds_Behind_Master'])) {
                        	        print "<h3>Replication Status: Offline.</h3>";
                                	print "<p>Because replication is offline, some parts of the the site may not be showing recent updates.</p>";

	                        } else {
					$seconds += $row['Seconds_Behind_Master'];
                	        }
			}
                }
        }
}

###################################

if (!empty($_POST['recreate']) || !empty($_POST['delete'])) {
	if (!empty($_POST['recreate'])) {
		$_POST['delete'] = $_POST['recreate'];
	}

	if (preg_match('/(\d+)x(\d+)/',$_POST['delete'],$m)) {

		//form our own path, rather than relying on the provided one!
		$path = $image->_getOriginalpath(false, false, "_{$m[0]}");

		if ($m[2] == 640) {
			//for 640s its very important that they recrated, so emai me so can cehck these!
			$con = "$path\n";
			$con .= print_r($_GET,true);
			$con .= print_r($_POST,true);
			 mail('geograph@barryhunter.co.uk','[Geograph] FIXING 640 '.date('r'),$con);
		}


	        if ($filesystem->unlink($_SERVER['DOCUMENT_ROOT'].$path, true)) {
	                // Task 1 - Delete the file
        	        print "deleted $path\n";

			$seconds +=20;
			$extra .= "&t=".time();

			$_POST['clearcache'] = $m[0]; //may as well clear the cache too!
        	} else {
	                print "DEBUG: $path not found\n";
        	}

		if (!empty($_POST['recreate']) && $m[2] >=640) { //we only use this for the large thumbnail for now, so only need to support >=640
			//for the 640 we need to immidately recreate. because the presence of the 640 is important
			$path = $image->getImageFromOriginal($m[1],$m[2]);
		}
	}
}

###################################

if (!empty($_POST['clearcache'])) {
	if (preg_match('/^(\d+)x(\d+)$/',$_POST['clearcache'],$m)) {

		 // Task 2 - Clear Memcache
                $key = "L~is:{$id}:{$_POST['clearcache']}";
                $mkey = "{$id}:{$_POST['clearcache']}";

                print " delete $key\n";
                $result = $memcache->name_delete('is',$mkey);
                print "Result: $result<br>";

		$maxd = $m[1];
		$db = GeographDatabaseConnection(false);

                // Task 3 - Clear ThumbSize
                $sql = "DELETE FROM gridimage_thumbsize WHERE gridimage_id = {$id} AND maxw = {$maxd}";
                print " $sql\n";
                $db->Execute($sql);
                print "Affected: ".$db->Affected_Rows()."<br>";

		print "Please wait <b>$seconds seconds</b>, then <a href=\"?id={$id}$extra\">return to viewer</a>";
		print " (if when get there, see no change, then can try pressing F5 once)";
		exit;
	}
}

###################################


$_GET['large'] = 1;  //make sure t responsive.
$tag = $image->getFull(true,true); //as used on photo page  - calls getSize etc

$postfix = empty($_GET['v'])?'':("&v=".intval($_GET['v']));

$db = $image->_getDB(true); //can reuse existing connection

$sizes = array(40,60,120,213,'full');
$res = array();

if ($image->moderation_status == 'rejected') {
	unset($sizes[0]); //remove the smallest!
} elseif (!empty($image->square->first)) {
	if ($image->square->first == $image->gridimage_id)
		array_unshift($sizes,80);
} elseif (!empty($image->gridsquare_id)) {
	if ($db->getOne("SELECT first FROM gridsquare WHERE gridsquare_id = {$image->gridsquare_id}") == $image->gridimage_id)
		array_unshift($sizes,80);
}
if ($db->getOne("SELECT gridimage_id FROM gridimage_daily WHERE gridimage_id = ".intval($image->gridimage_id))) {
	$sizes[] = 360;
	$sizes[] = 393;
}

if ($image->original_width>10) {
	$greatest = max($image->original_width,$image->original_height);

	print "This image is listed as having a greatest dimension of $greatest\n";

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

print "<a href=\"/editimage.php?id={$image->gridimage_id}\">Edit Page</a> Moderation Status: {$image->moderation_status}<br>";

print "<form method=post>";
print "<table border=1 cellpadding=4 cellspacing=0 style=background-color:white>";
print "<tr><th>Size";
print "<th>Normal Image</th>";
print "<th>Stamped</th>";
print "<th>Download</th>";
print "<th>Actions</th>";
foreach ($sizes as $size) {
	$html = '';
	print "<tr>";
	print "<th>$size</th>";

	if ($size == 40) {
		//$url = http://staging.t0.geograph.org.uk/tile/tiny.php?id=197573
		$url = "{$CONF['TILE_HOST']}/tile/tiny.php?id={$image->gridimage_id}&hash=".$image->_getAntiLeechHash();
                print "<td><img src=$url></td>";
		print "<th>n/a";
		print "<th>n/a";
		//dont support $html for now
	} elseif ($size == 80) {
		//$url = http://staging.t0.geograph.org.uk/tile/tiny.php?id=197573
		$url = "{$CONF['TILE_HOST']}/tile/tiny.php?id={$image->gridimage_id}&hash=".$image->_getAntiLeechHash()."&large=1";
                print "<td><img src=$url></td>";
		print "<th>n/a";
		print "<th>n/a";
		//dont support $html for now

	} elseif ($size == 60) {
                print "<td>".($html = $image->getSquareThumbnail(60,60))."</td>";
		print "<th>n/a";
		print "<th>n/a";

	} elseif ($size == 120) {
		print "<td>".($html = $image->getThumbnail(120,120))."</td>";
		print "<th>n/a";
		print "<th>n/a";
	} elseif ($size == 213) {
		print "<td>".($html = $image->getThumbnail(213,160))."</td>";
		print "<th>n/a";
		print "<th>n/a";

	} elseif ($size == 393) {
		print "<td>".($html = $image->getFixedThumbnail(393,300))."</td>";
		print "<th>n/a";
		print "<th>n/a";
	} elseif ($size == 360) {
		print "<td>".($html = $image->getFixedThumbnail(360,263))."</td>";
		print "<th>n/a";
		print "<th>n/a";

	} elseif ($size == 'full') {
		//normal
		//print "<td>$tag</td>";
		preg_match('/src="(https?:[\w\/\.]+?_\w+\.jpg)/',$tag,$m);

		$url = $m[1];
		print "<td><img src=$url $style>$stylefix</td>";
		$html = $url;

		if ($size != 640 && $image->moderation_status != 'rejected') {
			//stamp
			$url = "{$CONF['TILE_HOST']}/stamp.php?id={$image->gridimage_id}&gravity=SouthEast&hash=".$image->_getAntiLeechHash();
			//jsut so can see the text in the tiny thumbnail!
			$url .="&pointsize=45";
			print "<td><img src=$url $style>$stylefix</td>";

			//download
			$url = "/reuse.php?id={$image->gridimage_id}&amp;download=".$image->_getAntiLeechHash();
			print "<td><img src=$url $style>$stylefix</td>";
		} else {
			print "<th>n/a";
			print "<th>n/a";
		}

	} elseif(is_numeric($size)) {
		//normal
		if (!empty($res["{$size}x{$size}"])) {
			//$midurl = $this->getImageFromOriginal($size,$size,true);
			$url = $res["{$size}x{$size}"];
			print "<td><img src=$url $style>$stylefix";
			$html = $url;
		} else print "<th>n/a";

		if ($size != 640 && $image->moderation_status != 'rejected') {
			//stamp
			$url = "{$CONF['TILE_HOST']}/stamp.php?id={$image->gridimage_id}&gravity=SouthEast&hash=".$image->_getAntiLeechHash()."&large=$size$postfix";
			//jsut so can see the text in the tiny thumbnail!
			$url .="&pointsize=45";
			print "<td><img src=$url $style>$stylefix</td>";

			//use ths function, rather than getthumb, as want to avoid the magic that done to create the image!
			if (empty($html))
				//dont check_exists, as will be checked explicitly later
				$html = $image->_getOriginalpath(false, true, "_{$size}x{$size}");

			//download
			$url = "/reuse.php?id={$image->gridimage_id}&amp;download=".$image->_getAntiLeechHash()."&amp;size={$size}".$postfix;
			print "<td><img src=$url $style>$stylefix</td>";
		} else {
			print "<th>n/a";
			print "<th>n/a";
		}

	} elseif($size == 'original') {
		//normal
		if (!empty($res["original"])) {
			$url = $res["original"];
			print "<td><img src=$url $style>$stylefix";
			$html = $url;
		} else print "<th>n/a";

		if ($image->moderation_status != 'rejected') {
			//stamp
			$url = "{$CONF['TILE_HOST']}/stamp.php?id={$image->gridimage_id}&gravity=SouthEast&hash=".$image->_getAntiLeechHash()."&large=1$postfix";
			//jsut so can see the text in the tiny thumbnail!
			$url .="&pointsize=45";
			print "<td><img src=$url $style>$stylefix</td>";

			//download
			$url = "/reuse.php?id={$image->gridimage_id}&amp;download=".$image->_getAntiLeechHash()."&amp;size=original".$postfix;
			print "<td><img src=$url $style>$stylefix</td>";
		} else {
                        print "<th>n/a";
                        print "<th>n/a";
                }

	} else {
		print "huh?";
	}
	print "<th>";

	if (preg_match('/(https?:[\w\/\.]+?_\w+\.jpg)/',$html,$m)) {
		$url = $m[1];
		if (strpos(basename($url),'error') !== FALSE) {
			print "<button onclick=reportForm()>Report Error</button>";
		} elseif (is_numeric($size)) {
			$path = parse_url($url, PHP_URL_PATH);
			$stat = $filesystem->stat($_SERVER['DOCUMENT_ROOT'].$path); //use stat, rather than file_exists to SKIP running 'getimagesize optimiation'
			$cache = $db->getOne("SELECT gridimage_id FROM gridimage_thumbsize WHERE gridimage_id = {$image->gridimage_id} and maxw = $size");
			//todo! (will have to find the image filename and check via FS!)
			if (empty($stat)) {
				if (!empty($cache)) {
					if (preg_match('/(\d+)x(\d+)/',$url,$m)) {
						print "<button type=submit name=clearcache value={$m[0]}>Clear Cache</button>";
					}
				} else {
					print "<button onclick=reportForm()>Report Missing</button>";
				}
			} elseif (!empty($stat[10])) { //use time, not filesize
				if ($size == 640) //need to be more careful NOT to delete the 640 image as it wont get recreated automatically
					print "<button type=submit name=recreate value=\"$path\">Recreate Thumbnail</button>";
				else
					print "<button type=submit name=delete value=\"$path\">Delete Thumbnail</button>";
				print "<button onclick=reportForm()>Report Currupted</button>";
			} else {
				print "<button onclick=reportForm()>Report Currupted</button>";
			}
		} else {
			print "<button onclick=reportForm()>Report Missing/Currupted</button>";
		}
	} else {
		print "<button onclick=reportForm()>Report Missing/Currupted</button>";
	}

	print "</tr>\n";
}

print "<tr><th>";
print "<th>Served from S3, via CloudFront";
print "<th>Served from Apache/PHP, via CloudFront";
print "<th>Served from Apache/PHP directly";
print "<th>";
print "</table>";

if ($image->gridimage_id == 197577) {
	$url = "https://media.geograph.org.uk//files/20f07591c6fcb220ffe637cda29bb3f6/17-original.jpg";
	print "<p>Just for comparsion, old image saved by previous version of ImageMagick, just to see if the stamped version above, still has the same issue";
	print "<img src=$url $style>";
}


if (!empty($db) && ($r = $db->getRow("SELECT * FROM image_report_form WHERE gridimage_id = {$image->gridimage_id} ORDER BY report_id DESC"))) {
	print "<input type=hidden name=report_id value={$r['report_id']}>";

	if ($r['status'] == 'fixed') {
		print "<p>This has been previouslly fixed, if still an issue should submit a new report at: <button onclick=reportForm()>Report Missing/Currupted</button>";
	} elseif ($r['status'] == 'new') {
		print "<p>There is already an existing report for this case, if <b>all</b> images above appear, then <button type=submit name=fixed value=1>Mark as Fixed</button>";
	} elseif ($r['status'] == 'escalated') {
		print "<p>There is already an escalated report for this case, if absolutely sure <b>all</b> images above appear, then <button type=submit name=fixed value=1>Mark as Fixed</button>";
	}
	if ($USER->hasPerm("admin"))
		print " <button type=submit name=fixed value=0>Delete Report</button>";
}

?>

</form>

<ul>
<li>In theory, all there should be visible images in all the above boxes, unless <i>explicitly</i> marked 'n/a'</li>
<li>The 'stamped' <i>column</i> should of course have some text overlaid (uses a big font, so it can be seen in small thumbnail)</li>
<li>All images in a given <i>row</i> should of course show the same dimensions</li>
<li>Of course as well as checking all images are visible, should check they 'complete' (eg no gray/black bars), and the same in all images (including the same orientation!)
</ul>

<p>Right click image and 'open in new tab' to view full-size</p>

<p>Note, that the 'Delete Thumbnail', should really only be used if all three columns missing an image, if only some mising, please use the report function for me to check!</p>

<script>
function reportForm() {
	window.open('/stuff/image_report_form.php?id=<? echo $image->gridimage_id; ?>','_blank');
}
</script>
<style>
table th {
	background-color:#eee;
}
</style>
