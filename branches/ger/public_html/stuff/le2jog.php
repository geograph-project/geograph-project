<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 2961 2007-01-15 14:49:33Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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





$template='stuff_le2jog.tpl';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //1 day cache 

$cacheid = isset($_GET['hide']);


//regenerate?
if (!$smarty->is_cached($template, $cacheid)) {
	
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	$g_image=new GridImage;
	$g_image->_setDB($db);
	$square=new GridSquare;
	$square->_setDB($db);

	if (isset($_GET['kml'])) {
		if ($USER->hasPerm("admin")) {
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;	
			$kml = new kmlFile();
			$document = $kml->addChild('Document');
			$document->setItem('name','geotest');
		} else {
			unset($_GET['kml']);
		}
	}


	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$all = $db->getAssoc("select post_id,poster_name,poster_id,post_text,post_time from geobb_posts where topic_id = 822 order by post_id");

	$posts = array();
	$lx = 0;
	foreach ($all as $id => $row) {
		if (preg_match_all('/\[\[(\[?)(\w{0,2} ?\d+ ?\d*)(\]?)\]\]/',$row['post_text'],$g_matches)) {
			if (isset($_GET['kml'])) {
				$folder = $document->addChild('Folder');
				$folder->setItem('name',$row['poster_name']);	
				$folder->setTimeStamp(substr($row['post_time'],0,10));
			}
			$gridsquares = array();

			foreach ($g_matches[2] as $g_i => $g_id) {
				$image = 0;
				if (is_numeric($g_id)) {
					
					$g_ok = $g_image->loadFromId($g_id);
					if ($g_ok && $g_image->moderation_status == 'rejected' ) {
						$g_ok = false;
					} elseif ($g_ok) {
						$square = clone $g_image->grid_square;
						$image = 1;
					}			
				} else {
					$g_ok=$square->setByFullGridRef($g_id);
				} 
				
				if ($g_ok) {
					$g = $square->grid_reference;
					if (isset($gridsquares[$g])) {
						$gridsquares[$g]->mentions++;
						$gridsquares[$g]->images += $image;
					} else {
						$gridsquares[$g] = clone $square;
						unset($gridsquares[$g]->db);
						$gridsquares[$g]->mentions = 1;
						$gridsquares[$g]->images = $image;
						if ($lx) {
							$gridsquares[$g]->distance = sprintf("%0.1f",
										sqrt(
										pow($lx-$square->x,2)+
										pow($ly-$square->y,2)));
						}
						$lx = $square->x;
						$ly = $square->y;

						if (isset($_GET['kml'])) {

							list($lat,$long) = $conv->gridsquare_to_wgs84($square);

							$point = new kmlPoint($lat,$long);
							$placemark = $folder->addChild(new kmlPlacemark(null,$g,$point));
						}
					}
				}
			}
			if ($cacheid) {
				foreach ($gridsquares as $g => $square) {
					if ($square->distance < 1.5 && ($square->images || !$square->imagecount)) {
						unset($gridsquares[$g]);
					}
				}
				if (count($gridsquares)) {
					$row['gridsquares'] = $gridsquares;
					$posts[$id] = $row;
				}
			} else {
				$row['gridsquares'] = $gridsquares;
				$posts[$id] = $row;
			}
		}
	}
	$smarty->assign_by_ref('posts', $posts);
	$smarty->assign('hide', $cacheid);

	if (isset($_GET['kml']))
		$kml->outputKMZ(false,"../kml/le2jog$cacheid.kmz");
}


$smarty->display($template, $cacheid);

	
?>
