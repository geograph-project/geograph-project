<?php
/**
 * $Project: GeoGraph $
 * $Id: clusters.php 5786 2009-09-12 10:18:04Z barry $
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
$template = 'finder_dblock.tpl';

if (!empty($_GET['gridref']) || !empty($_GET['p'])) {
	$q=@trim($_GET['q']);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q;
	
	$square=new GridSquare;


	//set by grid components?
	if (isset($_GET['p']))
	{	
		//p=900y + (900-x);
		$p = intval($_GET['p']);
		$x = ($p % 900);
		$y = ($p - $x) / 900;
		$x = 900 - $x;
		$grid_ok=$square->loadFromPosition($x, $y, true);
		$smarty->assign('gridrefraw', $square->grid_reference);
	}

	//set by grid components?
	elseif (isset($_GET['setpos']))
	{	
		$grid_ok=$square->setGridPos($_GET['gridsquare'], $_GET['eastings'], $_GET['northings']);
		$smarty->assign('gridrefraw', $square->grid_reference);
	}

	//set by grid ref?
	elseif (isset($_GET['gridref']) && strlen($_GET['gridref']))
	{
		$grid_ok=$square->setByFullGridRef($_GET['gridref']);
	}
	
	if ($grid_ok && $square->reference_index == 1)
	{
		$smarty->assign('gridref', stripslashes($_GET['gridref']));

		$square->rememberInSession();

		$cacheid .= "|".$square->grid_reference;

	
		$sphinx->pageSize = $pgsize = 15;


		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}

		$cacheid .=".".$pg;

		if (!$smarty->is_cached($template, $cacheid)) {

			
			$e = intval($square->getNatEastings()/4000)*4;
			$n = intval($square->getNatNorthings()/3000)*3;
			
			$dblock = sprintf("GB-%d-%d",$e*1000,$n*1000);
			$smarty->assign("dblock",$dblock);
			
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			$grs = array();

			for($x=$e;$x<=$e+4;$x++) {
				for($y=$n;$y<=$n+3;$y++) {
					list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,4,$square->reference_index,false);
					if (strlen($gr2) > 4)
						$grs[] = $gr2;
				}
			}
			$sphinx->q .= " @grid_reference (".join(" | ",$grs).")";

			$sphinx->sort = "@weight DESC, @id ASC"; //this is the WITHIN GROUP ordering 

			$client = $sphinx->_getClient();
			$client->SetArrayResult(true);

			$sphinx->SetGroupBy('agridsquare', SPH_GROUPBY_ATTR, 'wgs84_long ASC, wgs84_lat DESC');
			$res = $sphinx->groupByQuery($pg,'_images');



			$imageids = array();

			if (!empty($res['matches'])) {
				foreach ($res['matches'] as $idx => $row) {
					$imageids[$idx] = $row['id'];
				}
			}
			
			if (!empty($imageids)) {

				$imagelist = new ImageList();
				$imagelist->getImagesByIdList($imageids,"gridimage_id,title,realname,user_id,grid_reference,credit_realname,x,y");

				$results = array();
				foreach ($imagelist->images as $idx => $image) {

					$res_id = array_search($image->gridimage_id,$imageids);	
					unset($imageids[$res_id]); //array_search finds them in order, so remove them once used to move down the list

					$image->count = $res['matches'][$res_id]['attrs']['@count'];
					$results[$image->x][$image->y] = $image;

				}
			
				$smarty->assign_by_ref('results', $results);
				$smarty->assign("query_info",$sphinx->query_info);

				if ($sphinx->numberOfPages > 1) {
					$smarty->assign('pagesString', pagesString($pg,$sphinx->numberOfPages,$_SERVER['PHP_SELF']."?q=".urlencode($q)."&amp;page=") );
					$smarty->assign("offset",(($pg -1)* $sphinx->pageSize)+1);
				}


				if (count($imagelist->images) < 9) {
					$smarty->assign('thumbw',213);
					$smarty->assign('thumbh',160);
				} else {
					$smarty->assign('thumbw',120);
					$smarty->assign('thumbh',120);
				}
				
				list($x,$y) = $conv->national_to_internal($e*1000,$n*1000,$square->reference_index);
				
				$smarty->assign("x",$x);
				$smarty->assign("y",$y);
				$smarty->assign("xarr",range($x,$x+3));
				$smarty->assign("yarr",range($y+2,$y));
			}
		}
	}

	$smarty->assign("q",$sphinx->qclean);

}

if (isset($_GET['popup'])) {
	$smarty->assign("popup",1);
}

$smarty->display($template,$cacheid);

