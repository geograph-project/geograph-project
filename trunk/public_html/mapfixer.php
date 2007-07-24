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

$smarty = new GeographPage;


if (isset($_GET['gridref']))
{
	$square=new GridSquare;

		
	
	$ok=$square->validGridRef($_GET['gridref']);
	if ($ok)
	{
		$gridref=$_GET['gridref'];
		$smarty->assign_by_ref('gridref', $gridref);
		$smarty->assign('showinfo', 1);
	
		$db = NewADOConnection($GLOBALS['DSN']);

		$smarty->assign('check_count', -2);
	
		//can we find a square?
		$sq=$db->GetRow("select * from gridsquare where grid_reference='{$gridref}' limit 1");
		if (count($sq))
		{
			$smarty->assign('percent_land', $sq['percent_land']);
			
			if ($count= $db->GetOne("select count(*) from mapfix_log where gridsquare_id='{$sq['gridsquare_id']}'"))
			{
				$smarty->assign('check_count', $count);
			}
		}
		
		//update?
		if (isset($_GET['save']))
		{
			$percent=-1;
			if (count($sq))
			{
				//update existing square
				$db->Execute("update gridsquare set percent_land='{$percent}' where gridsquare_id='{$sq['gridsquare_id']}'");
				$smarty->assign('status', "Existing gridsquare $gridref updated with new land percentage of $percent %");
				
				$db->Execute("REPLACE INTO mapfix_log SET user_id = {$USER->user_id}, gridsquare_id = {$sq['gridsquare_id']}, new_percent_land='{$percent}', old_percent_land='{$sq['percent_land']}',created=now()");
			}
			else
			{
				//we need to create a square
				$matches=array();
				preg_match('/^([A-Z]{1,2})(\d\d)(\d\d)$/',$gridref, $matches);
						
				$gridsquare=$matches[1];
				$eastings=$matches[2];
				$northings=$matches[3];
			
				$sql="select * from gridprefix where prefix='{$gridsquare}' limit 1";
				$prefix=$db->GetRow($sql);
				if (count($prefix))
				{
					$x=$prefix['origin_x'] + $eastings;
					$y=$prefix['origin_y'] + $northings;

					$sql="insert into gridsquare(x,y,percent_land,grid_reference,reference_index) ".
						"values($x,$y,$percent,'$gridref',{$prefix['reference_index']})";
					$db->Execute($sql);
					$gridimage_id=$db->Insert_ID();

					$smarty->assign('status', "New gridsquare $gridref created with new land percentage of $percent %");
						
					$db->Execute("REPLACE INTO mapfix_log SET user_id = {$USER->user_id}, gridsquare_id = {$gridsquare_id}, new_percent_land='{$percent}', old_percent_land='{$sq['percent_land']}',created=now()");
					
				} else {
					$smarty->assign('gridref_error', "Error, please try again later");
				}
			}
			
			$smarty->assign('percent_land', $percent);
		}
		
	}
	else
	{
		$smarty->assign_by_ref('gridref', strip_tags($_GET['gridref']));
		$smarty->assign('gridref_error', "Bad or unknown grid reference");
	}
	
	$smarty->assign('gridref_ok', $ok?1:0);
	
}

$smarty->display('mapfixer.tpl');

	
?>
