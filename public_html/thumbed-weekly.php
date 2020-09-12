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

customGZipHandlerStart();

$template='thumbed-weekly.tpl';



$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'';


$cacheid="$type";

//what style should we use?
$style = $USER->getStyle();

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 300;
	customExpiresHeader(300,false,true);
}

$smarty->assign('maincontentclass', 'content_photo'.$style);

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	$types = array(''=>'images or descriptions','img'=>'images','desc'=>'descriptions');
	$smarty->assign_by_ref('types', $types);
	$smarty->assign_by_ref('type', $type);

	$imagelist=new ImageList;

	if ($type == 'desc' || $type =='img') {
		$where = "type = '$type'";
	} else {
		$where = "type in ('img','desc')";
	}

	$db = $imagelist->_getDB(true);
	$num = $db->getOne("select max(num) from vote_stat where last_vote > date_sub(now(),interval 20 day)");

	$sql="select gridimage_id,grid_reference,imagetaken,user_id,title,comment,imageclass, type,num,last_vote,
		(($num-num)/10) + (crc32(id)/4294967295) + ((unix_timestamp(now())-unix_timestamp(last_vote))/1000000) as sorter
		from vote_stat as vs
		inner join gridimage_search as gi on (vs.id = gi.gridimage_id)
		where $where
		and num > 1
		group by vs.id
		order by sorter limit 25";

        $sql="
	select *,
                (($num-num)/10) + (crc32(gridimage_id)/4294967295) + ((unix_timestamp(now())-unix_timestamp(last_vote))/1000000) as sorter
	from (
		select gridimage_id,grid_reference,imagetaken,user_id,realname,title,comment,imageclass, type,num,last_vote
                from vote_stat as vs
                inner join gridimage_search as gi on (vs.id = gi.gridimage_id)
                where $where
		and gi.user_id != 60859
                and num > 1
                group by vs.id
                order by last_vote desc limit 60) t2
	order by sorter";


	$imagelist->_getImagesBySql($sql);

	if (count($imagelist->images)) {
		$done = array();
		foreach ($imagelist->images as $i => $image) {
			if (isset($done[$imagelist->images[$i]->user_id]) && $done[$imagelist->images[$i]->user_id] > 2) {
				unset($imagelist->images[$i]);
				continue;
			}
			$imagelist->images[$i]->imagetakenString = getFormattedDate($image->imagetaken);
			@$done[$imagelist->images[$i]->user_id]++;
		}

		$smarty->assign_by_ref('images', $imagelist->images);

		$count++;
	}
}


$smarty->display($template, $cacheid);



