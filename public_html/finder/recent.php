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
$template = 'finder_recent.tpl';

$cacheid = intval($_GET['page']);
$extra = array();

$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
	$src = 'src';//revert back to standard non lazy loading
}


if (true) {
	if (!empty($_GET['q'])) {
		$q=trim($_GET['q']);
	} else {
		$q = '';
	}

	$sphinx = new sphinxwrapper($q);
	if (!empty($sphinx->q))
		$extra[] = "q=".urlencode($sphinx->q);

	//gets a cleaned up verion of the query (suitable for filename etc)
	$cacheid = $sphinx->q;

	$sphinx->pageSize = $pgsize = 100;

	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$cacheid .=".".$pg.$src;

	if (isset($_REQUEST['inner'])) {
		$cacheid .= '.iframe';
		$smarty->assign('inner',1);
		$extra[] = "inner";
	}

        if (!empty($_REQUEST['status']) && preg_match('/^\w+$/',$_REQUEST['status'])) {
                $cacheid .= '.'.$_REQUEST['status'];
                $smarty->assign('status',$_REQUEST['status']);
                $extra[] = "status=".$_REQUEST['status'];

		$sphinx->q .= " @status {$_REQUEST['status']}";
        }


	if (!$smarty->is_cached($template, $cacheid)) {

		$sphinx->processQuery();

		$client = $sphinx->_getClient();

		$db = GeographDatabaseConnection(true);

		$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
		$client->SetIDRange($max-1000,$max+10);

			$bits = array();
			$bits[] = "uniqueserial(atakenyear)";
			$bits[] = "uniqueserial(takendays)";
			$bits[] = "uniqueserial(ahectad)";
			$bits[] = "uniqueserial(classcrc)";
			$bits[] = "uniqueserial(scenti)";
			$bits[] = "uniqueserial(uint(agridsquare))";
			if (!preg_match('/user_id/',$q)) {
				$bits[] = "uniqueserial(auser_id)";
			}
			$client->setSelect(implode('+',$bits)." as myint");
			$sphinx->sort = "myint ASC,sequence ASC";

		$ids = $sphinx->returnIds($pg,'_images');

		if (count($ids)) {
			$where = "gridimage_id IN(".join(",",$ids).")";


			$limit = $pgsize;

			$prev_fetch_mode = $ADODB_FETCH_MODE;
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$rows = $db->getAssoc("
			select gridimage_id,realname,user_id,title,grid_reference,imagetaken
			from gridimage_search
			where $where
			limit $limit");

			$results = array();
			foreach ($ids as $c => $id) {
				$row = $rows[$id];
				$row['gridimage_id'] = $id;
				$gridimage = new GridImage;
                                $gridimage->fastInit($row);
				$results[] = $gridimage;
			}

			$smarty->assign_by_ref('results', $results);
			$smarty->assign("query_info",$sphinx->query_info);

			if ($sphinx->numberOfPages > 1) {
				$smarty->assign('pagesString', pagesString($pg,$sphinx->numberOfPages,$_SERVER['PHP_SELF']."?".implode('&amp;',$extra)."&amp;page=") );
				$smarty->assign("offset",(($pg -1)* $sphinx->pageSize)+1);
			}
			$ADODB_FETCH_MODE = $prev_fetch_mode;
		}
	}

	$smarty->assign("q",$sphinx->qclean);
	$smarty->assign("src",$src);
}


$smarty->display($template,$cacheid);

