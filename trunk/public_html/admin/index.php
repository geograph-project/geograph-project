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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;


//lets get some stats
$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

$users_total=$db->GetOne("select count(*) from user where rights>0");
$users_thisweek=$db->GetOne("select count(*) from user where rights>0 and ".
	"(unix_timestamp(now())-unix_timestamp(signup_date))<604800");
$users_pending=$db->GetOne("select count(*) from user where rights=0");

$images_total=$db->GetOne("select count(*) from gridimage where moderation_status<>'rejected'");
$images_thisweek=$db->GetOne("select count(*) from gridimage where moderation_status<>'rejected' and ".
	"(unix_timestamp(now())-unix_timestamp(submitted))<604800");
$images_pending=$db->GetOne("select count(*) from gridimage where moderation_status='pending'");

$smarty->assign('users_total',  $users_total);
$smarty->assign('users_thisweek',  $users_thisweek);
$smarty->assign('users_pending',  $users_pending);

$smarty->assign('images_total',  $images_total);
$smarty->assign('images_thisweek',  $images_thisweek);
$smarty->assign('images_pending',  $images_pending);

$smarty->display('admin_index.tpl');

	
?>
