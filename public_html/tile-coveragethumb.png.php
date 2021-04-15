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

$seconds = 60;
customExpiresHeader($seconds,true);
//header('Access-Control-Allow-Origin: *');

if (empty($_GET))
	$_GET['label'] = "sea arches";


	$db = GeographDatabaseConnection(true);

########################################################

	$scale = 1;
	$fudge = $scale+1;

if (!empty($_GET['scale']))
	$scale = sprintf("%0.1f",$_GET['scale']);
if (!empty($_GET['fudge']))
	$fudge = intval($_GET['fudge']);


if (!empty($_GET['user_id'])) {
	$where = "user_id = ".intval($_GET['user_id']);

} elseif (!empty($_GET['label'])) {

	$table = "curated1";
	$where = "label = ".$db->Quote($_GET['label']);
} else {
	die("unable to continue");
}

########################################################

if (!empty($_GET['user_id'])) {

	if (!empty($_GET['p'])) {
		$sql = "
		select h.hectad,x,y,landsquares, u.geosquares/landsquares*100 as percent
		from hectad_stat h left join hectad_user_stat u on (u.hectad = h.hectad AND $where)
		where landsquares > 0 order by percent asc";
	} else {
		$sql = "
		select h.hectad,x,y,landsquares,u.images
		from hectad_stat h left join hectad_user_stat u on (u.hectad = h.hectad AND $where)
		where landsquares > 0 order by images asc";
	}
} else {

	$sql = "
	select hectad,x,y,landsquares,count(gridimage_id) images
	from hectad_stat
	left join (select gridimage_id,grid_reference,CONCAT(SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-3),SUBSTRING(gi.grid_reference,LENGTH(gi.grid_reference)-1,1)) AS hectad 
		from $table
		inner join gridimage_search gi using (gridimage_id)
		where $where) as t2  USING (hectad)
	where landsquares > 0 group by hectad order by images asc"; //order so get the ones with images LAST;

}

########################################################

       $im = imagecreate(88*$scale,$height = 123*$scale);

        $bg = imagecolorallocate($im, 255, 255, 255);
        imagecolortransparent($im,$bg);
//        $fg = imagecolorallocate($im, 0, 0, 255);

########################################################

                $colors = array();
                foreach (range(0,100) as $percent) {
                        $cr = 255 - $percent;
                        $cg = 255 - ($percent*$percent); if ($cg<0) $cg=0;

                        $colors[$percent] = imagecolorallocate($im, $cr, $cg, 0);
                }

		$colors[0] =  imagecolorallocate($im, 117,255,101); //green!
		//$colors[-1] = imagecolorallocate($im, 191,255,184); //halfgreen
		$colors[-1] = imagecolorallocatealpha($im, 117,255,101,64); //half transparent!

########################################################

	$scalesize=$scale-1;

	foreach($db->getAll($sql) as $row) {
		if (isset($row['percent'])) {
			if (empty($_GET['lin'])) {
				 $row['percent'] = pow($row['percent']+1,3);
			}
		} else {
			if (!empty($row['images']) && empty($_GET['lin']))
				$row['percent'] = pow($row['images']+1,3); //tofix, hardcoded test!
			elseif (!empty($row['images']))
				$row['percent'] = $row['images']+1;
			else
				$row['percent'] = 0;
		}
		$coloridx = floor($row['percent']);

		if ($coloridx>100) $coloridx = 100;
		if ($coloridx == 0 && $row['landsquares'] <=50)
			$coloridx = -1;
		$color = $colors[$coloridx];

		$e = ($coloridx>0)?$fudge:0;

		$x = floor($row['x']/10)*$scale;
		$y = $height-(floor($row['y']/10)*$scale);

		if (!empty($_GET['c']) && $coloridx)
			imagefilledellipse($im, $x,$y, ($e+$scalesize)*2,($e+$scalesize)*2, $color);
		else
	                imagefilledrectangle($im, $x-$e,$y-$e, $x+$scalesize+$e,$y+$scalesize+$e, $color);
	}

########################################################

       imagesavealpha($im, true);
       header('Content-type: image/png');
       imagepng($im);

