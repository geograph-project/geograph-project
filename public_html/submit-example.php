<?php
/**
 * $Project: GeoGraph $
 * $Id: staticpage.php 6962 2010-12-09 14:56:48Z geograph $
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

#$USER->mustHavePerm("basic");


$template='submit-example.tpl';

if (!$smarty->is_cached($template)) {


$square=new GridSquare;

$_POST['grid_reference'] = "TQ7040";

$selectedtab = 1;
$smarty->assign('tab', $selectedtab);

$smarty->assign('step', 1);



		$ok= $square->setByFullGridRef($_POST['grid_reference']);
		
		//preserve inputs in smarty
		$smarty->assign('grid_reference', $grid_reference = $_POST['grid_reference']);


		$smarty->assign('gridsquare', $square->gridsquare);
		$smarty->assign('eastings', $square->eastings);
		$smarty->assign('northings', $square->northings);
		$smarty->assign('gridref', $square->grid_reference);
	
		//store other useful info about the square
		$smarty->assign('imagecount', $square->imagecount);

			$smarty->assign('prefixes', $square->getGridPrefixes());
			$smarty->assign('kmlist', $square->getKMList());


			require_once('geograph/rastermap.class.php');

			$rastermap = new RasterMap($square,true);
			if (empty($_REQUEST['service']) && !empty($_COOKIE['MapSrv'])) { 
				$_REQUEST['service'] = $_COOKIE['MapSrv'];
			}
			if (isset($_REQUEST['service'])) {
				if ($_REQUEST['service'] == 'Google') {
					$rastermap->setService('Google');
				} elseif ($_REQUEST['service'] == 'OS50k') {
					$rastermap->setService('OS50k');
				}
			}
			if (isset($_POST['photographer_gridref'])) {
				$square2=new GridSquare;
				$ok= $square2->setByFullGridRef($_POST['photographer_gridref']);
				$rastermap->addViewpoint($square2->nateastings,$square2->natnorthings,$square2->natgrlen,$_POST['view_direction']);
			} elseif (isset($_POST['view_direction']) && strlen($_POST['view_direction']) && $_POST['view_direction'] != -1) {
				$rastermap->addViewDirection($_POST['view_direction']);
			}
			$smarty->assign_by_ref('rastermap', $rastermap);

			$smarty->assign_by_ref('square', $square);


			$smarty->assign('reference_index', $square->reference_index);

			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
			list($lat,$long) = $conv->gridsquare_to_wgs84($square);
			$smarty->assign('lat', $lat);
			$smarty->assign('long', $long);
			
			$rastermap->addLatLong($lat,$long);

			$images=$square->getImages($USER->user_id,'',"order by submitted desc limit 6");
			$square->totalimagecount = count($images);
			
			$smarty->assign('shownimagecount', $square->totalimagecount);
			
			if ($square->totalimagecount == 6) {
				$square->totalimagecount = $square->getImageCount($USER->user_id);
			}			
			
			$smarty->assign('totalimagecount', $square->totalimagecount);
				
			if ($square->totalimagecount > 0) {
				$smarty->assign_by_ref('images', $images);
			}
			
			$dirs = array (-1 => '');
			$jump = 360/16; $jump2 = 360/32;
			for($q = 0; $q< 360; $q+=$jump) {
				$s = ($q%90==0)?strtoupper(heading_string($q)):ucwords(heading_string($q));
				$dirs[$q] = sprintf('%s : %03d deg (%03d > %03d)',
					str_pad($s,16,' '),
					$q,
					($q == 0?$q+360-$jump2:$q-$jump2),
					$q+$jump2);
			}
			$dirs['00'] = $dirs[0];
			$smarty->assign_by_ref('dirs', $dirs);


$smarty->assign('preview_url', "http://s0.geograph.org.uk/photos/25/31/253172_53cebaff.jpg");


			$token=new Token;
			$token->setValue("g", !empty($_POST['grid_reference'])?$_POST['grid_reference']:$square->grid_reference);
			//$token->setValue("p", $_POST['photographer_gridref']);
			//$token->setValue("v", $_POST['view_direction']);
			$smarty->assign('reopenmaptoken', $token->getToken());


		$tags = new Tags;
			$tags->assignPrimarySmarty($smarty);	


$smarty->assign('upload_id', '123456789');

}


$smarty->display($template);

