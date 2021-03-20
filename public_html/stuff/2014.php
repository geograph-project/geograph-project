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


$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$user_id = $USER->user_id;

if (!empty($_GET['uuu'])) {
	$user_id = intval($_GET['uuu']);
}


customGZipHandlerStart();

$template= "stuff_2014.tpl";
$cacheid = $user_id;


//what style should we use?
$style = $USER->getStyle();

$smarty->assign('maincontentclass', 'content_photo'.$style);

$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
        $src = 'src';//revert back to standard non lazy loading
}
$cacheid .=".$src";
if (!empty($_GET['taken']))
	$cacheid .="TAken";

$smarty->assign('src',$src);


if (!$smarty->is_cached($template, $cacheid)) {
	$db = GeographDatabaseConnection(true);

//create table hits_in_2014 (primary key (gridimage_id)) select gridimage_id,f.hits+f.hits_archive-coalesce(t.hits+t.hits_archive,0) as hits from gridimage_search inner join gridimage_log f using (gridimage_id) left join geograph_live.gridimage_log2013 t  using (gridimage_id) where imagetaken like '2014%';
//Query OK, 388076 rows affected, 1 warning (23.58 sec)

#mysql> create table hits_in_2014 (primary key (gridimage_id)) select gridimage_id,f.hits+f.hits_archive-coalesce(t.hits+t.hits_archive,0) as hits from gridimage_log2014 f left join gridimage_log2013 t  using (gridimage_id);
#Query OK, 4251962 rows affected (36.62 sec)


	$cols = "gridimage_id,title,user_id,realname,grid_reference,credit_realname";
	$row = $db->getRow($sq = "SELECT $cols,baysian FROM gridimage_search INNER JOIN gallery_ids on (id = gridimage_id) where imagetaken like '2014%' AND user_id = {$user_id} ORDER BY baysian DESC LIMIT 1");

	if (empty($row)) {
		//fallback!
		$row = $db->getRow($sq = "SELECT $cols,AVG(baysian) AS baysian FROM gridimage_search INNER JOIN vote_stat v on (id = gridimage_id) where imagetaken like '2014%' AND user_id = {$user_id} GROUP BY gridimage_id ORDER BY baysian DESC LIMIT 1");
	}

        if (empty($row)) {
                //fallback!
                $row = $db->getRow($sq = "SELECT $cols,hits FROM gridimage_search INNER JOIN hits_in_2014 USING (gridimage_id) where imagetaken like '2014%' AND user_id = {$user_id} GROUP BY hits DESC LIMIT 1"); //limit 1 implici
        }

	if (!empty($row)) {
		$image = new GridImage();
        	$image->fastInit($row);
	        $image->compact();
        	$smarty->assign_by_ref('image', $image);

		$imagelist = new ImageList();
		if (!empty($_GET['taken'])) {
			$imagelist->_getImagesBySql("SELECT $cols,hits FROM gridimage_search INNER JOIN hits_in_2014 USING (gridimage_id) where user_id = {$user_id} AND imagetaken LIKE '2014%' ORDER BY hits DESC LIMIT 8");
			 $smarty->assign('taken',1);
		} else {
			$imagelist->_getImagesBySql("SELECT $cols,hits FROM gridimage_search INNER JOIN hits_in_2014 USING (gridimage_id) where user_id = {$user_id} ORDER BY hits DESC LIMIT 8");
		}
	        $smarty->assign_by_ref('hits', $imagelist->images);

		$stats = $db->getRow($sq = "SELECT count(*) AS images,
			count(distinct substring(grid_reference,1,3 - reference_index)) as myriads,
			sum(ftf>0) as personals,
			count(distinct imagetaken) as days,
			sum(points='tpoints') as tpoints
			from gridimage_search where imagetaken like '2014%' AND user_id = {$user_id}");

		$smarty->assign_by_ref('stats', $stats);

	}

        $smarty->assign_by_ref('user_id', $user_id);
}


$smarty->display($template, $cacheid);

