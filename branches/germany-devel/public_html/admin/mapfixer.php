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

$USER->hasPerm("admin") || $USER->hasPerm("mapmod") || $USER->mustHavePerm("moderator");

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
			$gridsquare_id=$sq['gridsquare_id'];
		} else {
			$gridsquare_id = false;
		}

		$recordSet = &$db->Execute("SELECT name,level,community_id FROM loc_hier ORDER BY level,name");
		while (!$recordSet->EOF) {
			$regionlist[$recordSet->fields[1]."_".$recordSet->fields[2]] = $recordSet->fields[0];
			$recordSet->MoveNext();
		}
		$recordSet->Close();
		$smarty->assign_by_ref('regionlist', $regionlist);

		//update?
		if (isset($_GET['save']))
		{
			$recalclp = false;
			$error = false;
			$createsq = false;
			$createregperc = false;
			$setregperc = false;
			$setperc = false;
			$percent=intval($_GET['percent_land']);
			if (isset($_GET['region']) &&  preg_match('/^[+-]?\d+_\d+$/',$_GET['region'])) {
				list($level,$cid) = explode('_',$_GET['region']);
				$level = intval($level);
				$cid = intval($cid);
				$smarty->assign('region', $_GET['region']);
				$percentreg = $percent;
				$percent = 0;
				$recalclp = $level == -1;
			} else {
				$level=-2;
				$cid=0;
				if ($percent == -2) {
					$percent = 0;
					$recalclp = true;
				}
			}
			if (count($sq)) {
				$gridsquare_id=$sq['gridsquare_id'];
				$x=$sq['x'];
				$y=$sq['y'];
				$oldperc = $sq['percent_land'];
				if ($level >= -1) {
					$oldpercreg = $db->GetOne("select percent from gridsquare_percentage where gridsquare_id=$gridsquare_id and level=$level and community_id=$cid");
					if ($oldpercreg === null) {
						$oldpercreg = 0;
						$createregperc = true;
					}
				} else {
					$oldpercreg = 0;
				}
			} else {
				//we need to create a square
				$matches=array();
				preg_match('/^([A-Z]{1,3})(\d\d)(\d\d)$/',$gridref, $matches);

				$gridsquare=$matches[1];
				$eastings=$matches[2];
				$northings=$matches[3];

				$sql="select * from gridprefix where prefix='{$gridsquare}' limit 1";
				$prefix=$db->GetRow($sql);
				if (count($prefix)) {
					$x=$prefix['origin_x'] + $eastings;
					$y=$prefix['origin_y'] + $northings;

					$sql="insert into gridsquare(x,y,percent_land,grid_reference,reference_index,point_xy) ".
						"values($x,$y,$percent,'$gridref',{$prefix['reference_index']},GeomFromText('POINT($x $y)') )";
					$db->Execute($sql);
					$gridsquare_id=$db->Insert_ID();
				} else {
					$smarty->assign('gridref_error', "Error, please try again later");
					$error = true;
				}
				$oldperc = 0;
				$oldpercreg = 0;
				$createsq = true;
				$createregperc = true;
			}
			if (!$error && $level >= -1) { # set regional percentage
				$sql="replace into gridsquare_percentage(gridsquare_id,level,community_id,percent) ".
					"values($gridsquare_id,$level,$cid,$percentreg)";
				$db->Execute($sql);
				$setregperc = true;
			}
			if (!$error && $recalclp) { # recalculate percent_land from other percentages
				# lpX is coalesce(gpX.percent,0) which is the percentage for level==-1 and cid==X (0 if not present)
				# lp1: land percentage (low water; lakes are included!)
				# lp2: land percentage (high water; lakes are included!)
				# lp3: lake percentage (low water)
				# lp4: lake percentage (high water)
				# photographs allowed: lp1 > 0
				# geographs possible:  lp1-lp3 > 0
				# map colours: only high water is taken into account for lakes; mudflats: factor 0.5
				#              => max(0.5*lp1 + 0.5*lp2 - lp4, 0)          [ "max" to ensure percentage >= 0 ]
				#              for compatibility reasons, the minimal value is set to 1 if lp1 > 0, i.e. photographs are allowed
				#              => max(0.5*lp1 + 0.5*lp2 - lp4, lp1>0 ? 1:0)
				$sql = "select ".
					"greatest(round(0.5*coalesce(gp1.percent,0)+0.5*coalesce(gp2.percent,0))-coalesce(gp4.percent,0),coalesce(gp1.percent,0)>0) as percent_land, ".
					"coalesce(gp1.percent,0)>0 as permit_photographs, ".
					"coalesce(gp1.percent,0)-coalesce(gp3.percent,0)>0 as permit_geographs ".
					"from gridsquare gs ".
					"left join gridsquare_percentage gp1 on (gs.gridsquare_id=gp1.gridsquare_id and gp1.level = -1 and gp1.community_id = 1) ".
					"left join gridsquare_percentage gp2 on (gs.gridsquare_id=gp2.gridsquare_id and gp1.level = -1 and gp2.community_id = 2) ".
					"left join gridsquare_percentage gp3 on (gs.gridsquare_id=gp3.gridsquare_id and gp1.level = -1 and gp3.community_id = 3) ".
					"left join gridsquare_percentage gp4 on (gs.gridsquare_id=gp4.gridsquare_id and gp1.level = -1 and gp4.community_id = 4) ".
					"where gs.gridsquare_id = $gridsquare_id limit 1";
				$newvalues = $db->GetRow($sql);
				$db->Execute("update gridsquare set ".
					"percent_land='{$newvalues['percent_land']}',".
					"permit_photographs='{$newvalues['permit_photographs']}',".
					"permit_geographs='{$newvalues['permit_geographs']}' ".
					"where gridsquare_id='{$gridsquare_id}'");
				$percent = $newvalues['percent_land'];
			}
			if (!$error && $level < -1 && !$recalclp && !$createsq) { # set percent_land if not already done when creating the square or recalculating the percentage
				$setperc = true;
				//update existing square
				$db->Execute("update gridsquare set percent_land='{$percent}' where gridsquare_id='{$sq['gridsquare_id']}'");
			}
			if (!$error) {
				$status = array();
				if ($createsq) {
					if ($recalclp)
						$status[] = "New gridsquare $gridref created.";
					else
						$status[] = "New gridsquare $gridref created with new land percentage of $percent %.";
				}
				if ($recalclp)
					$status[] = "Gridsquare $gridref updated with calculated land percentage of $percent %.";
				if ($setperc)
					$status[] = "Gridsquare $gridref updated with new land percentage of $percent %.";
				if ($setregperc) {
					if ($createregperc)
						$status[] = "Added regional percentage for square $gridref, $level, $cid: $percentreg %.";
					else
						$status[] = "Changed regional percentage for square $gridref, $level, $cid: $percentreg %.";
				}
				$smarty->assign('status', implode(' ', $status));
				if ($createsq||$recalclp||$setperc)
					$db->Execute("REPLACE INTO mapfix_log SET user_id = {$USER->user_id}, gridsquare_id = {$gridsquare_id}, new_percent_land='{$percent}', old_percent_land='{$oldperc}',created=now(),comment=".$db->Quote($_GET['comment']));
				if ($setregperc)
					$db->Execute("REPLACE INTO mapfix_log SET user_id = {$USER->user_id}, gridsquare_id = {$gridsquare_id}, level={$level}, community_id={$cid}, new_percent_land='{$percentreg}', old_percent_land='{$oldpercrec}',created=now(),comment=".$db->Quote($_GET['comment']));
				require_once('geograph/mapmosaic.class.php');
				$mosaic = new GeographMapMosaic;
				$mosaic->expirePosition($x,$y,0,true);
			}
			$smarty->assign('percent_land', $percent);
		}
		if ($gridsquare_id !== false) {
			$hier= $db->GetAssoc("select lh.name,gp.percent from gridsquare_percentage gp inner join loc_hier lh on (gp.level=lh.level and gp.community_id=lh.community_id) where gp.gridsquare_id={$gridsquare_id} order by lh.level");
			$smarty->assign('hier', $hier);
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
