<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 6407 2010-03-03 20:44:37Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

$results = array();


if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);
	
	$fuzzy = !empty($_GET['f']);
	
	$sphinx = new sphinxwrapper($q);

	$sphinx->pageSize = $pgsize = 15;

	
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}
	

		
		$offset = (($pg -1)* $sphinx->pageSize)+1;
		
		if ($offset < (1000-$pgsize) ) { 
			$sphinx->processQuery();

			$sphinx->sort = "score ASC, @relevance DESC, @id DESC";
			
			if ($fuzzy) {
				$sphinx->_getClient()->SetIndexWeights(array('gaz'=>10,'gaz_meta'=>1));
				$ids = $sphinx->returnIds($pg,'gaz,gaz_meta');	
			} else {
				$ids = $sphinx->returnIds($pg,'gaz');	
			}
			
			if (!empty($ids) && count($ids)) {
				$where = "id IN(".join(",",$ids).")";

				$db = GeographDatabaseConnection(true);

				$limit = 25;

				$prev_fetch_mode = $ADODB_FETCH_MODE;
				$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
				$rows = $db->getAssoc("
				select id,name,gr,localities,score
				from placename_index 
				where $where
				limit $limit");

				$results['items'] = array();
				foreach ($ids as $c => $id) {
					$row = $rows[$id];
					$row['id'] = $id;
					$results['items'][] = $row;
				}
				$results['total_found'] = $sphinx->resultCount;
				$results['query_info'] = $sphinx->query_info;
				$results['copyright'] = "Great Britain results (c) Crown copyright Ordnance Survey. All Rights Reserved. 100045616";
			}
		} else {
			$results = "Search will only return 1000 results - please refine your search";
		}
	
} else {
	$results = "No query!";
}




if (!empty($_GET['callback'])) {
        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($results);

if (!empty($_GET['callback'])) {
        echo ");";
}

