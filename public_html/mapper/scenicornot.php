<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
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

$smarty = new GeographPage;



$smarty->display('_std_begin.tpl');


?>

<h3>ScenicOrNot</h3>
<p style=max-width:900px>Data from <a href="https://scenicornot.datasciencelab.co.uk/">ScenicOrNot</a> website.

<ul>
	<li>Plotting rated scenicness, Darker red is more scenic. Yellow less so.
	<li> is based on one photo per 1km square, and is an effectively random image from the square.</li>
	<li>Is a snapshot of votes from about 2015, the images where provided to ScenicOrNot early 2009, so squares without images at that time wont have images
</ul>

<div id="map" style="width:800px; height:800px; max-height:90vh; max-width:80vw;"></div>
<div id="results"></div>

        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

        <script type="text/javascript">
        var map = null ;
        var issubmit = false;
	var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

                                        function loadmap() {

		//stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(

		var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));
		var layerAttrib='&copy; Geograph Project';
                var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
                var coverageCoarse = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});
                overlayMaps["Geograph Coverage"] = coverageCoarse;

		layerAttrib='&copy; Geograph Project &amp; Scenic Or Not';
                layerUrl='https://t0.geograph.org.uk/tile/tile-scenicornot.php?z={z}&x={x}&y={y}';
		var sceniclayer =  new L.TileLayer(layerUrl, {minZoom: 7, maxZoom: 10, attribution: layerAttrib, bounds: bounds, opacity:0.9});
                overlayMaps["ScenicOrNot"] = sceniclayer;

		var sceniclaye2 =  new L.TileLayer(layerUrl, {minZoom: 7, maxZoom: 10, attribution: layerAttrib, bounds: bounds, opacity:0.5});
                overlayMaps["ScenicOrNot (faded)"] = sceniclaye2;

						setupBaseMap(); //creates the map, but does not initialize a view

						//overlayMaps["Geograph Coverage"].addTo(map);
						overlayMaps["ScenicOrNot"].addTo(map);

						map.setView([53.593,-2.285],7);
                                        }
                                        AttachEvent(window,'load',loadmap,false);
        </script>


	<?


$smarty->display('_std_end.tpl');


