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
	
	$db=NewADOConnection($GLOBALS['DSN']);
	

	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$hectads= $db->getAll("select * from hectad_complete $wherewhere limit 10");
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
	
#	$test_data = array(); $labels = array(); $colours = array();
#	$test_data[] = $stats['percentage']; $colours[] = "0000ff"; $labels[] = urlencode($stats['percentage']."% or ".number_format($stats['squares']));
#	$test_data[] = 100 - $stats['percentage']; $colours[] = "00ff00"; $labels[] = number_format($stats['total'] - $stats['squares']);
	
#	$chart = "http://chart.apis.google.com/chart?cht=p3&chs=450x125&chl=".join('|',$labels)."&chco=".join('|',$colours).
#			"&chd=".chart_data($test_data)."&chtt=".urlencode(number_format($stats['total'])." Squares");
	
	$smarty->assign_by_ref('stats', $stats);
}


$smarty->display($template, $cacheid);


#echo "<img src=\"$chart\">";

function chart_data($values) {

	// Port of JavaScript from http://code.google.com/apis/chart/
	// http://james.cridland.net/code

	// First, find the maximum value from the values given
	$maxValue = max($values);

	// A list of encoding characters to help later, as per Google's example
	$simpleEncoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	$chartData = "s:";
	  for ($i = 0; $i < count($values); $i++) {
	    $currentValue = $values[$i];

	    if ($currentValue > -1) {
	    $chartData.=substr($simpleEncoding,61*($currentValue/$maxValue),1);
	    }
	      else {
	      $chartData.='_';
	      }
	  }

	// Return the chart data - and let the Y axis to show the maximum value
	return $chartData."&chxt=y&chxl=0:|0|".$maxValue;
}

	
?>
