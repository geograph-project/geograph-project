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
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");



if (!empty($_GET['action']) && !empty($_GET['imageclass'])) {

	if (empty($_GET['confirm']) && $_GET['action'] != 'checked') {
		$smarty->assign('imageclass',$_GET['imageclass']);
		$smarty->assign('field',$_GET['field']);
		$smarty->assign('action',$_GET['action']);
		$smarty->assign('value',$_GET['value']);

		if ($_GET['action'] == 'add' && empty($_GET['value'])) {
			$db = GeographDatabaseConnection(true);
			if ($_GET['field'] == 'context') {
				$values = $db->getCol("SELECT top FROM category_primary ORDER BY sort_order");
			} elseif ($_GET['field'] == 'subject') {
				$values = $db->getCol("SELECT subject FROM subjects ORDER BY subject");
			}
			if (!empty($values))
				$smarty->assign('values',array_combine($values,$values));
		}

		//to filter the categories shown!
		$_GET['subject'] = $_GET['imageclass'];
	} else {
		$db = GeographDatabaseConnection(false);

	        $updates = array();
        	$updates['imageclass'] = $_GET['imageclass'];
	        $updates['user_id'] = $USER->user_id;
        	$updates['action'] = $_GET['action'];
	        $updates['status'] = 1;
        	if (!empty($_GET['field'])) $updates['field'] = $_GET['field'];
	        if (!empty($_GET['value'])) $updates['value'] = $_GET['value'];
	        if (!empty($_GET['explanation'])) $updates['explanation'] = $_GET['explanation'];

        	$db->Execute('INSERT INTO category_mapping_change SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	}
}


if (empty($db))
	$db = GeographDatabaseConnection(true);


$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_GET['subject'])) {
	$crit = $db->Quote($_GET['subject']);
	$where = "m.imageclass like $crit OR m.subject like $crit";
	$smarty->assign('subject',$_GET['subject']);
	$smarty->assign('extra',"&amp;subject=".urlencode($_GET['subject']));
	$limit = 1000;
} else {
	$where = "gi.user_id = {$USER->user_id}";
	$smarty->assign('user_id',intval($USER->user_id));
	$limit = 110000;
}

$suggestions = $db->getAssoc("SELECT m.*,c.canonical,count(*) images
	from category_mapping m inner join gridimage_search gi using (imageclass)
		left join category_canonical_log c using (imageclass)
	where $where group by imageclass limit $limit");
foreach ($suggestions as $imageclass => $row) {
	//split into array
	if (!empty($row['tags']))
		$suggestions[$imageclass]['tags'] = preg_split('/\s*[,;]\s*/',$row['tags']);
}

$delta = $db->getAll("SELECT * FROM category_mapping_change WHERE user_id = {$USER->user_id} AND status > 0 ORDER BY change_id");
if (!empty($delta)) {
	foreach($delta as $row) {
		if (!isset($suggestions[$row['imageclass']]))
			continue;
		$mod =& $suggestions[$row['imageclass']];
		switch($row['action']) {
			case 'add':
				switch($row['field']) {
					case 'context':
						foreach(range(1,3) as $i) {
							if (empty($mod['context'.$i])) {
								$mod['context'.$i] = $row['value'];
								break;
							}
						}
					break;
					case 'subject':
						$mod['subject'] = $row['value'];
					break;
					case 'tag':
						if (empty($mod['tags'])) $mod['tags'] = array();
						$mod['tags'][] = $row['value'];
					break;
				}
			break;
			case 'remove':
				switch($row['field']) {
					case 'context':
						foreach(range(1,3) as $i) {
							if (!empty($mod['context'.$i]) && $mod['context'.$i] == $row['value']) {
								$mod['context'.$i] = '';
								break;
							}
						}
					break;
					case 'subject':
						if ($mod['subject'] == $row['value'])
							$mod['subject'] = '';
					break;
					case 'tag':
						$idx = array_search($row['value'], $mod['tags']);
						if ($idx !== FALSE)
							unset($mod['tags'][$idx]);
					break;
					case 'canonical':
						if ($mod['canonical'] == $row['value'])
							$mod['canonical'] = '';
					break;
				}
			break;
			case 'checked':
				if (empty($_GET['show']))
					unset($suggestions[$row['imageclass']]);
			break;
		}
	}
}




$smarty->assign_by_ref('suggestions',$suggestions);

$smarty->display('stuff_category_mapping.tpl');


