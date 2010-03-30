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


/*
couple of tools to add here....

1. enter grid reference - system tells you if it exists, what the current %age land
   is, and a link to the OS map - you can then update the %age land
   
2. list all '-1' squares for feeding into step 1
*/

require_once('geograph/global.inc.php');
require_once('geograph/gridsquare.class.php');
init_session();

$USER->hasPerm("admin") || $USER->mustHavePerm("moderator");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

if (isset($_GET['redo_basemap']) && preg_match('/^[\w]+$/',$_GET['redo_basemap'])) 
{
	require_once('geograph/mapmosaic.class.php');
	$mosaic = new GeographMapMosaic;
	set_time_limit(3600*24);
		
	print "<h2>Starting ...</h2>";
	flush();
	
	$sql="select l.x,l.y from gridsquare l left join {$_GET['redo_basemap']}.gridsquare s using (gridsquare_id) where s.percent_land != l.percent_land";

	$tiles = $count = 0;
	$recordSet = &$db->Execute($sql);
	while (!$recordSet->EOF) 
	{
		$tiles += $mosaic->expirePosition($recordSet->fields['x'],$recordSet->fields['y'],0,true);
		print "tiles = $tiles<br/>";
		flush();
		$count++;
		$recordSet->MoveNext();
	}
	$recordSet->Close();
	print "<h2>All Done</h2>";
	print "Squares done = $count<br/>";
	print "Tiles deleted = $tiles";
	flush();
	exit;
} 
elseif (isset($_GET['gridref']))
{
	$square=new GridSquare;
	
	
	$ok=$square->validGridRef($_GET['gridref']);
	if ($ok)
	{
		$gridref=$_GET['gridref'];
		$smarty->assign_by_ref('gridref', $gridref);
		$smarty->assign('showinfo', 1);
	
		//can we find a square?
		$sq=$db->GetRow("select * from gridsquare where grid_reference='{$gridref}'");
		if (count($sq))
		{
			$smarty->assign('percent_land', $sq['percent_land']);
		}
		
		//update?
		if (isset($_GET['save']))
		{
			$percent=intval($_GET['percent_land']);
			if (count($sq))
			{
				//update existing square
				$db->Execute("update gridsquare set percent_land='{$percent}' where gridsquare_id='{$sq['gridsquare_id']}'");
				$smarty->assign('status', "Existing gridsquare $gridref updated with new land percentage of $percent %");
				
				$db->Execute("REPLACE INTO mapfix_log SET user_id = {$USER->user_id}, gridsquare_id = {$sq['gridsquare_id']}, new_percent_land='{$percent}', old_percent_land='{$sq['percent_land']}',created=now(),comment=".$db->Quote($_GET['comment']));
				
				require_once('geograph/mapmosaic.class.php');
				$mosaic = new GeographMapMosaic;
				$mosaic->expirePosition($sq['x'],$sq['y'],0,true);
				
			}
			else
			{
				//we need to create a square
				$matches=array();
				preg_match('/^([A-Z]{1,3})(\d\d)(\d\d)$/',$gridref, $matches);
						
				$gridsquare=$matches[1];
				$eastings=$matches[2];
				$northings=$matches[3];
			
				$sql="select * from gridprefix where prefix='{$gridsquare}'";
				$prefix=$db->GetRow($sql);
				if (count($prefix))
				{
					$x=$prefix['origin_x'] + $eastings;
					$y=$prefix['origin_y'] + $northings;

					$sql="insert into gridsquare(x,y,percent_land,grid_reference,reference_index,point_xy) 
						values($x,$y,-1,'$gridref',{$prefix['reference_index']},GeomFromText('POINT($x $y)') )";
					$db->Execute($sql);
					$gridsquare_id=$db->Insert_ID();

					$smarty->assign('status', "New gridsquare $gridref created with new land percentage of $percent %");
					
					$db->Execute("REPLACE INTO mapfix_log SET user_id = {$USER->user_id}, gridsquare_id = {$gridsquare_id}, new_percent_land='{$percent}', old_percent_land='{$sq['percent_land']}',created=now(),comment=".$db->Quote($_GET['comment']));
					
					require_once('geograph/mapmosaic.class.php');
					$mosaic = new GeographMapMosaic;
					$mosaic->expirePosition($x,$y,0,true);
					
				}
			}
			
			$smarty->assign('percent_land', $percent);
		}
		
	}
	else
	{
		$smarty->assign_by_ref('gridref', strip_tags($_GET['gridref']));
		$smarty->assign('gridref_error', "Bad grid reference");
	}
	
	$smarty->assign('gridref_ok', $ok?1:0);
	
}

if ($_GET['save']=='quick')
{
	//return nice simple result
	$status=$smarty->get_template_vars('status');
	
	echo "Status: $status";
}
else
{
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$unknowns=$db->GetAll("select gridsquare_id,grid_reference,imagecount,
		group_concat(comment order by mapfix_log_id SEPARATOR ' | ') as comments,
		group_concat(concat(old_percent_land,'>',new_percent_land) order by mapfix_log_id SEPARATOR ', ') as percents		
	from gridsquare 
		left join mapfix_log using (gridsquare_id)
	where percent_land=-1 
	group by gridsquare_id
	order by reference_index asc,imagecount desc");
	
	$smarty->assign_by_ref('unknowns', $unknowns);
	
	
	$smarty->display('admin_mapfixer.tpl');
}
	
?>
