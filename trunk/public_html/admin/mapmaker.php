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
require_once('geograph/mapmaker.class.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

$default_x1=$db->GetOne("select min(x) from gridsquare");
$default_y1=$db->GetOne("select min(y) from gridsquare");

$default_x2=$db->GetOne("select max(x) from gridsquare");
$default_y2=$db->GetOne("select max(y) from gridsquare");

//gather inputs
$x1=isset($_POST['x1'])?$_POST['x1']:$default_x1;
$y1=isset($_POST['y1'])?$_POST['y1']:$default_y1;
$x2=isset($_POST['x2'])?$_POST['x2']:$default_x2;
$y2=isset($_POST['y2'])?$_POST['y2']:$default_y2;

//do some processing?
if (isset($_POST['make']))
{
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"mapmaker.php\">&lt;&lt;</a> Building map...</h3>";
	flush();
	set_time_limit(3600*24);
	
	$mapmaker=new MapMaker;
	$imgfile=$mapmaker->build($x1, $y1, $x2, $y2);
	
	echo "<img src=\"$imgfile\">";
	
	$smarty->display('_std_end.tpl');
	exit;
}


$smarty->assign('x1', $x1);
$smarty->assign('x2', $x2);
$smarty->assign('y1', $y1);
$smarty->assign('y2', $y2);
$smarty->display('mapmaker.tpl');

	
?>
