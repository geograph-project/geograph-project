<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 barry hunter (geo@barryhunter.co.uk)
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

$template = 'tags_synonym.tpl';

$USER->mustHavePerm("basic");




if (!empty($_GET['deal'])) {
	$USER->mustHavePerm("admin");

	
} else {
	if (!empty($_POST)) {

		$db = GeographDatabaseConnection(false);

		$u = array();
		foreach (array('tag','tag_id','tag2','tag2_id') as $key) {
			if (!empty($_POST[$key])) {
				$u[$key] = trim($_POST[$key]);
			}
		}

		if (!empty($u)) {
			$u['type'] = 'canonical';
			
			$u['user_id'] = $USER->user_id;

			$db->Execute('INSERT INTO tag_report SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

			$smarty->assign("message",'Suggestion saved at '.date('r'));
		}

	}



	if (empty($db))
		$db = GeographDatabaseConnection(true);

	if (!empty($_GET['tag'])) {
	
		$where = array();
		$where['prefix'] = "prefix = ''";
		
		if (isset($_GET['prefix'])) {
			$where['prefix'] = "prefix = ".$db->Quote($_GET['prefix']);
			$smarty->assign('theprefix', $_GET['prefix']);
			
		} elseif (strpos($_GET['tag'],':') !== FALSE) {
			list($prefix,$_GET['tag']) = explode(':',$_GET['tag'],2);

			$where['prefix'] = "prefix = ".$db->Quote($prefix);
			$smarty->assign('theprefix', $prefix);
		}
		$where['tag'] = "tag = ".$db->Quote($_GET['tag']);
		$smarty->assign('tag',$_GET['tag']);
		
		$row= $db->getRow("SELECT tag_id,prefix,tag,description,canonical FROM tag WHERE status = 1 AND ".implode(' AND ',$where));
		
		if (!empty($row)) {


			if (!empty($row['canonical'])) {
				//.. is part of a canonical set

				//find the definitive tag
				$canonical = $db->getRow("SELECT tag_id,prefix,tag,canonical FROM tag WHERE status = 1 AND tag_id = ".intval($row['canonical']));
				$smarty->assign_by_ref('canonical', $canonical);

				//find the siblings
				$synonyms = $db->getAll("SELECT tag_id,prefix,tag,canonical FROM tag WHERE status = 1 AND canonical = ".intval($row['canonical']));
				$smarty->assign_by_ref('synonyms', $synonyms);

			} else {
				//is not a synonum of another

				//check in case it as any children
				$synonyms = $db->getAll("SELECT tag_id,prefix,tag,canonical FROM tag WHERE status = 1 AND canonical = ".intval($row['tag_id']));
				$smarty->assign_by_ref('synonyms', $synonyms);

				if (!empty($synonyms)) {
					//it is the parent
					$smarty->assign_by_ref('canonical', $row);
					$smarty->assign('found', 1);
				}

			}
		}
	}
}


$smarty->display($template,$cacheid);
