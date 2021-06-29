<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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

pageMustBeHTTPS();

$smarty = new GeographPage;
$template = 'relocate.tpl';


$image=new GridImage;

if (isset($_REQUEST['id']))
{
        $image->loadFromId($_REQUEST['id']);
        $isowner=($image->user_id==$USER->user_id)?1:0;
        $isadmin=$USER->hasPerm('ticketmod')?1:0;

        if ($image->isValid())
        {
                if ($isowner||$isadmin)
                {
                        //ok, we'll let it lie...
                }
                else
                {
                        header("Location: /photo/{$_REQUEST['id']}");
                        exit;
                }

                //get the grid references
                $image->getSubjectGridref();
                $image->getPhotographerGridref();

                $smarty->assign_by_ref('image',$image);

######################################

                $conv = new Conversions;

		//this by 'magic' uses the exact eastings/northings, if it can!
                list($lat1,$long1) = $conv->gridsquare_to_wgs84($image->grid_square);
                $smarty->assign('lat1', $lat1);
                $smarty->assign('long1', $long1);

			if ($image->natgrlen == '4' || empty($image->natgrlen) || empty($image->nateastings)) {
				$smarty->assign('inaccurate_subject', 1);
			}

######################################

                if (!empty($image->viewpoint_northings)) {
                      //  $rastermap->addViewpoint($image->viewpoint_eastings,$image->viewpoint_northings,$image->viewpoint_grlen,$image->view_direction);


				  $ve = $image->viewpoint_eastings;        $vn = $image->viewpoint_northings;
                                        if (false) { //this isn't done by gridsquare_to_wgs84 - so doesnt make sence to do it here...
                                                if ($image->viewpoint_grlen == '4') {
                                                        $ve +=500; $vn += 500;
                                                }
                                                if ($image->viewpoint_grlen == '6') {
                                                        $ve +=50; $vn += 50;
                                                }
                                        }
                                        list($lat2,$long2) = $conv->national_to_wgs84($ve,$vn,$image->grid_square->reference_index,true,true);


			if ($image->viewpoint_grlen == '4') {
				$smarty->assign('inaccurate_viewpoint', 1);
			}
                } else {
			//TODO, use view_direction to 'project' a point (but will still be at arbitary distance!)

			$lat2 = $lat1+0.001;
			$long2 = $long1+0.001;
			$smarty->assign('fake_viewpoint', 1);
		}

                $smarty->assign('lat2', $lat2);
                $smarty->assign('long2', $long2);

######################################

                //build a list of view directions
                $dirs = array (-1 => '');
                $jump = 360/16; $jump2 = 360/32;
                for($q = 0; $q< 360; $q+=$jump) {
                        $s = ($q%90==0)?strtoupper(heading_string($q)):ucwords(heading_string($q));
                        $dirs[$q] = sprintf('%s : %03d deg (%03d > %03d)',
                                str_pad($s,16,' '),
                                $q,
                                ($q == 0?$q+360-$jump2:$q-$jump2),
                                $q+$jump2);
                }
                $dirs['00'] = $dirs[0];
                $smarty->assign_by_ref('dirs', $dirs);


	} else {
	        header("HTTP/1.0 404 Not Found");
        	header("Status: 404 Not Found");
	        $template = "static_404.tpl";
	}
} elseif (!empty($USER->user_id)) {

	$smarty->display('_std_begin.tpl');

	$db = GeographDatabaseConnection(true);

	if (!empty($_GET['part'])) {
		$rows = $db->getAll("SELECT gridimage_id,title FROM gridimage WHERE user_id = ".intval($USER->user_id)." AND (nateastings=0 OR viewpoint_eastings=0) GROUP BY substring(submitted,1,6) desc LIMIT 100");
	} else {
		$rows = $db->getAll("SELECT gridimage_id,title FROM gridimage_search WHERE user_id = ".intval($USER->user_id)." GROUP BY substring(submitted,1,6) desc LIMIT 100");
	}

	print "Some sample images to try it out on...<br>";
	foreach ($rows as $row) {
		print "&middot; <a href=\"?id={$row['gridimage_id']}\">".htmlentities($row['title'])."</a><br>";
	}


	$smarty->display('_std_end.tpl');
	exit;

} else {
        header("HTTP/1.0 404 Not Found");
        header("Status: 404 Not Found");
        $template = "static_404.tpl";
}


$smarty->display($template);
