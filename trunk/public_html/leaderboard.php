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
init_session();




$smarty = new GeographPage;

$template='leaderboard.tpl';
$cacheid='leaderboard';

if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, $cacheid);


$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*3; //3hour cache

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	//GetOne adds a LIMIT even when we already supply one!
	$rank50 = $db->GetRow("select count(*) as imgcount ".
	"from gridimage_search where ftf=1 ".
	"group by user_id order by imgcount desc limit 49,1");
	
	$topusers=$db->GetAll("select user_id,realname,count(*) as imgcount,max(gridimage_id) as last  ".
	"from gridimage_search where ftf=1 ".
	"group by user_id HAVING imgcount >= {$rank50[0]} order by imgcount desc,last asc ");
	
	foreach($topusers as $idx=>$entry)
	{
		$i=$idx+1;
		if ($lastimgcount == $topusers[$idx]['imgcount'])
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
			$lastimgcount = $topusers[$idx]['imgcount'];
		}
	}
	
	$smarty->assign_by_ref('topusers', $topusers);
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

	
?>
