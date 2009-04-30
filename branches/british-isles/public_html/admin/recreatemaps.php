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

if ( ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) &&
     (strpos($_SERVER['HTTP_X_FORWARDED_FOR'],$CONF['server_ip']) !== 0) )  //begins with
{
	init_session();
        $USER->mustHavePerm("admin");
}

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
	
} elseif (isset($_GET['customsql'])) {
	require_once('geograph/mapmosaic.class.php');
	$mosaic = new GeographMapMosaic;
	
	$basemap = isset($_GET['base']);
	$dummy = !isset($_GET['do']);

	$count = $mosaic->deleteBySql($_GET['customsql'],$dummy,$basemap);
	print "Deleted $count<br>";

	exit;

} elseif (isset($_GET['nonalign'])) {
	require_once('geograph/mapmosaic.class.php');
	$mosaic = new GeographMapMosaic;
	
	set_time_limit(6*90);
	
	$basemap = isset($_GET['base']);
	$dummy = !isset($_GET['do']);
	
	$prefixes = $db->GetAll("select * from gridprefix order by landcount desc, rand()");
	
	list($usec, $sec) = explode(' ',microtime());
	$GLOBALS['STARTTIME'] = ((float)$usec + (float)$sec);

	print "start<br>";flush();
	foreach($prefixes as $idx=>$prefix)
	{
		list($usec, $sec) = explode(' ',microtime());
		$endtime = ((float)$usec + (float)$sec);
		$timetaken = $endtime - $STARTTIME;

		if ($timetaken > 15) {
			//mysql might of closed the connection in the meantime
			unset($mosaic->db);
		}
		
	
		print "<h3>{$prefix['prefix']}</h3>";flush();

		$minx=$prefix['origin_x'];
		$maxx=$prefix['origin_x']+$prefix['width']-1;
		$miny=$prefix['origin_y'];
		$maxy=$prefix['origin_y']+$prefix['height']-1;

		
		$crit = "map_x between $minx and $maxx and ".
			"map_y between $miny and $maxy and ".
			"pixels_per_km >= 40 and ".
			"((map_x-{$prefix['origin_x']}) mod 5) != 0 and ".
			"((map_y-{$prefix['origin_y']}) mod 5) != 0";
			
		$count = $mosaic->deleteBySql($crit,$dummy,$basemap);
		print "Deleted $count<br>";flush();

		$total += $count;
		
		list($usec, $sec) = explode(' ',microtime());
		$GLOBALS['STARTTIME'] = ((float)$usec + (float)$sec);
	
	}
	print "<h2>Total: $total</h2>";
	exit;
	
} elseif (isset($_POST['inv'])) {
	$square=new GridSquare;
	require_once('geograph/mapmosaic.class.php');
	$mosaic = new GeographMapMosaic;
		
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"recreatemaps.php\">&lt;&lt;</a> Invalidating Maps...</h3>";
	flush();
	
	$squares = explode(",",$_POST['gridref']);
	
	$user_id = intval($_POST['user_id']);
	
	$and_crit = " and type_or_user IN (-1,0";
	if ($user_id > 0) {
		$and_crit .= ",$user_id";
	}
	$and_crit .= ")";
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$basemap = isset($_POST['base']);
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
			$sql="select * from mapcache 
					where $x between map_x and (map_x+image_w/pixels_per_km-1) and 
					$y between map_y and (map_y+image_h/pixels_per_km-1) $and_crit";
			
			$recordSet = &$db->Execute("$sql");
			while (!$recordSet->EOF) 
			{
				print implode(', ',array_values($recordSet->fields))."<br/>";
				$recordSet->MoveNext();
			}
			$recordSet->Close(); 
		}

		$mosaic->expirePosition($x,$y,$user_id,$basemap);
	}
	$smarty->display('_std_end.tpl');
	exit;
	
//do some processing?
} else if (isset($_POST['go']))
{
	if (isset($_POST['limit']) && preg_match("/^\d+(,\d+|)?$/",$_POST['limit'])) {
		$limit = $_POST['limit'];
	} else {
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
	$smarty->assign('invalid_maps',  $db->GetOne("select count(*) from mapcache where age > 0 and type_or_user >= 0"));
}



$smarty->display('admin_recreatemaps.tpl');

	
?>
