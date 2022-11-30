<?php
/**
 * $Project: GeoGraph $
 * $Id: first2square.php 7375 2011-08-13 23:13:01Z barry $
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

$template='statistics_first2square.tpl';
$cacheid='statistics|first2square';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*3*24; //3day cache

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db = GeographDatabaseConnection(true);

	$topusers = $db->GetAll("
		select user_id,realname,count(*) as imgcount
		from (select user_id from user_gridsquare group by right(grid_reference,4) order by null) t1
		inner join user using (user_id) group by user_id");

	function cmp($a, $b)
	{
	   if ($a['imgcount'] == $b['imgcount']) {
	       return 0;
	   }
	   return ($a['imgcount'] > $b['imgcount']) ? -1 : 1;
	}
	uasort($topusers, "cmp");

	$i = 1; $lastimgcount = '?';
	foreach($topusers as $idx=>$entry)
	{
		if ($lastimgcount == $topusers[$idx]['imgcount'])
			$topusers[$idx]['ordinal'] = '&nbsp;&nbsp;&nbsp;&quot;';
		else {
			$topusers[$idx]['ordinal'] = smarty_function_ordinal($i);
			$lastimgcount = $topusers[$idx]['imgcount'];
		}
		$i++;
	}

	$smarty->assign_by_ref('topusers', $topusers);

	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

