<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
$template = 'finder_contributors.tpl';

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q;

	$sphinx->pageSize = $pgsize = 15;

	
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}
	
	$cacheid .=".".$pg;
	
	if (!$smarty->is_cached($template, $cacheid)) {
	
		$sphinx->processQuery();
		
		$ids = $sphinx->returnIds($pg,'user');	

		if (count($ids)) {
			$where = "user_id IN(".join(",",$ids).")";

			$db = GeographDatabaseConnection(true);

			$limit = 25;

			$prev_fetch_mode = $ADODB_FETCH_MODE;
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$rows = $db->getAssoc("
			select user.user_id,nickname,realname,images
			from user 
			left join user_stat using (user_id)
			where $where
			limit $limit");

			$results = array();
			foreach ($ids as $c => $id) {
				$row = $rows[$id];
				$row['user_id'] = $id;
				$results[] = $row;
			}
			$smarty->assign_by_ref('results', $results);
			$smarty->assign("query_info",$sphinx->query_info);

			if ($sphinx->numberOfPages > 1) {
				$smarty->assign('pagesString', pagesString($pg,$sphinx->numberOfPages,$_SERVER['PHP_SELF']."?q=".urlencode($q)."&amp;page=") );
				$smarty->assign("offset",(($pg -1)* $sphinx->pageSize)+1);
			}
			$ADODB_FETCH_MODE = $prev_fetch_mode;
		}
	}
	
	$smarty->assign("q",$sphinx->qclean);

}

if (isset($_GET['popup'])) {
	$smarty->assign("popup",1);
}

$smarty->display($template,$cacheid);

?>
