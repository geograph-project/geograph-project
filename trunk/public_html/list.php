<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

$template='list.tpl';
$cacheid='';

if (isset($_GET['refresh']))
	$smarty->clear_cache($template, $cacheid);

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	$squares=array();
	$images=array();
	$i=0;

	$prefixes=$db->GetAll("select * from gridprefix where landcount>0");
	foreach ($prefixes as $prefix)
	{
		$squares[$i]=$prefix;
		$images=new ImageList;

		$count=$images->getImagesByArea($prefix['origin_x'],$prefix['origin_x']+$prefix['width']-1,
			$prefix['origin_y']+$prefix['height']-1,$prefix['origin_y']);
		if ($count>0)
		{
			$squares[$i]['images']=$images->images;
			$squares[$i]['imagecount']=$count;
			$i++;
		}
		else
		{
			//forget about it
			unset($squares[$i]);
			unset($images);
		}

	}


	$smarty->assign_by_ref('squares', $squares);
}

$smarty->display($template, $cacheid);

	
?>
