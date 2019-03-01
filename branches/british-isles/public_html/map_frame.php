<?php
/**
 * $Project: GeoGraph $
 * $Id: browse.php 2865 2007-01-05 14:24:01Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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


       if (strpos($_SERVER['HTTP_USER_AGENT'], 'Web Preview')!==FALSE) {
                header("HTTP/1.0 401 Forbidden");
                header("Status: 401 Forbidden");
               exit;
       }

//TODO, this might be better ONLY allowing certain domains. At the moment, this page is used m.geograph.org.uk etc
define('ALLOW_FRAMED',1); //HAVE to be CAREFUL to taint all input!

require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/token.class.php');
require_once('geograph/gazetteer.class.php');

init_session();


$smarty = new GeographPage;

$template='map_frame.tpl';
$cacheid='';


$square=new GridSquare;


$token=new Token;
if (!empty($_GET['t']) && $token->parse($_GET['t']))
{
	$s = false;
	$rastermap = new RasterMap($s);
	foreach ($token->data as $key => $value) {
		$rastermap->{$key} = $value;
	}
	$rastermap->inline=true;

	$smarty->assign_by_ref('rastermap', $rastermap);

} elseif (!empty($_GET['id']) && !empty($_GET['hash'])) {
	$image=new GridImage;
        $image->loadFromId(intval($_GET['id']));

        //is the image rejected? - only the owner and administrator should see it
        if ($image->moderation_status=='rejected')
        {
                //clear the image
                $image=new GridImage;
                $cacheid=0;
                $rejected = true;
        }

	if ($image->isValid())
	{
		if ($_GET['hash'] != $image->_getAntiLeechHash()) {
			die("invalid");
		}

        	//when this image was modified
	        $mtime = strtotime($image->upd_timestamp);

                customCacheControl($mtime,$image->gridimage_id.$mtime);

                require_once('geograph/conversions.class.php');
                $conv = new Conversions;

                list($lat,$long) = $conv->gridsquare_to_wgs84($image->grid_square);

	        $rastermap = new RasterMap($image->grid_square,false);
		if (!empty($_GET['i']))
			$rastermap->service = 'Leaflet';
        	$rastermap->addLatLong($lat,$long);
	        if (!empty($image->viewpoint_northings)) {
        	        $rastermap->addViewpoint($image->viewpoint_eastings,$image->viewpoint_northings,$image->viewpoint_grlen,$image->view_direction);
	        } elseif (isset($image->view_direction) && strlen($image->view_direction) && $image->view_direction != -1) {
        	        $rastermap->addViewDirection($image->view_direction);
	        }

		$rastermap->inline=true;

		$smarty->assign_by_ref('rastermap', $rastermap);
	} else {
		die("invalid");
	}
} else {
	die("invalid");
}

customExpiresHeader(3600*6,false,true);

$smarty->display($template,$cacheid);

