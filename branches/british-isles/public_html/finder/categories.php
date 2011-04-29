<?php
/**
 * $Project: GeoGraph $
 * $Id: contributors.php 6407 2010-03-03 20:44:37Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
$template = 'finder_categories.tpl';

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q;

	$sphinx->pageSize = $pgsize = 60;

	
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}
	
	$cacheid .=".".$pg;
	
	if (isset($_REQUEST['inner'])) {
		$cacheid .= '.iframe';
		$smarty->assign('inner',1);
	}
	
	if (!$smarty->is_cached($template, $cacheid)) {
	
		$sphinx->processQuery();
		
		$ids = $sphinx->returnIds($pg,'category');	

		if (count($ids)) {
			$where = "category_id IN(".join(",",$ids).")";

			$db = GeographDatabaseConnection(true);

			$limit = 60;

			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$rows = $db->getAssoc("
			select category_id,imageclass,c as images,top
			from category_stat 
			left join category_top using(imageclass)
			where $where
			limit $limit");

			$results = array();
			foreach ($ids as $c => $id) {
				$row = $rows[$id];
				$row['category_id'] = $id;
				$results[] = $row;
			}
			$smarty->assign_by_ref('results', $results);
			$smarty->assign("query_info",$sphinx->query_info);

			if ($sphinx->numberOfPages > 1) {
				$smarty->assign('pagesString', pagesString($pg,$sphinx->numberOfPages,$_SERVER['PHP_SELF']."?q=".urlencode($q).(isset($_REQUEST['inner'])?'&amp;inner':'')."&amp;page=") );
				$smarty->assign("offset",(($pg -1)* $sphinx->pageSize)+1);
			}
			$ADODB_FETCH_MODE = $prev_fetch_mode;
		}
	}
	
	$smarty->assign("q",$sphinx->qclean);

}

$smarty->display($template,$cacheid);

