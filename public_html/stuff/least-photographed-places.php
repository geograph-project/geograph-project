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


        //$db = GeographDatabaseConnection(false);

if (!empty($CONF['db_read_connect2'])) {
        //concoct a special writable connection to SECOND slave!
        $DSN_READ = $CONF['db_read_driver'].'://'.
                $CONF['db_user'].':'.$CONF['db_pwd'].
                '@'.$CONF['db_read_connect2'].
                '/'.$CONF['db_db'].$CONF['db_read_persist'];
}

        $db=NewADOConnection($DSN_READ);

        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$reference_index = 1;


if (!empty($_GET['ireland'])) {

	$reference_index = 2;
	$sql = "SELECT name AS title, e as x,n as y FROM ie_open_places WHERE images =0 LIMIT 10000";
        $desc  = "Showing squares with placenames, with <b>zero</b> images of subjects within 250m of center of settlement";

} elseif (!empty($_GET['iom'])) {
	$sql = "SELECT CONCAT(name,', ',county) AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM osmnames_places WHERE images =0 LIMIT 1000";
	$desc  = "places, with <b>zero</b> images of subjects within their 'minimum bounding box' rectangle.";

} elseif (!empty($_GET['q'])) {
	$q = $db->Quote($_GET['q']);
	$sql = "SELECT CONCAT(name1,' - ',local_type,' / images:',images) AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM os_open_places
                        WHERE name1 LIKE $q LIMIT 1000";
        $desc  = "Showing result of Custom Query.";

} elseif (!empty($_GET['hill'])) {

        $d = 100; //can't jsut change this, as the 'images' column is set using this distance!

	$class = $db->Quote($_GET['hill']);

        $sql = "SELECT CONCAT(name,' / ',county,' [',classes,']') AS title , e as x, n as y, n-$d AS mbr_ymin,n+$d AS mbr_ymax,e-$d AS mbr_xmin,e+$d AS mbr_xmax, reference_index
		FROM hilldb WHERE images =0 AND FIND_IN_SET($class,classes) AND reference_index <3 LIMIT 1000"; //exclude channle isalnds!

	$desc  = "Hills with zero images, within {$d}m rectangle of summit.";

} elseif (!empty($_GET['big'])) {
	$sql = "SELECT name1 AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax, images/((mbr_xmax-mbr_xmin)*(mbr_ymax-mbr_ymin))*10000 as per_centi
			FROM os_open_places WHERE images > 500 order by per_centi desc limit 100";
	$desc  = "showing MOST photographed places!";



} elseif (!empty($_GET['test'])) {

	$e = 333800; $n = 190300;

	$sql = "SELECT name1 AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax
			FROM os_open_places WHERE $n between mbr_ymin and mbr_ymax AND $e between mbr_xmin and mbr_xmax order by least_detail_view_res desc limit 100";
	$desc  = "places intersecting test point.";


} elseif (!empty($_GET['one'])) {
	$sql = "SELECT CONCAT(name1,' - ',local_type,' / images:',images) AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM os_open_places WHERE local_type = 'Village' AND users<2 LIMIT 3000";
	$desc  = "Settlements, with zero or only 1 visitor within their 'minimum bounding box' rectangle.";

} elseif (!empty($_GET['gblakes'])) {
	$sql = "SELECT name1 AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM gblakes WHERE images = 0";
	$desc  = "GBLakes, with <b>zero</b> images of subjects within 200m of lake center.";

	if ($_GET['gblakes'] !== '2') {
		$sql .= " order by rand() LIMIT 1000";
		$desc .= " (Sample of 1000)";
	}

} elseif (!empty($_GET['villages'])) {
	$sql = "SELECT name1 AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM os_open_places WHERE local_type = 'Village' AND images =0 LIMIT 1000";
	$desc  = "Villages, with <b>zero</b> images of subjects within their 'minimum bounding box' rectangle.";

} elseif (!empty($_GET['few'])) {
	$sql = "SELECT CONCAT(name1,' - ',local_type,' / images:',images) AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM os_open_places
			WHERE images < 5 AND local_type NOT in('Hamlet','Other Settlement','Suburban Area') LIMIT 1000";
	$desc  = "Village, with <b>less than 5</b> images of subjects within their 'minimum bounding box' rectangle.";

} elseif (!empty($_GET['town'])) {
	$sql = "SELECT CONCAT(name1,' - ',local_type,' / images:',images) AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM os_open_places
		        WHERE images < 100 AND local_type in ('town','city') LIMIT 1000";
	$desc  = "Town/City,  with <b>less than 100</b> images of subjects within their 'minimum bounding box' rectangle.";

} elseif (!empty($_GET['large'])) {
	$sql = "SELECT CONCAT(name1,' - ',local_type, ' / images:',images, ' (first id:',first,')') AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM os_open_places WHERE most_detail_view_res > 15000 and centis < (mbr_xmax-mbr_xmin)*(mbr_ymax-mbr_ymin) div 100000 ";
	$desc  = "Larger places with less than 10% coverage by area";

} elseif (!empty($_GET['new'])) {
	$sql = "SELECT CONCAT(name1,' - ',local_type, ' / images:',images, ' (first id:',first,')') AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM os_open_places WHERE images > 0 ORDER BY first DESC  LIMIT 100";
	$desc  = "Most recent first photographed Settlements, ie settlements that only got their first photo rcently - subjects within their 'minimum bounding box' rectangle. Includes Villages, as well as Hamlets, Other Settlements and Surburban Areas";

} else {
	$sql = "SELECT CONCAT(name1,' - ',local_type) AS title, geometry_x as x,geometry_y as y, mbr_xmin,mbr_ymin, mbr_xmax,mbr_ymax FROM os_open_places WHERE images =0 LIMIT 10000";
	$desc  = "Settlements, with <b>zero</b> images of subjects within their 'minimum bounding box' rectangle. Includes Villages, as well as Hamlets, Other Settlements and Surburban Areas";
}

print "<p>$desc</p>";

?>

<div id="mapCanvas" style="width:800px; height:700px; max-height:90vh; max-width:80vw;"></div>
<div id="results"></div>

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
                var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
                var coverageCoarse = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: bounds, opacity:0.6}).addTo(map);
                overlayMaps["Geograph Coverage"] = coverageCoarse;


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

		if (!empty($r['reference_index'])) $reference_index = $r['reference_index'];

		if (!empty($r['mbr_xmin'])) {
		        list($wgs84_lat1,$wgs84_long1) = $conv->national_to_wgs84($r['mbr_xmin'],$r['mbr_ymin'],$reference_index);
		        list($wgs84_lat2,$wgs84_long2) = $conv->national_to_wgs84($r['mbr_xmax'],$r['mbr_ymax'],$reference_index);

			print "L.rectangle( [[$wgs84_lat1,$wgs84_long1], [$wgs84_lat2,$wgs84_long2]],  {color: '#ff7800', weight: 1, interactive:false }).addTo(map);\n";
		}

	        list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($r['x'],$r['y'],$reference_index);

		$title = json_encode($r['title']);
		if (empty($title)) $title= "''";
		print "L.marker([$wgs84_lat,$wgs84_long], {title:$title}).addTo(map);\n";
		print "bounds.extend([$wgs84_lat,$wgs84_long]);\n\n";

                $recordSet->MoveNext();
        }
	print "document.getElementById('results').innerHTML = '$count results';\n";
} else {
	print "alert('No matching results');";
}
$recordSet->Close();

	?>
						map.fitBounds(bounds);

                                        }
                                        AttachEvent(window,'load',loadmap,false);
        </script>


	<?


$smarty->display('_std_end.tpl');


