<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

$smarty = new GeographPage;
$template='tags_tagger.tpl';


$USER->mustHavePerm("basic");

$gid = 0;

if (!empty($_GET['upload_id'])) {

	$gid = crc32($_GET['upload_id'])+4294967296;
	$gid += $USER->user_id * 4294967296;
	$gid = sprintf('%0.0f',$gid);

	$smarty->assign('upload_id',$_GET['upload_id']);
	$smarty->assign('gridimage_id',$gid);
	
	$smarty->assign('is_owner',$is_owner = 1);
	
	$linktable = "gridimage_tag"; //this will be needed for multiple types
	$linkid = "gridimage_id"; //this will be needed for multiple types
	
} elseif (!empty($_REQUEST['gridimage_id'])) {

	$gid = intval($_REQUEST['gridimage_id']);
	
	$image=new GridImage();
	$ok = $image->loadFromId($gid);
		
	if (!$ok) {
		die("invalid image");
	}
	
	if ($image->user_id == $USER->user_id) {
		$smarty->assign('is_owner',$is_owner = 1);
	}
	
	$smarty->assign('gridimage_id',$gid);

	$linktable = "gridimage_tag"; //this will be needed for multiple types
	$linkid = "gridimage_id"; //this will be needed for multiple types

} elseif (!empty($_REQUEST['ids']) && preg_match('/^\d+(,\d+)+$/',$_REQUEST['ids'])) {
	$ids = $_REQUEST['ids'];
	$smarty->assign('ids',$ids);
	
	$ids = explode(',',$ids);
	
	$smarty->assign('is_owner',$is_owner = 1); //we allow them to set public tags just that we verify it at write time!
	
	$linktable = "gridimage_tag"; //this will be needed for multiple types
	$linkid = "gridimage_id"; //this will be needed for multiple types
}

$db = GeographDatabaseConnection(false);

if (!empty($_POST['save']) && !empty($ids)) {
	//add only, no delete!

	$ownimages = $db->getCol("SELECT gridimage_id FROM gridimage_search WHERE gridimage_id IN (".implode(',',$ids).") AND user_id = {$USER->user_id}");
	
	$stats = array();
	$stats['total'] = $stats['tags'] = $stats['public'] = 0;
	
	foreach ($_POST['tag_id'] as $idx => $text) {
		if ($text == '-deleted-') {
			//its a tag created and deleted 

		} else {
			//its a new tag for this link!

			$u = array();
			$u['tag'] = $text;
			$bits = explode(':',$u['tag']);
			if (count($bits) > 1) {
				$u['prefix'] = trim($bits[0]);
				$u['tag'] = $bits[1];
			}
			$u['tag'] = trim(preg_replace('/[ _]+/',' ',$u['tag']));

			if (preg_match('/^id:(\d+)$/',$text,$m)) {
				$tag_id = $m[1];
			} else {
				$tag_id = $db->getOne("SELECT tag_id FROM `tag` WHERE `tag` = ".$db->Quote($u['tag'])." AND `prefix` = ".$db->Quote($u['prefix']));
			}

			if (empty($tag_id)) {
				//need to create it!

				$u['user_id'] = $USER->user_id;

				$db->Execute('INSERT INTO tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

				$tag_id = mysql_insert_id();
			}

			$u = array();

			$u['tag_id'] = $tag_id;
			$u['user_id'] = $USER->user_id;
			
			foreach ($ids as $gid) {
				$u['gridimage_id'] = $gid;

				$u['status'] = 1;
				if (($_POST['mode'][$idx] == 'Public' || $_POST['mode'][$idx] == 2) && in_array($gid,$ownimages)) { 
					$u['status'] = 2;
					$stats['public']++;
				}

				$db->Execute('INSERT IGNORE INTO gridimage_tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
				$stats['total']++;
			}
			$gid = 0;
			$stats['tags']++;
		}
		
	}
	$stats['images'] = count($ids);
	$smarty->assign("message","{$stats['tags']} different tags added to {$stats['images']} images. A total of {$stats['total']} individual tags, of which {$stats['public']} were public.");
	
} elseif (!empty($_POST['save']) && $gid) {

	//tags precheck
	if (!empty($_POST['tag_id'])) {
		$found = 0;
		if ($gid) {
			$tags = $db->getAssoc("SELECT tag.*,gs.status FROM tag INNER JOIN gridimage_tag gs USING (tag_id) WHERE gridimage_id = $gid AND gs.user_id = {$USER->user_id}");
			if ($tags) {
				foreach ($tags as $tid => $row2) {
					if (in_array("id:$tid",$_POST['tag_id'])) {
						$found++;
						
						$idx = array_search("id:$tid",$_POST['tag_id']);
						
						$status = 1;
						if ($_POST['mode'][$idx] == 'Public' || $_POST['mode'][$idx] == 2) { //TODO check allowed to make public!
							$status = 2;
						}
						
						if ($row['status'] != $status) {
							$sql = "UPDATE gridimage_tag SET status = $status WHERE gridimage_id = $gid AND tag_id = $tid AND user_id = {$USER->user_id}";
							$db->Execute($sql);
							
							if ($gid < 4294967296 && empty($cleared)) {
								//clear any caches involving this photo
								$ab=floor($gid/10000);
								$smarty->clear_cache(null, "img$ab|{$gid}");
								$cleared = true;
							}
						}
					}
				}
			}
		}
		if (count($_POST['tag_id']) != $found) {
			$tagsDontMatch = 1;
		}

	}

	if (!empty($tagsDontMatch)) {
		
		//$tags array set by the precheck :)

		if (!empty($tags)) {//see if any need deleting
			$found = 0 ;
			foreach ($tags as $tid => $row2) {
				if (in_array("id:$tid",$_POST['tag_id'])) {
					$found++;
				} else {
					$sql = "DELETE FROM gridimage_tag WHERE gridimage_id = $gid AND tag_id = $tid AND user_id = {$USER->user_id}";
					$db->Execute($sql);
					
					if ($u['status'] == 2 && $gid < 4294967296 && empty($cleared)) {
						//clear any caches involving this photo
						$ab=floor($gid/10000);
						$smarty->clear_cache(null, "img$ab|{$gid}");
						$cleared = true;
					}
				}
			}
		}

		foreach ($_POST['tag_id'] as $idx => $text) {
			if ($text == '-deleted-') {
				//its either a tag created and deleted (so we can ignore) or its been deleted above!
				
			} elseif (preg_match('/^id:(\d+)$/',$text,$m) && isset($tags[$m[1]])) {
				//its matches - nothing to do
				
			} else {
				//its a new tag for this link!
				
				$u = array();
				$u['tag'] = $text;
				$bits = explode(':',$u['tag']);
				if (count($bits) > 1) {
					$u['prefix'] = trim($bits[0]);
					$u['tag'] = $bits[1];
				}
				$u['tag'] = trim(preg_replace('/[ _]+/',' ',$u['tag']));
				
				if (preg_match('/^id:(\d+)$/',$text,$m)) {
					$tag_id = $m[1];
				} else {
					$tag_id = $db->getOne("SELECT tag_id FROM `tag` WHERE `tag` = ".$db->Quote($u['tag'])." AND `prefix` = ".$db->Quote($u['prefix']));
				}

				if (empty($tag_id)) {
					//need to create it!

					$u['user_id'] = $USER->user_id;

					$db->Execute('INSERT INTO tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
					
					$tag_id = mysql_insert_id();
				}

				$u = array();

				$u['gridimage_id'] = $gid;
				$u['tag_id'] = $tag_id;
				$u['user_id'] = $USER->user_id;
				
				if ($_POST['mode'][$idx] == 'Public' || $_POST['mode'][$idx] == 2) { //TODO check allowed to make public!
					$u['status'] = 2;
				}
				
				$db->Execute('INSERT IGNORE INTO gridimage_tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
				
				if ($u['status'] == 2 && $gid < 4294967296 && empty($cleared)) {
					//clear any caches involving this photo
					$ab=floor($gid/10000);
					$smarty->clear_cache(null, "img$ab|{$gid}");
					$cleared = true;
				}
				
			}
		}
	
	}

} elseif ($gid && !empty($_POST['remove'])) {
	
split_timer('tags'); //starts the timer

	$criteria = array();
	$criteria['gridimage_id'] = $gid;
	
	foreach ($_POST['remove'] as $id => $text) {
		
		$criteria['tag_id'] = $id;
		
		$db->Execute('DELETE FROM gridimage_tag WHERE `'.implode('` = ? AND `',array_keys($criteria)).'` = ?',array_values($criteria));
	}

	if ($gid < 4294967296) {
		//clear any caches involving this photo
		$ab=floor($gid/10000);
		$smarty->clear_cache(null, "img$ab|{$gid}");

	}

split_timer('tags','remove',$gid); //logs the wall time


} elseif ($gid && !empty($_POST['add'])) {
	
split_timer('add'); //starts the timer

	$updates = array();
	$updates['gridimage_id'] = $gid;
	$updates['user_id'] = $USER->user_id;
	
	foreach ($_POST['add'] as $id => $text) {
		
		$updates['tag_id'] = $id;
		
		$db->Execute('INSERT IGNORE INTO gridimage_tag SET `'.implode('` = ?, `',array_keys($updates)).'` = ?',array_values($updates));
	}
	
	if ($gid < 4294967296) {
		//clear any caches involving this photo
		$ab=floor($gid/10000);
		$smarty->clear_cache(null, "img$ab|{$gid}");
	}

split_timer('tags','remove',$gid); //logs the wall time

}



if ($gid) {
	
	$used = $db->getAll("SELECT *,gs.status,(gs.user_id = {$USER->user_id}) AS is_owner FROM gridimage_tag gs INNER JOIN tag s USING (tag_id) WHERE gridimage_id = $gid AND (gs.user_id = {$USER->user_id} OR gs.status = 2) AND gs.status > 0 ORDER BY gs.created");

	$smarty->assign_by_ref('used',$used);
	
	$db2 = GeographDatabaseConnection(true);	
	$suggestions = $db2->getAll("(SELECT label AS tag,'cluster' AS `prefix` FROM gridimage_group WHERE gridimage_id = $gid ORDER BY score DESC,sort_order) 
	UNION (SELECT result AS tag,'term' AS `prefix` FROM at_home_result WHERE gridimage_id = $gid ORDER BY at_home_result_id)
	UNION (SELECT result AS tag,'term' AS `prefix` FROM at_home_result_archive WHERE gridimage_id = $gid ORDER BY at_home_result_id)
	UNION (SELECT tag,'wiki' AS `prefix` FROM gridimage_wiki WHERE gridimage_id = $gid ORDER BY seq)");
	if (count($used) && count($suggestions)) {
		$list = array();
		foreach ($used as $row) $list[$row['tag']]=1;
		
		foreach ($suggestions as $idx => $row) {
			if (isset($list[$row['tag']]))
				unset($suggestions[$idx]);
		}
	}
	$smarty->assign_by_ref('suggestions',$suggestions);
	
} elseif ($ids) {
	//TODO -- look though the images, and compile popular terns/clusters...
}
	
	if (!empty($is_owner) && empty($_GET['v'])) {

		if (empty($db2))
			$db2 = GeographDatabaseConnection(true);
		
		$list = $db2->getAssoc("SELECT `top`,`grouping` FROM category_primary ORDER BY `sort_order`");
		foreach ($list as $top => $grouping) {

			$tree[$grouping][] = $top;
		}
		$smarty->assign_by_ref('tree',$tree);
	}
	
	$buckets = array('Closeup',
	'Arty',
	'Informative',
	'Aerial',
	'Telephoto',
	'Landscape',
	'Wideangle',
	'Indoor',
	'Historic',
	'People',
	'Temporary',
	'Life',
	'Subterranean', 
	'Transport');
	$smarty->assign_by_ref('buckets',$buckets);

if (!empty($_GET['title']) || !empty($_GET['comment'])) {
        $string = $_GET['title'].' '.$_GET['comment'];

        $smarty->assign('topicstring',$string);

} 

if ($db2 && $USER->user_id) {
		
	$recent = $db2->getAll("SELECT tag,prefix,MAX(gt.created) AS last_used FROM gridimage_tag gt INNER JOIN tag t USING (tag_id) WHERE gt.user_id = {$USER->user_id} AND prefix != 'top' GROUP BY gt.tag_id ORDER BY last_used DESC LIMIT 20");
	if (count($used) && count($recent)) {
		$list = array();
		foreach ($used as $row) $list[$row['tag']]=1;
		
		foreach ($recent as $idx => $row) {
			if (isset($list[$row['tag']]))
				unset($recent[$idx]);
		}
	}
	$smarty->assign_by_ref('recent',$recent);	
}





if (!empty($CONF['sphinx_host'])) {
	$smarty->assign('sphinx',1);
}
if (!empty($_GET['create'])) {
	$smarty->assign('create',1);
}






$smarty->display($template);

