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
$template = 'finder_grouped.tpl';

$smarty->assign('numbers',array(-1=>'sequence',0=>'any')+array_combine(range(1,8),range(1,8)));
		
$groups = array('auser_id'=>'Contributor','atakenyear'=>'Year','takendays'=>'Day','classcrc'=>'Category','amyriad'=>'Myriad','ahectad'=>'Hectad','scenti'=>'Centisquare','all'=>'Composite','exact'=>'Composite Exact');
//'agridsquare'=>'Grid Square' ==> currently a bigint :(

$smarty->assign('groups',$groups);

$number = (isset($_GET['number']) && is_numeric($_GET['number']))?intval($_GET['number']):3;
$number = min(8,max(-1,$number));
$smarty->assign('number',$number);
$extra = array("number=$number");


$group = (isset($_GET['group']) && ctype_lower($_GET['group']))?$_GET['group']:'auser_id';
$smarty->assign('group',$group);
$extra[] = "group=$group";


if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

	$sphinx = new sphinxwrapper($q);
	$extra[] = "q=".urlencode($sphinx->q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q.".$group$number";

	$sphinx->pageSize = $pgsize = ($number == -1)?12:30;
	if(!empty($_GET['more'])) {
		$sphinx->pageSize = $pgsize = 150;
		 $cacheid .= '.more';
	}
	
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}
	
	$cacheid .=".".$pg;
	
	if (isset($_REQUEST['inner'])) {
		$cacheid .= '.iframe';
		$smarty->assign('inner',1);
		$extra[] = "inner";
	}
	
	if (!$smarty->is_cached($template, $cacheid)) {
	
		$sphinx->processQuery();

		$client = $sphinx->_getClient();

		if ($number == -1) {
			if ($group == 'all' || $group == 'exact') {
				$bits = array();
				$bits[] = "uniqueserial(atakenyear)";
				$bits[] = "uniqueserial(takendays)";
				$bits[] = "uniqueserial(classcrc)";
				$bits[] = "uniqueserial(scenti)";
				if (!preg_match('/user_id/',$q)) {
					$bits[] = "uniqueserial(auser_id)";
				}
				$client->setSelect(implode('+',$bits)." as myint");
			} elseif (in_array($group,array_keys($groups))) {
				$client->setSelect("uniqueserial($group) as myint");
			}
			$sphinx->sort = "myint ASC,sequence ASC";
		} elseif ($number) {
			if ($group == 'all' || $group == 'exact') {
				$bits = array();
				$bits[] = "withinfirstx(atakenyear,$number)";
				$bits[] = "withinfirstx(takendays,$number)";
				$bits[] = "withinfirstx(classcrc,$number)";
				$bits[] = "withinfirstx(scenti,$number)";
				if (!preg_match('/user_id/',$q)) {
					$bits[] = "withinfirstx(auser_id,$number)";
				}
				$client->setSelect(implode('+',$bits)." as myint,id as group");
				$values = array();
				$values[] = count($bits);
				if ($group == 'all')
					$values[] = count($bits)-1;
				$client->setFilter('myint',$values);
	                        $sphinx->sort = 'myint DESC,@id DESC';
			} elseif (in_array($group,array_keys($groups))) {
				$client->setSelect("withinfirstx($group,$number) as myint,$group as group");
				$client->setFilter('myint',array(1));
				$sphinx->sort = "$group DESC";
			}
		} else {
			$group = '';
		}

		$ids = $sphinx->returnIds($pg,'_images');

		if (count($ids)) {
			$where = "gridimage_id IN(".join(",",$ids).")";

			$db = GeographDatabaseConnection(true);

			$limit = $pgsize;

			$prev_fetch_mode = $ADODB_FETCH_MODE;
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$rows = $db->getAssoc("
			select gridimage_id,realname,user_id,title,grid_reference,imagetaken
			from gridimage_search
			where $where
			limit $limit");

			if ($group)
				$matches = $sphinx->res['matches'];

			$results = array();
			foreach ($ids as $c => $id) {
				$row = $rows[$id];
				$row['gridimage_id'] = $id;
				if ($group)
					$row['group'] = $matches[$id]['attrs'][$group];
				$gridimage = new GridImage;
                                $gridimage->fastInit($row);
				$results[] = $gridimage;
			}

			//todo - sort by group?

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

}


$smarty->display($template,$cacheid);

