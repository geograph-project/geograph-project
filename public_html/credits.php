<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php,v 1.2 2006/04/19 10:00:00 barryhunter Exp $
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

if (!empty($_GET['i'])) {
	header("Location: /statistics/breakdown.php?by=user&i=".intval($_GET['i']));
	exit;
}

$smarty = new GeographPage;
if (isset($_GET['cloud'])) {
	$template='user.tpl';
} else {
	$template='userlist.tpl';
}

$when = (isset($_GET['when']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when']))?$_GET['when']:'';

$where = (isset($_GET['where']) && preg_match('/^[\w]{1,}$/',$_GET['where']))?$_GET['where']:'';

customGZipHandlerStart();

$cacheid=$when.'|'.$where;

//regenerate?
if (!$smarty->is_cached($template,$cacheid))
{
	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
	}
	$andwhere = '';
	
	if ($when) {
		if (strlen($when) == 7) {
			$andwhere = " and submitted < DATE_ADD('$when-01',interval 1 month)";
		} elseif (strlen($when) == 4) {
			$andwhere = " and submitted < DATE_ADD('$when-01-01',interval 1 year)";
		} else {
			$andwhere = " and submitted < '$when'";
		}
		$smarty->assign_by_ref('when',$when);
		$smarty->assign('whenname',getFormattedDate($when));
	}
	if ($where) {
		$andwhere .= " and grid_reference like '$where%'";
		
		$smarty->assign_by_ref('where',$where);
	}
	

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	if (isset($_GET['cloud'])) {
		if ($andwhere) {
			$users = $db->CacheGetAssoc(3600*6,"select
				nickname,user.user_id,user.realname,user.user_id,count(*) as images
				from user
					inner join gridimage_search gi using (user_id)
				where nickname != '' and rights IS NOT NULL $andwhere
				group by gi.user_id
				order by images desc");
			$smarty->assign('what','Contributors');
		} else {
			$users = $db->CacheGetAssoc(3600*6,"select
				nickname,user.user_id,user.realname,user.user_id,`us`.images
				from user
					inner join user_stat `us` using (user_id)
				where nickname != '' and rights IS NOT NULL 
				order by images desc");
			$smarty->assign('what','Contributors');
		}

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
	} else {
		if ($andwhere) {
			$users = $db->CacheGetAssoc(3600*6,"select
				user.user_id,nickname,gi.realname as realname,user.realname as user_realname,user.user_id,count(*) as images
				from user
					inner join gridimage_search gi using (user_id)
				where rights IS NOT NULL $andwhere
				group by gi.user_id,gi.realname
				order by realname");
			$smarty->assign('what','Photographers'); //because we *can*
		} else {
			$users = $db->CacheGetAssoc(3600*6,"select
				user.user_id,nickname,user.realname,user.user_id,`us`.images
				from user
					inner join user_stat `us` using (user_id)
				where rights IS NOT NULL
				order by realname");
			$smarty->assign('what','Contributors');
		}
	}

	$smarty->assign_by_ref('users',$users);
	$smarty->assign('user_count',count($users));
}





$smarty->display($template,$cacheid);


?>
