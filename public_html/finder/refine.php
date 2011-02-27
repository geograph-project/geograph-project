<?php
/**
 * $Project: GeoGraph $
 * $Id: sqim.php 6832 2010-09-15 20:07:50Z geograph $
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



if (!empty($_GET['more'])) {
	$_GET['m'] = (empty($_GET['m'])?'':($_GET['m'].',')).intval($_GET['more']);
	unset($_GET['more']);
}
if (!empty($_GET['less'])) {
	$_GET['l'] = (empty($_GET['l'])?'':($_GET['l'].',')).intval($_GET['less']);
	unset($_GET['less']);
}

$smarty = new GeographPage;
$template = 'finder_refine.tpl';



$cacheid=md5(serialize($_GET));

if (!$smarty->is_cached($template, $cacheid)) {
	
	$extra = array();
	
	$square = new GridSquare;
	if (!empty($_GET['g'])) {

		$grid_ok=$square->setByFullGridRef($_GET['g'],false,true);

		if ($grid_ok) {
			$smarty->assign("gridref",$square->grid_reference);
			$extra[] = "g={$square->grid_reference}";
		}
	} 

	if (!empty($square->grid_reference)) {
		if (!empty($_GET['q'])) {
			$q=trim($_GET['q']);

			$sphinx = new sphinxwrapper($q);

			$smarty->assign("q",$sphinx->qclean);
			$extra[] = "q=".urlencode($sphinx->qclean);
		} else {
			$sphinx = new sphinxwrapper();
		}

		$sphinx->pageSize = $pgsize = 20;

		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}
		
		$db = GeographDatabaseConnection(true);
		
		#####################################################
		
		$bits = $more = $less = array();
		
		$sphinx->qoutput = $sphinx->q;
		
		$terms = $db->CacheGetAll(3600*24,"SELECT gridimage_id,result FROM gridimage_search INNER JOIN at_home_result t USING (gridimage_id) WHERE grid_reference = '{$square->grid_reference}'");
		
		$g2t = array();
		foreach ($terms as $idx => $row) {
			$g2t[$row['gridimage_id']][$row['result']] = 1;
		}
		if (!empty($_GET['m'])) {
			$more = explode(',',$_GET['m']);
			$extra[] = "m=".implode(',',$more);
			$plus = array();
			foreach ($more as $id) {
				if (!empty($g2t[trim($id)])) {
					$plus = $plus + array_keys($g2t[trim($id)]);
				}
			}
			#if (!empty($plus)) {
			#	$bits = $bits + $plus;
			#}
		}
		if (!empty($_GET['l'])) {
			$less = explode(',',$_GET['l']);
			$extra[] = "l=".implode(',',$less);
			$minus = array();
			foreach ($g2t as $id => $row) {
				if (!in_array($id,$less)) { //the idea here is to promote everything - except the 'buried' keywords 
					$minus = $minus + array_keys($row);
				}
			}
			#if (!empty($minus)) {
			#	$bits = $bits + $minus;
			#}
		}
			
		if (!empty($sphinx->q)) {
			$sphinx->qoutput = $sphinx->q = "({$sphinx->q}) @grid_reference {$square->grid_reference}";
		} else {
			$sphinx->qoutput = $sphinx->q = "@grid_reference {$square->grid_reference}";
		}
		if (!empty($plus)) {
			$sphinx->q = "({$sphinx->q}) ( {$sphinx->q} | (".implode(') | (',$plus)."))";
		}
		if (!empty($minus)) {
			$sphinx->qminus = "({$sphinx->q}) ( {$sphinx->q} | (".implode(') | (',$minus)."))";
			$sphinx->pageSize = 500;
		}
		
		//TODO -remove - for debuging only!
		#$sphinx->qoutput = $sphinx->q;
		
		#####################################################
		
		$offset = (($pg -1)* $sphinx->pageSize)+1;
		
		if ($offset < (1000-$pgsize) ) { 
			##$sphinx->processQuery();
			
			if (!empty($plus) || !empty($minus) || !empty($_GET['q'])) {
				$cl = $sphinx->_getClient();
				if (!empty($plus)) {
					$cl->SetRankingMode(SPH_RANK_WORDCOUNT);
				}
				
				$ids = $sphinx->returnIds($pg,'_images');
				
				if (!empty($ids) && count($ids) && !empty($minus)) {
					$sorted = array();
					foreach ($ids as $id) {
						$sorted[$id] = $sphinx->res['matches'][$id]['weight'];
					}
					#print_r($sphinx->q);
					#print_r($sorted);
					#print_r($sphinx->qminus);
					$sphinx->q = $sphinx->qminus;
					$cl->SetRankingMode(SPH_RANK_MATCHANY);
					if ($sphinx->returnIds($pg,'_images')) {
						#print count($sphinx->res['matches'])." demots ";
						reset($sorted);
						list(, $first) = each($sorted);
						$step = $first/(count($sphinx->res['matches'])-10);
						$delta = $step*10;
						foreach ($sphinx->res['matches'] as $id => $row) {
							if (isset($sorted[$id])) {
								$sorted[$id] -= $delta;
							}
						#	print "demoting $id ";
							$delta += $step; 
						}
						
						arsort($sorted);
						#print_r($sorted);
						$ids = array_slice(array_keys($sorted),0, $pgsize);
					}
				}
				
			} else {
				
				if (count($g2t) > 20) {
				
					//cheat and use ones termed!
					$ids = array_slice(array_keys($g2t),0,$pgsize);
				} else {
					#$sphinx->sort = "@id ASC"; //most likly to be 'termed'
					$ids = $sphinx->returnIds($pg,'_images');
				}
			}
			
				
			
			if (!empty($ids) && count($ids)) {
				

				$images=new ImageList();
				$images->getImagesByIdList($ids);

				foreach ($images->images as $idx => $row) {
					if (in_array($row->gridimage_id,$less)) {
						$images->images[$idx]->less = 1;
					} elseif (in_array($row->gridimage_id,$more)) {
						$images->images[$idx]->more = 1;
					} elseif (!empty($g2t[$row->gridimage_id])) {
						$images->images[$idx]->terms = 1;
					}
				}
				$smarty->assign_by_ref('results', $images->images);
				$smarty->assign("query_info",$sphinx->query_info);

				$ADODB_FETCH_MODE = $prev_fetch_mode;
			}
		} else {
			$smarty->assign("query_info","Search will only return 1000 results - please refine your search");
		}
		$smarty->assign("extra",implode("&",$extra));
	} else {
		$smarty->assign("query_info","Invalid Grid Reference");
	}
	
	
}

$smarty->display($template,$cacheid);

