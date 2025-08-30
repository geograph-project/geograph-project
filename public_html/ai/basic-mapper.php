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

if (empty($_GET['query']))
	$_GET['query'] = 'bridge';

//simplify the query a bit, in the vague hope of increasing cachablity of tiles!
$_GET['query'] = strtolower(trim(preg_replace('/\s+/',' ',$_GET['query'])));

print "<p>Query: <b>".htmlentities($_GET['query'])."</b>.</p>";

?>

<div id="map" style="width:800px; height:700px; max-height:90vh; max-width:80vw;"></div>
<div id="results"></div>

        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
        <script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>

        <script type="text/javascript">
        var map = null ;
        var issubmit = false;
	var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

        function loadmap() {

		//stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(
		var bounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));

		setupBaseMap(); //creates the map, but does not initialize a view
		map.fitBounds(bounds,{maxZoom:15});

		var hash = new L.Hash(map);

        var query = '<?php echo urlencode($_GET['query']); ?>';
        var url = `/api-facetql.php?match=${query}&order=sequence+asc&limit=50&select=id,title,wgs84_lat,wgs84_long,hash`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.rows) {
                    var markerBounds = L.latLngBounds();
                    data.rows.forEach(row => {
                        if (row.wgs84_lat && row.wgs84_long) {
                            var latLng = L.latLng(row.wgs84_lat, row.wgs84_long);
                            var marker = L.marker(latLng).addTo(map);
                            var popupContent = `<a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">` +
                                               `<img src="${getGeographImageUrl(row.id, row.hash)}" width="120" height="120"><br>` +
                                               `${row.title}</a>`;
                            marker.bindPopup(popupContent);
                            marker.bindTooltip(row.title);
                            markerBounds.extend(latLng);
                        }
                    });
                    if (markerBounds.isValid()) {
                        map.fitBounds(markerBounds);
                    }
                }
            });
        }

        function getGeographImageUrl(id, hash) {
            var abcdef = ('000000' + id).slice(-6);
            var ab = abcdef.substring(0, 2);
            var cd = abcdef.substring(2, 4);
            return `https://s${id % 4}.geograph.org.uk/photos/${ab}/${cd}/${abcdef}_${hash}_120x120.jpg`;
        }

        AttachEvent(window,'load',loadmap,false);
        </script>


	<?


$smarty->display('_std_end.tpl');
