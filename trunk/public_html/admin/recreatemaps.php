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
require_once('geograph/image.inc.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);



//do some processing?
if (isset($_POST['go']))
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
