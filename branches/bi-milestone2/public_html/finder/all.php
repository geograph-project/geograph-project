<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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
$template = 'finder_all.tpl';

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q."..";

	$sphinx->pageSize = $pgsize = 15;

	
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}
	
	$cacheid .=".".$pg;
	
	if (!$smarty->is_cached($template, $cacheid)) {
		
		$offset = (($pg -1)* $sphinx->pageSize)+1;
		
		#$sphinx->q = "@@relaxed ".$sphinx->q;
		$gaz_ids = $sphinx->returnIds($pg,'gaz,gridimage');
		print "<pre>";
		print_r($sphinx->res);
		exit;
		
		if ($offset < (1000-$pgsize) ) { 
			$sphinx->processQuery();
			$gaz_ids = $sphinx->returnIds($pg,'gaz');	
			$user_ids = $sphinx->returnIds($pg,'user');	
			$images_ids = $sphinx->returnIds($pg,'gi_stemmed,gi_delta_stemmed');	
			if (1) {
				$post_ids = $sphinx->returnIds($pg,'post_stemmed,post_delta_stemmed');	
			}
			#print "<pre>";
			#print "gaz:".join(',',$gaz_ids)."\n";
			#print "user:".join(',',$user_ids)."\n";
			#print "iamge:".join(',',$images_ids)."\n";
			#print "post:".join(',',$post_ids)."\n";
			
			if (count($gaz_ids)) {
				$where = "id IN(".join(",",$gaz_ids).")";

				$db=NewADOConnection($GLOBALS['DSN2']);

				$limit = 25;

				$prev_fetch_mode = $ADODB_FETCH_MODE;
				$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
				$rows = $db->getAssoc("
				select id,name,gr,localities
				from placename_index 
				where $where
				limit $limit");

				$results = array();
				foreach ($gaz_ids as $c => $id) {
					$row = $rows[$id];
					$row['id'] = $id;
					$results[] = $row;
				}
				$smarty->assign_by_ref('gaz_results', $results);
				$smarty->assign("query_info",$sphinx->query_info);

				if ($sphinx->numberOfPages > 1) {
					$smarty->assign('pagesString', pagesString($pg,$sphinx->numberOfPages,$_SERVER['PHP_SELF']."?q=".urlencode($q)."&amp;page=") );
					$smarty->assign("offset",$offset);
				}
				$ADODB_FETCH_MODE = $prev_fetch_mode;
			}
		} else {
			$smarty->assign("query_info","Search will only return 1000 results - please refine your search");
			$smarty->assign('pagesString', pagesString($pg,1,$_SERVER['PHP_SELF']."?q=".urlencode($q)."&amp;page=") );

		}
			
	}
	
	$smarty->assign("q",$sphinx->q);

}

$smarty->display($template,$cacheid);

?>
