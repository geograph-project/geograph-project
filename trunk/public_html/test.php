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
 * This file should test the local environment to ensure the geograph
 * application can run successfully
 */

$ok=true;

echo "<h1>Geograph System Test...</h1>";
//do some tests
if (!extension_loaded('gd'))
{
	$ok=false;
	echo "<li>PHP GD extension not available - REQUIRED</li>";
}

//show some diagnostics if not ok...
if (!$ok)
{
	echo "<br><br><br><br>";
	phpinfo();
}
else
{
	echo "<li>Server is correctly configured to run Geograph!</li>";
}

?>
