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
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/image.inc.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;


if (isset($_GET['deleteInvalidateAll']) && $USER->hasPerm('admin'))
{
	$mosaic=new GeographMapMosaic;
	$mosaic->deleteAndInvalidateAll();
	
	//redirect to prevent page refreshes of this url

	header("Location:http://{$_SERVER['HTTP_HOST']}/admin/recreatemaps.php");
	exit;
}

if (isset($_GET['invalidateAll']) && $USER->hasPerm('admin'))
{
	$mosaic=new GeographMapMosaic;

	$mosaic->invalidateAll();
	
	//redirect to prevent page refreshes of this url

	header("Location:http://{$_SERVER['HTTP_HOST']}/admin/recreatemaps.php");
	exit;
}

if (isset($_GET['expireAll']) && $USER->hasPerm('admin'))
{
	$mosaic=new GeographMapMosaic;

	$mosaic->expireAll($_GET['expireAll']?true:false);
	$smarty->clear_cache(null, 'mapbrowse');
	

	//redirect to prevent page refreshes of this url

	header("Location:http://{$_SERVER['HTTP_HOST']}/admin/recreatemaps.php");
	exit;
}

$db = NewADOConnection($GLOBALS['DSN']);


if (isset($_GET['coast_GB_40'])) {
	//invalidate coast'ish' GB squares at thumbnail level!
	$prefixes = $db->GetAll("select * 
	from gridprefix 
	where reference_index = 1 
	and landcount < 9500 
	and landcount > 0");	
	foreach($prefixes as $idx=>$prefix)
	{

		$minx=$prefix['origin_x'];
		$maxx=$prefix['origin_x']+$prefix['width']-1;
		$miny=$prefix['origin_y'];
		$maxy=$prefix['origin_y']+$prefix['height']-1;

		if ($_GET['do']) {
			$db->Execute("update mapcache set age=age+1 where ".
				"map_x between $minx and $maxx and ".
				"map_y between $miny and $maxy and ".
				"pixels_per_km >= 40");
			$count = mysql_affected_rows();	
		} else {
			$count=$db->GetOne("select count(*) from mapcache where ".
				"map_x between $minx and $maxx and ".
				"map_y between $miny and $maxy and ".
				"pixels_per_km >= 40");
		}

		$total += $count;
		print "{$prefix['prefix']} = $count<BR>";

	}
	print "<h2>$total</h2>";
	exit;
} elseif (isset($_POST['inv']))
{

	$square=new GridSquare;
	require_once('geograph/mapmosaic.class.php');
	$mosaic = new GeographMapMosaic;
		
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"recreatemaps.php\">&lt;&lt;</a> Invalidating Maps...</h3>";
	flush();
	
	$squares = explode(",",$_POST['gridref']);
	
	foreach ($squares as $gridref) {
		$grid_ok=$square->setGridRef($gridref);



		if (!$grid_ok) {
			$smarty->assign('errormsg',$square->errormsg);


			$smarty->display('admin_recreatemaps.tpl');
			exit;
		}
		$x = $square->x;
		$y = $square->y;

		print "<h3>$gridref</h3>";
		if (count($squares) < 5) {
				$sql="select * from mapcache ".
							"where $x between map_x and (map_x+image_w/pixels_per_km-1) and ".
							"$y between map_y and (map_y+image_h/pixels_per_km-1)";
				$db->Execute($sql);
			$recordSet = &$db->Execute("$sql");
			while (!$recordSet->EOF) 
			{
				print implode(',',array_values($recordSet->fields))."<br/>";
				$recordSet->MoveNext();
			}
			$recordSet->Close(); 
		}

		$mosaic->expirePosition($x,$y);
	}
	$smarty->display('_std_end.tpl');
	exit;
	
//do some processing?
} else if (isset($_POST['go']))
{
	$limit = intval($_POST['limit']);
	if (!$limit) {
		$limit = 10;
	}

	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"recreatemaps.php\">&lt;&lt;</a> Re-Creating Maps...</h3>";
	flush();
	
	$map=new GeographMap;
		
	$recordSet = &$db->Execute("select * from mapcache where age > 0 order by pixels_per_km desc, age desc limit $limit");
	while (!$recordSet->EOF) 
	{
		foreach($recordSet->fields as $name=>$value)
		{
			if (!is_numeric($name))
				$map->$name=$value;
		}
		
		$map->_renderMap();

		echo "<li>re-rendered ".$map->getImageFilename()."</li>";
		flush();
			
	
	
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 
	
	$smarty->display('_std_end.tpl');
	exit;
	

	
} else {
	$smarty->assign('invalid_maps',  $db->GetOne("select count(*) from mapcache where age > 0"));
}



$smarty->display('admin_recreatemaps.tpl');

	
?>
