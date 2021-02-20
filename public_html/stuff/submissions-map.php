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

$USER->mustHavePerm("basic");

$smarty->display('_std_begin.tpl');



        //concoct a special writable connection to SECOND slave!
if (!empty($CONF['db_read_connect2'])) {
        if (!empty($DSN_READ))
                $DSN_READ = str_replace($CONF['db_read_connect'],$CONF['db_read_connect2'],$DSN_READ);
        if (!empty($CONF['db_read_connect']))
                $CONF['db_read_connect'] = $CONF['db_read_connect2'];
}

$db = GeographDatabaseConnection(100);


        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


//needs to use gridimage/gridsquare because may be pending images. but join in gridimage_search, as may already be moderated, which case have the lat/long ready to use!
$sql = "select gridimage_id,g.submitted,gs.grid_reference,g.title,nateastings,natnorthings,natgrlen,gs.reference_index,wgs84_lat,wgs84_long
	from gridimage g
		inner join gridsquare gs using (gridsquare_id)
		left join gridimage_search gi using (gridimage_id)
	where g.user_id = {$USER->user_id} and g.moderation_status != 'rejected'
	order by gridimage_id desc limit 100";

?>

<h2>Your Submissions on Map (at <? echo date('H:i:s'); ?> today)</h2>

<div class="interestBox">NOTE: A 'Recent Uploads' layer has been added to the <a href="/mapper/combined.php?mine=1">main Coverage Map</a>, which shows recent uploads in similar method to this map.
It's now recommended to use that instead. As can combine it with other layers already on that map. Also can just turn the layer off, then back on and it automatically refreshes. Dont need to reload the whole page like this function</div>



<p>The red circles are your 100 most recently submitted images, including unmoderated images <span id="zoomer"></span></p>
<p>The coloured squares are squares you've submitted images to ever, not just most recent. Doesn't update as regularly as the circles layer</p>

<div id="mapCanvas" style="width:800px; height:700px; max-height:90vh; max-width:80vw;"></div>
<div id="results"></div>
<a href="?reload">Reload Map</a> - Use to see latest images, please use instead of F5/Refresh in browser!

        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

        <script type="text/javascript">
        var map = null ;
        var issubmit = false;


                                        function loadmap() {
                                                var newtype = readCookie('GMapType');

                                                mapTypeId = firstLetterToType(newtype);

                                                map = L.map('mapCanvas',{attributionControl:false}).addControl(
                                                        L.control.attribution({ position: 'bottomright', prefix: ''}) );

		//stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(

		var layerAttrib='&copy; Geograph Project';
                var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}&user_id=<? echo $USER->user_id; ?>';
                var coverageCoarse = new L.TileLayer(layerUrl, {user_id: <? echo $USER->user_id; ?>, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6}).addTo(map);
                overlayMaps["Personalized Coverage"] = coverageCoarse;


                                                setupOSMTiles(map,mapTypeId);

                                                map.on('baselayerchange', function (e) {
                                                        if (e.layer && e.layer.options && e.layer.options.mapLetter) {
                                                                var t = e.layer.options.mapLetter;
                                                                createCookie('GMapType',t,10);
                                                        } else {
                                                                console.log(e);
                                                        }
                                                });

						var bounds = L.latLngBounds();
	<?

require_once('geograph/conversions.class.php');
$conv = new Conversions;


$recordSet = &$db->Execute($sql) or die("$sql\n".$db->ErrorMsg()."\n\n");
if ($count = $recordSet->RecordCount()) {
        while (!$recordSet->EOF) {
                $r = $recordSet->fields;

		if (empty($r['wgs84_lat']) || $r['wgs84_lat'] < 1) {
		        list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($r['nateastings'],$r['natnorthings'],$r['reference_index']);
		} else {
			$wgs84_lat = $r['wgs84_lat'];
			$wgs84_long = $r['wgs84_long'];
		}

		if (empty($title)) { //used to detect the first loop!
			print "document.getElementById('zoomer').innerHTML = '<a href=\"#\" onclick=\"map.setZoomAround([$wgs84_lat,$wgs84_long],18); return false;\">Zoom to last image</a>';\n";
		}

		$title = json_encode($r['grid_reference']." : ".$r['title']);
		if (empty($title)) $title= "''";
		print "L.circleMarker([$wgs84_lat,$wgs84_long], {title:$title, radius:4, color:'#f93024'}).addTo(map).on('click',function() {window.location.href='/photo/{$r['gridimage_id']}';});\n";
		print "bounds.extend([$wgs84_lat,$wgs84_long]);\n\n";

                $recordSet->MoveNext();
        }
	print "document.getElementById('results').innerHTML = '$count results';\n";
} else {
	print "alert('No matching results');";
}
$recordSet->Close();

//useful trick so that the coverage map is centered!
if (empty($_SESSION['gridref']) && !empty($r['grid_reference'])) {
	$_SESSION['gridref'] = $r['grid_reference'];
}

	?>
						map.fitBounds(bounds);

                                        }
                                        AttachEvent(window,'load',loadmap,false);
        </script>


	<?


$smarty->display('_std_end.tpl');


