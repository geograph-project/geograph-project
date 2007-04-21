<?php
/**
 * $Project: GeoGraph $
 * $Id: mapbrowse.php 3087 2007-02-18 10:53:05Z barry $
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
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/conversions.class.php');



init_session();


$tileSize = 125; 
$zoomLevel = 11;

$fullSize = $tileSize * pow(2,$zoomLevel);

$widthInMapUnits = pow(2,$zoomLevel);



$conv = new Conversions();

$e = 401000;
$n = 101000;
$reference_index = 1;
 
list($x,$y) = $conv->national_to_internal($e,$n,$reference_index );


$x_ratio = ($x/2) / $widthInMapUnits;
$y_ratio = ($widthInMapUnits-($y/2)) / $widthInMapUnits;
#print "$x,$y<BR>";

#print "$x_ratio,$y_ratio";
#exit;
print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
 		<meta http-equiv="imagetoolbar" content="no" />
		<script type="text/javascript" src="/geograph.js"></script>
		<script type="text/javascript" src="assets/js/GSIV.js"></script>
		<script type="text/javascript">
// <![CDATA[

var viewerBean = null;


myTileUrlProvider = function(baseUri) {
	this.baseUri = baseUri;
}

myTileUrlProvider.prototype = new GSIV.TileUrlProvider;

myTileUrlProvider.prototype.assembleUrl = function(xIndex, yIndex, zoom) {
	if (zoom == 11) {
		return this.baseUri + '?z=' + zoom + '&p=' + (900*((<?=$widthInMapUnits?>-yIndex)*2)+900-(xIndex*2));
	} else {
		return 'assets/gfx/blank.gif';
	}
}



function initializeGraphic(e) {
	// opera triggers the onload twice
	if (viewerBean == null) {
		viewerBean = new GSIV('viewer', {
			tileSize: 125,
			maxZoom: 11,
			initialZoom: 11,
			initialPan: { 'x' : <?= $x_ratio ?>, 'y' : <?= $y_ratio ?>},
			blankTile: 'assets/gfx/blank.gif',
			loadingTile: 'assets/gfx/progress.gif',
			tileUrlProvider:  new myTileUrlProvider("http://<?= $_SERVER['HTTP_HOST']; ?>/tile.php")
		});
		viewerBean.init();
	}
}

 AttachEvent(window,'load',initializeGraphic,false);
 

// ]]>
		</script>
		<style type="text/css">
@import url(assets/styles/gsiv.css);
		</style>
		<style type="text/css">
body {
	font-family: sans-serif;
	margin: 0;
	padding: 10px;
	color: #FFFFFF;
	background-color: #999999;
	font-size: 0.7em;
}
#viewer {
	background-color: #000000;
	width: 400px;
	height: 400px;
}
		</style>
	</head>
	<body>
		<div id="header">
		<h1>Geograph Slippery Overview Map - TEST TEST TEST</h1>
		</div>
		<div id="viewer">
			<div class="well"><!-- --></div>
			<div class="surface"><!-- --></div>
			<div class="copyright">&copy; Crown Copyright <?= $CONF['OS_licence'] ?></div>
		</div>

	</body>
</html>
