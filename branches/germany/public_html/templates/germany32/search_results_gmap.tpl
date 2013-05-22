{include file="_search_begin.tpl"}

{if !$google_maps_api_key}
	<div class="interestBox">
	<p>This page is no longer able to display a map - please use a different display method.</p>
	<p>However you may be able to display a map on {if $engine->currentPage > 1}
{external href="http://maps.google.com/?q=http://`$http_host`/feed/results/`$i`/`$engine->currentPage`.kml" text="Google Maps"}.{else}
{external href="http://maps.google.com/?q=http://`$http_host`/feed/results/`$i`.kml" text="Google Maps"}.{/if}</p>
	</div>

{elseif $engine->resultCount}

	<div id="map" style="width:100%; height:500px; position:relative;"></div>
	{if $engine->results}{literal}
	<script type="text/javascript">
	//<![CDATA[
	var map;
	var infoWindow;

	function onLoad() {
		var point = null;
		var zoom = null;
		var mapType = google.maps.MapTypeId.HYBRID;

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
					point = new google.maps.LatLng(parseFloat(bits[0]),parseFloat(bits[1]));
				}
				if (argname == "z") {
					zoom = parseInt(value);
				}
				if (argname == "t") {
					if (value == "m") {
						mapType = google.maps.MapTypeId.ROADMAP;
					} else if (value == "k") {
						mapType = google.maps.MapTypeId.SATELLITE;
					} else if (value == "h") {
						mapType = google.maps.MapTypeId.HYBRID;
					} else if (value == "p") {
						mapType = google.maps.MapTypeId.TERRAIN;
					}
					//if (value == "e") {mapType = G_SATELLITE_3D_MAP; map.addMapType(G_SATELLITE_3D_MAP);}
				}
			}
		}

		var fitbounds = point === null || zoom === null;
		if (fitbounds) {
			point = new google.maps.LatLng(0,0);
			zoom = 0;
		}

		infoWindow = new google.maps.InfoWindow();

		map = new google.maps.Map(
			document.getElementById('map'), {
			center: point,
			zoom: zoom,
			mapTypeId: mapType,
			streetViewControl: false, //true,
			mapTypeControlOptions: {
				mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID, google.maps.MapTypeId.TERRAIN],
				style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
			}
		});

		if (fitbounds) {
			var bounds = new google.maps.LatLngBounds();

			{/literal}{foreach from=$engine->results item=image}
				bounds.extend(new google.maps.LatLng({$image->wgs84_lat}, {$image->wgs84_long}));
			{/foreach}{literal}

			map.fitBounds(bounds);
		}
		
		{/literal}

		{if $markers}
			{foreach from=$markers item=marker}
				createMarker(new google.maps.LatLng({$marker.1},{$marker.2}),'{$marker.0}');
			{/foreach}
		{/if}{literal}

		var html;
		var thumburl;
		var thumbwidth;
		var thumbheight;
		{/literal}{foreach from=$engine->results item=image}
			thumburl = '{$image->getThumbnail(120,120,3)|escape:"javascript"}';
			thumbwidth = {$image->last_width};
			thumbheight = {$image->last_height};
			html = makeHtml(
				'/photo/{$image->gridimage_id}',
				'{$image->realname|escape:"html"|escape:"javascript"}',
				'{$image->grid_reference|escape:"html"|escape:"javascript"}',
				'{$image->title1|escape:"html"|escape:"javascript"}',
				'{$image->title2|escape:"html"|escape:"javascript"}',
				'{$image->comment1|escape:"html"|nl2br|geographlinks|escape:"javascript"}',
				'{$image->comment2|escape:"html"|nl2br|geographlinks|escape:"javascript"}',
				thumburl, thumbwidth, thumbheight
			);
			createMarker(new google.maps.LatLng({$image->wgs84_lat}, {$image->wgs84_long}), html, thumburl, thumbwidth, thumbheight);
		{/foreach}{literal}

		google.maps.event.addListener(map, "dragend", makeHash);
		google.maps.event.addListener(map, "zoom_changed", makeHash);
		google.maps.event.addListener(map, "maptypeid_changed", makeHash);
		// FIXME tilt_changed?
	}

	function makeHash() {
		var ll = map.getCenter().toUrlValue(6);
		var z = map.getZoom();
		var t = map.getMapTypeId();//CurrentMapType().getUrlArg();
		if (t == google.maps.MapTypeId.ROADMAP) {
			t = 'm';
		} else if (t == google.maps.MapTypeId.SATELLITE) {
			t = 'k';
		} else if (t == google.maps.MapTypeId.HYBRID) {
			t = 'h';
		} else if (t == google.maps.MapTypeId.TERRAIN) {
			t = 'p';
		} else {
			t = 'h';
		}
		window.location.hash = '#ll='+ll+'&z='+z+'&t='+t;
	}

	function makeHtml(photourl, realname, gridref, title1, title2, comment1, comment2, thumburl, thumbwidth, thumbheight) {
		var title = title2 === '' ? title1 : (title1 === '' ? title2 : title1 + ' (' + title2 + ')');
		title = gridref + ' : ' + title;
		var comment = comment2 === '' ? comment1 : (comment1 === '' ? comment2 : comment1 + '</p><hr style="width:3em"/><p style="font-weight:bold">' + comment2);
		var html = '<h4 style="font-family:Arial,sans-serif;font-weight:bold;font-size:medium">'+title+'</h4>';
		html += '<div style="font-family:Arial,sans-serif;text-align:center;font-size:small">';
		html += '<p><a href="'+photourl+'" target="_blank"><img src="'+thumburl+'" width="'+thumbwidth+'" height="'+thumbheight+'" alt="'+title+'"/></a></p>';
		if (comment !== '') {
			html += '<p style="font-weight:bold">' + comment + '</p>';
		}
		html += '<p>&copy; Copyright <i>'+realname+'</i> and licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank">Creative Commons Licence</a></p>';
		html += '<p><a href="'+photourl+'" target="_blank">View photo page</a></p>';
		html += '</div>';
		return html;
	}

	function createMarker(point,myHtml,url,w,h) {
		if (typeof url !== "undefined") {
			var maxdim = Math.max(w, h);
			var scale = maxdim <= 40 ? 1 : 40.0/maxdim;
			var sw = Math.round(scale * w);
			var sh = Math.round(scale * h);
			var ax = Math.round(0.5 * sw);
			var ay = Math.round(0.5 * sh);
			var icon = new google.maps.MarkerImage(url, new google.maps.Size(w, h), null, new google.maps.Point(ax, ay), new google.maps.Size(sw, sh));
			var marker = new google.maps.Marker({
				position: point,
				map: map,
				icon: icon,
			});
		} else {
			var marker = new google.maps.Marker({
				position: point,
				map: map,
				draggable: true,
			});
		}

		google.maps.event.addListener(marker, "click", function() {
			infoWindow.setContent(myHtml);
			infoWindow.open(map, marker);
		});
		google.maps.event.addListener(marker, "dragend", function() {
			marker.setPosition(point);
		});

		return marker;
	}

	AttachEvent(window,'load',onLoad,false);
	//]]>
	</script>
	{/literal}{/if}
	{if $nofirstmatch}
	<p style="font-size:0.8em">[We have no images for {$engine->criteria->searchq|escape:"html"}, <a href="/submit.php?gridreference={$engine->criteria->searchq|escape:"url"}">Submit Yours Now</a>!]</p>
	{/if}
	{if $singlesquares}
	<p style="font-size:0.8em">[<a href="/squares.php?p={math equation="900*(y-1)+900-(x+1)" x=$engine->criteria->x y=$engine->criteria->y}&amp;distance={$singlesquare_radius}">{$singlesquares} squares within {$singlesquare_radius}km have no or only one photo</a> - can you <a href="/submit.php">add more</a>?]</p>
	{/if}


	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	
	<script src="http://maps.googleapis.com/maps/api/js?sensor=false&amp;key={$google_maps_api_key}" type="text/javascript"></script>
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
