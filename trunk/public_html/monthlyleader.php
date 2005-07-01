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

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

$template='monthlyleader.tpl';
$cacheid=$_GET['month'];

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	$length = $_GET['month']?10:7;

	$topusers=$db->GetAll("SELECT gridimage_id,SUBSTRING( submitted, 1, $length ) AS 
submitted_month , user_id, realname, COUNT(*) as imgcount
FROM `gridimage_search` 
GROUP BY SUBSTRING( submitted, 1, $length ) , user_id");
	
	foreach($topusers as $idx=>$entry)
	{
		if ($topusers[$idx]['imgcount'] > $month[$topusers[$idx]['submitted_month']]) {
			$month[$topusers[$idx]['submitted_month']] = $topusers[$idx]['imgcount'];
		}
	}
	$iamge = new GridImage;
	foreach($topusers as $idx=>$entry)
	{
		if ($topusers[$idx]['imgcount'] < $month[$topusers[$idx]['submitted_month']]) {
			unset ($topusers[$idx]);
		} else {
			$iamge->imagetaken = $topusers[$idx]['submitted_month'];
			$topusers[$idx]['month'] = $iamge->getFormattedTakenDate();
		}
	}
	
	$smarty->assign_by_ref('topusers', $topusers);
	
	//lets find some recent photos
	$recent=new ImageList(array('pending', 'accepted', 'geograph'), 'submitted desc', 5);
	$recent->assignSmarty($smarty, 'recent');
}

$smarty->display($template, $cacheid);

	
?>
