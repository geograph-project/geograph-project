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

##############################################

if (empty($_GET['ids'])) {
	print "<form method=get>";
	print "<textarea name=ids rows=2 cols=40 placeholder=\"Enter IDS here\"></textarea>";
	print "<input type=submit>";
	print "</form>";
	exit;
}

##############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


        $str = preg_replace('/[\w:\/\.]*\/(\d{6,7})_\w{8}(_\w+)?\.jpg/','$1',$_GET['ids']); //replace any thumbnail urls with just the id.
        $str = trim(preg_replace('/[^\d]+/',' ',$str));
        $done = 0;
        $ids = explode(' ',$str);

##############################################

if (!empty($_POST)) {

	if (!empty($_POST['tags']))
		foreach ($_POST['tags'] as $gridimage_id => $dummy) {
			$other = null;
			foreach ($ids as $id)
				if ($id != $gridimage_id)
					$other = $id;
			if (empty($id))
				die("unable to find other for ".intval($gridimage_id));

			$columns = $db->getAssoc("DESCRIBE gridimage_tag");
			$values = array();
			foreach ($columns as $column => $data) {
				if ($column == 'gridimage_id')
					$values[] = intval($gridimage_id)." AS $column";
				else
					$values[] = $column;
			}
			$sql = "INSERT INTO gridimage_tag SELECT ".implode(', ',$values)." FROM gridimage_tag WHERE gridimage_id = $other";

			print "$sql; ";
			$db->Execute($sql);
			print "Tags Copied = ".$db->Affected_Rows()."<hr>";
		}

##############################################

	if (!empty($_POST['redirect']))
		foreach ($_POST['redirect'] as $gridimage_id => $dummy) {
			$gridimage_id = intval($gridimage_id);
			$other = null;
			foreach ($ids as $id)
				if ($id != $gridimage_id)
					$other = $id;
			if (empty($id))
				die("unable to find other for ".intval($gridimage_id));

			$sql = "REPLACE INTO gridimage_redirect SET gridimage_id=$other,  destination=$gridimage_id";
			print "$sql; ";
			$db->Execute($sql);
			print "Rows Updated = ".$db->Affected_Rows()."<hr>";
		}

##############################################

	if (!empty($_POST['original']))
		foreach ($_POST['original'] as $gridimage_id => $dummy) {
			$gridimage_id = intval($gridimage_id);
			$other = null;
			foreach ($ids as $id)
				if ($id != $gridimage_id)
					$other = $id;
			if (empty($id))
				die("unable to find other for ".intval($gridimage_id));

			$src = new GridImage($other);
			$dst = new GridImage($gridimage_id);

			//save the original
			$src->originalUrl =   $src->_getOriginalpath(true,false,'_original');
			if (basename($src->originalUrl) != "error.jpg") {
				$dst->storeImage($_SERVER['DOCUMENT_ROOT'].$src->originalUrl,false,'_original');
				print "Copying: {$src->originalUrl}<hr>";
			}

			// the preview image
			$src->previewUrl =    $src->_getOriginalpath(true,false,'_640x640');
			if (basename($src->previewUrl) != "error.jpg") {
				$dst->storeImage($_SERVER['DOCUMENT_ROOT'].$src->previewUrl,false,'_640x640');
				print "Copying: {$src->previewUrl}<hr>";
			}

			//clear memcache
                        $mkey = "{$gridimage_id}:F";
                        $memcache->name_delete('is',$mkey);

                        //delete the cache
                        $db->Execute("DELETE FROM gridimage_size WHERE gridimage_id = $gridimage_id"); // could populate this now, but easier to delete, and let it autorecreate
			print "Size Rows Updated = ".$db->Affected_Rows()."<hr>";
		}

	print "<a href=\"?\">Go again!</a>";
	exit;
}

##############################################


if (count($ids) == 2) {

	$str = implode(',',$ids);
	//$images = $db->getAll("SELECT gridimage_id,user_id,realname,title,grid_reference,submitted,imagetaken FROM gridimage_search WHERE gridimage_id IN ($str) ORDER BY gridimage_id");
	//we can't use gridimage_serach, as need pending too!

	//$crit = "moderation_status != 'rejected'";
	//if (!empty($_GET['ignore']))
		$crit = "1";

	$images = $db->getAll($sql = "SELECT gridimage_id,
				width,height,original_width,original_height, original_diff,
				gi.user_id,IF(gi.realname!='',gi.realname,user.realname) AS realname,title,imageclass,'' as tags,
				moderation_status,submitted,imagetaken,
				grid_reference,nateastings,natnorthings,viewpoint_eastings,viewpoint_northings
				FROM gridimage AS gi INNER JOIN gridsquare AS gs USING(gridsquare_id)
				INNER JOIN user ON(gi.user_id=user.user_id)
				LEFT JOIN gridimage_size using (gridimage_id)
				WHERE gridimage_id IN ($str) AND $crit
				ORDER BY gridimage_id");

	if (count($images) > 1) {
		$larger = false;

		foreach ($images as $idx => $row) {
			$images[$idx]['tags'] = $db->getOne("SELECT GROUP_CONCAT(tag) FROM tag_public WHERE gridimage_id = {$row['gridimage_id']}");
		}

		print "<form method=post>";

		print "<table cellspacing=0 cellpadding=6 border=1>";
		foreach ($images[0] as $key => $value) {
			print "<tr>";
			print "<th>$key</th>";
			$stat = array();
			foreach ($images as $row) {
				if (empty($row[$key]) && $key == 'tags') {
					print "<td><label><input type=checkbox name=\"tags[{$row['gridimage_id']}]\">Copy Tags from Other</label></td>";

				} else {
					print "<td>".htmlentities($row[$key])."</td>";
				}
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
					$image = new GridImage();
		                        $image->fastInit($row);

					$link = "/editimage.php?id={$row['gridimage_id']}&thumb=0";
					$url = $image->_getFullpath(false,true);
					$name = "original 640px";

        	                        print "<td><a href=\"$link\"><img src=$url /></a><br>$name</td>";
	                        }
				print "</tr>";

			} elseif ($key == 'original_height') {
				print "<tr>";
                                print "<th>original</th>";
				foreach ($images as $row) {
					if ($row['original_height'] > 0) {
	                                        $image = new GridImage();
        	                                $image->fastInit($row);

						if ($row['original_diff'] == 'yes')
							$url = $image->_getOriginalpath(false,true,$name = '_640x640');
						else
							$url = $image->_getOriginalpath(false,true,$name = '_original'); //todo, could find the smallest thumbnaul!

	        	                        print "<td><a href=\"$url\"><img src=$url /></a><br>$name</td>";
					} else {
						print "<td><label><input type=checkbox name=\"original[{$row['gridimage_id']}]\">Copy Image from Other</label></td>";
					}
				}
			}

		}
		print "<tr>";
                print "<th></th>";
		foreach ($images as $idx => $row) {
			print "<td><label><input type=checkbox name=\"redirect[{$row['gridimage_id']}]\">Redirect other image to this one</label></td>";
		}


		print "<tr>";
                print "<th></th>";
		$message = "Duplicate Image";
                foreach ($images as $idx => $row) {
	                print "<td><a href=\"/photo/{$row['gridimage_id']}\" target=win$idx>Photo Page</a> | <a href=\"/editimage.php?id={$row['gridimage_id']}\" target=win$idx>Edit Page</a>";
			if ($larger)
				print " | <b><a href=\"/more.php?id={$row['gridimage_id']}\" target=win$idx>More Sizes</a></b>";

			print " <input type=button value=\"Reject\" onclick='moderateImage({$row['gridimage_id']},\"rejected\",".json_encode($message).")'>";
			print "</td>";
			//setting it for the NEXT image!
			$message = "Duplicate of [[[{$row['gridimage_id']}]]]";
                }
                print "</tr>";

		print "</table>";


?>
	<input type=submit value="copy ticked items">
</form>


<style>
img {
	max-width:350px;
	max-height:350px;
}
label {
	background-color:yellow;
}
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>

function moderateImage(gridimage_id, status, message)
{
        var url="/admin/moderation.php?gridimage_id="+gridimage_id+"&status="+status;

	$('input[type=checkbox]').each(function() {
		if (m = this.name.match(/\[(\d+)\]/)) {
			if (m[1] != gridimage_id)
				this.checked = true;
		}
	});

        if (status == 'rejected') {
                comment = prompt("Please leave a comment to explain the reason for rejecting this image.",message);
                if (comment.length > 1) {
                        url=url+"&comment="+escape(comment);
                } else {
                        return false;
                }
        }
	$.ajax({
		url: url,
		success: function(data) {
			alert("The Server Said: "+data);
		}
	});


}
</script>


<?


	}

}


