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

/**
* Provides a way for other processes on the local host to fire an event
* cron uses this to fire time based events
*/

require_once('geograph/global.inc.php');
require_once('geograph/event.class.php');

//need perms if not requested locally
if (($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) &&
     ($_SERVER['HTTP_X_FORWARDED_FOR']!='87.124.24.35'))
{
	die("Can only request this from localhost");
}

if (isset($_GET['event'])&& isset($_GET['param']) && isset($_GET['priority']))
{
	Event::fire(stripslashes($_GET['event']), stripslashes($_GET['param']), stripslashes($_GET['priority']));
	echo "OK: {$_GET['event']}({$_GET['param']}, {$_GET['priority']}) fired successfully\n";
}
else
{
	echo "FAIL: No must specify event_name, event_param and event_priority\n";
}

?>
