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

$USER->hasPerm("mapmod") || $USER->mustHavePerm("admin");

$smarty = new GeographPage;

if (isset($_POST['newfilename']) && isset($_POST['shader_image_new'])) {
	$_POST['shader_image'] = $_POST['shader_image_new'];
}

if (!empty($_FILES['uploadpng'])) {
	if (isset($_POST['upload']) && $_FILES['uploadpng']['error'] === 0 && filesize($_FILES['uploadpng']['tmp_name'])) {
		$name = basename($_FILES['uploadpng']['name']);
		$name = preg_replace('/[^-_A-Za-z0-9.]/', '', $name);
		$name = preg_replace('/\.[pP][nN][gG]$/', '', $name);
		$name .= '.png';
		$prefix = 'u'.$USER->user_id.strftime('_%Y-%m-%d_%H.%M.%S_');
		$ok = false;
		for ($i = 0; $i < 10; $i++) {
			$filename = $prefix.$i.'_'.$name;
			$fullname = $_SERVER['DOCUMENT_ROOT'].'/admin/gridshade/'.$filename;
			if (!file_exists($fullname)) {
				$ok = true;
				break;
			}
		}
		if ($ok && copy($_FILES['uploadpng']['tmp_name'], $fullname)) {
			$_POST['shader_image']=$filename;
		}
	}
	@unlink($_FILES['uploadpng']['tmp_name']);
}

//gather inputs
$shader_image='';
if (isset($_POST['shader_image']) && preg_match('/^[-_A-Za-z0-9][-_A-Za-z0-9.]*\.[pP][nN][gG]$/', $_POST['shader_image'])) {
	$shader_image=$_POST['shader_image'];
}
$shader_x=isset($_POST['shader_x'])?$_POST['shader_x']:(54 + 206);                 #FIXME default?
$shader_y=isset($_POST['shader_y'])?$_POST['shader_y']:7;                          #FIXME default?
$reference_index=isset($_POST['reference_index'])?$_POST['reference_index']:1;     #FIXME default?


$clearexisting=isset($_POST['clearexisting'])?true:false;
$skipupdategridprefix=isset($_POST['skipupdategridprefix'])?true:false;
$redrawmaps=isset($_POST['redrawmaps'])?true:false;
$ignore100=isset($_POST['ignore100'])?true:false;
$dryrun=isset($_POST['dryrun'])?true:false;
$minx=isset($_POST['minx'])?intval($_POST['minx']):0;
$maxx=isset($_POST['maxx'])?intval($_POST['maxx']):100000;
$miny=isset($_POST['miny'])?intval($_POST['miny']):0;
$maxy=isset($_POST['maxy'])?intval($_POST['maxy']):100000;

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

$smarty->assign('shader_image', $shader_image);
$smarty->assign('shader_x', $shader_x);
$smarty->assign('shader_y', $shader_y);
$smarty->assign('clearexisting', $clearexisting);
$smarty->assign('skipupdategridprefix', $skipupdategridprefix);
$smarty->assign('redrawmaps', $redrawmaps);
$smarty->assign('ignore100', $ignore100);
$smarty->assign('reference_index', $reference_index);
$smarty->assign('dryrun', $dryrun);
$smarty->assign('minx', $minx);
$smarty->assign('maxx', $maxx);
$smarty->assign('miny', $miny);
$smarty->assign('maxy', $maxy);

if (isset($_POST['listfiles'])) {
	$filelist = array();
	$files = glob( $_SERVER['DOCUMENT_ROOT'].'/admin/gridshade/*.[pP][nN][gG]', GLOB_NOESCAPE);
	foreach($files as $file) {
		$filelist[] = basename($file);
	}
	$smarty->assign('filelist', $filelist);
	$smarty->display('gridbuilder_files.tpl');
	exit;
}

if (isset($_POST['uploadfile'])) {
	$smarty->display('gridbuilder_upload.tpl');
	exit;
}

//do some processing?
if (isset($_POST['shader']))
{
	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"gridbuilder.php\">&lt;&lt;</a> Shading...</h3>";
	flush();
	set_time_limit(3600*24);
	
	//create shader and set it going!
	$imgfile=$_SERVER['DOCUMENT_ROOT'].'/admin/gridshade/'.$shader_image;
	$shader=new GridShader;
	if ($dryrun)
		$smarty->display('gridbuilder_back.tpl');

	$shader->process($imgfile, $shader_x, $shader_y, $reference_index, $clearexisting, !$skipupdategridprefix,$redrawmaps,$ignore100,$dryrun,$minx,$maxx,$miny,$maxy);
	
	//close output and exit (we don't want to output a page twice)
	if (!$dryrun)
		$smarty->display('gridbuilder_back.tpl');
	$smarty->display('_std_end.tpl');
	exit;
}

$smarty->display('gridbuilder.tpl');

?>
