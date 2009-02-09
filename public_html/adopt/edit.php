<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/mapmosaic.class.php');
init_session();


$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$template='adopt_edit.tpl';

$cacheid='';

$db = NewADOConnection($GLOBALS['DSN']);

if (isset($_GET['gsid'])) {
	$template='adopt_edit_inner.tpl';
	
	$square = new GridSquare();
	$square->loadFromId(intval($_GET['gsid']));
	
	if (isset($_GET['gid'])) {
		$smarty->caching=0;
		$template='_adopt_cell.tpl';
		
		$image=new GridImage($_GET['gid']);
		
		if ($image->gridsquare_id == $square->gridsquare_id) {
		
			$hectad = strtoupper(preg_replace('/[^\w]/','',$_GET['hectad']));
			$smarty->assign_by_ref('hectad',$hectad);

			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			if(!($row = $db->getRow("
				SELECT * FROM hectad_assignment
				WHERE user_id = {$USER->user_id} AND status = 'accepted'
				AND hectad = '$hectad'"))) {

				die("invalid hectad");
			}


			$db->Execute("INSERT INTO gridsquare_assignment 
			SET hectad_assignment_id = {$row['hectad_assignment_id']}, gridsquare_id = {$square->gridsquare_id}, gridimage_id = {$image->gridimage_id}
			ON DUPLICATE KEY UPDATE gridimage_id = {$image->gridimage_id}");
			
		} else {
			die("invalid assignment");
		}
		
		$image->imagecount = $square->imagecount;
		$smarty->assign_by_ref('image',$image);
		$smarty->assign('x',intval($_GET['gx']));
		$smarty->assign('y',intval($_GET['gy']));
		
	} else {
		$template='adopt_edit_inner.tpl';
		
		$images=$square->getImages($USER->user_id,'',"order by submitted desc limit 20");

		$smarty->assign_by_ref('square',$square);
		$smarty->assign_by_ref('images',$images);
	}
	
} elseif (isset($_GET['hectad'])) {
	$template='adopt_edit.tpl';

	$hectad = strtoupper(preg_replace('/[^\w]/','',$_GET['hectad']));
	$smarty->assign_by_ref('hectad',$hectad);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	if(!($row = $db->getRow("
		SELECT * FROM hectad_assignment
		WHERE user_id = {$USER->user_id} AND status = 'accepted'
		AND hectad = '$hectad'"))) {

		$smarty->display("static_404.tpl");
		exit;
	}
	$smarty->assign_by_ref('assignment',$row);
	$square = new GridSquare();
	$grid_ok=$square->setByFullGridRef($hectad,false,true);

	if (empty($square->x) && empty($square->y)) {
		//even all at sea squares return a X & Y
		die("invalid hectad, please contact us if this is unexpected");
	}

	$x = $square->x - 5; //hectad references are centered
	$y = $square->y - 5;
	$stepdist = 9;
	
	$scanleft=$x;
	$scanright=$x+$stepdist;
	$scanbottom=$y;
	$scantop=$y+$stepdist;

	$rectangle = "'POLYGON(($scanleft $scanbottom,$scanright $scanbottom,$scanright $scantop,$scanleft $scantop,$scanleft $scanbottom))'";

	
	$sql="select gi.*,gs.gridsquare_id,imagecount,percent_land,has_geographs,gs.grid_reference,ga.gridimage_id
		from gridsquare gs
		left join gridsquare_assignment ga on (gs.gridsquare_id = ga.gridsquare_id and hectad_assignment_id = {$row['hectad_assignment_id']})
		left join gridimage_search gi on (gi.gridimage_id = ga.gridimage_id)
		where CONTAINS( GeomFromText($rectangle),	gs.point_xy)
		group by gs.gridsquare_id";
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$squares = $db->getAll($sql);
	
	$stats = $grid = array();
	$x1 = 9999999;
	$x2 = 0;
	#print_r($sql);print "<hr>";
	#print_r($squares);
	foreach ($squares as $i => $row) {
		#print_r($row);print "<br>";
		if (is_null($row['gridimage_id']) && $row['imagecount']) {
			$sql="select * from gridimage_search where grid_reference='{$row['grid_reference']}'
				and moderation_status in ('accepted','geograph') order by moderation_status+0 desc,seq_no limit 1";
			$row2=$db->GetRow($sql);
			foreach($row2 as $k=>$v) {
				$row[$k] = $v;
			}
		} else {
			#print_r($row);print "<hr>";exit;
		}
		if (count($row) && !empty($row['x']))
		{
			$gridimage=new GridImage;
			$gridimage->fastInit($row);
			
			$grid[$row['y']][$row['x']]=$gridimage;
			$x1 = min($row['x'],$x1);
			$x2 = max($row['x'],$x2);
			
			@$stats[$row['realname']]++;
		}
	}

	$smarty->assign_by_ref('grid',$grid);

	$ys = array_keys($grid);
	$smarty->assign_by_ref('ys',$ys);

	$xs = range($x1,$x2);
	$smarty->assign_by_ref('xs',$xs);

	$smarty->assign('stats',$stats);
	
	//get a token to show a suroudding geograph map
	$mosaic=new GeographMapMosaic;
	$smarty->assign('map_token', $mosaic->getGridSquareToken($square));
	$smarty->assign_by_ref('square',$square);

} else {
	$template="static_404.tpl";

}


$smarty->display($template,$cacheid);

?>
