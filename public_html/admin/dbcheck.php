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
	
	if (isset($_POST['dbtables']))
	{
		echo "<h3>Check Database tables</h3><table class=\"report\">";
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
	}
	if (isset($_POST['anatables']))
	{
		echo "<h3>Analyse Database tables</h3><table class=\"report\">";
		echo "<thead><tr><td>Table</td><td>Status</td></tr></thead><tbody>";
		#$tables=$db->MetaTables();
		$tables=$db->MetaTables('TABLES');
		foreach ($tables as $table)
		{
			$result=$db->GetRow("analyze table $table");
			echo "<tr><td>$table</td><td>{$result[2]}:{$result[3]}</td></tr>\n";
			flush();

		}
		echo "</tbody></table>\n";
		flush();
	}
	if (isset($_POST['opttables']))
	{
		echo "<h3>Optimise Database tables</h3><table class=\"report\">";
		echo "<thead><tr><td>Table</td><td>Status</td></tr></thead><tbody>";
		#$tables=$db->MetaTables();
		$tables=$db->MetaTables('TABLES');
		foreach ($tables as $table)
		{
			$result=$db->GetRow("optimize table $table");
			echo "<tr><td>$table</td><td>{$result[2]}:{$result[3]}</td></tr>\n";
			flush();

		}
		echo "</tbody></table>\n";
		flush();
	}
	
	if (isset($_POST['gridsquares']))
	{

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
	}		
	if (isset($_POST['gridsquareperm'])) {
		echo "<h3>Check gridsquare permissions</h3>\n";

		echo "<ul>";
		flush();
		$recordSet = &$db->Execute("SELECT gridsquare_id,grid_reference,percent_land,permit_photographs FROM gridsquare where percent_land=0 and permit_photographs!=0");
		while (!$recordSet->EOF) {
			$gridsquare_id=$recordSet->fields['gridsquare_id'];
			$grid_reference=$recordSet->fields['grid_reference'];
			$percent_land=$recordSet->fields['percent_land'];
			$permit_photographs=$recordSet->fields['permit_photographs'];
			echo "<li>gridsquare $gridsquare_id (<a href=\"/gridref/$grid_reference\">{$grid_reference}</a>) ".
				"has percent_land $percent_land and permit_photographs $permit_photographs ";
			if (isset($_POST['fix']))
			{
				$db->Execute("update gridsquare set permit_photographs=0 where gridsquare_id=$gridsquare_id");
				echo "[fixed]";
			}

			echo "</li>";
			flush();
			$recordSet->MoveNext();
		}
		echo "</ul>";
		$recordSet->Close();

		echo "<ul>";
		flush();
		$recordSet = &$db->Execute("SELECT gridsquare_id,grid_reference,permit_geographs,permit_photographs FROM gridsquare where permit_photographs=0 and permit_geographs!=0");
		while (!$recordSet->EOF) {
			$gridsquare_id=$recordSet->fields['gridsquare_id'];
			$grid_reference=$recordSet->fields['grid_reference'];
			$permit_photographs=$recordSet->fields['permit_photographs'];
			$permit_geographs=$recordSet->fields['permit_geographs'];
			echo "<li>gridsquare $gridsquare_id (<a href=\"/gridref/$grid_reference\">{$grid_reference}</a>) ".
				"has permit_geographs $permit_geographs and permit_photographs $permit_photographs ";
			if (isset($_POST['fix']))
			{
				$db->Execute("update gridsquare set permit_geographs=0 where gridsquare_id=$gridsquare_id");
				echo "[fixed]";
			}

			echo "</li>";
			flush();
			$recordSet->MoveNext();
		}
		echo "</ul>";
		$recordSet->Close();

		echo "<ul>";
		flush();
		$recordSet = &$db->Execute("SELECT gridsquare_id,grid_reference,imagecount,has_geographs,permit_geographs,permit_photographs FROM gridsquare where "
			."imagecount > 0 and permit_photographs=0 or "
			."has_geographs > 0 and permit_geographs=0");
		while (!$recordSet->EOF) {
			$gridsquare_id=$recordSet->fields['gridsquare_id'];
			$grid_reference=$recordSet->fields['grid_reference'];
			$imagecount=$recordSet->fields['imagecount'];
			$has_geographs=$recordSet->fields['has_geographs'];
			$permit_photographs=$recordSet->fields['permit_photographs'];
			$permit_geographs=$recordSet->fields['permit_geographs'];
			echo "<li>gridsquare $gridsquare_id (<a href=\"/gridref/$grid_reference\">{$grid_reference}</a>) ".
				"has permit_geographs $permit_geographs, has_geographs $has_geographs and permit_photographs $permit_photographs, imagecount $imagecount ";
			echo "</li>";
			flush();
			$recordSet->MoveNext();
		}
		echo "</ul>";
		$recordSet->Close();
	}
	if (isset($_POST['gridsquareperc'])) {
		echo "<h3>Check gridsquare percentages</h3>\n";

		echo "<ul>";
		flush();
		$sql = "select gs.gridsquare_id, grid_reference, percent_land, gp1.percent as p1, gp2.percent as p2, gp3.percent as p3, gp4.percent as p4, permit_photographs, permit_geographs ".
			"from gridsquare gs ".
			"left join gridsquare_percentage gp1 on (gs.gridsquare_id=gp1.gridsquare_id and gp1.level = -1 and gp1.community_id = 1) ".
			"left join gridsquare_percentage gp2 on (gs.gridsquare_id=gp2.gridsquare_id and gp1.level = -1 and gp2.community_id = 2) ".
			"left join gridsquare_percentage gp3 on (gs.gridsquare_id=gp3.gridsquare_id and gp1.level = -1 and gp3.community_id = 3) ".
			"left join gridsquare_percentage gp4 on (gs.gridsquare_id=gp4.gridsquare_id and gp1.level = -1 and gp4.community_id = 4) where ".
			"greatest(round(0.5*coalesce(gp1.percent,0)+0.5*coalesce(gp2.percent,0))-coalesce(gp4.percent,0),coalesce(gp1.percent,0)>0) != percent_land or ".
			"coalesce(gp1.percent,0)<coalesce(gp2.percent,0) or ".
			"coalesce(gp2.percent,0)<coalesce(gp4.percent,0) or ".
			"coalesce(gp4.percent,0)<coalesce(gp3.percent,0) or ".
			"(coalesce(gp1.percent,0)>0) != permit_photographs or ".
			"(coalesce(gp1.percent,0)-coalesce(gp3.percent,0)>0) != permit_geographs;";
		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) {
			$gridsquare_id=$recordSet->fields['gridsquare_id'];
			$grid_reference=$recordSet->fields['grid_reference'];
			$percent_land=$recordSet->fields['percent_land'];
			$permit_photographs=$recordSet->fields['permit_photographs'];
			$permit_geographs=$recordSet->fields['permit_geographs'];
			$p1=$recordSet->fields['p1'];
			$p2=$recordSet->fields['p2'];
			$p3=$recordSet->fields['p3'];
			$p4=$recordSet->fields['p4'];
			echo "<li>gridsquare $gridsquare_id (<a href=\"/gridref/$grid_reference\">{$grid_reference}</a>) ".
				"has percent_land $percent_land, permit_photographs $permit_photographs, permit_geographs $permit_geographs and percentages $p1,$p2,$p3,$p4 ";
			#if (isset($_POST['fix']))
			#{
			#	$db->Execute("update gridsquare set permit_photographs=0 where gridsquare_id=$gridsquare_id");
			#	echo "[fixed]";
			#}
			echo "</li>";
			flush();
			$recordSet->MoveNext();
		}
		echo "</ul>";
		$recordSet->Close();

	}
	if (isset($_POST['geographs']))
	{
		echo "<h3>Geographs Per Square</h3><table class=\"report\">";
		flush();
		echo "<thead><tr><td>Square</td><td>Number of Geographs</td><td>Number of Firsts</td></tr></thead><tbody>";
		
		if ($_POST['table'] == 'gridimage_search') {
			$table = "inner join gridimage_search as gi using (grid_reference)";
			$group = "grid_reference";
		} else {
			$table = "inner join gridimage as gi using (gridsquare_id)";
			$group = "gridsquare_id";			
		}
		
		$squares=$db->getAll("select
		gs.grid_reference,count(gridimage_id) as geographs,sum(ftf = 1) as firsts
		from gridsquare as gs
			$table
		where moderation_status = 'geograph'
		group by gi.$group
		having firsts != 1");
		foreach ($squares as $id => $square)
		{
			echo "<tr><td><a href=\"/gridref/{$square['grid_reference']}\">{$square['grid_reference']}</a></td><td>{$square['geographs']}</td><td>{$square['firsts']}</td></tr>\n";
		}
		echo "</tbody></table>\n";
		flush();
	}
	
	
	$smarty->display('_std_end.tpl');
	exit;
}



$smarty->display('admin_dbcheck.tpl');

	
?>
