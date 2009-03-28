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
require_once('geograph/eventprocessor.class.php');

set_time_limit(5000); 


//need perms if not requested locally
if ( ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) ||
     ($_SERVER['HTTP_X_FORWARDED_FOR']=='87.124.24.35'))
{
        $smarty=null;
}
else
{
	init_session();
        $smarty = new GeographPage;
        $USER->mustHavePerm("admin");
}



if (isset($_GET['start']))
{
	if ($smarty)

	{
		$smarty->display('_std_begin.tpl');
		echo "<h2><a title=\"Admin home page\" href=\"/admin/index.php\">Admin</a> : ";
		echo "<a title=\"Event Diagnostics\" href=\"/admin/events.php\">Events</a> : ";
		echo "Processing Events...</h2>";
		flush();
	}

	
	$processor=new EventProcessor;
	$processor->setTestMode(isset($_GET['testmode'])?$_GET['testmode']:0);
	$processor->setVerbosity(isset($_GET['verbosity'])?$_GET['verbosity']:3);
	$processor->setMaxTime(isset($_GET['max_execution'])?$_GET['max_execution']:180);
	$processor->setMaxLoad(isset($_GET['max_load'])?$_GET['max_load']:0.8);

	$processor->start();

	if ($smarty)
	{
		$smarty->display('_std_end.tpl');
	}
	exit;
}


$smarty->display('admin_processevents.tpl');
?>
