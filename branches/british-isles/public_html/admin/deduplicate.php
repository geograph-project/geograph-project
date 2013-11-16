<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
init_session();

$USER->mustHavePerm("moderator");

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$db = GeographDatabaseConnection(false);

$filedb=NewADOConnection($CONF['filesystem_dsn']);

/*

#first create the intermediate table - to avoid long locks on the main file table, but also so can get just rows we want AND be able to add a md5sum index..

	#this NEEDS the md5sum and filename columns, file_id just in case.
	CREATE TABLE thumb_md5 SELECT file_id,md5sum,filename FROM file WHERE class = 'thumb.jpg'; #AND md5sum!='';

	ALTER TABLE thumb_md5 ADD index(md5sum);

#then inital creation:

	CREATE TABLE thumb_dup SELECT md5sum,COUNT(*) cnt FROM thumb_md5 GROUP BY md5sum HAVING cnt > 1 ORDER BY NULL;

	ALTER TABLE thumb_dup ADD `status` enum('new','pending','delt','invalid','deleted','unknown') NOT NULL DEFAULT 'new',
	 ADD `user_id` int(10) unsigned NOT NULL,
	 ADD `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP;

	ALTER TABLE thumb_dup ADD UNIQUE(md5sum);

#or updating later:

	#the ignore means any 'duplicate' hashes are silently dropped. might like to change that to use
		on duplicate key SET status = IF(file.file_created>dup.updated,'new',dup.status)
	#.. so that and duplicates with NEW files will also reset the dup entry.
	INSERT IGNORE thumb_dup INTO SELECT md5sum,COUNT(*) cnt,'new',0,NOW() FROM thumb_md5 GROUP BY md5sum HAVING cnt > 1 ORDER BY NULL;
*/


if (!empty($_POST['md5sum']) && !empty($_POST['status'])) {
	switch ($_POST['status']) {
		case 'Delt': $status = 'pending'; break;
		case 'Invalid': $status = 'invalid'; break;
		case 'Unknown': $status = 'unknown'; break;
	}
	if ($status)
		$filedb->Execute($sql = "UPDATE thumb_dup SET status = '$status',user_id={$USER->user_id} WHERE md5sum=".$db->Quote($_POST['md5sum']));
}


if (!empty($_GET['md5sum'])) {
	$rows = $filedb->getAll("select * from thumb_md5 where md5sum = ".$db->Quote($_GET['md5sum']));
} else {
	$rows = $filedb->getAll("select * from thumb_md5 where md5sum = (select md5sum from thumb_dup where status = 'new' limit 1)");
}


if (empty($rows)) {
	die("no more sets. yay!");
}

$md5sum = $rows[0]['md5sum'];

foreach ($rows as $row) {
	if (preg_match('/\/(\d{6,7})_/',$row['filename'],$m)) {
		$ids[intval($m[1])]=$row['filename'];
	}
}
if (count($ids) > 1) {

	$str = implode(',',array_keys($ids));
	$images = $db->getAll("SELECT gridimage_id,user_id,realname,title,grid_reference,submitted,imagetaken FROM gridimage_search WHERE gridimage_id IN ($str) ORDER BY gridimage_id");

	if (count($images) > 1) {

		print "<table cellspacing=0 cellpadding=6 border=1>";
		foreach ($images[0] as $key => $value) {
			print "<tr>";
			print "<th>$key</th>";
			foreach ($images as $row) {
				print "<td>".htmlentities($row[$key])."</td>";
			}
			print "</tr>";
			if ($key == 'gridimage_id') {
				print "<tr>";
				print "<th>image</th>";
				foreach ($images as $row) {
        	                        print "<td><a href=\"/photo/{$row['gridimage_id']}\"><img src=".str_replace('/geograph_live/public_html','/combined',$ids[$row['gridimage_id']])." /></a></td>";
	                        }
				print "</tr>";
			}

		}
		print "<tr>";
                print "<th></th>";
                foreach ($images as $idx => $row) {
                       print "<td><a href=\"/photo/{$row['gridimage_id']}\" target=win$idx>Photo Page</a> | <a href=\"/editimage.php?id={$row['gridimage_id']}\" target=win$idx>Edit Page</a></td>";
                }
                print "</tr>";

		print "</table>";

		print "<form method=post>";
		print "<input type=hidden name=md5sum value=\"$md5sum\"/>";
		print "Mark as: <br/>";
		print " <input type=submit name=status value=\"Delt\"> There should only be one of the images left active<br/>";
		print " <input type=submit name=status value=\"Invalid\"> This is not actully a real duplicate (false positive)<br/>";
		print " <input type=submit name=status value=\"Unknown\"> Skip this duplicate but flag it for closer insepection by someone else ";
		print "</form>";

?>
Note from tuppence:
<blockquote>Just a note to anyone else checking these, there is more to it than just rejecting the second and marking it done.<br/><br/>

Do look at both images in full (either on edit page or photo page) and compare map positions. If they are different both may need checking and it's a good idea to suggest to the submitter that they may have selected the wrong photo for the mislocated one.
</blockquote>
<?


	} else {
		print "<meta http-equiv=\"refresh\" content=\"1\"/>";
		print "Found a set [$md5sum] that appears to have already been rejected/delt. This page will refresh in 1 second and try another. Please wait...";
		$filedb->Execute($sql = "UPDATE thumb_dup SET status = 'delt' WHERE md5sum='$md5sum'");

		if (count($ids) == 2 && count($images) == 1) {
			$to = $images[0]['gridimage_id'];

			$line = array_keys($ids);
                        $from = ($to == $line[0])?$line[1]:$line[0];

                        $db->Execute($sql = "REPLACE INTO gridimage_redirect SET gridimage_id=$from,  destination=$to");
		}

	}
} else {
	print "<meta http-equiv=\"refresh\" content=\"1\"/>";
	print "Found an invalid set  [$md5sum]. This page will refresh in 1 second and try another. Please wait...";
        $filedb->Execute($sql = "UPDATE thumb_dup SET status = 'invalid' WHERE md5sum='$md5sum'");
}


