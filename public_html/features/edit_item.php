<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm('basic');
$isadmin=$USER->hasPerm('moderator')?1:0;

if (empty($_REQUEST['id'])) {
	$smarty->display('static_404.tpl');
	exit;
}


$template = 'features_edit_item.tpl';
$cacheid = '';

	$db=GeographDatabaseConnection(false);



	if (!empty($_GET['type_id'])) {
		//todo, honour licewnce=none?!
		$type_id = intval($_REQUEST['type_id']);
		$row = $db->getRow("SELECT t.*,realname FROM feature_type t LEFT JOIN user USING (user_id) WHERE feature_type_id = $type_id AND status > 0");

		if (empty($row))
			die("invalid type");
		$smarty->assign('columns', array_flip(explode(',',$row['item_columns'])));

	} else {
		$smarty->display('static_404.tpl');
	        exit;
	}




	if ($_REQUEST['id'] == 'new') {
		$smarty->assign('item', array(
			'name' => "New Item",
			'user_id' => $USER->user_id,
			'feature_type_id' => intval($_REQUEST['type_id']),
			'gridref' => @$_GET['gridref']
		));
		$smarty->assign('id', "new");
	} else {
		$sql_where = " feature_item_id = ".$db->Quote($_REQUEST['id'])." AND feature_type_id = ".$db->Quote($_REQUEST['type_id']);

		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$page = $db->getRow($sql = "
		select feature_item.*
		from feature_item
		where $sql_where
		limit 1");

		if (count($page)) { // && ($page['user_id'] == $USER->user_id || $USER->hasPerm('moderator'))) { ... currently anyone can edit items!
			$smarty->assign('item',$page);
			$smarty->assign('id', $page['feature_item_id']);
		} else {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$template = 'static_404.tpl';
		}
	}

if ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['submit'])) {
	$errors = array();
	$updates = array();
	foreach (explode(',',$row['item_columns']) as $key) {
		if (isset($_POST[$key]) && $page[$key] != $_POST[$key] && $key != 'nearby_images') {
			$updates[$key] = trim(strip_tags($_POST[$key]));
			$smarty->assign($key, $_POST[$key]);
		}
	}

	if (isset($updates['gridimage_id'])) //only set if user changed it!
		$updates['gridimage_id_user_id'] = $USER->user_id;
	elseif (!empty($_POST['gridimage_id']) && empty($page['gridimage_id_user_id']) && !isset($_POST['name']))
		//but also allow them to 'claim' an previously auto selected image. But not if doing a general edit of all feilds
		$updates['gridimage_id_user_id'] = $USER->user_id;

	//todo, when edit 'gridref' need to invalidae the eastings/norhting/ri + lat/long
	//same is was enable editing easting/northing directly for example.

	if (!count($updates)) {
		$smarty->assign('error', "No Changes to Save");
		$errors[1] =1;
	}
	if ($_REQUEST['id'] == 'new') {
		$updates['feature_type_id'] = $_REQUEST['type_id'];
		$updates['user_id'] = $USER->user_id;
		$sql = 'INSERT INTO feature_item SET `'.implode('` = ?,`',array_keys($updates)).'` = ?';
	} else {
		$sql = 'UPDATE feature_item SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE feature_item_id = '.$db->Quote($_REQUEST['id']);
	}

	if (!count($errors) && count($updates)) {
		$db->Execute($sql, array_values($updates));
		if ($_REQUEST['id'] == 'new') {
			$_REQUEST['id'] = $db->Insert_ID();
		}

                $inserts = array();
                $inserts['feature_item_id'] = $_REQUEST['id'];

		//store the type_id and table_id - just in case a future import does a 'replace into'!!
		$inserts['feature_type_id'] = $page['feature_type_id'] ?? $_REQUEST['type_id'];
		$inserts['table_id'] = $page['table_id'];

                $inserts['user_id'] = $USER->user_id;

                foreach ($updates as $key => $value) {
                        if ($value != @$page[$key] && $key != 'gridimage_id_user_id') { //gridimage_id_user_id ends up kinda duplicating user_id anyway
                                $inserts['field'] = $key;
                                $inserts['oldvalue'] = @$page[$key];
                                $inserts['newvalue'] = $value;

                                $db->Execute('INSERT INTO feature_item_log SET `'.implode('` = ?,`',array_keys($inserts)).'` = ?',array_values($inserts));
                        }
                }

		if (!empty($_GET['inner'])) {
			print "<script>parent.closePopup(true);</script>";
		} else {
			header("Location: /features/view.php?id=".intval($_REQUEST['type_id']));
		}
		exit;
	} else {
		if ($errors[1] != 1)
			$smarty->assign('error', "Please see messages below...");
		$smarty->assign_by_ref('errors',$errors);
	}
}

if (!empty($_GET['inner']))
	 $smarty->assign('inner',1);

$smarty->display($template, $cacheid);


