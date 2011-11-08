<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
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
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;


$template = 'tags_description.tpl';


	$db = GeographDatabaseConnection(false);
		
	$where = '';
	$andwhere = '';

	if (isset($_GET['prefix'])) {

		$andwhere = " AND prefix = ".$db->Quote($_GET['prefix']);
		$smarty->assign('theprefix', $_GET['prefix']);
	}

	if (!empty($_GET['tag'])) {

		if (strpos($_GET['tag'],':') !== FALSE) {
			list($prefix,$_GET['tag']) = explode(':',$_GET['tag'],2);

			$andwhere = " AND prefix = ".$db->Quote($prefix);
			$smarty->assign('theprefix', $prefix);
			$sphinxq = "tags:\"$prefix {$_GET['tag']}\"";
			$tag = "$prefix:{$_GET['tag']}";
			
		} elseif (isset($_GET['prefix'])) {
			#$sphinxq = "tags:\"{$_GET['prefix']} {$_GET['tag']}\"";
			$tag = "{$_GET['prefix']}:{$_GET['tag']}";
		} else {
			#$sphinxq = "tags:\"{$_GET['tag']}\"";
			$tag = "{$_GET['tag']}";
		}
			
		$tags= $db->getAll("SELECT tag_id,description FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['tag']).$andwhere);
		
		if (count($tags) == 1) {
			reset($tags);
			
			$tag_id = $tags[key($tags)]['tag_id'];
			
			if (!empty($_POST) && !empty($_POST['submit'])) {
				
				if ($tag_id != $_POST['tag_id'])
					die("huh?");
				
				$error = array();
				$updates= array();
				
				
				if (empty($_POST['description']) && !$USER->hasPerm('moderator')) {
				
					$errors['description'] = "missing required info";
				} elseif (trim($_POST['description']) == $tags[key($tags)]['description']) {
				
					$errors['description'] = "description not changed";
				} else {
					$updates['description'] = 'description = '.$db->Quote(trim($_POST['description']));
					
					$sql = "UPDATE tag SET ".implode(',',$updates)." WHERE tag_id = ".$db->Quote($tag_id);
				}
					
				if (!count($errors) && count($updates)) {
					
					$db->Execute($sql);
					$smarty->assign('error', "Description saved. ".date('r').". It may take an hour or so to appear on the tag page.");
					
					$tags[key($tags)]['description'] = $_POST['description'];
					
					
					$updates[] = "`tag_id` = $tag_id";
					$updates[] = "`user_id` = {$USER->user_id}";
					$updates[] = "`created` = NOW()";
					$sql = "INSERT INTO tag_description_log SET ".implode(',',$updates);
					$db->Execute($sql);
				} else {
					if ($errors[1] != 1)
						$smarty->assign('error', "Please see messages below...");
					$smarty->assign_by_ref('errors',$errors);
				}	
			}		
			
			reset($tags);
			$smarty->assign('onetag',1);
			$smarty->assign('description',$tags[key($tags)]['description']);
			
			$smarty->assign('tag_id', $tag_id);
			$smarty->assign('thetag', $_GET['tag']);
		} else {
			die("unable to find tag");
		}
	} else {
		die("please specify a tag!");
	}


$smarty->display($template);


