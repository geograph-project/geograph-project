<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');
init_session();

# let the browser know what's coming
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"geograph.csv\"");


$images=new ImageList;

$count=$images->getImages(array('accepted','geograph'));
if ($count>0)
{
	echo "Id,Name,Grid Ref,Submitter,Image Class\n";
	
	foreach ($images->images as $image) 
	{
		if (strpos($image->title,',') !== FALSE) 
		{
			$image->title = '"'.$image->title.'"';
		}
		echo "{$image->gridimage_id},{$image->title},{$image->grid_reference},{$image->realname},{$image->imageclass}\n";
	}
} 
else 
{
	echo "No Images";
}



	
?>
