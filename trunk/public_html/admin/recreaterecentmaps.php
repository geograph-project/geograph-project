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


$db = NewADOConnection($GLOBALS['DSN']);

if (isset($_GET['check2']))
{
	set_time_limit(3600*24);

		
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"recreatemaps.php\">&lt;&lt;</a> Checking Missing GD images...</h3>";
	flush();
	

	$base=&$_SERVER['DOCUMENT_ROOT'];
	$size = 40;
	
	$sql="select gridsquare_id,grid_reference from gridsquare where imagecount>0";
	
	$recordSet = &$db->Execute($sql);	
	while (!$recordSet->EOF) 
	{
		$sql2="select * from gridimage where gridsquare_id={$recordSet->fields['gridsquare_id']} ".
				"and moderation_status<>'rejected' order by moderation_status+0 desc,seq_no limit 1";

		$recordSet2 = &$db->Execute($sql2);
		
		if ($recordSet2->fields['gridimage_id']) {
			$image=new GridImage;
			$image->fastInit($recordSet2->fields);	

			$yz=sprintf("%02d", floor($this->gridimage_id/1000000));
			$ab=sprintf("%02d", floor(($this->gridimage_id%1000000)/10000));
			$cd=sprintf("%02d", floor(($image->gridimage_id%10000)/100));
			$abcdef=sprintf("%06d", $image->gridimage_id);
			$hash=$image->_getAntiLeechHash();

			$thumbpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}_{$size}x{$size}.gd";
			if (!file_exists($base.$thumbpath))
			{
				print "Missing GD image for: {$recordSet->fields['grid_reference']}<BR>";
			}
		}
		$recordSet->MoveNext();
		$recordSet2->Close();
	}
	$recordSet->Close();
	print "<h3>Done</h3>";
	$smarty->display('_std_end.tpl');
	exit;
//do some processing?
} elseif (isset($_GET['check']))
{
	set_time_limit(3600*24);
	
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"recreatemaps.php\">&lt;&lt;</a> Checking Folders...</h3>";
	flush();

	# /maps/detail/741/120/detail_741_120_200_200_40_0.png
	$cutoff = time() - 60*60*24*4;
function recurse_maps($folder) {	
	global $db,$cutoff;
	$root=&$_SERVER['DOCUMENT_ROOT'];
	$dh = opendir($root.$folder);
	
	while (($file = readdir($dh)) !== false) {
		if (is_dir($root.$folder.$file) && strpos($file,'.') !== 0) {
			recurse_maps($folder.$file.'/');
			print "done $folder $file<br/>";
		} elseif (preg_match("/detail_(\d+)_(\d+)_(\d+)_(\d+)_(\d+)_(\d+)\./",$file,$m)) {
			array_shift($m);
			if (filemtime($root.$folder.$file) > $cutoff && $m[4] == 80) {
				$sql = "INSERT DELAYED IGNORE INTO mapcache2 VALUES(".join(',',$m).",0)";
				$db->Execute($sql);
			}
		}		
	}
	closedir($dh);
}
	
	recurse_maps("/maps/detail/");
	
	print "<h3>Done</h3>";
	$smarty->display('_std_end.tpl');
	exit;
	

	
} elseif (isset($_GET['compare'])) {
	set_time_limit(3600*24);

	
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"recreatemaps.php\">&lt;&lt;</a> Comparing tables...</h3>";
	flush();
	

	$sql = "SELECT mapcache2.* FROM mapcache2";
	$recordSet = &$db->Execute("$sql");
	while (!$recordSet->EOF) 
	{
		$values = array();
		foreach($recordSet->fields as $name=>$value)
		{
			if (!is_numeric($name))
				$values[]=$value;
															
		}
		$values[6] = 17; //we always want to invalidate this tile! Overkill but probably need to update this tile anyway...
		$sql = "REPLACE INTO mapcache VALUES(".join(',',$values).")";
		print "$sql";
		$db->Execute($sql);
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 
	#$db->Execute("TRUNCATE mapcache2");
	print "<h3>Done</h3>";
	$smarty->display('_std_end.tpl');
	exit;
	
} elseif (isset($_GET['setup'])) {
$db->Execute("CREATE TABLE `mapcache2` (
			    `map_x` smallint(6) NOT NULL default '0',
			    `map_y` smallint(6) NOT NULL default '0',
			    `image_w` smallint(6) unsigned NOT NULL default '0',
			    `image_h` smallint(6) unsigned NOT NULL default '0',
			    `pixels_per_km` float NOT NULL default '0',
			    `type_or_user` smallint(6) NOT NULL default '0',
			    `age` smallint(5) unsigned NOT NULL default '0',
			    PRIMARY KEY  (`map_x`,`map_y`,`image_w`,`image_h`,`pixels_per_km`,`type_or_user`)
			  ) TYPE=MyISAM ");
} elseif (isset($_GET['remove'])) {
	$db->Execute("DROP TABLE `mapcache2`");

} else {
	$smarty->assign('invalid_maps',  $db->GetOne("select count(*) from mapcache where age > 0"));
}



$smarty->display('admin_recreatemaps.tpl');

	
?>
