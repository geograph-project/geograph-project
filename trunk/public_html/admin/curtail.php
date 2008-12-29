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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$USER->mustHavePerm("admin");

print '<a href="curtail.php">reload</a><hr/>';

print "<p>Current Level: <b>{$CONF['curtail_level']}</b>";
if (isset($CONF['real_curtail_level'])) {
	print " (temporally override in effect - <a href='?level=0&amp;for=1s'>clear</a>)</p>";
	print "<p style='color:gray'>Global Level: <b>{$CONF['real_curtail_level']}</b> (the value from the config file)</p>";
} else {
	print "</p>";
}
$level = $CONF['curtail_level'];

if (isset($_REQUEST['level'])) {
	$level = intval($_REQUEST['level']);

	$for = empty($_REQUEST['for'])?1:$_REQUEST['for'];
	$for = str_replace('s','*1',$for);
	$for = str_replace('m','*60',$for);
	$for = str_replace('h','*3600',$for);
	$for = str_replace('d','*86400',$for);
	
	$l2 = $level+1;
	eval("\$for = $for;");
	
	$memcache->set('curtail_level',$l2,$memcache->compress,$for);//+1 to easily detect not set
		
	print "<h4>Set to $level for {$_REQUEST['for']}</h4>";
}

$a = array();
$a[$level] = " checked";

?>

<hr/>

<form>

<b>Set temporally to:</b> (setting to a level applies all the limitations above too) 
<ol start="0">
	<li><input type="radio" name="level" value="0" <? echo $a[0]; ?>/>  -all normal-</li>
	<li><input type="radio" name="level" value="1" <? echo $a[1]; ?>/>  -unused-</li>
	<li><input type="radio" name="level" value="2" <? echo $a[2]; ?>/>  Redirect GridImages (thumbs&amp;full) <small>- only applies to NON signed in users</small></li>
	<li><input type="radio" name="level" value="3" <? echo $a[3]; ?>/>  Redirect Coverage Maps <small>- only applies to NON signed in users</small></li>
	<li><input type="radio" name="level" value="4" <? echo $a[4]; ?>/>  Redirect Raster Maps <small>- only applies to NON signed in users</small></li>
	<li><input type="radio" name="level" value="5" <? echo $a[5]; ?>/>  Redirect Javascript &amp; Disable Legacy Search</li>
	<li><input type="radio" name="level" value="6" <? echo $a[6]; ?>/>  Disable Admin/Moderation</li>
	<li><input type="radio" name="level" value="7" <? echo $a[7]; ?>/>  Disable all (non cached) Statistics pages</li>
	<li><input type="radio" name="level" value="8" <? echo $a[8]; ?>/>  -unused-</li>
	<li><input type="radio" name="level" value="9" <? echo $a[9]; ?>/>  Disable Games</li>
	<li><input type="radio" name="level" value="10" <? echo $a[10]; ?>/> Disable Forum</li>
</ol>

<b>For:</b>
<ul>
	<li><input type="radio" name="for" value="1m"/> 1 minute</li>
	<li><input type="radio" name="for" value="10m"/> 10 minutes</li>
	<li><input type="radio" name="for" value="1h"/> 1 hour</li>
	<li><input type="radio" name="for" value="2h"/> 2 hours</li>
	<li><input type="radio" name="for" value="3h"/> 3 hours</li>
	<li><input type="radio" name="for" value="6h"/> 6 hours</li>
	<li><input type="radio" name="for" value="12h"/> 12 hours</li>
	<li><input type="radio" name="for" value="1d"/> 24 hours</li>

</ul>

<input type="submit" name="submit" value="Set Override Level" />

</form>