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
require_once('geograph/gridshader.class.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;


//gather inputs
$shader_image=isset($_POST['shader_image'])?$_POST['shader_image']:'admin/gb.png';
$shader_x=isset($_POST['shader_x'])?$_POST['shader_x']:(54 + 206);
$shader_y=isset($_POST['shader_y'])?$_POST['shader_y']:7;

//do some processing?
if (isset($_POST['shader']))
{
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"gridbuilder.php\">&lt;&lt;</a> Shading...</h3>";
	flush();
	set_time_limit(3600*24);
	
	//create shader and set it going!
	$imgfile=$_SERVER['DOCUMENT_ROOT'].'/'.$shader_image;
	$shader=new GridShader;

	$shader->process($imgfile, $shader_x, $shader_y);
	

	//close output and exit (we don't want to output a page twice)

	$smarty->display('_std_end.tpl');
	exit;
}


$smarty->assign('shader_image', $shader_image);
$smarty->assign('shader_x', $shader_x);
$smarty->assign('shader_y', $shader_y);
$smarty->display('gridbuilder.tpl');

	
?>
