<?php
/**
 * $Project: GeoGraph $
 * $Id: thankyou.php 2630 2006-10-18 21:12:28Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
 
require_once('geograph/functions.inc.php');
 customExpiresHeader(86400*180,true);
 
?>
<html>
<head>
<title>Done</title>
</head>
<body>
<h2>Thank You</h2>
<p>The result of your request has been saved. You may <a href="javascript:window.close();">close this window/tab</a>.</p>
</body>
</html>
