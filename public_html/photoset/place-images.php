<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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
//        $sph = GeographSphinxConnection('sphinxql',true);
  //      $sprt = GeographSphinxConnection('manticorert',true);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

        $conv = new Conversions;


$primary = array();
$secondary = array();
$name = $_GET['query'];

//1. os_gaz (sphinx_placenames)
if (!empty($_GET['os_gaz'])) {
	$row = $db->getRow("SELECT * FROM os_gaz WHERE seq = ".intval($_GET['os_gaz']));
	if (!empty($row)) {
		//check sphinx_placenames
		$row2 = $db->getRow("SELECT * FROM sphinx_placenames WHERE placename_id = {$row['seq']}+1000000");
		if (!empty($row2['images'])) {
			$primary[] = array('name'=> "Nearest ".$row2['Place'], 'sphinx'=> "@place ".$row2['Place'], 'placename_id'=> $row2['placename_id']);
			@$secondary['query'][] = "@place ".$row2['Place'];
		} else {
			//there is a chance that some OTHER feature was choosen, but as 1km grid anyway, will work the same as actual placename!?!
			$row3 = $db->getRow("SELECT * FROM sphinx_placenames WHERE km_ref = '{$row['km_ref']}' ORDER BY images DESC");
			if (!empty($row3['images'])) {
				$primary[] = array('name'=> "Nearest ".$row3['Place'], 'sphinx'=> "@place ".$row3['Place'], 'placename_id'=> $row2['placename_id']);
				@$secondary['query'][] = "@place ".$row3['Place'];
			}
		}


		$primary[] = array('name'=> "Centered on ".$row['def_nam'], 'gr'=>$row['km_ref'], 'dist'=>5000);
		@$secondary['near'][] = $row['km_ref'];

		$name = $row['def_nam']; //todo, split on langauge! - this is assuming os_gaz has the best version of the name!
	}
}

//2. os_gaz_250
if (!empty($_GET['os_gaz_250'])) {
	$ri = 1;
        $row = $db->getRow("SELECT * FROM os_gaz_250 WHERE seq = ".intval($_GET['os_gaz_250']));
        if (!empty($row)) {
		list ($gridref,) = $conv->national_to_gridref($row['east'],$row['north'],6,$ri);
		//todo, recaps?
		$primary[] = array('name'=> "Centered on ".$row['def_nam'], 'gr'=>$gridref, 'dist'=>5000);
		@$secondary['near'][] = $gridref;
	}
}
if (!empty($_GET['loc_abgaz'])) {
        $ri = 1;
        $row = $db->getRow("SELECT * FROM loc_abgaz WHERE gaz_id = ".intval($_GET['loc_abgaz']));
        if (!empty($row)) {
                $primary[] = array('name'=> "Centered on ".$row['full_name'], 'gr'=>$row['gridref'], 'dist'=>5000);
                @$secondary['near'][] = $row['gridref'];
        }
}

if (!empty($_GET['loc_placenames'])) {
        $row = $db->getRow("SELECT * FROM loc_placenames WHERE id = ".intval($_GET['loc_placenames']));
        if (!empty($row)) {
                list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],6,$row['reference_index']);
                $primary[] = array('name'=> "Centered on ".$row['full_name'], 'gr'=>$gridref, 'dist'=>5000);
                @$secondary['near'][] = $gridref;
        }
}

if (!empty($_GET['os_open_names'])) {
        $ri = 1;
        $row = $db->getRow("SELECT * FROM os_open_names WHERE auto_id = ".intval($_GET['os_open_names']));
        if (!empty($row)) {
                list ($gridref,) = $conv->national_to_gridref($row['geometry_x'],$row['geometry_y'],6,$ri);
		//todo, should use most_detail_view_res or least_detail_view_res to change distance??
                $primary[] = array('name'=> "Centered on ".$row['name1'].($row['name2']?" ({$row['name2']})":''), 'gr'=>$gridref, 'dist'=>5000);
                @$secondary['near'][] = $gridref;
        }
}

if (!empty($_GET['ie_open_data'])) {
        $ri = 2;
        $row = $db->getRow("SELECT * FROM ie_open_data WHERE id = ".intval($_GET['ie_open_data']));
        if (!empty($row)) {
                list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],6,$ri);
                $primary[] = array('name'=> "Centered on ".$row['name'], 'gr'=>$gridref, 'dist'=>5000);
                @$secondary['near'][] = $gridref;
        }
}

//this is mimiking using the OS urban area dataset!
if ($_GET['query'] == 'ffestiniog') {
	$primary[] = array('name'=> "In Polygon (hand drawn)", "polygon" =>
	"-3.93794,52.96054+-3.93795,52.96054+-3.93857,52.95993+-3.93927,52.95930+-3.94063,52.95905+-3.94136,52.95944+-3.94136,52.95944+-3.94136,52.95944+-3.94136,52.95944+-3.94137,52.95944+-3.94137,52.95944+-3.94137,52.95944+-3.94137,52.95944+-3.94138,52.95944+-3.94138,52.95944+-3.94187,52.95900+-3.94187,52.95900+-3.94188,52.95899+-3.94188,52.95899+-3.94188,52.95899+-3.94187,52.95899+-3.94187,52.95899+-3.94187,52.95898+-3.94061,52.95815+-3.94060,52.95814+-3.94060,52.95814+-3.94060,52.95814+-3.94060,52.95814+-3.93765,52.95808+-3.93765,52.95808+-3.93508,52.95839+-3.93507,52.95839+-3.93507,52.95839+-3.93371,52.95897+-3.93312,52.95865+-3.93340,52.95836+-3.93340,52.95836+-3.93340,52.95836+-3.93340,52.95836+-3.93340,52.95836+-3.93340,52.95835+-3.93340,52.95835+-3.93339,52.95835+-3.93219,52.95774+-3.93219,52.95774+-3.93219,52.95774+-3.93218,52.95774+-3.93218,52.95774+-3.93218,52.95774+-3.93217,52.95774+-3.93217,52.95774+-3.93104,52.95837+-3.92891,52.95778+-3.92890,52.95778+-3.92890,52.95778+-3.92890,52.95778+-3.92889,52.95778+-3.92889,52.95778+-3.92889,52.95778+-3.92889,52.95778+-3.92833,52.95822+-3.92833,52.95822+-3.92833,52.95823+-3.92833,52.95823+-3.92833,52.95823+-3.92833,52.95823+-3.92833,52.95823+-3.92833,52.95824+-3.92833,52.95824+-3.92834,52.95824+-3.92988,52.95876+-3.92862,52.95954+-3.92807,52.95926+-3.92807,52.95925+-3.92807,52.95925+-3.92807,52.95925+-3.92806,52.95925+-3.92806,52.95925+-3.92806,52.95925+-3.92805,52.95926+-3.92805,52.95926+-3.92730,52.95972+-3.92730,52.95972+-3.92730,52.95972+-3.92730,52.95973+-3.92724,52.95996+-3.92579,52.96033+-3.92500,52.95994+-3.92500,52.95994+-3.92500,52.95994+-3.92500,52.95994+-3.92499,52.95994+-3.92499,52.95994+-3.92186,52.96059+-3.92185,52.96059+-3.92185,52.96059+-3.92185,52.96059+-3.92185,52.96059+-3.92185,52.96059+-3.92185,52.96059+-3.92185,52.96060+-3.92204,52.96120+-3.92204,52.96120+-3.92204,52.96121+-3.92204,52.96121+-3.92205,52.96121+-3.92205,52.96121+-3.92205,52.96121+-3.92205,52.96121+-3.92206,52.96121+-3.92582,52.96073+-3.92893,52.96356+-3.92893,52.96356+-3.92893,52.96356+-3.92894,52.96356+-3.92894,52.96357+-3.92956,52.96367+-3.92956,52.96367+-3.92957,52.96367+-3.92957,52.96367+-3.92957,52.96367+-3.92957,52.96367+-3.92958,52.96366+-3.92958,52.96366+-3.92958,52.96366+-3.93041,52.96232+-3.93190,52.96147+-3.93335,52.96236+-3.93335,52.96236+-3.93336,52.96236+-3.93336,52.96236+-3.93488,52.96258+-3.93488,52.96258+-3.93489,52.96258+-3.93489,52.96258+-3.93489,52.96258+-3.93490,52.96258+-3.93490,52.96258+-3.93490,52.96258+-3.93619,52.96113+-3.93619,52.96113+-3.93619,52.96113+-3.93619,52.96112+-3.93619,52.96112+-3.93619,52.96112+-3.93619,52.96112+-3.93541,52.96060+-3.93537,52.96035+-3.93600,52.95993+-3.93612,52.96011+-3.93612,52.96011+-3.93612,52.96011+-3.93612,52.96011+-3.93613,52.96011+-3.93613,52.96011+-3.93613,52.96011+-3.93613,52.96011+-3.93614,52.96011+-3.93754,52.95975+-3.93793,52.96008+-3.93670,52.96080+-3.93670,52.96080+-3.93670,52.96080+-3.93669,52.96080+-3.93669,52.96080+-3.93669,52.96080+-3.93670,52.96081+-3.93670,52.96081+-3.93702,52.96106+-3.93702,52.96107+-3.93702,52.96107+-3.93703,52.96107+-3.93703,52.96107+-3.93703,52.96107+-3.93703,52.96107+-3.93704,52.96107+-3.93704,52.96107+-3.93704,52.96107+-3.93794,52.96054"
	);
}


//features TODO!

/*

if (!empty($_GET[''])) {
        $ri = 1;
        $row = $db->getRow("SELECT * FROM  WHERE seq = ".intval($_GET['']));
        if (!empty($row)) {
                list ($gridref,) = $conv->national_to_gridref($row['east'],$row['north'],6,$ri);
                $primary[] = array('name'=> "Centered on ".$row['def_nam'], 'gr'=>$gridref, 'dist'=>5000);
                @$secondary['near'][] = $gridref;
        }
}

	//7. features (Exclude placenames!) - but will cover rivers, greanspaces etc
		//4=greenspace copy, 5=os_open_names and 9=ie_open which already included above
		$sql = "select id,feature_type_id,grid_reference,name,county,category from feature_item where match($query) and feature_type_id NOT in (4,5,9) limit 40
			option field_weights=(name=10,category=9)";
		if ($rows = $sph->getAll($sql)) {
			$names= $db->getAssoc("select feature_type_id,title FROM feature_type");
			foreach($rows as &$row) {
				$bits = array($row['name'], $row['grid_reference'], $row['county'], $row['category'], @$names[$row['feature_type_id']]);
				$row['label'] = implode(', ',array_filter($bits));
			} unset($row);
			renderResults("Features Directory", 'feature_item', $rows, 'radio', 'Wont generally include placenames, so expected not to find settlements in this list');
		}

	//9. content
		$sql = "select id,asource,title FROM content where match($query) and asource !=6 limit 100";
		if ($rows = $sph->getAll($sql)) {
			//seems bad way to do it, but runs quickly!
			$names= $db->getAssoc("select source+0 as asource, source from content group by source order by null");
			$names[9] = "shared description";
			foreach($rows as &$row) {
				$bits = array($row['title'], @$names[$row['asource']]);
				$row['label'] = implode(", ",array_filter($bits))."\n";
			} unset($row);
			renderResults("Collections", 'contents[]', $rows, 'checkbox', "There is a chance that someone has created a collection for this place. Only select if appears the collection is specifially 'for' this place");
		}
*/

if (!empty($_GET['tags'])) {
	$ids = array_filter(array_map('intval',$_GET['tags']));
	if (!empty($ids)) {
		$idstr = implode(',',$ids);
		$rows = $db->getAll("SELECT * FROM tag WHERE status=1 AND tag_id IN ($idstr)");
		foreach($rows as $row) {
			if (!empty($row['prefix']))
				$row['tag'] = strtolower(trim($row['prefix'])).":".$row['tag']; //todo recap?
			$primary[] = array('name'=> "Tagged with [".$row['tag']."]", 'sphinx'=>"[".$row['tag']."]");
			@$secondary['tagged'][] = "[".$row['tag']."]";
		}
	}
}

//content TODO!


##################################################################################################

if (!empty($name)) {
	################
	//keywods based

	$qname = '"'.$name.'"';

	$primary[] = array('name'=> "Matching Keywords $qname", 'sphinx'=> $qname);
	@$secondary['query'][] = $qname;

	$primary[] = array('name'=> "Title/description with $qname", 'sphinx'=> '@(title,comment) '.$qname);
	@$secondary['query'][] = '@(title,comment) '.$qname;

	//dont need to offer an explicit tag seach, done above. But can still offer a very general tags search!
	if (!empty($_GET['tags'])) {
		$primary[] = array('name'=> "$qname Anywhere in Tags", 'sphinx'=> "@tags $qname");
		@$secondary['query'][] = "@tags $qname";
	}
	################
	//visual

	//we dont explicitly filte by location, but user can use a secondary filter!
	$primary[] = array('name'=> "Visually Similar to $qname", 'label'=> $name);

	################
}



##################################################################################################

$done = array();
?>
<div class="main-layout">
	<form class="sidebar">
		<b>Primary Filter:</b><br>
		<? foreach ($primary as $idx => $row) {
			if (!empty($row['gr'])) { //dedulicate the GR filters
				if (!empty($done[$row['gr']]))
					continue;
				$done[$row['gr']]=1;
			}
			print "<label><input type=radio name=primary value=$idx > ";
			if (!empty($row['gr'])) {
				print "<tt>{$row['gr']}</tt> ";
			}
			print "<span class=nowrap>".htmlentities2($row['name'])."</span>";
			print "</label><br>";
		} ?><hr>

		<b>Secondary Filter(s):</b><br>
		<? foreach ($secondary as $name => $rows) {
			print "<div id=\"div$name\">";
			print "<input type=checkbox name=$name value=1>$name ";
			print "<select name=$name>";
			$done = array();
			foreach (array_unique($rows) as $value) {
				printf('<option value="%s"%s>%s</option>',$v = htmlentities($value), '', $v);
			}
			print "</select></div>";
		} ?><hr>

		<div id="divdist" style="float:right;width:120px">
			&middot; <b>Distance:</b><br> <input type=number name=dist style="text-align:right;width:80px" value="5000" min="100" max="10000" step=100>m
		</div>

	    <div class="radio-group">
		<b>Order:</b><br>
	        <div class="radio-item">
	            <input type="radio" id="distance" name="order" value="geodist asc" data-attrs="gr" checked>  <label for="distance">Distance</label>
	        </div>
	        <div class="radio-item">
	            <input type="radio" id="relevance" name="order" value="weight desc" data-attrs="sphinx">  <label for="relevance">Relevance</label>
	        </div>
	        <div class="radio-item">
	            <input type="radio" id="sequence" name="order" value="sequence asc" data-attrs="gr,polygon,sphinx">  <label for="sequence">Spread</label>
	        </div>
	        <div class="radio-item">
	            <input type="radio" id="taken_asc" name="order" value="takendays asc" data-attrs="gr,polygon,sphinx">  <label for="taken_asc">Taken Asc</label>
	        </div>
	        <div class="radio-item">
	            <input type="radio" id="taken_desc" name="order" value="takendays desc" data-attrs="gr,polygon,sphinx">  <label for="taken_desc">Taken Desc</label>
	        </div>
	        <div class="radio-item">
	            <input type="radio" id="similarity" name="order" value="" data-attrs="label">  <label for="similarity">Similarity</label>
	        </div>
	    </div>

		<hr>
		<label><input type=checkbox name=map> Show results on Map</label>
		<div id="countMessage"></div>
	</form>
	<div id="results" class="grid-container">

	</div>
</div>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>

<script src="<? echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>
<script src="/js/geograph-api-libs.js?<? echo filemtime("../js//geograph-api-libs.js"); ?>"></script>

<script>
let primary = <? echo json_encode($primary); ?>;
let secondary = <? echo json_encode($secondary); ?>;
let map = null;
let layerGroup = null;

function updateSecondry() {
	var selected = $('input[name=primary]:checked').val();
	if (!selected)
		return;
	var row = primary[selected];

	$('#divnear').toggle((row.gr||row.polygon)?false:true);
	$('#divquery').toggle((row.sphinx||row.label)?false:true);
	$('#divtagged').toggle(row.label?false:true);

	//actully, if gr isnt the primary, they gr is still available as secondary!
		//could maybe hide, if primary isnt gr AND near filter is off
	//$('#spandist').toggle(row.gr?true:false);

	/////////////////////////////////////

        // Get the value of the currently checked radio button
        var prevVal = $('input[name=order]:checked').val();

        // Iterate through all radio buttons with the name 'order'
        $('input[name=order]').each(function() {
            var $radio = $(this);
            // Split the data-attrs string into an array
            var attrs = $radio.data('attrs').split(',');
            var show = false;

            // Check if any of the attributes exist as a property on the 'row' object
            for (var i = 0; i < attrs.length; i++) {
                var attr = attrs[i].trim();
                if (row[attr]) {
                    show = true;
                    break;
                }
            }

            // Disable the radio button if it should not be available
            $radio.prop('disabled', !show);
        });

        // After filtering, check if the previously selected option is still available
        var $prevRadio = $('input[name=order][value="' + prevVal + '"]');

        // If the previous radio is NOT disabled, keep it checked
        if (!$prevRadio.prop('disabled')) {
            $prevRadio.prop('checked', true);
        } else {
            // If the previous radio is now disabled, find the first enabled radio and check it
            var $firstEnabledRadio = $('input[name=order]:not(:disabled)').first();
            if ($firstEnabledRadio.length) {
                $firstEnabledRadio.prop('checked', true);
            }
        }

	/////////////////////////////////////

//	var url = "https://www.geograph.org.uk/api-facetql.php?match=ffestiniog&select=id,user_id,realname,grid_reference,title,hash&long=1&limit=30";
	let base = "https://www.geograph.org.uk/api-facetql.php";
	let data = {long:1, select:"id,user_id,realname,grid_reference,title,hash,wgs84_lat,wgs84_long", limit:30, utf:1};
	let match = [];

	if (row.sphinx)
		match.push(row.sphinx);
	if ($('input[name=tagged]:checked:visible').length)
		match.push($('select[name=tagged]').val());
	if (match.length)
		data['match'] = match.map(getTextQuery).join(' '); //need getTextQuery to deal with our [tag] syntax

	if (row.label) {
		base = "https://www.geograph.org.uk/api-facetql-vector.php"; //for now, requires a different API endpoint
		data['label'] = row.label;
	} else {
	        var order = $('input[name=order]:checked').val();
		if (order) {
			data['order'] = order;
			if (order == 'weight desc')
				data.select += ", WEIGHT() as weight";
		}
	}

	if (row.polygon) {
		//sphinx can't use expressions direct in WHERE!
		data.select += ", "+getPolygonFilter(row.polygon)+" as inside";
		data.where = "inside=1";
	}

	let distance = parseInt($('input[name=dist]').val(),10) || 5000;
	let wgs84;
	if (row.gr) {
	 	wgs84 = gridref2wgs(row.gr); //should automaticalyl 'fudge' 4fig GRs
		data.geo=parseFloat(wgs84.latitude).toFixed(6)+","+parseFloat(wgs84.longitude).toFixed(6)+","+distance;
	} else {
		if ($('input[name=near]:checked:visible').length) {
		 	wgs84 = gridref2wgs($('select[name=near]').val());
			data.geo=parseFloat(wgs84.latitude).toFixed(6)+","+parseFloat(wgs84.longitude).toFixed(6)+","+distance;
		}
	}

	if ($('input[name=map]:checked').length) {
		$('#results').removeClass('grid-container');

		if (map && layerGroup) {
		    layerGroup.clearLayers();
		} else {
			$('#results').empty();
	            // Set the map's initial view to the UK
	            map = L.map('results').setView([54.0, -2.0], 6);

	            // Add a base map tile layer
	            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
	                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
	            }).addTo(map);

		    layerGroup = L.layerGroup().addTo(map);
		}
		if (wgs84 && wgs84.latitude)
			L.circleMarker([wgs84.latitude, wgs84.longitude], {radius:6}).addTo(layerGroup);
		mapAPIResults(base+'?'+$.param(data), layerGroup, true, 'countMessage'); //pass the layergroup, so markers are added to the group!

	} else {
		if (map) { //first need to destroy the map!
			map.remove();
			map = null;
			document.getElementById('results').className = ''; //remove all!

		}
		$('#results').addClass('grid-container');
		renderAPIResults(base+'?'+$.param(data), 'results', 'countMessage');
	}
}

$(function() {
	$('form.sidebar input[type=radio], form.sidebar input[type=checkbox]').on('click', updateSecondry);
	$('form.sidebar select, form.sidebar input[type=number]').on('change', updateSecondry);
});

</script>
<style>
input[disabled] + label {
    color: #a0a0a0; /* A light gray color */
    cursor: not-allowed; /* Change the cursor to indicate it's not clickable */
}
        .main-layout {
            display: grid;
            /* Creates a left sidebar with a fixed width of 250px
               and a main content area that takes up the rest of the space. */
            grid-template-columns: 320px 1fr;
            gap: 10px;
        }

        .sidebar {
            background-color: #eee;
	    padding:10px;
        }
	#maincontent form.sidebar label {
	    font-size:1.01em;
	}

        .grid-container {
            /* Use a CSS Grid layout */
            display: grid;
            /* This is the key property for responsive columns.
               It creates as many columns as possible (auto-fill)
               with a minimum width of 213px and a maximum of 1fr (fractional unit). */
            grid-template-columns: repeat(auto-fill, minmax(213px, 1fr));
            gap: 2px; /* Spacing between grid items */
        }
	.grid-container div {
	    text-align: center;
	    min-height:160px;
	}
	.grid-container div a:first-child {
	    display:block;
	}

	#results.leaflet-container {
		max-height:calc( 100dvh - 100px );
	}

</style>
<?

$smarty->display('_std_end.tpl');



