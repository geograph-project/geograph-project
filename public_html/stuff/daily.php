<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6368 2010-02-13 19:45:59Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/imagelist.class.php');
init_session();

if (!function_exists('json_decode')) {
        require_once('/var/www/geograph_svn/libs/3rdparty/JSON.php');
}



$smarty = new GeographPage;

customGZipHandlerStart();

customExpiresHeader(3600*6,false,true);

if (!empty($_GET['gallery'])) {
	$_GET['tab'] = 'gallery';
}
if (empty($_GET['tab']) || !preg_match('/^\w+$/',$_GET['tab'])) {
	$_GET['tab'] = 'potd';
}


$template='stuff_daily.tpl';
$cacheid=$_GET['tab'];

//what style should we use?
$style = $USER->getStyle();


$smarty->assign('maincontentclass', 'content_photo'.$style);

$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
        $src = 'src';//revert back to standard non lazy loading
}

$src = 'loading="lazy" src'; //experimenting with moving to it permentanty!

$cacheid .=".$src";

$smarty->assign('src',$src);

if (!$smarty->is_cached($template, $cacheid)) {
	$imagelist = new ImageList();


	$gi_columns = "gridimage_id,user_id,realname,title,grid_reference,credit_realname";

	$q = array();
        $q['yomp'] = "SELECT $gi_columns,NULL AS showday FROM gridimage_search inner join gridimage_post using (gridimage_id) inner join geobb_topics using (topic_id) where type = 'I' and forum_id = 6 AND topic_title LIKE 'YOMP %' and moderation_status = 'geograph' group by gridimage_id desc";
        $q['poty'] = "SELECT $gi_columns,NULL AS showday FROM gridimage_search inner join gridimage_post using (gridimage_id) inner join geobb_topics using (topic_id) where type = 'I' and forum_id = 17  and moderation_status = 'geograph' group by gridimage_id desc";
        $q['gallery'] = "SELECT $gi_columns,showday FROM gridimage_search inner join gallery_ids on (id=gridimage_id) WHERE showday <= date(now()) ORDER BY showday DESC";
        $q['top'] = "SELECT $gi_columns,NULL AS showday FROM gridimage_search inner join gallery_ids on (id=gridimage_id) WHERE moderation_status = 'geograph' AND gallery_ids.baysian > 4 ORDER BY gallery_ids.baysian DESC"; //gi also has gallery_ids, but use gallery_ids like an index
	$q['weekly'] = "SELECT $gi_columns,NULL AS showday FROM gridimage_search inner join gallery_ids on (id=gridimage_id) WHERE fetched > date_sub(now(),interval 10 day) and moderation_status = 'geograph' ORDER BY gallery_ids.baysian DESC";
        $q['potd'] = "SELECT $gi_columns,showday FROM gridimage_search inner join gridimage_daily using (gridimage_id) WHERE showday <= date(now()) ORDER BY showday DESC";
	$q['user'] = "SELECT DISTINCT $gi_columns,NULL AS showday FROM gridimage_search inner join gridimage_post using (gridimage_id) WHERE topic_id = 17652 ORDER BY post_id DESC";
	$q['poty2014'] = "SELECT DISTINCT $gi_columns,imagetaken AS showday FROM gridimage_search WHERE gridimage_id IN (3831340,3857309,3873725,3933193,4010306,4035293,4066025,4145642,4185695,4226895,4235832,4277690)";
        $q['more'] = "SELECT $gi_columns, NULL AS showday FROM gridimage_search inner join gridimage_daily using (gridimage_id) WHERE showday IS NULL AND updated < DATE_SUB(NOW(),INTERVAL 5 YEAR) ORDER BY RAND(YEARWEEK(NOW())) DESC";

	if (isset($q[$_GET['tab']])) {
                //todo, can only - for now, do if not using UNION
                if ($_GET['tab'] == 'potd') {
			$q['potd'] = str_replace(' FROM ',',brightness FROM ',$q['potd']);
                }

		$imagelist->_getImagesBySql($q[$_GET['tab']]." LIMIT 24");

	} elseif ($_GET['tab'] == 'mixed') {

		$sql = "(".implode(" LIMIT 5) UNION (",$q)." LIMIT 5) ORDER BY CRC32(gridimage_id)";

                if (!empty($_GET['ddd']))
                        die($sql);

		$imagelist->_getImagesBySql($sql);

	} elseif ($_GET['tab'] == 'twitter') {

		$v = json_decode(file_get_contents('../sitemap/twitter.json'));
		$ids = array();
		foreach ($v as $tweet) {
			foreach ($tweet->entities->urls as $url) {
				if (preg_match('/photo\/(\d+)/',$url->expanded_url,$m)) {
					$ids[] = intval($m[1]);
				}
			}
		}
		if (!empty($ids)) {
			$imagelist->getImagesByIdList($ids,$gi_columns);
		}
	}

	$smarty->assign_by_ref('results', $imagelist->images);
	$smarty->assign('tab', $_GET['tab']);
}


$smarty->display($template, $cacheid);


