<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
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
$template = 'finder_multi.tpl';

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

	$fuzzy = !empty($_GET['f']);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q.'.'.$fuzzy;

	$sphinx->pageSize = $pgsize = 15;


	#$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$cacheid .=".".$pg;

	if (!$smarty->is_cached($template, $cacheid)) {

		$db = GeographDatabaseConnection(true);

		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$others = $otherstop = array();
		$inners = array();
		##########################################################

		$u2 = urlencode($sphinx->qclean);

		$others['search'] = array('title'=>'Full Image Search','url'=>"/search.php?q=$u2");
		$others['content'] = array('title'=>'Collections','url'=>"/content/?q=$u2");
		$others['places'] = array('title'=>'Placenames','url'=>"/finder/places.php?q=$u2");
		$others['users'] = array('title'=>'Contributors','url'=>"/finder/contributors.php?q=$u2");
		$others['sqim'] = array('title'=>'Images by Square','url'=>"/finder/sqim.php?q=$u2");
		$others['text'] = array('title'=>'Simple Text Search','url'=>"/fulltext.php?q=$u2");
		if ($CONF['forums']) {
			$others['discuss'] = array('title'=>'Discussions','url'=>"/finder/discussions.php?q=$u2");
		}

		$others['google'] = array('title'=>'Google Search','url'=>"http://www.google.co.uk/search?q=$u2&sitesearch=".$_SERVER['HTTP_HOST']);
		$others['gimages'] = array('title'=>'Google Images','url'=>"http://images.google.com/images?q=$u2+site:".$_SERVER['HTTP_HOST']);

		$try_words = true;

		if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\b/",$sphinx->qclean,$gr)) {
			$square=new GridSquare;
			$grid_ok=$square->setByFullGridRef($sphinx->qclean,true);

			if ($grid_ok) {
				$gr = $square->grid_reference;
				
				$otherstop['browse'] = array('title'=>'Browse Page for '.$gr,'url'=>"/gridref/$gr");
				$otherstop['links'] = array('title'=>'Links Page for '.$gr,'url'=>"/gridref/$gr/links");
				
				$old = $sphinx->qclean;

				//todo - also have special handling myriad?
				
				if ($square->natgrlen == 6) { //centisquare
					
					$inners['centi'] = array('title'=>'In Centisquare '.$sphinx->qclean,'url'=>"/gridref/$gr?inner&centi=".strtoupper($sphinx->qclean));
					
					$otherstop['self'] = array('title'=>'Multi Search for '.$gr,'url'=>"/finder/multi.php?q=$gr");
				}
				
				if ($square->natgrlen == 2) { //hectad!
					$hectad  = $square->gridsquare.intval($square->eastings/10).intval($square->northings/10);
					$otherstop['browse'] = array('title'=>'Hectad Page for '.$hectad,'url'=>"/gridref/".$hectad);
					
					$inners['browse'] = array('title'=>'In Hectad '.$hectad,'url'=>"/finder/search-service.php?q={$square->gridsquare}+$hectad&amp;inner");
					
				} else {
				
					##########################################################

					$sphinx->prepareQuery("grid_reference:{$square->grid_reference}");
					$ids = $sphinx->returnIds(1,"_images");
					if (!empty($ids) && count($ids)) {

						if (count($ids) > 15) {
							$inners['browse'] = array('title'=>'In '.$gr.' ['.$sphinx->resultCount.']','url'=>"/gridref/$gr?inner");
						} else {
							$u2 = urlencode($sphinx->qclean);

							$inners['browse'] = array('title'=>'In '.$gr.' ['.$sphinx->resultCount.']','url'=>"/finder/search-service.php?q=$u2&amp;inner");
						}
					}

					##########################################################

					$ids = $sphinx->returnIdsViewpoint($square->getNatEastings(),$square->getNatNorthings(),$square->reference_index,$square->grid_reference);
					if (!empty($ids) && count($ids)) {

						$u2 = urlencode($sphinx->q);

						$inners['taken'] = array('title'=>'Taken From '.$gr.' ['.$sphinx->resultCount.']','url'=>"/finder/search-service.php?q=$u2&amp;inner");
					}

					##########################################################

					$sphinx->prepareQuery("{$square->grid_reference} -grid_reference:{$square->grid_reference}");
					$ids = $sphinx->returnIds(1,"_images");
					if (!empty($ids) && count($ids)) {
						//search-service automatically searches nearby, if first param is a gr, so swap them
						$u2 = urlencode("-grid_reference:{$square->grid_reference} @* {$square->grid_reference}");

						$inners['mentioning'] = array('title'=>'Mentioning '.$gr.' ['.$sphinx->resultCount.']','url'=>"/finder/search-service.php?q=$u2&amp;inner");
					}

					##########################################################

					//search-service automatically searches nearby, but we can exclude the current square
					$u2 = urlencode("{$square->grid_reference} -grid_reference:{$square->grid_reference}"); 

					$inners['nearby'] = array('title'=>'Near '.$gr,'url'=>"/finder/search-service.php?q=$u2&amp;inner");

					##########################################################
				}
				
				$sphinx->qclean = $old;
				$try_words = false;
			} 
		}

		if ($try_words) {
			##########################################################

			//specifically exclude contributor column!
			$old = $sphinx->q;
			if (!preg_match('/@\w+/',$old)) {
				//todo, maybe extend this to myriad etc?
				$sphinx->q = "@(title,comment,imageclass) ".$sphinx->q;
			}

			$ids = $sphinx->returnIds($pg,'_images');
			if (!empty($ids) && count($ids)) {
				if (count($ids) == 1) {
					$inners['text'] = array('title'=>'One Text Match','url'=>"/frame.php?id=".implode(',',$ids));
				} else {
					$u3 = urlencode($sphinx->q);
					$inners['text'] = array('title'=>'Textual Matches','url'=>"/finder/search-service.php?q=$u3&amp;inner");

				}
				unset($others['text']);
			}

			$sphinx->q = $old;

			##########################################################
			//alternate: $places = $gaz->findPlacename($placename);
			$ids = $sphinx->returnIds($pg,'gaz');
			if (!empty($ids) && count($ids)) {
				if (count($ids) == 1) {
					$where = "id IN(".join(",",$ids).")";
					$row = $db->getRow("select name,gr from placename_index where $where");

					$inners['search-maker'] = array('title'=>'Images in '.$row['name'],'url'=>"/finder/search-maker.php?placename=".implode(',',$ids)."&amp;do=1&displayclass=search");

					$inners['places'] = array('title'=>'around '.$row['name'].', '.$row['gr'],'url'=>"/search.php?placename=".implode(',',$ids)."&amp;do=1&displayclass=search");
				} else {
					$inners['places'] = array('title'=>'Places matching '.$sphinx->qclean.' ['.$sphinx->resultCount.']','url'=>"/finder/places.php?q=$u2&amp;inner");

				}
				unset($others['places']);
			} else {
				//last ditch attempt incase we have a single match (farms etc not in placename index) 
				$qplacename = $db->Quote($sphinx->qclean);
				$places = $db->GetAll("
				(select (seq + 1000000) as id,`def_nam` as full_name,km_ref as gridref,`east` as e,`north` as n,1 as reference_index from os_gaz where def_nam=$qplacename limit 15) 
				UNION
				(select id,full_name,'' as gridref,e,n,reference_index from loc_placenames where full_name=$qplacename limit 15)
				");
				
				if (count($places) == 1) {
					$full_name = _utf8_decode($places[0]['full_name']);
					
					$inners['places'] = array('title'=>'around '.$full_name,'url'=>"/search.php?placename=".$places[0]['id']."&amp;do=1&displayclass=search");
					unset($others['places']);
				} elseif (count($places)) {
					require_once('geograph/conversions.class.php');
					$conv = new Conversions;
					$grs = array();
					foreach($places as $id => $row) {
						if (empty($row['gridref'])) {
							list($places[$id]['gridref'],) = $conv->national_to_gridref($row['e'],$row['n'],4,$row['reference_index']);
						}
						$grs[$places[$id]['gridref']]=1;
						
						$full_name = _utf8_decode($places[0]['full_name']);
						
						$otherstop['place'.$id] = array('title'=>'Images near '.$full_name.' in '.$places[$id]['gridref'],'url'=>"/search.php?placename=".$places[$id]['id']."&amp;do=1");
					}
					//hmm what can we do with THEM...
					if (count($grs)) {
						$inners['places'] = array('title'=>'In likely squares','url'=>"/finder/search-service.php?q=grid_reference:".implode('%7C',array_keys($grs))."&amp;inner");
					}
				}
			}

			##########################################################

			$ids = $sphinx->returnIds($pg,'user');
			if (!empty($ids) && count($ids)) {
				if (count($ids) == 1) {
					$where = "user_id IN(".join(",",$ids).")";
					$row = $db->getRow("select realname from user where $where");

					$inners['users'] = array('title'=>'Images by '.$row['realname'],'url'=>"/search.php?user_id=".implode(',',$ids)."&amp;do=1&displayclass=search");
				} else {
					$inners['users'] = array('title'=>'Contributors Matching '.$sphinx->qclean.' ['.$sphinx->resultCount.']','url'=>"/finder/contributors.php?q=$u2&amp;inner");

				}
				unset($others['users']);
			}

			##########################################################

			$ids = $sphinx->returnIds($pg,'category');
			if (!empty($ids) && count($ids)) {
				if (count($ids) == 1) {
					$where = "category_id IN(".join(",",$ids).")";
					$row = $db->getRow("select imageclass from category_stat where $where");

					$inners['category'] = array('title'=>'Category ',$row['imageclass'],'url'=>"/search.php?imageclass=".urlencode($row['imageclass'])."&amp;do=1&displayclass=search");
				} else {
					$inners['category'] = array('title'=>'Images in similar categories','url'=>"/finder/search-service.php?q=category:$u2&amp;inner");

				}
			}

			##########################################################
		}
		
		if (!empty($otherstop)) {
			$others = array_merge($otherstop,$others);
		}
		
		$smarty->assign_by_ref("others",$others);
		$smarty->assign_by_ref("inners",$inners);
	}

	$smarty->assign("q",$sphinx->qclean);
	$smarty->assign("fuzzy",$fuzzy);
}

$smarty->display($template,$cacheid);
