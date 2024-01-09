<?php
/**
 * $Project: GeoGraph $
 * $Id: juploader.php 8210 2014-11-29 21:52:36Z geograph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 David Morris 
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
require_once('geograph/uploadmanager.class.php');

init_session();

$USER->mustHavePerm('basic');

$db = GeographDatabaseConnection(false);

$uploadmanager=new UploadManager;

///[taken] => 2024-01-02T16:43:05.228Z [lat] => 51.1279104 [lng] => -0.0131072 [dir] => 360 [src] => data:image/jpeg;base64,/9j/4AAQSkZJRgABAgEC0ALQ

######################################

if (strpos($_POST['src'], "data:image/jpeg;base64,") !== 0)
	die("Only submission of jpegs allowed");

######################################

//ideally would be a $um->processDataURL Or processLocalFile

$upload_id=md5(uniqid('upload'));
$temp_file = tempnam("/tmp",'upload');

$bits = explode(',',$_POST['src'],2);
$encodedData = str_replace(' ','+', $bits[1]); //https://stackoverflow.com/questions/6735414/php-data-uri-to-file
unset($bits); //can be quite big!
file_put_contents($temp_file, base64_decode($encodedData));
unset($encodedData);

if ($uploadmanager->_isJpeg($temp_file)) {
         $ok = $uploadmanager->_processFile($upload_id,$temp_file,false);

	if (file_exists($temp_file)) //it SHOULD of been moved!;
		unlink($temp_file);

} else {
	@unlink($temp_file);

	die("only submission of jpegs allowed");
}

//tod0, store the taken+lat+long somewhere. maybe write then to EXIF (incase not there!) 

######################################

       require_once('geograph/conversions.class.php');
        $conv = new Conversions;

list($e,$n,$reference_index) = $conv->wgs84_to_national($_POST['lat'],$_POST['lng'],true);

list ($photographer_gridref,$len) = $conv->national_to_gridref(intval($e),intval($n),0,$reference_index);
list ($grid_reference,$len) = $conv->national_to_gridref(intval($e),intval($n),4,$reference_index);


//print "Subject: $grid_reference. Photographer: $photographer_gridref.<hr>";

?>
<html>
<head>
 <meta name="viewport" content="minimal-ui, width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#000066" />
</head>
<body>


$url = "/submit2.php?transfer_id={$upload_id}&gridref=$photographer_gridref"; //gr is NOT used, but submit it incase!

print "<a href=\"$url\"\>Continue with submit v2</a>";

?>

- or - <br><br>

<form action="/submit.php" method="post" target="_blank" style="margin:0; background-color:lightgrey; padding:5px">
         Subject GR: <input type="text" name="grid_reference" size="10" value="<? echo $grid_reference; ?>"/> <br/>
         Camera: <input type="text" name="photographer_gridref" size="10" value="<? echo $photographer_gridref; ?>"/><br/>

         <br/><input type="hidden" name="gridsquare" value="1">

         <input type="hidden" name="transfer_id" value="<? echo $upload_id; ?>">

         <input type="submit" value="continue with v1 &gt;">

</form>

