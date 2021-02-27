<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6368 2010-02-13 19:45:59Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');
require_once('geograph/imagelist.class.php');
init_session();


$smarty = new GeographPage;

$USER->mustHavePerm("basic");

customGZipHandlerStart();


$smarty->display("_std_begin.tpl");

print "<h2>Geograph Image Issue Report Form</h2>";

##############################################################

if (!empty($_POST)) {

/*
 CREATE TABLE `image_report_form` (
  `report_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_url` varchar(255) NOT NULL,
  `gridimage_id` int(10) unsigned NOT NULL,
  `affected` varchar(1024) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `notes` text NOT NULL,
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `status` ENUM(),
  PRIMARY KEY (`report_id`),
  KEY `gridimage_id` (`gridimage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
*/


	$db = GeographDatabaseConnection(false);

	$updates = array();

function check_path($server,$path, $row) {
        $cmd = "ls -l {$_SERVER['DOCUMENT_ROOT']}$path";
        print "$cmd\n";
        passthru($cmd);

	$url = $server.$path;
        print "$url\n";
}

##############################################################

	if (!empty($_POST['bulk'])) {
		$lines = explode("\n",str_replace("\r",'',$_POST['bulk']));
print "<pre>";
		$count = 0;
		foreach ($lines as $line) {
			if (empty($line))
				continue;
			$bits = explode("\t",$line);
			if ($bits[0] == "Timestamp" || empty($bits[1]))
				continue;


//get dates like "19/08/2018 09:32:21" which Google DOcs provided in UK format, but strtotime is US absed?
$bits[0] = preg_replace('/(\d+)\/(\d+)\/(\d{4})/','$2/$1/$3', $bits[0]);

			$updates["created"] = date('Y-m-d H:i:s',strtotime($bits[0]));
			$updates["image_url"] = $bits[1];

			$str = preg_replace('/[\w:\/\.]*\/(\d{6,7})_\w{8}(_\w+)?\.jpg/','$1',$bits[1]); //replace any thumbnail urls with just the id.
        		$updates["gridimage_id"] = trim(preg_replace('/[^\d]+/',' ',$str));

			$updates["affected"] = $bits[2];
			$updates["page_url"] = $bits[3];

print_r($updates);
			$db->Execute('INSERT INTO image_report_form SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			$count += $db->Affected_Rows();
		}
		print "Count = $count;";
print "</pre>";

##############################################################

	} elseif (!empty($_POST['image_url'])) {
		$updates["image_url"] = $_POST['image_url'];

		$str = preg_replace('/[\w:\/\.]*\/(\d{6,7})_\w{8}(_\w+)?\.jpg/','$1',$_POST['image_url']); //replace any thumbnail urls with just the id.
        	$updates["gridimage_id"] = trim(preg_replace('/[^\d]+/',' ',$str));

		if (!empty($_POST['affected']))
			$updates["affected"] = implode(', ',$_POST['affected']);

		if (!empty($_POST['page_url']))
			$updates["page_url"] = $_POST['page_url'];
                $updates["user_id"] = intval($USER->user_id);

		$db->Execute('INSERT INTO image_report_form SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		print "<p>Thank you, report recorded for {$updates["gridimage_id"]}</p>";

		////////////////////////////////////////////////////////

		ob_start();
		print "https://{$_SERVER['HTTP_HOST']}/stuff/image-file-viewer.php?id={$updates['gridimage_id']}\n";
                debug_print_backtrace();
                print "\n\nHost: ".`hostname`."\n\n";

                print_r($updates);

		if (false && !empty($updates["gridimage_id"])) {
			$image = new Gridimage($updates['gridimage_id']);
			$path = $image->_getFullpath(true);
		        print "$path\n";
			check_path($CONF['STATIC_HOST'],$path,$updates);

		        $image->_getFullSize(); //just sets orginal_width

			if ($image->original_width) {
	                        $path = $image->getLargestPhotoPath(false); //true, gets the URL
			        print "$path\n";
				check_path($CONF['STATIC_HOST'],$path,$updates);
			}

		        if (strpos($updates['affected'],'120px')!== FALSE) {
		                $thumbw=120; $thumbh=120;

		                $resized = $image->getThumbnail($thumbw,$thumbh, 2);
		                print_r($resized);
				check_path($resized['server'],$resized['url'],$updates);
		        }
		        if (strpos($updates['affected'],'213px')!== FALSE) {
		                $thumbw=213; $thumbh=160;

		                $resized = $image->getThumbnail($thumbw,$thumbh, 2);
		                print_r($resized);
				check_path($resized['server'],$resized['url'],$updates);
		        }

		}
		$con = ob_get_clean();

		if ($email = $db->getOne("SELECT email FROM user WHERE user_id = 12192 and rights LIKE '%basic%'")) {
	                mail_wrapper($email,'[Geograph] Failed Image Report '.date('r'),$con);
		} else {
	                debug_message('[Geograph] Failed Image Report '.date('r'),$con);
		}

		////////////////////////////////////////////////////////
	}

##############################################################

} elseif (!empty($_GET['results'])) {
	$db = GeographDatabaseConnection(true);
	print "<p>Recently fixed images:<br>";


	function outputthumbs($rows,$thumbw,$thumbh) {
		foreach ($rows as $id => $row) {
			$image = new Gridimage($id);
			$title = htmlentities2("{$image->title} by {$image->realname}");

			print "<div style=\"float:left;width:".($thumbw+10)."px;height:".($thumbh+30)."px;border:1px solid gray; margin:2px;\">";
			print "<a href=\"/photo/$id\" title=\"$title\">";
			if ($thumbw != 200) {
				print $image->getThumbnail($thumbw,$thumbh);
			} else {
				$image->_getFullSize(); //just sets orginal_width
                                $url = $image->getLargestPhotoPath(true); //true, gets the URL
				print "<img src=\"$url\" style=\"max-width:200px;max-height:200px\">";
			}
			print "</a><br>";
			print "Reported: {$row['created']}";
			print "</div>";
		}
		print "<br style=clear:both>";
	}

	$rows = $db->getAssoc("select gridimage_id,created,notes from image_report_form where affected like '%120px%' and notes like '%_120x120.jpg:okurl%' order by report_id desc limit 10");
	 $thumbw=120; $thumbh=120;
	outputthumbs($rows,$thumbw,$thumbh);

	$rows = $db->getAssoc("select gridimage_id,created,notes from image_report_form where affected like '%213px%' and notes like '%_213x160.jpg:okurl%' order by report_id desc limit 10");
	 $thumbw=213; $thumbh=160;
	outputthumbs($rows,$thumbw,$thumbh);

	$rows = $db->getAssoc("select gridimage_id,created,notes from image_report_form where affected like '%Photo Page%' and notes like '%.jpg:okurl%' order by report_id desc limit 10");
	 $thumbw=200; $thumbh=200;
	outputthumbs($rows,$thumbw,$thumbh);


	print "If dont see all thumbnails above, press F5 to try reloading<hr>";

##############################################################

} elseif (!empty($_GET['list'])) {
	$db = GeographDatabaseConnection(false);

	$status = ($USER->hasPerm("admin") && empty($_GET['new']))?'escalated':'new';

	if ($r = $db->getAll("SELECT * FROM image_report_form WHERE status = '$status' AND gridimage_id > 0 ORDER BY report_id DESC")) {
		print "<ul>";
		foreach ($r as $row) {
			print "<li>{$row['gridimage_id']}, {$row['status']}, {$row['created']} <a href=/stuff/image-file-viewer.php?id={$row['gridimage_id']}>View Images</a> - {$row['affected']}</li>";
		}
		print "</ul>";
	} else {
		print "none";
	}
	exit;
}

##############################################################

if (!empty($_GET['id'])) {
	if (empty($db))
		$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$gridimage_id = intval($_GET['id']);
	$r = $db->getRow("SELECT * FROM image_report_form WHERE gridimage_id = {$gridimage_id} and status in ('new','escalated')");

	if (!empty($_POST['report_id']) && $_POST['report_id'] == $r['report_id']) {

		$db->Execute("UPDATE image_report_form SET status = 'escalated' WHERE report_id = {$r['report_id']}");

		$con = "https://{$_SERVER['HTTP_HOST']}/stuff/image-file-viewer.php?id={$r['gridimage_id']}\n\n";
                $con .= "Host: ".`hostname`."\n\n";
                $con .= print_r($r,true);

		mail('geograph@barryhunter.co.uk','[Geograph] Failed Image Report '.date('r'),$con);

	} else if (!empty($r['status']) && empty($_POST)) { //dont show this when submitting a report either!

		print "<form method=post>";
		print "<input type=hidden name=report_id value={$r['report_id']}>";
		print "<div class=interestBox>There is already an existing report for this image, there is no need to submit another report";
		$seconds = time() - strtotime($r['created']);
		$hours = ceil($seconds/60/60);
		if ($hours > 72 || $USER->hasPerm('forum')) {
			print ", however if still need fixing, <button name=submit value=esc>Bring to attention of developer</button>";
		}
		print "</div>";
		print "</form>";
	}
}

##############################################################

?>

<p>Please let us know here if unable to view an image on geograph. This includes if having issues with fresh submissions.  </p>

<p>If have multiple images to report, will have to submit multiple times</p>

<form method=post>
<table border=0 cellspacing=0 cellpadding=5>
<?

if (!empty($_GET['bulk'])) {
	print "<textarea name=bulk cols=80 rows=30></textarea>";
}

?>
<tr>
	<th>URL of the affected Image</th>
	<td><input type=text name=image_url placeholder="enter image-id here" maxlength="128" size="60" required <? if (!empty($_GET['id'])) { echo ' value='.intval($_GET['id']); } ?>></td>
	<td><small>can be link of the photo page, probably something like "http://www.geograph.org.uk/photo/99999", a direct link to the .jpg file, - or at least just the Image ID. 
</tr>
<tr>
	<th>What's affected?</th>
	<td><small>Tick any that you know are affected, tick as many as needed
</tr>
<?
$list = "
Tiny Thumbnail (on Map Mosaic)
Small Thumbnail (120px)
Medium Thumbnail (213px)
Large Thumbnail (as seen on POTD/homepage)
Image Shown on Photo Page
The Photo page itself not functional
Preview shown on More SIzes
Largest Available via More Sizes
larger Mid-Resolution Downloads (from More Sizes)
The Stamped Image
";

foreach (explode("\n",trim($list)) as $idx => $value) { ?>
	<tr>
        	<th></th>
	        <td colspan=2><input type=checkbox name="affected[]" value="<? echo $value; ?>" id="c<? echo $idx; ?>">
		<label for="c<? echo $idx; ?>"><? echo $value; ?>
<? } ?>
<tr>
	<th>URL of page where see this</th>
	<td><input type=text name=page_url placeholder="https://www.geograph.org.uk/...." maxlength="128" size="60"> (optional)</td>
	<td><small>Page where seeing the missing image - eg forum thread, article, search result, etc. If its not the Photo Page itself.
</tr>
<tr>
	<td>
	<td><input type=submit value="submit"></td>
</tr>
</table>
</form>
<?




$smarty->display("_std_end.tpl");

