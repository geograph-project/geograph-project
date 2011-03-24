<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 7069 2011-02-04 00:06:46Z barry $
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


$template = 'tags_primary.tpl';
$cacheid = "";



if (!$smarty->is_cached($template, $cacheid))
{
	
	$db = GeographDatabaseConnection(true);
	
	$tags = $db->getAssoc("
		SELECT top,CONCAT(ids,IF(ids != '',',',''),COALESCE(GROUP_CONCAT(gridimage_id),'')) AS ids,COUNT(*) AS images,description,grouping
		FROM category_primary 
		LEFT JOIN tag t ON (top = tag AND prefix = 'top') 
		LEFT JOIN gridimage_tag gt ON (t.tag_id = gt.tag_id AND gt.status = 2) 
		GROUP BY top
		ORDER BY sort_order
		LIMIT 75");

	$ids = array();
	foreach ($tags as $tag => $row) {
		if ($row['ids']) {
			$i = explode(',',str_replace(' ','',$row['ids']));
			$ids = array_merge($ids,array_slice($i,0,4));
		}
	}
	
		$ids = implode(',',$ids);
		$sql = "select gi.*
			from gridimage_search gi 
			where gridimage_id in ($ids)";

	$imagelist = new ImageList();
	$imagelist->_setDB($db);//to reuse the same connection
	$imagelist->_getImagesBySql($sql);

	$ids2 = array();
	foreach ($imagelist->images as $idx => $image) {
		$ids2[$image->gridimage_id]=$idx;
	}

	$results = array();
	foreach ($tags as $tag => $row) {
		$row['description']= str_replace("|",'',$row['description']);
		
		$result = array('tag'=>$tag,'resultCount'=>$row['images'],'description'=>$row['description'],'grouping'=>$row['grouping']);

		$i = explode(',',str_replace(' ','',$row['ids']));
		$ids = array_slice($i,0,4);
		foreach ($ids as $id) {
			if ($imagelist->images[$ids2[$id]])
				$result['images'][] = $imagelist->images[$ids2[$id]];		
		}
		$results[] = $result;
	}

	$smarty->assign_by_ref('results', $results);
			
	$smarty->assign('example',1);
}

$smarty->display($template, $cacheid);

