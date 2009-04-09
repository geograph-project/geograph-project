{assign var="page_title" value="Cluster Maps"}
{include file="_std_begin.tpl"}


{if $google_maps_api_key}
	<h2>Draggable Interactive Map <sup style="color:red">Beta!</sup></h2>
	
	<form action="" onsubmit="return updateFilters(this);" name="theForm">
	<div style="float:left;position:relative;padding:10px;">
	Title Keyword:<br/> <input type="text" name="q" value="" id="q"/><br/>
	<small>(Example: <tt>river</tt> - single keyword only)</small>
	</div>

	<div style="float:left;position:relative;padding:10px;">
	User ID:<br/> <input type="text" name="user_id" value="" id="user_id" size="3"/><br/>
	<small>(Example: <tt>{dynamic}{$user->user_id|default:123}{/dynamic}</tt>)</small>
	</div>

	<div style="float:left;position:relative;padding:10px;">
	<br/>
	<input type="submit" value="Update"/>
	</div>

	<br style="clear:both" />
	</form>

	<div id="map" style="width:100%; height:600px; position:relative;"></div>
	{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map;
	var gc;
	var filterUrl = '';

	function onLoad() {
		map = new GMap2(document.getElementById("map"));
		map.addMapType(G_PHYSICAL_MAP);
		map.addControl(new GLargeMapControl());
		map.addControl(new GMapTypeControl());
	
	//
		var mapType = G_PHYSICAL_MAP;
		var newZoom = 6;
		var center = new GLatLng(54.55,-3.88);
		
		var filter = false;
		
		if (location.hash.length) {
			// If there are any parameters at the end of the URL, they will be in location.search
			// looking something like  "#ll=50,-3&z=10&t=h"

			// skip the first character, we are not interested in the "#"
			var query = location.hash.substring(1);

			var pairs = query.split("&");
			for (var i=0; i<pairs.length; i++) {
				// break each pair at the first "=" to obtain the argname and value
				var pos = pairs[i].indexOf("=");
				var argname = pairs[i].substring(0,pos).toLowerCase();
				var value = pairs[i].substring(pos+1).toLowerCase();

				if (argname == "ll") {
					var bits = value.split(',');
					center = new GLatLng(parseFloat(bits[0]),parseFloat(bits[1]));
				}
				if (argname == "z") {newZoom = parseInt(value);}
				if (argname == "t") {
					if (value == "m") {mapType = G_NORMAL_MAP;}
					if (value == "k") {mapType = G_SATELLITE_MAP;}
					if (value == "h") {mapType = G_HYBRID_MAP;}
					if (value == "p") {mapType = G_PHYSICAL_MAP;}
					if (value == "e") {mapType = G_SATELLITE_3D_MAP; map.addMapType(G_SATELLITE_3D_MAP);}
				}
				if (argname == "q") {document.theForm.elements['q'].value = decodeURI(value); filter = true;}
				if (argname == "u") {document.theForm.elements['user_id'].value = decodeURI(value); filter = true;}
				if (argname == "c") {document.theForm.elements['imageclass'].value = decodeURI(value); filter = true;}
			}
		}

		map.setCenter(center, newZoom, mapType);

		GEvent.addListener(map, "moveend", makeHash);
		GEvent.addListener(map, "zoomend", makeHash);
		GEvent.addListener(map, "maptypechanged", makeHash);
	
	
	// 
		gc = new gcGrid(map, "{/literal}{$geocubes_api_key}{literal}");

		gc.setOption(GC_OP_DEBUG, 1);
		gc.setOption(GC_OP_CLUSTERCOUNT, 1);

		gc.setIcon(GC_IC_CLUSTERMOUSEOVER, 0);
		gc.setVar(GC_VR_COUNTDESCR, 0);

		gc.setCallback(GC_CB_ONCREATECLUSTER, function (cl, latNE, lngNE, latSW, lngSW) {

			cl.cclayer.div_.style.marginTop = 0;
			cl.cclayer.div_.style.fontSize = "20px";
			cl.cclayer.div_.style.fontWeight = "bold";

			if (cl.count > 1000) {

				cl.cclayer.div_.style.fontSize = "16px";
				cl.setImage('http://www.geocubes.com/bla/cube1.png');

			} else if (cl.count > 100) {

				cl.cclayer.div_.style.fontSize = "14px";
				cl.setImage('http://www.geocubes.com/bla/cube2.png');

			} else {

				cl.cclayer.div_.style.fontSize = "12px";
				cl.setImage('http://www.geocubes.com/bla/cube3.png');

			}

		});

		gc.setCallback(GC_CB_POINTCLICK, function (marker, point_id, freetext, opt_field1, opt_field2) {
			var myHtml = "<b><a href='/photo/" + point_id + "' target='_blank'>" + freetext + "</a></b><br/><br/><a href='/profile/" + opt_field1 + "' target='_blank'>User Profile</a>";
			map.openInfoWindowHtml(marker.getLatLng(),myHtml);
		});

		if (filter) {
			updateFilters(document.theForm);
		}

		gc.enableRenderGrid();

	}

	function makeHash() {
		var ll = map.getCenter().toUrlValue(6);
		var z = map.getZoom();
		var t = map.getCurrentMapType().getUrlArg();
		window.location.hash = '#ll='+ll+'&z='+z+'&t='+t+filterUrl;
	}

	function updateFilters(f) {

		gc.releaseFilters();
		var render = false;
		filterUrl = '';

		if (f.q.value != '') {
			gc.textFilter (f.q.value);
			filterUrl = filterUrl + "&q=" + encodeURIComponent(f.q.value);
		} else {
			gc.textFilterRelease();
		}

		if (f.user_id.value != '') {
			gc.andFilter (GC_FD1, GC_EQ, parseInt(f.user_id.value,10));
			filterUrl = filterUrl + "&u=" + parseInt(f.user_id.value,10);
		}

		if (filterUrl.length > 0) {
			gc.renderFilter();
		}

		makeHash();
		return false;
	}

	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
	{/literal}
	
	<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}&amp;sensor=false" type="text/javascript"></script>
	<script src="http://api.geocubes.com/api/geocubes.js?v=1" type="text/javascript"></script>

{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_std_end.tpl"}
