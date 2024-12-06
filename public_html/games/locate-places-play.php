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

print "<h2>Can you locate this place on the map?</h2>";


$db = GeographDatabaseConnection(true);

/////////////////////////////////////////////////////

$where = array();

/////////////////////////////////////////////////////

if ($USER->registered) {
        print "<p>As a registered user, you guesses will be stored on your profile and will provide a score below. The data will be remembered long term, and play over multiple sessions.";

        $where = "user_id = ".$USER->user_id;

} else {
        print "<p>The score will only be tracked for this session. <a href=?login=1>Login</a> to save your score on your profile";

        $where = "session = ".$db->Quote(session_id());
}

$filter = "has_dup = 0";
if (!empty($_GET['city']))
	$filter .= " AND f_code IN ('C','City')";

$places = $db->getAll($sql = "SELECT g.id,substring_index(def_nam,'/',1) as def_nam, east, north, reference_index as ri
 FROM gaz_locate g
	LEFT JOIN locate_log l ON (l.id = g.id AND l.$where)
 WHERE $filter AND l.response_id IS NULL
 ORDER BY RAND()
 LIMIT 100");

// still need to filter by has_dup gaz_locate has looked up duplicates cross grid etc, that the original gaz's didnt!


if (empty($places))
	$places = array(); //to keep the json output correct
	//die("unable to find places");
else
	foreach ($places as &$row)
		$row['def_nam'] = latin1_to_utf8($row['def_nam']); //for json!


print "<script src=".smarty_modifier_revision("/js/to-title-case.js")."></script>";
print "<script src=".smarty_modifier_revision("/mapper/geotools2.js")."></script>";

?>

<div class=interestBox id="outputMsg">Lets Go! <b>Please don't cheat!</b></div>
<div style="position:relative">
	<form id="side" onsubmit="return checkSub(this)">
		<h3></h3>

		Guess: <input type=search size=10 name=gridref value="" readonly> (click on map!)<br>
		-or-<br><br>

		<input type=radio name=guess value="heard" id="g1"><label for=g1>Heard of it, but don't know where</label><br><br>
		<input type=radio name=guess value="never" id="g2"><label for=g2>Never heard of this place</label><br><br>
		<input type=radio name=guess value="bogus" id="g3"><label for=g3>I don't think this is real place</label><br><br>
		<br>
		<input type=submit value="Submit Answer"><br><br>

		-or-<br><br>

		<a href="locate-places.php">Back to scoreboard</a><br> (your answers are saved)
	</div>
	<div id="map"></div>
</div>
<br style=clear:both>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
<script type="text/javascript" src="<? echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

<style>
form#side {
	float:left;
	height:900px;  max-height:70vh;
	width:300px;  max-width:30vw;
	max-width:100%;
	background-color:#eee;
	padding:10px;
}
div#map {
	float:left;
	width:800px; height:900px; max-height:70vh; max-width:50vw;
	margin-bottom: 50vh ;
}
</style>

<script type="text/javascript">
        var map = null ;
        var issubmit = false;
	var static_host = '<? echo $CONF['STATIC_HOST']; ?>';

	var bounds;
	var marker;
	var places = <? echo json_encode($places); ?>;
	var place;
	var guess; //guess as a lat/lng

	////////////////////////////////////////////////////

	function loadNextPlace() {
		if (places.length) {
			place = places.pop();
			var name = place.def_nam.toTitleCase(); //standaise case
			$('#side h3').text('Place: '+name);
			$('#side input[name=gridref]').val('');
			$('#side input[name=guess]').prop('checked',false);
			if (marker)
                                marker.removeFrom(map);
			guess = null;
		} else {
			my_alert('No More Places. You may still be able to go back to scoreboard, and play again to get more places');
		}
	}
	function my_alert(msg) {
		$('#outputMsg').text(msg);
		if (msg.indexOf('Nope') == 0 || msg.indexOf('Sorry') == 0 || msg.indexOf('No More') == 0)
			$('#outputMsg').css('backgroundColor','pink');
		else
			$('#outputMsg').css('backgroundColor','lightgreen');
	}

	function checkSub(form) {
		//guess = latlong
		checked = $('input[name=guess]:checked').val();

		var payload = {
			'id': place.id,
			'guess': checked,
		};

		if (checked == 'bogus') {
			if (place.east == 0 && place.north == 0) {
				my_alert('Correct. It was a fake place!');
			} else {
				my_alert('Nope, it was a real place!');
			}
		} else if (guess) {
			if (place.east == 0 && place.north == 0) {
				my_alert('Nope. It was a fake place!');
			} else if (place.ri == 1) {
				var grid=new GT_OSGB();
			} else if (place.ri == 2) {
				var grid=new GT_Irish();
			}
			if (grid) {
				grid.eastings = place.east;
				grid.northings = place.north;

				//convert to a wgs84 coordinate
		                wgs84 = grid.getWGS84(true);
				var distance = guess.distanceTo([wgs84.latitude,wgs84.longitude]);

				if (distance < 10000) {
					my_alert('Amazing. Got within 10km!');
				} else if (distance < 50000) {
					my_alert('Great. Got within 50km!');
				} else if (distance < 100000) {
					my_alert('Good. Got within 100km!');
				} else if (distance < 200000) {
					my_alert('Ok. Got within 200km!');
				} else {
					my_alert("Sorry, wasn't within 200km of the actual location");
				}
				payload['distance'] = distance;
				payload['lat'] = guess.lat;
				payload['lng'] = guess.lng;
			}
		}

		if ('sendBeacon' in navigator)
			navigator.sendBeacon("/stuff/record_locate.php", JSON.stringify(payload) );
		else
			$.ajax("/stuff/record_locate.php", {
			    data : JSON.stringify(payload),
			    contentType : 'application/json',
			    type : 'POST'
			});

		loadNextPlace();
		return false;
	}

	////////////////////////////////////////////////////

        function loadmap() {

		setupBaseMap(); //creates the map, but does not initialize a view

		baseMaps["ESRI Imagery"].addTo(map);

		bounds = L.latLngBounds([49.863788, -13.688451], [60.860395, 1.795260]);
		map.fitBounds(bounds,{maxZoom:15});

map.touchZoom.disable();
map.doubleClickZoom.disable();
map.scrollWheelZoom.disable();
map.boxZoom.disable();
map.keyboard.disable();
map.dragging.disable();
$(".leaflet-control-zoom").css("visibility", "hidden");
$(".leaflet-control-layers").css("visibility", "hidden");

		map.on('click',function(e) {
			if (marker)
				marker.removeFrom(map);
			marker = createMarker(e.latlng);
		});

		loadNextPlace();
        }

	////////////////////////////////////////////////////

	 var icons = [];
	 function createMarker(point) {
                var marker = L.marker(point, {draggable: true}).addTo(map);
		setGridRef(point);
		marker.on('dragend',function(e) {
			 setGridRef(marker.getLatLng());
		});
      		return marker;
	}

	function setGridRef(center) {
		guess = center; //this will be used for distance

                        wgs84=new GT_WGS84();
                        wgs84.setDegrees(center.lat, center.lng);
                        if (wgs84.isIreland2()) {
                                //convert to Irish
                                var grid=wgs84.getIrish(true);
                        } else if (wgs84.isGreatBritain()) {
                                //convert to OSGB
                                var grid=wgs84.getOSGB();
                        }
                        var gridref = grid.getGridRef(2);
		$('input[name=gridref]').val(gridref);
		$('#side input[name=guess]').prop('checked',false);
	}

	////////////////////////////////////////////////////

        AttachEvent(window,'load',loadmap,false);
	$(function() {
		$('#side input[name=guess]').on('click', function() {
			$('input[name=gridref]').val('');
			guess = null;
		});
	});
</script>

	<?


$smarty->display('_std_end.tpl');


