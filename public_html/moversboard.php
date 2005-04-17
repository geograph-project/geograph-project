<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

$template='moversboard.tpl';
$cacheid='dfg';

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed'); 
	#$db->debug=true;

	$topusers=$db->GetAssoc("select user.user_id,realname,count(*) as newcount,max(submitted) as last  ".
	"from user inner join gridimage using(user_id) where ftf=1 ".
	"and (unix_timestamp(now())-unix_timestamp(submitted))<604800 ".
	"group by user_id order by newcount desc,last asc limit 50");
	
	$pending=$db->GetAssoc("select user_id,count(*) as imgcount  ".
	"from gridimage where moderation_status = 'pending' ".
	"group by user_id");
	
	
	$i++;
	foreach($topusers as $idx=>$entry)
	{
			$topusers[$idx]['pending'] = $pending[$idx]['imgcount'];
	
		$units=$i%10;
		switch($units)
		{
			case 1:$end=($i==11)?'th':'st';break;
			case 2:$end=($i==12)?'th':'nd';break;
			case 3:$end=($i==13)?'th':'rd';break;
			default: $end="th";	
		}
		
		$topusers[$idx]['ordinal']=$i.$end;
		$i++;
	}
	
	$smarty->assign_by_ref('topusers', $topusers);
	
	//lets find some recent photos
	$recent=new ImageList(array('pending', 'accepted', 'geograph'), 'submitted desc', 5);
	$recent->assignSmarty(&$smarty, 'recent');
}

$smarty->display($template, $cacheid);

	
?>
