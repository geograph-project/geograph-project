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

##############################################

if (!empty($_POST['md5sum']) && !empty($_POST['status'])) {
	switch ($_POST['status']) {
		case 'Delt': $status = 'pending'; break;
		case 'Invalid': $status = 'invalid'; break;
		case 'Unknown': $status = 'unknown'; break;
	}
	if ($status)
		$db->Execute($sql = "UPDATE full_dup SET status = '$status',user_id={$USER->user_id} WHERE md5sum=".$db->Quote($_POST['md5sum']));
}

##$_GET['offset'] = $USER->user%59; #59 is just a nice mod with few duplcates
/*
mysql> select user_id mod 59 as m,count(*),user_id,group_concat(realname) from user where rights like '%moderator%' group by m having count(*) > 1;
+------+----------+---------+-----------------------------+
| m    | count(*) | user_id | group_concat(realname)      |
+------+----------+---------+-----------------------------+
|   25 |        2 |    2562 | Andrew Smith,Patrick Mackie |
|   47 |        2 |    1109 | Kate Jewell,Gordon Brown    |
+------+----------+---------+-----------------------------+
*/

$offset = @intval($_GET['offset']);
$status = 'new'; if (!empty($_GET['status']) && ctype_alpha($_GET['status'])) $status = $_GET['status'];

##############################################

if (!empty($_GET['list'])) {
	$rows = $db->getAll($sql = "select status,count(*) cnt,md5sum from full_dup where (user_id > 0 and status != 'delt') or status = 'new' group by status");
	print "<ol>";
	foreach ($rows as $row) {
		$link = ($row['status']=='new')?'?':"?list=1&status={$row['status']}";
		if ($row['cnt'] == 1) $link = "?md5sum={$row['md5sum']}";
		print "<li value={$row['cnt']}><a href=$link>{$row['status']}</a>";
	}
	print "</ol>";


	$rows = $db->getAll($sql = "select full_md5.md5sum,filename from full_md5 inner join full_dup using (md5sum)
		where status = ".$db->Quote($status)." and user_id > 0 limit 100"); //user_id>0 is just to exclude automatic invalid items.
	print "<table>";
	$last = 0;
	foreach ($rows as $row) {
		if ($last != $row['md5sum']) {
			if ($last) print "</tr>";
			print "<tr><th><a href=?md5sum={$row['md5sum']}>{$row['md5sum']}</a></th>";
			$last = $row['md5sum'];
		}
		if (preg_match('/\/(\d{6,7})_/',$row['filename'],$m)) {
			$row['gridimage_id'] = $m[1];
		}
		//todo, this not right!
		print "<td><a href=\"/photo/{$row['gridimage_id']}\"><img src=".str_replace('/geograph_live/public_html','/combined',$row['filename'])." /></a></td>";
	}
	print "</table>";
	exit;
}


##############################################

//todo, could convert to a single query
//select file_id,class,basename,gridimage_id,submitted,moderation_status from full_md5 left join gridimage on (gridimage_id = basename) where md5sum='e228077c601f546aac047769b5f1b893';
//basename on conversion in int on the join, will match.

if (!empty($_GET['md5sum'])) {
	$rows = $db->getAll($sql = "select full_md5.md5sum,basename from full_md5
		where full_md5.md5sum = ".$db->Quote($_GET['md5sum']));

##############################################

} else {
	$rows = $db->getAll($sql = "select full_md5.md5sum,basename from full_md5
		where full_md5.md5sum = (select md5sum from full_dup where status = ".$db->Quote($status)." limit $offset,1)");
}

##############################################

if ($USER->user_id == 3)
	print "$sql<hr>";

if (empty($rows)) {
	die("no more sets. yay!");
}

$md5sum = $rows[0]['md5sum'];

$ids = array();
foreach ($rows as $row) {
	if (preg_match('/^(\d{6,7})_/',$row['basename'],$m)) {
		$ids[intval($m[1])]=$row['basename'];
	}
}

if (count($ids) > 1) {

	$str = implode(',',array_keys($ids));
	//$images = $db->getAll("SELECT gridimage_id,user_id,realname,title,grid_reference,submitted,imagetaken FROM gridimage_search WHERE gridimage_id IN ($str) ORDER BY gridimage_id");
	//we can't use gridimage_serach, as need pending too!

	$crit = "moderation_status != 'rejected'";
	if (!empty($_GET['ignore']))
		$crit = "1";

	$images = $db->getAll($sql = "SELECT gridimage_id,gi.user_id,IF(gi.realname!='',gi.realname,user.realname) AS realname,title,
				moderation_status,submitted,imagetaken,
				grid_reference,nateastings,natnorthings,viewpoint_eastings,viewpoint_northings
				FROM gridimage AS gi INNER JOIN gridsquare AS gs USING(gridsquare_id)
				INNER JOIN user ON(gi.user_id=user.user_id)
				WHERE gridimage_id IN ($str) AND $crit
				ORDER BY gridimage_id");

	if (count($images) > 1) {
		$larger = false;
		print "<table cellspacing=0 cellpadding=6 border=1>";
		foreach ($images[0] as $key => $value) {
			print "<tr>";
			print "<th>$key</th>";
			$stat = array();
			foreach ($images as $row) {
				print "<td>".htmlentities($row[$key])."</td>";
				$stat[$row[$key]]=1;
			}
			if ($key != 'gridimage_id' && $key != 'submitted' && count($stat)!=1) {
				print "<td style=color:red>different!</td>";
			}
			print "</tr>";
			if ($key == 'gridimage_id') {
				print "<tr>";
				print "<th>image</th>";
				foreach ($images as $row) {
					$link = "/editimage.php?id={$row['gridimage_id']}&thumb=0";
					$url = $CONF['STATIC_HOST'].'/'.getGeographFolder($row['gridimage_id']).$ids[$row['gridimage_id']];
					$name = "original 640px";

					if (preg_match('/640px|800x|1024x|original/',$ids[$row['gridimage_id']])) {
						$link = "/more.php?id={$row['gridimage_id']}";
						$name = "thumbnail of larger upload";
						$larger = true;
					}

        	                        print "<td><a href=\"$link\"><img src=$url /></a><br>$name</td>";
	                        }
				print "</tr>";
			}

		}
		print "<tr>";
                print "<th></th>";
                foreach ($images as $idx => $row) {
	                print "<td><a href=\"/photo/{$row['gridimage_id']}\" target=win$idx>Photo Page</a> | <a href=\"/editimage.php?id={$row['gridimage_id']}\" target=win$idx>Edit Page</a>";
			if ($larger)
				print " | <b><a href=\"/more.php?id={$row['gridimage_id']}\" target=win$idx>More Sizes</a></b>";
			print "</td>";
                }
                print "</tr>";

		print "</table>";

if (!empty($_GET['offset']) && !empty($_GET['auto'])) {
	$offset = intval($_GET['offset'])+1;
	print "<meta http-equiv=\"refresh\" content=\"1; url=?offset=$offset&auto=1\"/>";
	exit;
}

		print "<a href=\"deduplicate-single.php?ids=".urlencode($str)."\">Load these images into single tool</a>";

		print "<form method=post>";
		print "<input type=hidden name=md5sum value=\"$md5sum\"/>";
		print "Mark as: <br/>";
		print " <input type=submit name=status value=\"Delt\"> There should only be one of the images left active<br/>";
		print " <input type=submit name=status value=\"Invalid\"> This is not actully a real duplicate (false positive)<br/>";
		print " <input type=submit name=status value=\"Unknown\"> Skip this duplicate but flag it for closer insepection by someone else ";
		print "</form>";

?>
<style>
img {
	max-width:350px;
	max-height:350px;
}
</style>

Note from tuppence:
<blockquote>Just a note to anyone else checking these, there is more to it than just rejecting the second and marking it done.<br/><br/>

Do look at both images in full (either on edit page or photo page) and compare map positions. If they are different both may need checking and it's a good idea to suggest to the submitter that they may have selected the wrong photo for the mislocated one.
</blockquote>
<?


	} else {
		if (empty($_GET['md5sum']))
			print "<meta http-equiv=\"refresh\" content=\"1\"/>";
		print "Found a set [$md5sum] that appears to have already been rejected/delt.";
		if (empty($_GET['md5sum']))
	                print " This page will refresh in 1 second and try another. Please wait...";
		$db->Execute($sql = "UPDATE full_dup SET status = 'delt' WHERE md5sum='$md5sum'");

		if (count($ids) == 2 && count($images) == 1) {
			$to = $images[0]['gridimage_id'];

			$line = array_keys($ids);
                        $from = ($to == $line[0])?$line[1]:$line[0];

                        $db->Execute($sql = "REPLACE INTO gridimage_redirect SET gridimage_id=$from,  destination=$to");
		}

	}

} else {
	//print_r($rows);
	//var_dump($ids);
	//die("invalid set\n $sql $md5sum");

	if (empty($_GET['md5sum']))
		print "<meta http-equiv=\"refresh\" content=\"1\"/>";
	print "Found an invalid set [$md5sum].";
	if (empty($_GET['md5sum']))
                print " This page will refresh in 1 second and try another. Please wait...";
        $db->Execute($sql = "UPDATE full_dup SET status = 'invalid' WHERE md5sum='$md5sum'");
}


function getGeographFolder($gridimage_id) {

       $yz=sprintf("%02d", floor($gridimage_id/1000000));
       $ab=sprintf("%02d", floor(($gridimage_id%1000000)/10000));
       $cd=sprintf("%02d", floor(($gridimage_id%10000)/100));

        if ($yz == '00') {
                $fullpath="photos/$ab/$cd/";
        } else {
                $fullpath="geophotos/$yz/$ab/$cd/";
        }
	return $fullpath;
}

