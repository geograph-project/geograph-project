<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
require_once('geograph/flickr.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);



	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3> Contacting Flickr...</h3>";
	flush();
	
	$flickr = new Flickr($CONF['flickr_api_key']);
	$owners = array();

print "<h3>Checking Geograph Group Pool</h3>";
$photos = $flickr->request('flickr.groups.pools.getPhotos', array('group_id' => '52524535@N00')); //52524535@N00 = geograph group
if ($photos->photos)
	foreach ($photos->photos->photo as $photo) {
		_precess_photo($photo);
	}
//todo - repeat if more pages !!

print "<h3>Checking tags geograph,gridref</h3>";
$photos = $flickr->request('flickr.photos.search', array('tags' => 'geograph,gridref')); 
if ($photos->photos)
	foreach ($photos->photos->photo as $photo) {
		_precess_photo($photo);
	}
//todo - repeat if more pages !!


//todo - check geobloggers.com for lat/long coded images!
#http://www.geobloggers.com/feed.cfm?mode=mapdata&lat=54.33700&lon=-3.64115&username=-23&range=4.30232



function _precess_photo(&$photo) {
	global $db,$flickr,$owners;
	print "ID = {$photo['id']}<BR>";

	$checksql = '';
	$insertsql = '';

	foreach($photo->attributes() as $a => $b) {
		if ($a == 'ownername') {
			$ownername = $b;
		} else {
			$b = $db->Quote($b);
			$checksql .= " OR `$a` != $b";
			$insertsql .= " , `$a` = $b";
			if ($a == 'owner') {
				$owner = $b;
			} 
		}
	}
	$id = $photo['id'];
	
	$check = $db->GetRow("SELECT id,(upd_timestamp < date_sub(now(), interval 7 day)) as isold FROM flickr_photos WHERE id = $id");
	
	if ($check['id']) {
		if ($check['isold']) {
			$do = 3;
		} else {
			$check = $db->GetRow("SELECT id FROM flickr_photos WHERE id = $id AND (0 $checksql)");
			if ($check) {
				$do = 2;
			} else {
				$do = 0;
			}
		}
	} else {
		$do = 1;
	}
	
	if ($do) {
		print "Fetching tags for $id (
		do = $do)<BR>";
		
		if ($do > 1) {
			$db->Execute("DELETE FROM flickr_tags WHERE photo_id = $id");
		}

		$tags = $flickr->request('flickr.tags.getListPhoto', array('photo_id' => $id)); 
		
		$gid = 0;
		$isgeograph = 0;
		$grid_reference = '';
		foreach ($tags->photo->tags->tag as $tag) {
			$inserttagsql = '';
			$authorname = '';
			foreach($tag->attributes() as $a => $b) {
				if ($a == 'authorname') {
					$authorname = $b;
				} else {
					$b = $db->Quote($b);
					$inserttagsql .= " , `$a` = $b";
					if ($a == 'author') {
						$author = $b;
					} 
				}
			}
			$db->Execute("REPLACE INTO flickr_tags SET photo_id = $id,tag_tidy = ".$db->Quote($tag)." $inserttagsql");
			
			if ($authorname) 
				$owners[$author] = $authorname;
			
			if (preg_match("/^([a-zA-Z]{1,3})(\d+)$/","$tag")) {
				$square=new GridSquare;
				$grid_ok=$square->setByFullGridRef($tag);
				if ($grid_ok) {
					if (strlen($tag) > strlen($grid_reference)) {
						$grid_reference = $tag;
						$gid = $square->gridsquare_id;
						if ($square->nateastings) {//ie was a 6fig gr!
							$insertsql .= " , `nateastings` = ".$square->nateastings;
							$insertsql .= " , `natnorthings` = ".$square->natnorthings;
						}
					}
				}
			} else if (strcasecmp($tag, 'geograph') == 0) {
			#	$isgeograph = 1;
			}
			print "Found $tag = $grid_reference = $gid<BR>";
		}
		
		$db->Execute("REPLACE INTO flickr_photos SET gridsquare_id = $gid, isgeograph = '$isgeograph' $insertsql");
		if ($ownername) 
			$owners[$owner] = $ownername;

	}
}

foreach ($owners as $owner => $ownername) {
	$db->Execute("REPLACE INTO flickr_users SET owner = $owner, ownername=".$db->Quote($ownername));
}
	
	$smarty->display('_std_end.tpl');
	exit;
	


	
?>
