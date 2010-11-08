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
require_once('geograph/gridsquare.class.php');
init_session();

$smarty = new GeographPage;


if (isset($_GET['gridref']))
{
	$square=new GridSquare;

	$ok=$square->setByFullGridRef($_GET['gridref'],false,true);
	if ($ok || ($square->x && strlen($square->grid_reference) > 4))
	{
		$gridref=$square->grid_reference;
		$smarty->assign_by_ref('gridref', $gridref);
		$smarty->assign('showinfo', 1);
	
		$isadmin = $USER->hasPerm('moderator')?1:0;
		$smarty->assign_by_ref('isadmin', $isadmin);
	
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
			else 
			{
				$smarty->assign('check_count', 0);
			}
		}
		
		//update?
		if (isset($_GET['save']))
		{
			if ($isadmin) {
				$percent=intval($_GET['percent_land']);
			} else {
				$percent=-1;
			}
			if (count($sq))
			{
				//update existing square
				$db->Execute("update gridsquare set percent_land='{$percent}' where gridsquare_id='{$sq['gridsquare_id']}'");
				$smarty->assign('status', "Existing gridsquare $gridref updated with new land percentage of $percent %");
				
				$db->Execute("REPLACE INTO mapfix_log SET user_id = {$USER->user_id}, gridsquare_id = {$sq['gridsquare_id']}, new_percent_land='{$percent}', old_percent_land='{$sq['percent_land']}',created=now(),comment=".$db->Quote($_GET['comment']));

				if ($isadmin) {
					require_once('geograph/mapmosaic.class.php');
					$mosaic = new GeographMapMosaic;
					$mosaic->expirePosition($sq['x'],$sq['y'],0,true);
				}
			}
			else
			{
				//we need to create a square
				$matches=array();
				preg_match('/^([A-Z]{1,3})(\d\d)(\d\d)$/',$gridref, $matches);
						
				$gridsquare=$matches[1];
				$eastings=$matches[2];
				$northings=$matches[3];
			
				$sql="select * from gridprefix where prefix='{$gridsquare}' limit 1";
				$prefix=$db->GetRow($sql);
				if (count($prefix))
				{
					$x=$prefix['origin_x'] + $eastings;
					$y=$prefix['origin_y'] + $northings;

					$sql="insert into gridsquare(x,y,percent_land,grid_reference,reference_index,point_xy) ".
						"values($x,$y,$percent,'$gridref',{$prefix['reference_index']},GeomFromText('POINT($x $y)') )";
					$db->Execute($sql);
					$gridsquare_id=$db->Insert_ID();

					$smarty->assign('status', "New gridsquare $gridref created with new land percentage of $percent %");
						
					$db->Execute("REPLACE INTO mapfix_log SET user_id = {$USER->user_id}, gridsquare_id = {$gridsquare_id}, new_percent_land='{$percent}', old_percent_land='{$sq['percent_land']}',created=now(),comment=".$db->Quote($_GET['comment']));
					
					if ($isadmin) {
						require_once('geograph/mapmosaic.class.php');
						$mosaic = new GeographMapMosaic;
						$mosaic->expirePosition($x,$y,0,true);
					}
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
