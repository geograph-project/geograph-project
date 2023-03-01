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

$db = GeographDatabaseConnection(true);

require_once('geograph/conversions.class.php');
$conv = new Conversions;

$smarty->display('_std_begin.tpl');

/////////////////////////////////////////////////////
// setting up the defaults

$param = array(
	'id' => 0,
	'gridref'=> false,
	'limit'=>1000,

	'q'=>false,

	'masklayer'=>true,
	'markers'=>false,
	'thumbs'=>false,

	'editing'=>false,
	'create'=>false,
	'select'=>false,
);

$where = array();
$type_id = 0;

/////////////////////////////////////////////////////
// setting up the query

if (!empty($_GET['id']))
	$type_id = intval($_GET['id']);

if (!empty($type_id)) {
	$param['id'] = $type_id;
	$row = $db->getRow("SELECT t.*,realname FROM feature_type t LEFT JOIN user USING (user_id) WHERE feature_type_id = $type_id AND status > 0");
	if (!empty($row['title']))
		print "<h2>".htmlentities($row['title'])."</h2>";
	if (!empty($row['query_string']))
		$param['q'] = $row['query_string'];
	$where[] = "feature_type_id = $type_id";
}


if (!empty($_GET['loc'])) {

        require "geograph/location-decode.inc.php";

        if (!empty($lat) && isset($lng)) {

print "<!-- ($lat,$lng) -->";
		$distance = $type_id?25000:10000;
		$desc = sprintf("Features with %.1fkm of (%.5f,%.5f)",$distance/1000,$lat,$lng);

                //need to use sphinx for this, to get WITHIN GROUP ORDER BY!
                $sph = GeographSphinxConnection('sphinxql',true);
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

                $where2 = $where;
		//dont need status=1 as the index is pre filtered!
		$where2[] = 'wgs84_lat > 0';
                if (!empty($sphinxq))
                        $where2[] = "match(".$sph->Quote($sphinxq).")";
                $lat = deg2rad($lat);
                $lng = deg2rad($lng);
                $columns = ", GEODIST($lat, $lng, wgs84_lat, wgs84_long) as distance";
                $where2[] = "distance < $distance";
		//tood, add BBOX
		$where2 = implode(' and ',$where2);
		$sql = "SELECT id $columns FROM feature_item WHERE $where2 LIMIT {$param['limit']}";
		$ids = $sph->getCol($sql);
		if (count($ids))
			$where[] = "feature_item_id in (".implode(',',$ids).")";
		else
			$where[] = "0";
	}

} elseif (!empty($_GET['all'])) {
	$desc = "all rows from ".htmlentities($row['title'])." dataset";
	$param['limit'] = 100000; //still not all!

} elseif (!empty($_GET['rand'])) {
	$where[] = "1 ORDER BY RAND()";
	$desc  = "{$param['limit']} random features";
} else {
	$where[] = "nearby_images = 0";
	$desc  = "{$param['limit']} sample features, with <b>zero</b> images";
}

/////////////////////

$where[] = "status = 1";
$where[] = "(e > 0 OR f.wgs84_lat > 0)"; //only mapped features

$where = implode(" AND ",$where);
$cols = array();
$cols[] = "name";
//if ($param['select']) //at the moment this is probably turned ok below!
	$cols[] = "feature_item_id,radius,gridref";
if (!$type_id)
	$cols[] = "feature_type_id"; //to deal with mutliepl datasets!
//$cols should always have wgs84_lat,wgs84_long,gridimage_id but do incase the join changes it
$cols[] = "e,n,f.reference_index"; //should be rate, but include these as a fallback if wgs84 not available.

if (!empty($_GET['thumbs'])) {
	//specically using feature loation! (not image location)
	$cols[] = "title,realname,gi.user_id,f.wgs84_lat,f.wgs84_long,gridimage_id,credit_realname";
	$sql = "SELECT ".implode(",",$cols)." FROM feature_item f LEFT JOIN gridimage_search gi USING (gridimage_id) WHERE $where LIMIT {$param['limit']}";
} else {
	$cols[] = "wgs84_lat,wgs84_long,gridimage_id";
	$sql = "SELECT ".implode(",",$cols)." FROM feature_item f WHERE $where LIMIT {$param['limit']}";
}

/////////////////////////////////////////////////////
//runing the query

$recordSet = $db->Execute($sql) or die("$sql\n".$db->ErrorMsg()."\n\n");
$count = $recordSet->RecordCount();

if ($count <= 1000) {
	if (!empty($_GET['thumbs']) && $count <= 100)
		$param['thumbs'] = true;
	else {
		$param['markers'] = true;
		if ($USER->registered) {
			if (!empty($row['create_enabled']))
				$param['create'] = true;
			if (strpos($row['item_columns'],'gridimage_id') !== false || !$type_id) //will just have to assume all layers have it
				$param['select'] = true;
		}

                while (!$recordSet->EOF) {
                        $r = $recordSet->fields;
			$image = $r['gridimage_id']?1:0;
			@$param["photos".$image]++;
			$recordSet->MoveNext();
		}
	}
}

/////////////////////////////////////////////////////
// full render of page.


print "<ul>";
	print "<li>$desc</li>";
	if (!empty($param['q'])) {
		print "<li>The 'Photo Subjects' layer is showing images matching (( ".htmlentities($param['q'])." )). ";
		print "<li>You can also <b>(left) click</b> on the map (not marker) to view images matching the same query. ";
	} else {
		print "<li>Turn on the 'Photo Subjects' to show all images - unfiltered. ";
		print "<li>Similally, click anywhere on the map to view nearby photos (also unfiltered) - shows photos within the blue circle. ";
	}
	if (!empty($param['create'])) {
		print "<li><b>Right click</b> the map to create a new <tt>".htmlentities($row['title'])."</tt> feature at that location. ";
	}
	if ($param['markers']) {
		print "<li>";
		if (!empty($param['photos0']))
			print "<img src=\"".$CONF['STATIC_HOST']."/geotrips/bike.png\"> Without Image. ";
		if (!empty($param['photos1']))
			print "<img src=\"".$CONF['STATIC_HOST']."/geotrips/boat.png\"> With Image. ";
		if ($param['select'])
			print " (<b>Click a <img src=\"".$CONF['STATIC_HOST']."/geotrips/bike.png\"> Icon</b> to select an image for that feature). ";
	}
	?>
	<li id="results"><? echo $count; if (!$param['markers']) { echo " (NON CLICKABLE!)"; } ?> results</li>
</ul>

<div id="container">
	<div id="map"></div>
	<div id="gridref"></div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
<script type="text/javascript" src="<? echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
<script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>
<script type="text/javascript" src="<? echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>

<? if (!empty($param['thumbs'])) { ?>
        <link type="text/css" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" rel="stylesheet"/>
	<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster-src.js"></script>

        <link type="text/css" href="<? echo smarty_modifier_revision("/js/Leaflet.Photo.css"); ?>" rel="stylesheet"/>
	<script src="<? echo smarty_modifier_revision("/js/Leaflet.Photo.js"); ?>"></script>
<? }

if (true || !empty($param['q'])) { ?>
        <link type="text/css" href="<? echo smarty_modifier_revision("/js/Leaflet.GeographClickLayer.css"); ?>" rel="stylesheet"/>
        <script src="<? echo smarty_modifier_revision("/js/Leaflet.GeographClickLayer.js"); ?>"></script>
<? }

if (!empty($param['masklayer'])) { ?>
	<script src="https://www.geograph.org/leaflet/leaflet-maskcanvas-master/src/QuadTree.js"></script>
	<script src="https://www.geograph.org/leaflet/leaflet-maskcanvas-master/src/L.GridLayer.MaskCanvas.js"></script>
<? } ?>

<style>
div#container {
	position:relative;
	width:800px; height:700px; max-height:90vh; max-width:80vw;
	margin-bottom: 50vh ;
}
div#map {
	width:800px; height:700px; max-height:90vh; max-width:80vw;
}
div#gridref {
	z-index:10000;position:absolute;top:0;right:180px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8;padding:1px;
}
#preview {
  position:fixed;
  left:10px;
  bottom:10px;
  z-index:3000;
}
#preview span {
   background-color:rgba(200,200,200,0.7);
   padding:2px;
}

        .black_overlay{
            display: none;
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: black;
            z-index:1001;
            -moz-opacity: 0.8;
            opacity:.80;
            filter: alpha(opacity=80);
        }
        .white_content {
            display: none;
            position: absolute;
            top: 20%;
            left: 20%;
            width: 60%;
            height: 60%;
            border: 6px solid orange;
            background-color: white;
            z-index:10000;
            overflow: hidden;
        }
	.white_content iframe {
		width:100%;
		height:100%;
		border:0;
	}
	#clicklayer_thumbs {
		padding:0;
		height:calc( 100% - 30px );
		overflow:hidden;
	}
	#clicklayer_thumbs iframe {
		width:100%;
		height:100%;
		border:0;
	}

</style>

<script type="text/javascript">
        var map = null ;
        var issubmit = false;
	var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

	var bounds;
	var photoLayer;

	////////////////////////////////////////////////////

        function loadmap() {

		//stolen from Leaflet.base-layers.js - alas that file no compatible with mappingLeaflet.js at the moment :(

		var layerAttrib='&copy; Geograph Project';
                var layerUrl='https://t0.geograph.org.uk/tile/tile-coverage.php?z={z}&x={x}&y={y}';
		var biBounds = L.latLngBounds(L.latLng(49.863788, -13.688451), L.latLng(60.860395, 1.795260));
                var coverageCoarse = new L.TileLayer(layerUrl, {user_id: 0, minZoom: 5, maxZoom: 12, attribution: layerAttrib, bounds: biBounds, opacity:0.6});
                overlayMaps["Geograph Coverage"] = coverageCoarse;

		<? if ($param['masklayer']) { ?>
			overlayMaps["Unphotographed Features"] = L.TileLayer.maskCanvas({noMask:true, radius: 2, useAbsoluteRadius: false, color: '#ff0000' });
			overlayMaps["Photographed Features"] = L.TileLayer.maskCanvas({noMask:true, radius: 2, useAbsoluteRadius: false, color: '#000000' });

		<? }
		if ($param['thumbs']) { ?>

			var timer = null;

			//create photo layer to hold thumbnails (Leaflet.Photo intergrates markerClusterer nicely!
			photoLayer = L.photo.cluster({maxClusterRadius:50, showCoverageOnHover: true, spiderfyDistanceMultiplier: 2})
				.on('click', function (evt) {
				var photo = evt.layer.photo,
					template = '<a href="{link}" target=_blank><img src="{url}"/></a><p>{caption}</p>';

				evt.layer.bindPopup(L.Util.template(template, photo), {
					className: 'leaflet-popup-photo',
					minWidth: 300
				}).openPopup();
			});

		        photoLayer.on('mouseover', function(event) {
		                 if (timer != null) {
		                       clearTimeout(timer);
		                 }
		                 var value = event.layer.photo;
		                  $('#preview').html('<img src="'+value.thumbnail+'"> <span><b></b></span>').find('b').text(value.caption);
		                  timer = setTimeout(function() {
		                       $('#preview img').attr('src',value.thumbnail.replace(/_120x120/,'_213x160'));
		                  },2000);
		            });
		        photoLayer.on('mouseout', function() {
		                 if (timer != null) {
		                       clearTimeout(timer);
		                       timer = null;
		                 }
		                 $('#preview').empty();
		        });

			overlayMaps['Photos'] = photoLayer;

		<? } ?>

		setupBaseMap(); //creates the map, but does not initialize a view

		bounds = L.latLngBounds();

	////////////////////////////////////////////////////

		<? if (!empty($param['q'])) { ?>
			var newlayerUrl='https://t0.geograph.org.uk/tile/tile-density.php?z={z}&x={x}&y={y}&match=<? echo urlencode($param['q']); ?>&l=1&6=1';
			overlayMaps["Photo Subjects"].setUrl(newlayerUrl);

			overlayMaps["Photo Subjects"].addTo(map);

		        if (L.GeographClickLayer) {
		           clickLayer = L.geographClickLayer({query: <? echo json_encode($param['q']); ?>}).addTo(map);
		        }
		<? } else { ?>
		        if (L.GeographClickLayer) {
		           clickLayer = L.geographClickLayer().addTo(map);
		        }
		<? }

	////////////////////////////////////////////////////

		if ($param['masklayer']) {
			print "var layerData1 = new Array();\n";
			print "var layerData2 = new Array();\n";

			$recordSet->moveFirst();
		        while (!$recordSet->EOF) {
		                $r = $recordSet->fields;

				if ($r['wgs84_lat'] < 1) {
				        list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($r['e'],$r['n'],$r['reference_index']);
				} else {
					$wgs84_lat = $r['wgs84_lat'];
					$wgs84_long = $r['wgs84_long'];
				}
				if ($r['gridimage_id'])
					print "layerData2.push([$wgs84_lat,$wgs84_long]);\n";
				else
					print "layerData1.push([$wgs84_lat,$wgs84_long]);\n";
				print "bounds.extend([$wgs84_lat,$wgs84_long]);\n\n";

		                $recordSet->MoveNext();
		        }

			print "overlayMaps[\"Unphotographed Features\"].setData(layerData1);\n";
			print "overlayMaps[\"Photographed Features\"].setData(layerData2);\n";
			print "map.addLayer( overlayMaps[\"Unphotographed Features\"] );\n";
			print "map.addLayer( overlayMaps[\"Photographed Features\"] );\n";
		}

	////////////////////////////////////////////////////

		if (!empty($param['thumbs'])) {

			print "var newRows = new Array();\n";

			$recordSet->moveFirst();
		        while (!$recordSet->EOF) {
		                $r = $recordSet->fields;

				if ($r['wgs84_lat'] < 1) {
				        list($wgs84_lat,$wgs84_long) = $conv->national_to_wgs84($r['e'],$r['n'],$r['reference_index']);
				} else {
					$wgs84_lat = $r['wgs84_lat'];
					$wgs84_long = $r['wgs84_long'];
				}
				if ($r['gridimage_id']) {
					$image = new GridImage();
					$image->fastInit($r);
					$image->compact();
					$a = [];
					$a['gridimage_id'] = $r['gridimage_id'];
					$a['link'] = "https://www.geograph.org.uk/photo/".$r['gridimage_id'];
					$a['thumbnail'] = $image->getThumbnail(120,120,true);
					$a['url'] = $image->_getFullpath(true,true);
					$a['caption'] = $r['title'].' by '.$r['realname'];
					$a['lat'] = $wgs84_lat;
					$a['lng'] = $wgs84_long;

					print "newRows.push(".json_encode($a).");\n";
				}
				if (!$param['masklayer'])
					print "bounds.extend([$wgs84_lat,$wgs84_long]);\n\n";

		                $recordSet->MoveNext();
		        }
			print "photoLayer.add(newRows).addTo(map);\n";

		}
	////////////////////////////////////////////////////

		if ($param['markers']) {
			$recordSet->moveFirst();
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
		//bike,boat,bus,rail,road,walk
				$icon = ($r['gridimage_id'])?'boat':'bike';

				print "marker = createMarker([$wgs84_lat,$wgs84_long], '$icon', $title)\n";
				if (!$param['masklayer'])
					print "bounds.extend([$wgs84_lat,$wgs84_long]);\n\n";

				if ($param['select']) { ?>
					marker.on('click',function() {
						current_item_id = <? echo $r['feature_item_id']; ?>;
						current_marker = this;
						<? if (!empty($r['feature_type_id'])) { echo "feature_type_id = {$r['feature_type_id']};\n"; } ?>
						var near_url = "/features/near.php?q=<? echo urlencode($r['gridref']); ?>&type_id="+feature_type_id;
                        			<? if ($r['radius'] && $r['radius']>1) { ?>
			                                near_url = near_url + "&dist=" + Math.floor(<? echo $r['radius']; ?>*1.2);
                        			<? } elseif ($row['default_radius'] && $row['default_radius']>1) { ?>
			                                near_url = near_url + "&dist=" + Math.floor(<? echo $row['default_radius']; ?>*1.2);
			                        <? } if ($r['gridimage_id'] && $r['gridimage_id']>0) { ?>
			                                near_url = near_url + "&img=<? echo $r['gridimage_id']; ?>";
			                        <? } if ($r['name']) { ?>
			                                near_url = near_url + "&name=<? echo urlencode($r['name']); ?>";
						<? } ?>
		                                near_url = near_url + "&editing=true";
						openPopup(near_url);
					});
				<? }

		                $recordSet->MoveNext();
		        }
		}

	////////////////////////////////////////////////////

		$recordSet->Close();
		?>

		map.fitBounds(bounds,{maxZoom:15});

		var grid;
		var gr;
		map.on('mousemove', function(e) {
			var wgs84=new GT_WGS84();
			wgs84.setDegrees(e.latlng.lat,e.latlng.lng);

			if (wgs84.isIreland() && wgs84.isIreland2()) //isIsland is a quick BBOX test, so do that first!
				grid=wgs84.getIrish(true);
			else if (e.latlng.lat > 49.8 && wgs84.isGreatBritain()) // the isGB test is not accurate enough!
				grid=wgs84.getOSGB();
			else
				grid = null;

			if (grid && grid.status && grid.status == 'OK') {
				var z = map.getZoom();
				if (z > 15) precision = 5;
				else if (z > 12) precision = 4;
				else if (z > 9) precision = 3;
				else precision = 2;

				gr = grid.getGridRef(precision);
				if (document.getElementById('gridref') && gr.indexOf('undefined') == -1)
					document.getElementById('gridref').innerText = gr;
			};
		});
		<? if ($param['create']) { ?>
			map.on('contextmenu', function(e) {
				//call mousemove to make sure updated?

				var href = "/features/edit_item.php?id=new&type_id=7&gridref="+encodeURIComponent(gr);
				openPopup(href);
			});
		<? } ?>
        }

	var uniqueSerial = 0;
	var current_item_id = 0;
	var current_marker = null;
	var feature_type_id = <? echo $param['id']; ?>;
	var _mapBounds = null;
	var _marker = null;
	function openPopup(href) {
		_mapBounds = map.getBounds().pad(-0.2); //it often zooms out a step

		if (true) {
			$('#clicklayer_lightback').fadeIn('fast');
			$('#clicklayer_thumbs').removeClass('small').removeClass('med');
			$('#clicklayer_thumbs').html('<iframe></iframe>').find('iframe').attr('src',href+"&inner=1");
			$('#clicklayer_lightfront').show();
			$('#clicklayer_links').hide();

			var offset = $(map._container).offset();
			$('html, body').scrollTop(offset.top);
			$(map._container).addClass('click_smallmap');
			map.invalidateSize();

			$('.leaflet-control-container .leaflet-top').hide(); //hides top controls, but not attribution!
			return;
		}

	        document.getElementById('light').style.display='block';
        	document.getElementById('fade').style.display='block';
		document.getElementById('light').style.position = 'fixed';
		document.getElementById('iframe').src = href+"&inner=1";
	}
	function closePopup(trigger) {
		if (_mapBounds) {
			//map.fitBounds(_mapBounds);
			map.flyToBounds(_mapBounds,{duration:0.5});
			_mapBounds = null;
		}
		if (_marker) {
			_marker.removeFrom(map);
			_marker = null;
		}

		$(map._container).removeClass('click_smallmap');
		map.invalidateSize();

		$('.leaflet-control-container .leaflet-top').show(); //hides top controls, but not attribution!

		//if (true) {
			$('#clicklayer_lightfront').hide();
			$('#clicklayer_lightback').fadeOut('fast');
			$('#clicklayer_show').hide();
		//}
		document.getElementById('light').style.display='none';
		document.getElementById('fade').style.display='none';
		if (trigger) {
			uniqueSerial++;
			// refreshData();
		}
	}
	function zoomMap(lat,lng) {
		if (_marker)
			_marker.removeFrom(map);
		_marker = L.circleMarker([lat,lng],{color:'blue'}).addTo(map);

		//the click defines has it own close event, by connecting the two here, means it will revert our version when closing too!
		clickLayer._circle = _marker;
		clickLayer._mapBounds = _mapBounds;
		clickLayer._returnwhenoff = true;

		var bounds = L.latLngBounds();
		bounds.extend([lat,lng]);

		var testBounds = map.getBounds().pad(-0.2);

//console.log(bounds, bounds.getNorthWest().distanceTo(bounds.getSouthEast()), map.getBoundsZoom(bounds));

		if (testBounds.contains(bounds)) {
			//todo, might still want to check if worth zooming in. with setZoomAround
			//if (bounds.getNorthWest().distanceTo(bounds.getSouthEast()) > 20) {
				var possibleZoom = map.getBoundsZoom(bounds);

				if (Math.abs(map.getZoom() - possibleZoom) > 4) {
					map.setZoomAround(bounds.getCenter(),possibleZoom-2);
				}
			//}
			return;
		}

		if (bounds.getNorthWest().distanceTo(bounds.getSouthEast()) > 20) {

			var possibleZoom = map.getBoundsZoom(bounds);
			if (Math.abs(map.getZoom() - possibleZoom) <= 2) {
				//if close, just try panning, to minimis jumping around
				map.panInsideBounds(bounds.pad(0.1));
			} else {
				//todo, could perhaps note, when target bounds are in view (its just really just zoom!)
					// and instead use setZoomAround to avoid recentering

				//todo dont zoom beyond the maxZoom of the baselayer
				map.fitBounds(bounds);
			}

		} else {
			//if points are same or only a single point, then just make sure in view at current zoom

			map.panInsideBounds(bounds.pad(0.1));
		}
	}
	function useImage(gridimage_id) {
	        <? if ($param['select']) { ?>
        	        var data = {};
	                data['id'] = current_item_id;
	                data['gridimage_id'] = gridimage_id;
	                data['submit'] = 1;
        	        $.post('edit_item.php?type_id='+feature_type_id, data, function(result) {
	                        //only update table after got response!
                        	uniqueSerial++;
                	       // refreshData();
        	        });
			//todo, we should also change the colour of the dot!
			if (current_marker && gridimage_id && current_marker._icon) {
				//tehre isnt a 'getIcon' in this version!
				icon = 'boat';
				current_marker._icon.src = static_host+"/geotrips/"+icon+".png";
			}
	        <? } ?>

		closePopup(false); //close right away
	}

	////////////////////////////////////////////////////

	 var icons = [];
	 function createMarker(point,icon,title,html) {
                if (!icons[icon]) {
	                icons[icon] = L.icon({
        	            iconUrl: static_host+"/geotrips/"+icon+".png",
	                    iconSize:     [9, 9], // size of the icon
        	            iconAnchor:   [5, 5], // point of the icon which will correspond to marker's location
                	    popupAnchor:  [0, -5] // point from which the popup should open relative to the iconAnchor
	                });
		}
                var marker = L.marker(point, {title: title, icon: icons[icon], draggable: false}).addTo(map);
		if (html)
			marker.bindPopup(html);
      		return marker;
	}

	////////////////////////////////////////////////////

        AttachEvent(window,'load',loadmap,false);
</script>


<div id="preview"></div>

<div id="light" class="white_content">
<iframe src="about:blank" id="iframe" width="100%" height="100%"></iframe>
</div><div id="fade" class="black_overlay" onclick="closePopup()"></div>

	<?


$smarty->display('_std_end.tpl');


