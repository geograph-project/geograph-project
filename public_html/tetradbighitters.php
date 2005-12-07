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
init_session();




$smarty = new GeographPage;

$template='tetradbighitters.tpl';
$cacheid='tetradbighitters';

if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, $cacheid);


$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*3*24; //3day cache

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	$topsquares = array();
	foreach (array(1,2) as $ri) {
		$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?
		
		$fully = $db->GetAssoc("select 
		concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1)) as tenk_square,
		sum(has_geographs) as geograph_count,
		(sum(has_geographs) * 100 / sum(percent_land >0)) as percentage
		from gridsquare 
		where reference_index = $ri 
		group by tenk_square 
		having geograph_count > 0 and percentage >=100
		order by null
		");
		
		if (count($fully)) {	
			$most = $db->GetAssoc("select 
			concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1)) as tenk_square,
			user_id,realname,
			count(*) as squares
			from gridimage_search
			where reference_index = $ri and
			moderation_status = 'geograph' and
			ftf = 1
			group by tenk_square ,user_id
			order by null");

			foreach($most as $tenk_square => $entry) {
				if (isset($fully[$tenk_square])) {
					if (isset($topsquares[$tenk_square])) {
						if ($entry['squares'] > $topsquares[$tenk_square]['squares']) {
							$topsquares[$tenk_square] = $entry;
						}
					} else {
						$topsquares[$tenk_square] = $entry;
					}
				}
			}
		}
	}
	
	
	$topusers = array();
	foreach($topsquares as $tenk_square=>$entry)
	{
		if (isset($topusers[$entry['user_id']])) {
			$topusers[$entry['user_id']]['sqcount']++;
		} else {
			$topusers[$entry['user_id']] = $entry;
			$topusers[$entry['user_id']]['sqcount'] = 1;
		}
	}
	
	function cmp(&$a, &$b) 
	{
	   global $topusers;
	   if ($a['sqcount'] == $b['sqcount']) {
	       return 0;
	   }
	   return ($a['sqcount'] > $b['sqcount']) ? -1 : 1;
	}
	
	uasort($topusers, "cmp");
	
	
	$i = 1; $lastimgcount = '?';
	foreach($topusers as $idx=>$entry)
	{
		if ($lastimgcount == $topusers[$idx]['sqcount'])
			$topusers[$idx]['ordinal'] = '&nbsp;&nbsp;&nbsp;&quot;';
		else {
			$units=$i%10;
			switch($units)
			{
				case 1:$end=($i==11)?'th':'st';break;
				case 2:$end=($i==12)?'th':'nd';break;
				case 3:$end=($i==13)?'th':'rd';break;
				default: $end="th";	
			}

			$topusers[$idx]['ordinal']=$i.$end;
			$lastimgcount = $topusers[$idx]['sqcount'];
		}
		$i++;
	}
	
	$smarty->assign_by_ref('topusers', $topusers);
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

	
?>
