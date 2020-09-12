<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 8142 2014-06-10 21:30:56Z geograph $
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

$template = 'content_recent.tpl';

$db = GeographDatabaseConnection(true);

$data = $db->getRow("show table status like 'content'");

//when this table was modified
$mtime = strtotime($data['Update_time']);


//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
customCacheControl($mtime,$cacheid,!($USER->registered));


$order = (isset($_GET['order']) && ctype_lower($_GET['order']))?$_GET['order']:'updated';

switch ($order) {
	case 'relevance': $sql_order = "NULL"; //will be fixed later
		$title = "Relevance"; break;
	case 'views': $sql_order = "views desc";
		$title = "Most Viewed"; break;
	case 'images': $sql_order = "images desc";
		$sphinx_sort = "aimages desc, @id desc";
		$title = "Most Images"; break;
	case 'created': $sql_order = "created desc";
		$sphinx_sort = "created desc";
		$title = "Recently Created"; break;
	case 'rand': $sql_order = "rand()";
		$sphinx_sort = "@random";
		$title = "Random Order"; break;
	case 'title': $sql_order = "title";
		$title = "By Collection Title";break;
	case 'updated':
	default: $sql_order = "updated desc";
		$sphinx_sort = "updated desc";
		$title = "Recently Updated";
		$order = 'updated';
}
$orders = array('views'=>'Most Viewed','created'=>'Recently Created','title'=>'Alphabetical','updated'=>'Last Updated','images'=>'Most Images','rand'=>'Random Order');

$sources = $CONF['content_sources'];

if ((isset($CONF['forums']) && empty($CONF['forums'])) || !$USER->registered ) {
	unset($sources['themed']);
}

	unset($sources['portal']);

$cacheid = $time.$order.$USER->registered;


if (!$smarty->is_cached($template, $cacheid)) {

	$datecolumn = ($order == 'created')?'created':'updated';

	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$lists = array();
	foreach ($sources as $source => $title) {
		$where = "source = '$source'";
		$lists[$source] = $db->getAll($sql = "
		select content.content_id,content.user_id,url,title,extract,unix_timestamp(replace(content.$datecolumn,'-00','-01')) as $datecolumn,realname,content.source
		from content
			left join user using (user_id)
		where $where and content.`type` = 'info'
		order by $sql_order
		limit 30");
	}

	$smarty->assign_by_ref("lists",$lists);
	$smarty->assign_by_ref("sources",$sources);

	#pallet by http://jiminy.medialab.sciences-po.fr/tools/palettes/index.php
	$colours = array('E1BBDA','DDEA8E','83E7E1','D5CEA9','E6B875','A7CEE5','E9B1A5','A6E09A','7DE0B8','CED4CF','B2DAAD','C5C474','A0DACA');

	$keys = array_keys($sources);
	foreach ($keys as $idx => $key) {
		$colours[$key] = $colours[$idx];
	}
	$smarty->assign_by_ref("colours",$colours);

	$smarty->assign_by_ref("order",$order);
	$smarty->assign_by_ref("orders",$orders);
}

$smarty->display($template, $cacheid);


