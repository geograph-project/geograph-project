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

if (empty($_GET['callback'])) {
        header('Access-Control-Allow-Origin: *');
}


$results = array();


if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);


		$sphinx = new sphinxwrapper($q);

		$sphinx->pageSize = $pgsize = 1000;


		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}


		$offset = (($pg -1)* $sphinx->pageSize);

		if ($offset <= (1000-$pgsize) ) { 
			$rbefore = "/(\w+)\s+".preg_quote($sphinx->q,'/')."\b/i";
			$rafter = "/\b".preg_quote($sphinx->q,'/')."\s+(\w+)/i";
			$results = array();

			$original = $sphinx->q;

			if (!empty($_GET['new'])) {
				if (!empty($_GET['f'])) {
					$sphinx->q .= " ".$_GET['f'];
				}
				$ids = $sphinx->returnIds($pg,'sample8');
			} else {
				$sphinx->q = "@(title,comment) \"{$sphinx->q}\"";
				$ids = $sphinx->returnIds($pg,'_images');
			}

			if (!empty($ids) && count($ids)) {
				$where = "gridimage_id IN(".join(",",$ids).")";

				if (empty($db))
					$db = GeographDatabaseConnection(true);

				$limit = $pgsize;

				$prev_fetch_mode = $ADODB_FETCH_MODE;
				$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
				$rows = $db->getAssoc("
				select gridimage_id,comment,title
				from gridimage_search
				where $where
				limit $limit");


				foreach ($rows as $gid => $row) {
					if (preg_match_all($rbefore,$row['title']." | ".$row['comment'],$m)) {
						foreach ($m[0] as $str) {
							@$results[$str]++;
						}
					}
					if (preg_match_all($rafter,$row['title']." | ".$row['comment'],$m)) {
						foreach ($m[0] as $str) {
							@$results[$str]++;
						}
					}
				}
				$results[$original] = $sphinx->resultCount;
			}
		} else {
			$results = "Search will only return 1000 results - please refine your search";
		}

} else {
	$results = "No query!";
}




outputJSON($results);


