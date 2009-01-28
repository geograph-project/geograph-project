<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
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

require_once('geograph/global.inc.php');
init_session();

if (!function_exists('apc_fetch')) {
	die("no apc installed");
}

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

$smarty->display('_std_begin.tpl');
flush();
	
if ($_POST) {
	apc_store($_POST['name'],$_POST['value'],intval($_POST['for']));
	print "Saved!";
} 

?>
<form method="post">

<h3>APC Set</h3>

Name: <input type="text" name="name" value=""/><br/>

Value: <input type="text" name="value" value="1"><br/>

For: <input type="text" name="for" value="30" size=3> seconds<br/>


<input type=submit>

</form>

<?
$smarty->display('_std_end.tpl');
exit;
?>