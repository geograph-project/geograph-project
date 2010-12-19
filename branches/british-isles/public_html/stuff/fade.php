<?php
/**
 * $Project: GeoGraph $
 * $Id: captcha.php 5967 2009-10-31 12:31:43Z geograph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2010 Barry Hunter (geo@barryhunter.co.uk)
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

customCacheControl(filemtime(__FILE__),$_SERVER['QUERY_STRING']);
customExpiresHeader(86400*3,true);


$image1=new GridImage();
$ok1 = $image1->loadFromId($_REQUEST['1']);

if (!$ok1 || $image1->moderation_status=='rejected') {
	die("image 1 not available");
}

$image2=new GridImage();
$ok2 = $image2->loadFromId($_REQUEST['2']);

if (!$ok2 || $image2->moderation_status=='rejected') {
	die("image 2 not available");
}

$filepath1 = $image1->_getFullpath(true,true);
$size1 = $image1->_getFullSize();
$filepath2 = $image2->_getFullpath(true,true);
##$size2 = $image2->_getFullSize();

?>

<div id="slider" style="margin:20px"></div>

<div style="position:relative;width:<? echo $size1[0]; ?>px;width:<? echo $size1[1]; ?>px">

<div style="position:absolute;top:0;left:0;width:<? echo $size1[0]; ?>px;width:<? echo $size1[1]; ?>px">
<?
	echo "<img src=\"$filepath1\" {$size1[3]}/>";
?>
</div>
<div style="position:absolute;top:0;left:0;width:<? echo $size1[0]; ?>px;width:<? echo $size1[1]; ?>px">
<?
	echo "<img id=\"fade\" src=\"$filepath2\" {$size1[3]}/>";
?>
</div>
</div>

<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7/jquery-ui.min.js"></script>

	<script type="text/javascript">


	$(document).ready(function() {
		$("#slider").slider({min:0,max:100,value:50,step:5,
			slide: function(event,ui) {
				location.hash = ui.value;
				$('#fade').fadeTo(1,ui.value/100);
			}
		});

		if (location.hash) {
			var value = parseInt(location.hash.substr(1),10);

			$("#slider").slider("option", "value", value);
		}
	});


	</script>



