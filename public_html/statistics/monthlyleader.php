<?php
/**
 * $Project: GeoGraph $
 * $Id: monthlyleader.php 8076 2014-04-09 19:41:09Z barry $
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

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

$template='statistics_monthlyleader.tpl';
$cacheid='';

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db = GeographDatabaseConnection(true);

	$topusers=$db->GetAll("
SELECT month AS submitted_month , user_id, realname, images as imgcount
FROM user_date_stat INNER JOIN user USING (user_id)
WHERE month != '' and type = 'submitted'
ORDER BY images DESC
LIMIT 150");

	$month = array();
	foreach($topusers as $idx=>$entry)
	{
		if (!isset($month[$topusers[$idx]['submitted_month']]) || $topusers[$idx]['imgcount'] > $month[$topusers[$idx]['submitted_month']]) {
			$month[$topusers[$idx]['submitted_month']] = $topusers[$idx]['imgcount'];
		}
	}
	foreach($topusers as $idx=>$entry)
	{
		if ($topusers[$idx]['imgcount'] < $month[$topusers[$idx]['submitted_month']]) {
			unset ($topusers[$idx]);
		} else {
			$topusers[$idx]['month'] = getFormattedDate($topusers[$idx]['submitted_month']);
		}
	}

	$smarty->assign_by_ref('topusers', $topusers);

	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

