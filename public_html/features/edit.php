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


$template = 'features_edit.tpl';
$cacheid = '';

	$db=GeographDatabaseConnection(false);
	if ($_REQUEST['id'] == 'new') {
		$smarty->assign('id', "new");
		$smarty->assign('title', "New Dataset");
		$smarty->assign('realname', $USER->realname);
		$smarty->assign('user_id', $USER->user_id);
		$page = array();
		$page['item_columns'] = 'name,category,gridref,gridimage_id';
		$page['default_grlen'] = '8';
		$page['default_radius'] = '100';
		$smarty->assign($page);

		$USER->getStats();
	        if ($USER->stats['images'] < 5) {
			die("Due to spam - this feature is only available to photo contributors. If you would still like to contribute, please <a href=\"/contact.php\">Contact Us</a>, otherwise <a href=\"javascript:history.go(-1)\">Go back</a>");
		}


	} else {
		$sql_where = " feature_type_id = ".$db->Quote($_REQUEST['id']);

		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$page = $db->getRow($sql = "
		select feature_type.*,realname
		from feature_type
			left join user using (user_id)
		where $sql_where
		limit 1");

		if (count($page) && ($page['user_id'] == $USER->user_id || $USER->hasPerm('moderator'))) {
			$smarty->assign($page);
			$smarty->assign('id', $page['feature_type_id']);
		} else {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$template = 'static_404.tpl';
		}
	}


if ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['submit'])) {
	$errors = array();

	$_POST['published']=sprintf("%04d-%02d-%02d %02d:%02d:%02d",$_POST['publishedYear'],$_POST['publishedMonth'],$_POST['publishedDay'],$_POST['publishedHour'],$_POST['publishedMinute'],$_POST['publishedSecond']);
        if (!empty($_POST['title']))
                $_POST['title'] = preg_replace('/[^\w\-\.,:;\' ]+/','',trim($_POST['title']));
        if (empty($_POST['url']) && !empty($_POST['title'])) {
                $_POST['url'] = $_POST['title'];
        }
        if (!empty($_POST['url'])) {
                $_POST['url'] = preg_replace('/ /','-',trim($_POST['url']));
                $_POST['url'] = preg_replace('/[^\w-]+/','',$_POST['url']);
        }

	if ($_POST['title'] == "New Dateset")
		$errors['title'] = "Please give a meaningful title";

	if (!empty($_POST['item_columns'])) {
		array_unshift($_POST['item_columns'],'name'); //always want this!
		$_POST['item_columns'] = implode(",",$_POST['item_columns']);
	}

	$updates = array();
	foreach (array('title','url','content','extract','footnote','licence','source','published','default_grlen','item_columns','default_radius') as $key) {
		if (@$page[$key] != $_POST[$key]) {
			$updates[$key] = trim(strip_tags($_POST[$key]));
			$smarty->assign($key, $_POST[$key]);
		}
	}

	if (isset($_POST['initial'])) {
		$smarty->assign('error', "Please review your new entry and press Save below to post the Entry");
		$errors[1] =1;
	} elseif (!count($updates)) {
		$smarty->assign('error', "No Changes to Save");
		$errors[1] =1;
	}
	if ($_REQUEST['id'] == 'new') {
		$updates['user_id'] = $USER->user_id;
		$sql = 'INSERT INTO feature_type SET `'.implode('` = ?,`',array_keys($updates)).'` = ?';
	} else {
		$sql = 'UPDATE feature_type SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE feature_type_id = '.$db->Quote($_REQUEST['id']);
	}

	if (!count($errors) && count($updates)) {
		$db->Execute($sql, array_values($updates));
		if ($_REQUEST['id'] == 'new') {
			$_REQUEST['id'] = $db->Insert_ID();
		}

                        foreach ($updates as $key => $value) {
                                if ($value != @$page[$key]) {
                                        $inserts = array();
                                        $inserts['feature_type_id'] = $_REQUEST['id'];
                                        $inserts['user_id'] = $USER->user_id;
                                        $inserts['field'] = $key;
                                        $inserts['oldvalue'] = @$page[$key];
                                        $inserts['newvalue'] = $value;

                                        $db->Execute('INSERT INTO feature_type_log SET `'.implode('` = ?,`',array_keys($inserts)).'` = ?',array_values($inserts));
                                }
                        }


		$smarty->clear_cache('features_index.tpl');
		$smarty->clear_cache('features_view.tpl',$_REQUEST['id']."|");

		header("Location: /features/view.php?id=".intval($_REQUEST['id']));
		exit;
	} else {
		if ($errors[1] != 1)
			$smarty->assign('error', "Please see messages below...");
		$smarty->assign_by_ref('errors',$errors);
	}
}

$smarty->assign('licences', array('none' => '(Temporarily) Not Published',
	'pd' => 'Public Domain',
	'cc-by-sa/2.0' => 'Creative Commons BY-SA/2.0',
	'cc-by-sa/4.0' => 'Creative Commons BY-SA/4.0',
	'geograph' => 'CC-BY-SA/4.0 attributed to Geograph Project',
	'odbl' => 'Open Database License + Database Contents License',
	'ogl' => 'Open Government Licence v3.0',
	'copyright' => 'Copyright to you'
));

$list = array(
        'name' => array('info'=>'the main name for the feature','required'=>1),
        'label' => array('info'=>'optional textual label, useful to add supplemental info to each row. Could also a unique id'),
        'category' => array('info'=>'optional category, useful to filter/sort items'),
        'subcategory' => array('info'=>'another optional cagegory, again for filtering etc'),
        'county' => array('info'=>'can allocate items to county for better filtering'),
        'country' => array('info'=>'can allocate items to country for filtering'),
        'region' => array('info'=>'can allocate items to region for filtering'),
        'gridref' => array('info'=>'Grid-Reference, of arbitary precision, if features have a location, this is recommended way to refer to it'),
        'e' => array('info'=>'eastings on national grid (either osgb or irish grid)'),
        'n' => array('info'=>'northings on national grid (either osgb or irish grid)'),
        'reference_index' => array('info'=>'1=osgb36, 2=irish grid'),
        'wgs84_lat' => array('info'=>'latitude (wgs84)'),
        'wgs84_long' => array('info'=>'longitude (wgs84)'),
        'radius' => array('info'=>'allows storing an approximate radius (in meters) of the feature, to make more refine local searches for "images nearby"'),
        'gridimage_id' => array('info'=>'stores the ID of a selected image. As the whole point of function is to link images, would be unusual not to enable this one!'),
        'nearby_images' => array('info'=>'automated column of count of nearby images (only works, if have location AND radius)'),
        'bound_images' => array('info'=>'automated column of count of images within bounds (only works, if have boundary geometry)'),
        'sorter' => array('info'=>'supplementry numberic column, intended for users to sort the table. Could be size or population based'),
);
foreach (explode(',',$page['item_columns']) as $key)
	$list[$key]['enabled']=1;


$smarty->assign('columns',$list);

$smarty->display($template, $cacheid);

