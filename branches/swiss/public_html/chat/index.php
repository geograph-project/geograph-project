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
$USER->mustHavePerm("basic");

$smarty = new GeographPage;


//figure out nickname
if (isset($_GET['nickname']))
{
	$nick=$_GET['nickname'];
}
elseif (strlen($USER->nickname))
{
	$nick=$USER->nickname;
}
else
{
	$nick=$USER->realname;
}

//clean up nick
$nick=trim($nick);
$nick=preg_replace('/[^a-z0-9|_]/i', '', $nick);
if (strlen($nick)==0)
{
	$nick="Geograph".$USER->user_id;	
}

//give it to smarty
$smarty->assign('nickname', $nick);
$smarty->assign('realname', $USER->realname);

//show the applet?
if (isset($_GET['join']))
{
	$smarty->assign('show_applet', 1);
}

$template='chat.tpl';
$smarty->display($template);

	
?>
