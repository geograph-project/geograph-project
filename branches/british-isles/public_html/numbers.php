<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 2950 2007-01-14 23:45:28Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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
init_session();


$smarty = new GeographPage;

$template='numbers.tpl';
$cacheid='';

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	
	//lets find some recent photos
	new RecentImageList($smarty);
	
	$db = GeographDatabaseConnection(true);
	

	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$hectads= $db->getAll("SELECT * FROM hectad_stat WHERE geosquares >= landsquares ORDER BY last_submitted DESC LIMIT 10");
	$smarty->assign_by_ref('hectads', $hectads);
	
	$stats= $db->GetRow("select * from user_stat where user_id = 0");
	$stats += $db->GetRow("select count(*)-1 as users from user_stat");
	$stats += $db->cacheGetRow(3600,"select count(*) as total,sum(imagecount in (1,2,3)) as fewphotos from gridsquare where percent_land > 0");
	
	$stats['nophotos'] = $stats['total'] - $stats['squares'];
	
	$stats['percentage'] = sprintf("%.2f",$stats['points']/$stats['total']*100);
	$stats['fewpercentage'] = sprintf("%.2f",$stats['fewphotos']/$stats['total']*100);
	$stats['negfewpercentage'] = sprintf("%.1f",100-$stats['fewpercentage']);
	$stats['persquare'] = sprintf("%.1f",$stats['images']/$stats['squares']);
	$stats['peruser'] = sprintf("%.1f",$stats['images']/$stats['users']);
	
	
	$smarty->assign_by_ref('stats', $stats);
}


$smarty->display($template, $cacheid);


