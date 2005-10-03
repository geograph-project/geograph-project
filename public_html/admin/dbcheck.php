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
require_once('geograph/gridshader.class.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;
$smarty->debugging=false;



//do some processing?
if (isset($_POST['check']))
{
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h2><a title=\"Admin home page\" href=\"/admin/index.php\">Admin</a> :Performing Database Check...</h2>";
	flush();
	set_time_limit(3600*24);
	
	$db = NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed'); 
	
	echo "<h3>Database tables</h3><table class=\"report\">";
	echo "<thead><tr><td>Table</td><td>Status</td></tr></thead><tbody>";
	$tables=$db->MetaTables();
	foreach ($tables as $table)
	{
		$result=$db->GetRow("check table $table");
		echo "<tr><td>$table</td><td>{$result[2]}:{$result[3]}</td></tr>\n";
		flush();
		
	}
	echo "</tbody></table>\n";
	flush();
	
	echo "<h3>Gridsquare Integrity Check</h3>\n";
	echo "<p>Here we check how many images we have for a gridsquare and see if it tallies ".
		"with the cached count in gridsquare.imagecount. ".
		"We also check if the gridsquare.has_geographs value is sane too.</p>";
	echo "<p>This can take some time....<span id=\"completed\"></span></p>\n";
	echo "<script language=\"javascript\">completed=document.getElementById('completed');</script>\n";
	echo "<ul>";
	
	flush();
	
	
	$count=0;
	$recordSet = &$db->Execute("SELECT gridimage.gridsquare_id, grid_reference, 
	imagecount as gridsquare_imagecount, count( * ) AS gridsimage_imagecount,
	has_geographs as gridsquare_has_geographs, sum(moderation_status='geograph') AS gridsimage_geographcount
	FROM gridimage
	INNER JOIN gridsquare USING ( gridsquare_id ) 
	WHERE moderation_status in ('accepted','geograph')
	GROUP BY gridimage.gridsquare_id
	HAVING (gridsquare_imagecount != gridsimage_imagecount) OR (gridsquare_has_geographs != (gridsimage_geographcount>0))");
	
	while (!$recordSet->EOF) 
	{
		$gridsquare_id=$recordSet->fields['gridsquare_id'];
		$grid_reference=$recordSet->fields['grid_reference'];
		$cached_count=$recordSet->fields['gridsquare_imagecount'];
		$has_geographs=$recordSet->fields['gridsquare_has_geographs'];
		
		$realcount=$recordSet->fields['gridsimage_imagecount'];
		$geographcount=$recordSet->fields['gridsimage_geographcount'];
		$real_has_geographs=($geographcount>0)?1:0;
		
		if ($cached_count!=$realcount)
		{
			echo "<li>gridsquare $gridsquare_id (<a href=\"/gridref/$grid_reference\">{$grid_reference}</a>) ".
				"has imagecount $cached_count, should be $realcount ";
			if (isset($_POST['fix']))
			{
				$db->Execute("update gridsquare set imagecount=$realcount where gridsquare_id=$gridsquare_id");
				echo "[fixed]";
			}
			
			echo "</li>";
			flush();
		}
		
		if ($real_has_geographs!=$has_geographs)
		{
			echo "<li>gridsquare $gridsquare_id (<a href=\"/gridref/$grid_reference\">{$grid_reference}</a>) ".
				"has has_geographs $has_geographs, should be $real_has_geographs";
			if (isset($_POST['fix']))
			{
				$db->Execute("update gridsquare set has_geographs=$real_has_geographs where gridsquare_id=$gridsquare_id");
				echo "[fixed]";
			}
			
			echo "</li>";
			flush();
		}
		
		$recordSet->MoveNext();
	}
	echo "</ul>";
	$recordSet->Close(); 
	echo "<script language=\"javascript\">completed.innerHTML='Completed';</script>\n";
			
	
	
	$smarty->display('_std_end.tpl');
	exit;
}



$smarty->display('dbcheck.tpl');

	
?>
