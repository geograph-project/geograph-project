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
$shader_image=isset($_POST['shader_image'])?$_POST['shader_image']:'admin/channel-islands.png';
$shader_x=isset($_POST['shader_x'])?$_POST['shader_x']:15;
$shader_y=isset($_POST['shader_y'])?$_POST['shader_y']:40;
$reference_index=isset($_POST['reference_index'])?$_POST['reference_index']:6;


$clearexisting=isset($_POST['clearexisting'])?true:false;
$skipupdategridprefix=isset($_POST['skipupdategridprefix'])?true:false;
$redrawmaps=isset($_POST['redrawmaps'])?true:false;
$ignore100=isset($_POST['ignore100'])?true:false;
$dryrun=isset($_POST['dryrun'])?true:false;

/*
ireland image is 
left = 16808.222
right = 366547.765
top = 19480.97
bottom = 465594.31

so bottom left of image is 16.808,19.480 km from Irish origin

Irish origin is at 0,159

So this bitmap should be 17,178

This doesn't look right! Try 27,168 (Irish origin of 10,149)
*/

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

	$shader->process($imgfile, $shader_x, $shader_y, $reference_index, $clearexisting, !$skipupdategridprefix,$redrawmaps,$ignore100,$dryrun);
	

	//close output and exit (we don't want to output a page twice)

	$smarty->display('_std_end.tpl');
	exit;
}


$smarty->assign('shader_image', $shader_image);
$smarty->assign('shader_x', $shader_x);
$smarty->assign('shader_y', $shader_y);
$smarty->assign('clearexisting', $clearexisting);
$smarty->assign('skipupdategridprefix', $skipupdategridprefix);
$smarty->assign('redrawmaps', $redrawmaps);
$smarty->assign('ignore100', $ignore100);
$smarty->assign('reference_index', $reference_index);
$smarty->assign('dryrun', $dryrun);

$smarty->display('gridbuilder.tpl');

	
?>
