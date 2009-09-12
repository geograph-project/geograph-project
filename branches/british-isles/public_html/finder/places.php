<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
init_session();




$smarty = new GeographPage;
$template = 'finder_places.tpl';

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);
	
	$fuzzy = !empty($_GET['f']);
	
	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q.'.'.$fuzzy;

	$sphinx->pageSize = $pgsize = 15;

	
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}
	
	$cacheid .=".".$pg;
	
	if (!$smarty->is_cached($template, $cacheid)) {
		
		$offset = (($pg -1)* $sphinx->pageSize)+1;
		
		if ($offset < (1000-$pgsize) ) { 
			$sphinx->processQuery();

			$sphinx->sort = "score ASC, @relevance DESC, @id DESC";
			#$sphinx->sort = -1;
			#$sphinx->_getClient()->SetSortMode(SPH_SORT_EXPR,"(10-score) * @weight");
			
#			if (preg_match('/^\w+$/',$sphinx->q)) {
#				$sphinx->qoutput = $sphinx->q;
#				$sphinx->q = "{$sphinx->q} | {$sphinx->q}*"; //rank full matches first
#			}
		
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
				select id,name,name_2,gr,localities,localities_2,score
				from placename_index 
				where $where
				limit $limit");

				$results = array();
				foreach ($ids as $c => $id) {
					$row = $rows[$id];
					$row['id'] = $id;
					$results[] = $row;
				}
				$smarty->assign_by_ref('results', $results);
				$smarty->assign("query_info",$sphinx->query_info);

				if ($sphinx->numberOfPages > 1) {
					$smarty->assign('pagesString', pagesString($pg,$sphinx->numberOfPages,$_SERVER['PHP_SELF']."?q=".urlencode($q).($fuzzy?"&amp;f=on":'')."&amp;page=") );
					$smarty->assign("offset",$offset);
				}
				$ADODB_FETCH_MODE = $prev_fetch_mode;
			}
		} else {
			$smarty->assign("query_info","Search will only return 1000 results - please refine your search");
			$smarty->assign('pagesString', pagesString($pg,1,$_SERVER['PHP_SELF']."?q=".urlencode($q)."&amp;page=") );

		}
	}
	
	$smarty->assign("q",$sphinx->qclean);
	$smarty->assign("fuzzy",$fuzzy);
}

$smarty->display($template,$cacheid);

?>
