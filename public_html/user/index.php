<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php,v 1.2 2006/04/19 10:00:00 barryhunter Exp $
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

$template='user.tpl';

$cacheid=0;

//regenerate?
if (!$smarty->is_cached($template,$cacheid))
{
	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
	}

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$users = $db->GetAssoc("select
		nickname,gi.realname,gi.user_id,count(*) as images
		from gridimage_search gi
			inner join user using (user_id)
		group by gi.user_id 
		order by images desc");

	unset($users['']);
	unset($users[' ']);
	

	$size = $startsize = 30;
		$sizedelta = 0.05;

	foreach($users as $nick=>$obj) {
				
		$users[$nick]['size'] = round($size);
		
		$size -= $sizedelta; 
	
		if ($size < 10)
			$sizedelta = 0;

	}
	
	function cmp($a, $b) {
		return strnatcasecmp ($a, $b);
	}
	uksort($users, "cmp");

	$smarty->assign_by_ref('users',$users);	
}




$smarty->display($template,$cacheid);

	
?>
