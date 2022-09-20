<?php /**
 * $Project: GeoGraph $
 * $Id: index.php 7361 2011-08-11 23:11:50Z geograph $
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

$USER->mustHavePerm("basic");

if (!empty($_GET['export']) && !empty($USER->user_id)) {
	$db = GeographDatabaseConnection(true);

	$sql = "select prefix,tag,gt.created,gridimage_id,grid_reference,title,realname,gi.user_id,imagetaken
	 from gridimage_tag gt inner join tag using (tag_id) inner join gridimage_search gi using (gridimage_id) where gt.user_id = {$USER->user_id} and gt.status = 1";

	$recordSet = $db->Execute($sql);
	if (!$recordSet->RecordCount())
		die("No Private Tags found\n");

	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"geograph-private-tags-".date('Y-m-d').".csv\"");

	$f = fopen("php://output", "w");
	if (!$f) {
		die("ERROR:unable to open output stream");
	}

	fputcsv($f,array_keys($recordSet->fields));
	while (!$recordSet->EOF) {
		$recordSet->fields['title'] = latin1_to_utf8($recordSet->fields['title']);
		$recordSet->fields['realname'] = utf8_encode($recordSet->fields['realname']);

		fputcsv($f,$recordSet->fields);
		$recordSet->MoveNext();
	}

	$recordSet->Close();
	exit;
}


$template = 'tags_private.tpl';
$cacheid = $USER->user_id.'.'.md5(serialize($_GET));

if (!empty($_GET['share'])) {
        $template = 'tags_private_share.tpl';
}
if (!empty($_POST['share'])) {
	$db = GeographDatabaseConnection(false);

	foreach ($_POST['share'] as $tag_id => $dummy) {
		$updates['tag_id'] = intval($tag_id);
		$updates['user_id'] = $USER->user_id;
		$updates['own_too'] = isset($_POST['own_too'][$tag_id]);

		//we could use $_POST['was'] was to decide if insert or update, but duplicate key works too :)
		$db->Execute('INSERT INTO tag_share SET created=NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ? '.
			'ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ? ',array_merge(array_values($updates),array_values($updates)) );
	}
	foreach ($_POST['was'] as $tag_id => $dummy) {
		if (!isset($_POST['share'][$tag_id])) {
			$db->Execute("DELETE FROM tag_share WHERE tag_id = ".intval($tag_id)." AND user_id = ".intval($USER->user_id));
		}
	}
	$cacheid.=".".time();
}


if (!$smarty->is_cached($template, $cacheid))
{
	if (empty($db))
		$db = GeographDatabaseConnection(true);

	$where = '';
	$andwhere = '';

	if (isset($_GET['prefix'])) {

		$andwhere = " AND prefix = ".$db->Quote($_GET['prefix']);
		$smarty->assign('theprefix', $_GET['prefix']);
	}

	$tables = "tag t INNER JOIN gridimage_tag gt USING(tag_id)";
	$wheres = "gt.status = 1 AND gt.user_id = {$USER->user_id} AND gridimage_id < 4294967296";

	if (!empty($_GET['tag'])) {
		//TODO - this will be rewritten using sphinx...

		if (strpos($_GET['tag'],':') !== FALSE) {
			list($prefix,$_GET['tag']) = explode(':',$_GET['tag'],2);

			$andwhere = " AND prefix = ".$db->Quote($prefix);
			$smarty->assign('theprefix', $prefix);
		}

		$col= $db->getCol("SELECT tag_id FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['tag']).$andwhere);

		if (!empty($col)) {

			$ids = implode(',',$col);

			if (!empty($_GET['exclude'])) {
				$exclude= $db->getRow("SELECT * FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['exclude']));
			}

			if (!empty($exclude)) {
				$sql = "select gi.*
					from gridimage_tag gt
						inner join gridimage_search gi using(gridimage_id)
					where status =1 AND gt.user_id = {$USER->user_id}
					and gt.tag_id IN ($ids)
					and gt.gridimage_id NOT IN (SELECT gridimage_id FROM gridimage_tag gt2 WHERE gt2.tag_id = {$exclude['tag_id']})
					group by gt.gridimage_id
					order by created desc
					limit 50";
			} else {
				$sql = "select gi.*
					from gridimage_tag gt
						inner join gridimage_search gi using(gridimage_id)
					where status =1 AND gt.user_id = {$USER->user_id}
					and tag_id IN ($ids)
					group by gt.gridimage_id
					order by created desc
					limit 50";
			}

			$imagelist = new ImageList();

			$imagelist->_getImagesBySql($sql);

			$ids = array();
			foreach ($imagelist->images as $idx => $image) {
				$ids[$image->gridimage_id]=$idx;
				$imagelist->images[$idx]->tags = array();
			}
			$db = $imagelist->_getDB(true); //to reuse the same connection

			if ($idlist = implode(',',array_keys($ids))) {
				$sql = "SELECT gridimage_id,tag,prefix FROM $tables WHERE $wheres AND gridimage_id IN ($idlist) ORDER BY tag";

				$tags = $db->getAll($sql);
				if ($tags) {
					foreach ($tags as $row) {
						$idx = $ids[$row['gridimage_id']];
						$imagelist->images[$idx]->tags[] = $row;
					}
				}

				$smarty->assign('search_link', "/search.php?markedImages=$idlist");
			}

			$smarty->assign_by_ref('results', $imagelist->images);

			$smarty->assign('thetag', $_GET['tag']);

			if (!empty($_GET['photo']) && !empty($db)) {
				$smarty->assign('gridref',$db->getOne("SELECT grid_reference FROM gridimage_search WHERE gridimage_id = ".intval($_GET['photo'])));
			}
		}
	}

	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$tables .= " inner join gridimage_search gi using(gridimage_id)"; //to exclude pending/reject images

	$prefixes = $db->getAll($sql = "SELECT LOWER(prefix) AS prefix,COUNT(*) AS tags FROM $tables WHERE $wheres GROUP BY prefix");
	$smarty->assign_by_ref('prefixes', $prefixes);

	if (empty($_GET['tag'])) {
		if (isset($_GET['share'])) {
			$tags = $db->getAll($sql = "SELECT t.tag_id,prefix,LOWER(tag) AS tag,COUNT(*) AS images,ts.user_id AS checked,own_too
					FROM $tables LEFT JOIN tag_share ts ON (ts.tag_id = gt.tag_id AND ts.user_id = gt.user_id)
				WHERE $wheres $andwhere GROUP BY tag_id ORDER BY tag");
		} else {
			$tags = $db->getAll($sql = "SELECT prefix,LOWER(tag) AS tag,COUNT(*) AS images FROM $tables WHERE $wheres $andwhere GROUP BY tag_id ORDER BY tag");
		}

		$smarty->assign_by_ref('tags', $tags);
	}
	$smarty->assign('private', 1);
}

$smarty->display($template, $cacheid);

