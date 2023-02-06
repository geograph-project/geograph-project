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


        $db = GeographDatabaseConnection(true);

$where = array();
$where[] = "status = 1";
$where[] = "(e > 0 OR wgs84_lat > 0)";
$limit = 100;

if (!empty($_GET['all'])) {
	$type_id = 1;
	if (!empty($_GET['id']))
		$type_id = intval($_GET['id']);

	$row = $db->getRow("SELECT t.*,realname FROM feature_type t LEFT JOIN user USING (user_id) WHERE feature_type_id = $type_id AND status > 0");
	$desc = "all rows from ".htmlentities($row['title'])." dataset";

	$where[] = "feature_type_id = $type_id";
	$limit = 100000; //still not all!

} else {
	$where[] = "nearby_images =0";
	$desc  = "$limit sample features, with <b>zero</b> images";
}

$where = implode(" AND ",$where);
$sql = "SELECT name,e,n,reference_index,wgs84_lat,wgs84_long FROM feature_item WHERE $where LIMIT $limit";

print "<p>$desc</p>";

require_once('geograph/conversions.class.php');
$conv = new Conversions;


$recordSet = $db->Execute($sql) or die("$sql\n".$db->ErrorMsg()."\n\n");
$count = $recordSet->RecordCount();

//todo, if count>1000 use MarkerClusterGroup ? masklayer

?>

<div id="map" style="width:800px; height:700px; max-height:90vh; max-width:80vw;"></div>
<div id="results"><? echo $count; if ($count > 1000) { echo " (NON CLICKABLE!)"; } ?> results</div>

        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-maskcanvas-master/src/QuadTree.js"></script>
	<script src="https://www.geograph.org/leaflet/leaflet-maskcanvas-master/src/L.GridLayer.MaskCanvas.js"></script>

        <script type="text/javascript">
        var map = null ;
        var issubmit = false;
	var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

                                        function loadmap() {

		//stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(

		var layerAttrib='&copy; Geograph Project';
                var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
                var coverageCoarse = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6});
                overlayMaps["Geograph Coverage"] = coverageCoarse;

						setupBaseMap(); //creates the map, but does not initialize a view

						overlayMaps["Geograph Coverage"].addTo(map);

						var bounds = L.latLngBounds();
	<?

if ($count > 1000) {
	print "var masklayer = L.TileLayer.maskCanvas({noMask:true, radius: 2, useAbsoluteRadius: false });\n";
	print "var layerData = new Array();\n";
	//map.addLayer(this._masklayer);
	//masklayer.setData(this._layerData);

        while (!$recordSet->EOF) {
                $r = $recordSet->fields;

		if ($r['wgs84_lat'] < 1) {
		        list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($r['e'],$r['n'],$r['reference_index']);
		} else {
			$wgs84_lat = $r['wgs84_lat'];
			$wgs84_long = $r['wgs84_long'];
		}

		print "layerData.push([$wgs84_lat,$wgs84_long]);\n";
		print "bounds.extend([$wgs84_lat,$wgs84_long]);\n\n";

                $recordSet->MoveNext();
        }
	print "masklayer.setData(layerData);\n";
	print "map.addLayer(masklayer);\n";
} else {
        while (!$recordSet->EOF) {
                $r = $recordSet->fields;

		if (!empty($r['mbr_xmin'])) {
		        list($wgs84_lat1,$wgs84_long1) = $conv->national_to_wgs84($r['mbr_xmin'],$r['mbr_ymin'],$r['reference_index']);
		        list($wgs84_lat2,$wgs84_long2) = $conv->national_to_wgs84($r['mbr_xmax'],$r['mbr_ymax'],$r['reference_index']);

			print "L.rectangle( [[$wgs84_lat1,$wgs84_long1], [$wgs84_lat2,$wgs84_long2]],  {color: '#ff7800', weight: 1, interactive:false }).addTo(map);\n";
		}

		if ($r['wgs84_lat'] < 1) {
		        list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($r['e'],$r['n'],$r['reference_index']);
		} else {
			$wgs84_lat = $r['wgs84_lat'];
			$wgs84_long = $r['wgs84_long'];
		}

		$title = json_encode($r['name']);
		if (empty($title)) $title= "''";
		print "L.marker([$wgs84_lat,$wgs84_long], {title:$title}).addTo(map);\n";
		print "bounds.extend([$wgs84_lat,$wgs84_long]);\n\n";

                $recordSet->MoveNext();
        }
}
$recordSet->Close();

	?>
						map.fitBounds(bounds,{maxZoom:15});

                                        }
                                        AttachEvent(window,'load',loadmap,false);
        </script>


	<?


$smarty->display('_std_end.tpl');


