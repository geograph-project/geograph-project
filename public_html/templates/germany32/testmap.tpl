{if $inner}
{assign var="page_title" value="Geograph Map"}

{include file="_basic_begin.tpl"}
{else}

{assign var="page_title" value="Geograph Map"}
{include file="_std_begin.tpl"}
{/if}

<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingG.js"|revision}"></script>
<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_api_key}" type="text/javascript"></script>
{literal}
	<script type="text/javascript">
	//<![CDATA[
		var issubmit = 1;
		var ri = -1;
		
		//the google map object
		var map;

		//the geocoder object
		var geocoder;
		var running = false;

{/literal}
		{strip}
		var yflip = [
		{foreach from=$yflip item=val name=fe}
			{$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		];
		{/strip}
		{strip}
		var pixperkm = [
		{foreach from=$pixperkm item=val name=fe}
			{$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		];
		{/strip}
		{strip}
		var kmpertile = [
		{foreach from=$kmpertile item=val name=fe}
			{$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		];
		{/strip}
		var ts = {$ts};
		{strip}
		var ris = [
		{foreach from=$ris item=val name=fe}
			{$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		];
		{/strip}
		var ridefault = {$ridefault};
		{strip}
		var areanames = {ldelim}
		{foreach from=$areanames key=ri item=val name=fe}
			{$ri}: '{$val|escape:javascript}'{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var grids = {ldelim}
		{foreach from=$grids key=ri item=val name=fe}
			{$ri}: new GT_{$val}(){if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var gridconv = {ldelim}
		{foreach from=$grids key=ri item=val name=fe}
			{$ri}: 'get{$val}'{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var x0 = {ldelim}
		{foreach from=$x0 key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var y0 = {ldelim}
		{foreach from=$y0 key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var xmin = {ldelim}
		{foreach from=$xmin key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var ymin = {ldelim}
		{foreach from=$ymin key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var xmax = {ldelim}
		{foreach from=$xmax key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var ymax = {ldelim}
		{foreach from=$ymax key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var latmin = {ldelim}
		{foreach from=$latmin key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var lonmin = {ldelim}
		{foreach from=$lonmin key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var latmax = {ldelim}
		{foreach from=$latmax key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
		{strip}
		var lonmax = {ldelim}
		{foreach from=$lonmax key=ri item=val name=fe}
			{$ri}: {$val}{if !$smarty.foreach.fe.last},{/if}
		{/foreach}
		{rdelim};
		{/strip}
{literal}

		function GeoProj(ri) {
			this.wgs84 = new GT_WGS84();
			this.lonmin = lonmin[ri];
			this.lonmax = lonmax[ri];
			this.latmin = latmin[ri];
			this.latmax = latmax[ri];
			this.xmin = xmin[ri];
			this.xmax = xmax[ri];
			this.ymin = ymin[ri];
			this.ymax = ymax[ri];
			this.x0 = x0[ri];
			this.y0 = y0[ri];
			this.wgs84.getEN = this.wgs84[gridconv[ri]]; // should belong to this.grid...
			this.grid = grids[ri];
		}

		GeoProj.prototype=new GProjection();
		GeoProj.prototype.fromLatLngToPixel=function(ll, z) {
			//var coord = GT_Math.wgs84_to_utm(ll.lat(), ll.lng(), this.zone);
			//var e = coord[0];
			//var n = coord[1];
			this.wgs84.setDegrees(ll.lat(), ll.lng());
			var grid = this.wgs84.getEN(true, false, true);
			var e = grid.eastings;
			var n = grid.northings;
			var y = Math.round((     yflip[z]-(n/1000+this.y0)) *      pixperkm[z]);
			var x = Math.round(               (e/1000+this.x0)  *      pixperkm[z]);
			return new GPoint(x,y);
		}
		GeoProj.prototype.fromPixelToLatLng=function(xy, z, unb) {
			var x = xy.x;
			var y = xy.y;
			var e = (              x/     pixperkm[z] - this.x0) * 1000;
			var n = (     yflip[z]-y/     pixperkm[z] - this.y0) * 1000;
			//var coord = GT_Math.utm_to_wgs84(e, n, this.zone);
			//var lat = coord[0];
			//var lon = coord[1];
			this.grid.setGridCoordinates(e, n);
			var ll= this.grid.getWGS84(true);
			var lat = ll.latitude;
			var lon = ll.longitude;
			return new GLatLng(lat,lon,unb);
		}
		GeoProj.prototype.tileCheckRange=function(txy,z,ts) {
			var y1 = Math.round(     yflip[z]- txy.y *      kmpertile[z] -      kmpertile[z]);
			var x1 = Math.round(               txy.x *      kmpertile[z]);

			if (y1 +      kmpertile[z] < this.ymin || y1 > this.ymax)
				return false;
			if (x1 +      kmpertile[z] < this.xmin || x1 > this.xmax)
				return false;

			return true;
		}
		GeoProj.prototype.getWrapWidth=function(z) {
			return 1.0e30; //FIXME
		}

		// The allowed region which the whole map must be within
		var allowedBounds;
		function setBounds() {
			var proj = map.getCurrentMapType().getProjection();
			allowedBounds = new GLatLngBounds(new GLatLng(proj.latmin,proj.lonmin), new GLatLng(proj.latmax,proj.lonmax));
		}

		function radioVal(obj) {
			for (var i = 0; i < obj.length; i++) if (obj[i].checked) return obj[i].value;
			return false;
		}

		function openGeoWindow(prec, url) {
			var curgridref = document.theForm.grid_reference.value.replace(/ /g,'');
			if (curgridref.search(/^[A-Za-z]+(\d\d)+$/) != 0)
				return;
			var preflen = curgridref.search(/\d/);
			var curprec = (curgridref.length-preflen)/2;
			prec = Math.min(prec, curprec);
			var gridref = curgridref.substr(0, preflen) + curgridref.substr(preflen, prec) + curgridref.substr(preflen+curprec, prec);
			window.open(url+gridref,'_blank');
		}

		function clearMarker() {
			if (currentelement) {
				map.removeOverlay(currentelement);
				currentelement = null;
				document.theForm.grid_reference.value = '';
			}
		}

		function showAddress(address) {
			if (!geocoder) {
				 geocoder = new GClientGeocoder();
			}
			if (geocoder) {
				geocoder.getLatLng(address,function(point) {
					if (!point) {
						alert("Your entry '" + address + "' could not be geocoded, please try again");
					} else {
						if (currentelement) {
							currentelement.setPoint(point);
							GEvent.trigger(currentelement,'drag');

						} else {
							currentelement = createMarker(point,null);
							map.addOverlay(currentelement);

							GEvent.trigger(currentelement,'drag');
						}
						var curmap = map.getCurrentMapType();//null
						var maptypes = map.getMapTypes();
						var lon = point.lng();
						for (idx in maptypes) {
							var mtype = maptypes[idx];
							var proj = mtype.getProjection();
							if (proj.lonmin < lon && lon <= proj.lonmax) {
								curmap = mtype;
								break;
							}
						}

						map.setCenter(point, 2, curmap);
						setBounds();
					}
				 });
			}
			return false;
		}

		function GetTileUrl_Geo(txy, z, ri) {
			var y1 = Math.round(yflip[z]- txy.y * kmpertile[z] - kmpertile[z]);
			var x1 = Math.round(          txy.x * kmpertile[z]);
			return "/tile.php?x="+x1+"&y="+y1+"&z="+(-z-1)+"&i="+ri;
		}

		function loadmap() {
			if (GBrowserIsCompatible()) {
				var copyright = new GCopyright(1,
					new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
					'(<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)');
				var copyrightCollection =
					new GCopyrightCollection('&copy; <a href="http://geo.hlipp.de">Geograph</a> and <a href="http://www.openstreetmap.org/">OSM</a> Contributors');
				copyrightCollection.addCopyright(copyright);

				var curmap;
				var geomaps = [];
				for (var i in ris) {
					var curri = ris[i];
					tilelayer = [new GTileLayer(copyrightCollection,0,7)];
					tilelayer[0].ri = curri;
					tilelayer[0].getTileUrl = function (txy, z) { return GetTileUrl_Geo(txy, z, this.ri)};
					tilelayer[0].isPng = function () { return true; };
					tilelayer[0].getOpacity = function () { return 1.0; };
					geomaps[i] = new GMapType(tilelayer, new GeoProj(curri), areanames[curri], {tileSize: ts});
					if (curri == ridefault)
						curmap = geomaps[i];
				}

				map = new GMap2(document.getElementById("map"), {mapTypes:geomaps});

				map.addControl(new GLargeMapControl());
				map.addControl(new GMapTypeControl(true));
				
				var curproj = curmap.getProjection();
				var point = new GLatLng((curproj.latmin+curproj.latmax)*.5, (curproj.lonmin+curproj.lonmax)*.5);
				map.setCenter(point, 0, curmap);
				setBounds();

				map.enableDoubleClickZoom(); 
				map.enableContinuousZoom();
				map.enableScrollWheelZoom();
		
				GEvent.addListener(map, "click", function(marker, point) {
					if (marker) {
						return; /* FIXME? */
					}
					curSelection = "marker";//radioVal(document.settings.on_click);
					if (curSelection == "marker") {
						if (currentelement) {
							currentelement.setPoint(point);
							GEvent.trigger(currentelement,'drag');
						
						} else {
							currentelement = createMarker(point,null);
							map.addOverlay(currentelement);
							
							GEvent.trigger(currentelement,'drag');
						}
						return;
					}

					//FIXME -> function in mappingG.js, see also createMarker in that file
					var wgs84=new GT_WGS84();
					wgs84.setDegrees(point.lat(), point.lng());
					if (ri == -1||issubmit) {
						if (wgs84.isIreland()) {
							//convert to Irish
							var grid=wgs84.getIrish(true);
						
						} else if (wgs84.isGreatBritain()) {
							//convert to OSGB
							var grid=wgs84.getOSGB();
						} else if (wgs84.isGermany32()) {
							//convert to German
							var grid=wgs84.getGerman32();
						} else if (wgs84.isGermany33()) {
							//convert to German
							var grid=wgs84.getGerman33();
						} else if (wgs84.isGermany31()) {
							//convert to German
							var grid=wgs84.getGerman31();
						} else {
							//FIXME?
							return;
						}
					}
					else if (ri == 1)
						var grid=wgs84.getOSGB();
					else if (ri == 2)
						var grid=wgs84.getIrish();
					else if (ri == 3)
						var grid=wgs84.getGerman32(true, false);
					else if (ri == 4)
						var grid=wgs84.getGerman33(true, false);
					else if (ri == 5)
						var grid=wgs84.getGerman31(true, false);
					
					if (curSelection == "search") {
						var gridref = grid.getGridRef(5);
						var url = "/search.php?q=";
					} else if (curSelection == "submit") {
						var gridref = grid.getGridRef(5);
						var url = "/submit.php?gridreference=";
					} else if (curSelection == "square") {
						var gridref = grid.getGridRef(5/*2*/);
						var url = "/gridref/";
					} else {
						//FIXME?
						return;
					}
					url += gridref.replace(/ /g,'');
					window.open(url,'_blank'/*,'width=650,height=500,scrollbars=yes'*/);
				});


				AttachEvent(window,'unload',GUnload,false);

				// Add a move listener to restrict the bounds range
				GEvent.addListener(map, "maptypechanged", function() {
					setBounds();
					checkBounds();
				});

				// Add a move listener to restrict the bounds range
				GEvent.addListener(map, "move", function() {
					checkBounds();
				});

				// The allowed region which the whole map must be within
				//var allowedBounds = new GLatLngBounds(new GLatLng(45,2), new GLatLng(57,18));//(new GLatLng(49.4,-11.8), new GLatLng(61.8,4.1));
				// If the map position is out of range, move it back
				function checkBounds() {
					
					// Perform the check and return if OK
					if (allowedBounds.contains(map.getCenter())) {
					  return;
					}
					// It`s not OK, so find the nearest allowed point and move there
					var C = map.getCenter();
					var X = C.lng();
					var Y = C.lat();

					var AmaxX = allowedBounds.getNorthEast().lng();
					var AmaxY = allowedBounds.getNorthEast().lat();
					var AminX = allowedBounds.getSouthWest().lng();
					var AminY = allowedBounds.getSouthWest().lat();

					if (X < AminX) {X = AminX;}
					if (X > AmaxX) {X = AmaxX;}
					if (Y < AminY) {Y = AminY;}
					if (Y > AmaxY) {Y = AmaxY;}

					map.setCenter(new GLatLng(Y,X));

					// This Javascript Function is based on code provided by the
					// Blackpool Community Church Javascript Team
					// http://www.commchurch.freeserve.co.uk/   
					// http://econym.googlepages.com/index.htm
				}
			}
		}

		AttachEvent(window,'load',loadmap,false);

		function updateMapMarkers() {
			updateMapMarker(document.theForm.grid_reference,false,true);
		}
		AttachEvent(window,'load',updateMapMarkers,false);
	</script>
{/literal}

<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px\"/>
<p>
This feature is still in development. Please use with care and try to avoid high server load.
</p>
<p>
Diese Kartenansicht ist noch in einem frühen Entwicklungsstadium! Bitte nicht übermäßig nutzen um zu hohe Serverlast zu vermeiden.
</p>
</div>

<p>Click on the map to create a point, pick it up and drag to move to better location...</p>

<form {if $submit2}action="/submit2.php?inner"{elseif $picasa}action="/puploader.php?inner"{elseif $ext}action="javascript:void()"{else}action="/submit.php" {if $inner} target="_top"{/if}{/if}name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

{if !$ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Selected Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>

<input type="submit" value="Next Step &gt; &gt;"/> <span id="dist_message"></span></div>
{/if}

<div id="map" style="width:600px; height:500px;border:1px solid blue">Loading map...</div>		

{if $ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Selected Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/><br />

<input type="button" value="Show gridsquare"   onclick="openGeoWindow(5, '/gridref/');" />
<input type="button" value="Submit image"      onclick="openGeoWindow(5, '/submit.php?gridreference=');" />
<input type="button" value="Search for images" onclick="openGeoWindow(5, '/search.php?q=');" />
<input type="button" value="Clear marker"      onclick="clearMarker();" />
</div>
{/if}

<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>

</form>
<form action="javascript:void()" onsubmit="return showAddress(this.address.value);" style="padding-top:5px">
<div style="width:600px; text-align:center;"><label for="addressInput">Enter Address: 
	<input type="text" size="50" id="addressInput" name="address" value="" />
	<input type="submit" value="Find"/><small><small><br/>
	(Powered by the Google Maps API Geocoder)</small></small>
</div>
</form>
{*
<form name="settings" action="javascript:void()">
<!--div style="width:600px; text-align:center;"-->
<div>
<fieldset>
<legend>On Click:</legend>
<input checked type="radio" name="on_click" id="on_click_marker" value="marker"><label for="on_click_marker">Place marker</label><br/ >
<input         type="radio" name="on_click" id="on_click_square" value="square"><label for="on_click_square">Show gridsquare</label><br/ >
<input         type="radio" name="on_click" id="on_click_submit" value="submit"><label for="on_click_submit">Submit image</label><br/ >
<input         type="radio" name="on_click" id="on_click_search" value="search"><label for="on_click_search">Search for images nearby</label>
</fieldset>
</div>
</form>
*}
{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
