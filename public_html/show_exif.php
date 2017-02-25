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

init_session();

$smarty = new GeographPage;
$template='show_exif.tpl';

if (!isset($_GET['kml']))
	$USER->mustHavePerm("basic");


function smarty_function_show_exif($params) {
	require_once('3rdparty/show_exif.inc.php');

	show_exif($params['exif']);
}
$smarty->register_function("show_exif", "smarty_function_show_exif");

if (isset($_REQUEST['id']))
{
	//initialise message
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/gridimage.class.php');

	$image=new GridImage();
	$ok = $image->loadFromId($_REQUEST['id']);

	if (!$ok || ($image->moderation_status=='rejected' && !$USER->hasPerm('admin'))) {
		//clear the image
		$image=new GridImage;
		header("HTTP/1.0 410 Gone");
		header("Status: 410 Gone");
		$template = "static_404.tpl";
	} else {
		$smarty->assign_by_ref('image', $image);

		$db=GeographDatabaseConnection(false);

		$exif = $db->getOne("SELECT exif FROM gridimage_exif WHERE gridimage_id = ".$image->gridimage_id);
		if (!empty($exif)) {
			$exif = unserialize($exif);
		} else {
			$exif = read_exif($image->gridimage_id);
			//returns already unserialized
		}

		if (!empty($exif)) {

			$smarty->assign_by_ref('exif', $exif);

			if (isset($_GET['kml'])) {
				require_once('3rdparty/show_exif.inc.php');

				require_once('geograph/conversions.class.php');
				$conv = new Conversions;

				list($lat,$lon) = $conv->gridsquare_to_wgs84($image->grid_square);

				$filename = "geograph-".$image->gridimage_id."-view.kml";

				angle_kml($exif,$image->viewpoint_eastings,$image->viewpoint_northings,$image->viewpoint_grlen,$image->view_direction,$lat,$lon,$filename,$image->grid_square->reference_index);

				exit;
			} elseif ($image->viewpoint_eastings && $image->view_direction) {
				$smarty->assign('kml_available', 1);
			}
		}
	}
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template = "static_404.tpl";
}


$smarty->display($template);



//this function should be in a central lib - maybe even gridimage.class.php - but for now this is the only page that uses it!
function read_exif($id) {
        $folder = "/mnt/combined/geograph_live/exif";

        $start = intval($id/1000)*1000;
        $dir1 = sprintf("%02d", floor($start/1000000)%100);
        $dir2 = sprintf("%02d", floor($start/10000)%100);
        $filename = "$folder/$dir1/$dir2/$start.exif";

print "<!-- $filename -->";

        if (file_exists("$filename.gz"))
                $h = gzopen($opened = "$filename.gz",'rb');
        elseif (file_exists("$filename"))
                $h = gzopen($opened = $filename,'rb'); //still use gzopen as it reads uncompressed anyway, and needed for gzgets/gzclose
        else
                return false;

print "<!-- $filename  --  $h -- $opened -->";


        $prefix = "$id\t";
        $prefixlen = strlen($prefix);
        while ($h && !feof($h)) {
                $string = gzgets($h);

                //use prefix compare, rather than split then compare, to avoid splitting all strings.
                if (strncmp($string, $prefix, $prefixlen) === 0) {
                        list($id2,$encoded) = explode("\t",$string,2);
                        return unserialize(base64_decode($encoded));
                }
        }
        gzclose($h);
}

