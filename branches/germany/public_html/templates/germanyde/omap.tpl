{assign var="olayersmap" value="1"}
{if $inner}
{assign var="page_title" value="Geograph-Karte"}
{include file="_basic_begin.tpl"}
{else}
{assign var="page_title" value="Geograph-Karte"}
{include file="_std_begin.tpl"}
{/if}
<script type="text/javascript" src="/ol/OpenLayers.js"></script>
<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
<script type="text/javascript" src="{"/mappingO.js"|revision}"></script>
{* FIXME/TODO

text like "Loading Map (JavaScript Required)..."

ommap.tpl, rastermap.class.php:
- pan when moving marker out of box?
- continue panning when moving mouse pointer out of box?

*}

{literal}
	<script type="text/javascript">
	//<![CDATA[
		var issubmit = 1;
		var iscmap = 0;
		var ri = -1;
		
		var map;

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
		function transformGeoToLonLat(point) {
			var valid = false;
			var x = point.x / 1000.;
			var y = point.y / 1000.;
			for (ri in xmin) {
				if  (xmin[ri] <= x && x < xmax[ri]
				  && ymin[ri] <= y && y < ymax[ri]) {
					valid = true;
					break;
				}
			}
			if (!valid) {
				point.x = 0;
				point.y = 0;
				return;
			}
			var grid = grids[ri];
			var e = point.x - x0[ri]*1000;
			var n = point.y - y0[ri]*1000;
			grid.setGridCoordinates(e, n);
			var ll = grid.getWGS84(true);
			point.x = ll.longitude;
			point.y = ll.latitude;
		}
		function transformLonLatToGeo(point) {
			var valid = false;
			for (ri in lonmin) {
				if  (lonmin[ri] <= point.x && point.x < lonmax[ri]
				  && latmin[ri] <= point.y && point.y < latmax[ri]) {
					valid = true;
					break;
				}
			}
			if (!valid) {
				point.x = 0;
				point.y = 0;
				return;
			}
			var wgs84 = new GT_WGS84();
			wgs84.getEN = wgs84[gridconv[ri]]; // should belong to grids[ri]...
			wgs84.setDegrees(point.y, point.x);
			var grid = wgs84.getEN(true, false, true);
			var e = grid.eastings;
			var n = grid.northings;
			point.x = e + x0[ri]*1000;
			point.y = n + y0[ri]*1000;
		}

		OpenLayers.Projection.addTransform("GEOGRAPH", "EPSG:4326", transformGeoToLonLat);
		OpenLayers.Projection.addTransform("EPSG:4326", "GEOGRAPH", transformLonLatToGeo);
		var geoproj = new OpenLayers.Projection("GEOGRAPH");

/**
 * Subclass OpenLayers.Layer.XYZ for layers with a restristricted range of zoom levels.
 */
OpenLayers.Layer.Geograph = OpenLayers.Class(OpenLayers.Layer.XYZ, {
    /**
     * Constructor: OpenLayers.Layer.Geograph
     *
     * Parameters:
     * ri - {Integer}
     * url - {String}
     * errortileurl - {String}
     * options - {Object} Hashtable of extra options to tag onto the layer
     */
    initialize: function(ri, url, errortileurl, options) {
        this.minZoomLevel = 0;
        this.maxZoomLevel = kmpertile.length - 1;
        //this.numZoomLevels = null; //FIXME?
        this.errorTile = errortileurl;
        this.ri = ri;
        this.xmin = xmin[ri];
        this.xmax = xmax[ri];
        this.ymin = ymin[ri];
        this.ymax = ymax[ri];
        this.lonmin = lonmin[ri];
        this.lonmax = lonmax[ri];
        this.latmin = latmin[ri];
        this.latmax = latmax[ri];
        url = url || this.url;
        var name = areanames[ri];
        var newArguments = [name, url, options];
        OpenLayers.Layer.XYZ.prototype.initialize.apply(this, newArguments);
    },
    /**
     * APIMethod: clone
     * Create a clone of this layer
     *
     * Parameters:
     * obj - {Object} Is this ever used?
     * 
     * Returns:
     * {<OpenLayers.Layer.Geograph>} An exact clone of this OpenLayers.Layer.Geograph
     */
    clone: function (obj) {
        
        if (obj == null) {
            obj = new OpenLayers.Layer.Geograph(this.ri,
                                            this.url,
                                            this.errorTile,
                                            this.getOptions());
        }

        //get all additions from superclasses
        obj = OpenLayers.Layer.XYZ.prototype.clone.apply(this, [obj]);

        return obj;
    },    
    /**
     * Method: getURL
     *
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     *
     * Returns:
     * {String} A string with the layer's url and parameters and also the
     *          passed-in bounds and appropriate tile size specified as
     *          parameters
     */
    getURL: function (bounds) {
        var xyz = this.getXYZ(bounds);
        if (xyz.z < this.minZoomLevel || xyz.z > this.maxZoomLevel) {
            return this.errorTile;
        }
        var ext = kmpertile[xyz.z];
        if (xyz.x > this.xmax || xyz.y > this.ymax || (xyz.x+ext) < this.xmin || (xyz.y+ext) < this.ymin)
            return this.errorTile;
        var url = this.url;
        if (OpenLayers.Util.isArray(url)) {
            var s = '' + xyz.x + xyz.y + xyz.z;
            url = this.selectUrl(s, url);
        }
        
        if ('userParam' in this && this.userParam != null) {
            xyz.u = this.userParam;
        }
        xyz.i = this.ri;
        return OpenLayers.String.format(url, xyz);
    },
    
    /**
     * Method: getXYZ
     * Calculates x, y and z for the given bounds.
     *
     * Parameters:
     * bounds - {<OpenLayers.Bounds>}
     *
     * Returns:
     * {Object} - an object with x, y and z properties.
     */
    getXYZ: function(bounds) {
        var z = this.serverResolutions != null ?
            OpenLayers.Util.indexOf(this.serverResolutions, res) :
            this.map.getZoom() + this.zoomOffset;
        //var res = this.map.getResolution();
        //var ext = res * ts / 1000.0;
        var ext = kmpertile[z];
        var x = Math.round(bounds.left/1000.0 / ext) * ext;   /* Math.round(bounds.left/1000.0)*/
        var y = Math.round(bounds.bottom/1000.0 / ext) * ext; /* Math.round(bounds.bottom/1000.0)*/

        /*var limit = Math.pow(2, z);
        if (this.wrapDateLine)
        {
           x = ((x % limit) + limit) % limit;
        }*/

        return {'x': x, 'y': y, 'z': z};
    },
    CLASS_NAME: "OpenLayers.Layer.Geograph"
});
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
				dragmarkers.removeFeatures([currentelement]);
			        currentelement.destroy();
				currentelement = null;
				document.theForm.grid_reference.value = '';
				map.events.triggerEvent("dragend");
			}
		}

		function loadmapO() {
			//OpenLayers.Lang.setCode("de"); /* TODO Needs OpenLayers/Lang/de.js built into OpenLayers.js */

			OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
				defaultHandlerOptions: {
					'single': true,
					'double': false,
					'pixelTolerance': 0,
					'stopSingle': false,
					'stopDouble': false
				},

				initialize: function(options) {
					this.handlerOptions = OpenLayers.Util.extend(
						{}, this.defaultHandlerOptions
					);
					OpenLayers.Control.prototype.initialize.apply(
						this, arguments
					); 
					this.handler = new OpenLayers.Handler.Click(
						this, {
							'click': this.trigger
						}, this.handlerOptions
					);
				},

				trigger: function(e) {
				}
			});

			var layerswitcher = new OpenLayers.Control.LayerSwitcher({'ascending':true});

			var resolutions = [];
			for (var z in kmpertile) {
				resolutions[z] = kmpertile[z] * 1000.0 / ts;
			}
			var totxmin = null;
			var totxmax = null;
			var totymin = null;
			var totymax = null;
			for (var i in ris) {
				var curri = ris[i];
				if (totxmin == null || xmin[curri] < totxmin)
					totxmin = xmin[curri];
				if (totymin == null || ymin[curri] < totymin)
					totymin = ymin[curri];
				if (totxmax == null || xmax[curri] > totxmax)
					totxmax = xmax[curri];
				if (totymax == null || ymax[curri] > totymax)
					totymax = ymax[curri];
			}
			// FIXME ensure totxmin, totymin = n*tilesize

			map = new OpenLayers.Map({
				div: "map",
				projection: geoproj,
				displayProjection: epsg4326,
				units: "m",
				maxExtent: new OpenLayers.Bounds(totxmin*1000, totymin*1000, totxmax*1000, totymax*1000),
				resolutions : resolutions,
				numZoomLevels : resolutions.length,
				tileSize : new OpenLayers.Size(ts, ts),
				//user: user,
				controls : [
					new OpenLayers.Control.Navigation(),
					new OpenLayers.Control.PanZoomBar(),
					layerswitcher,
					new OpenLayers.Control.Attribution(),
				]
			});

			var curmap;
			var geomaps = [];
			for (var i in ris) {
				var curri = ris[i];
				var tilelayer = new OpenLayers.Layer.Geograph(
					curri,
					"/tile.php?x=${x}&y=${y}&z=${z}&i=${i}",
					OpenLayers.Util.Geograph.MISSING_TILE_URL,
					{
						attribution: '&copy; <a href="/">Geograph</a> und <a href="http://www.openstreetmap.org/">OSM</a>-User (<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC</a>)',
						//userParam : user,
					}
				);
				geomaps[i] = tilelayer;
				if (curri == ridefault)
					curmap = geomaps[i];
			}

			initMarkersLayer();

			/*map.setUser = function(u) {
				if (u < -1 || u == map.user)
					return;
				map.user = u;
				geo.userParam = u;
				geosq.userParam = u;
				if (map.baseLayer == geo) {
					geo.redraw();
					map.events.triggerEvent("changelayer", { layer: geo, property: "user" });
				} else if (geosq.getVisibility()) {
					geosq.redraw();
					map.events.triggerEvent("changelayer", { layer: geosq, property: "user" });
				}
			}
			map.trySetUserId = function(s) {
				u = parseInt(s, 10);
				if (u > 0) {
					map.setUser(u);
					return true;
				}
				return false;
			}*/
			/*function updateMapLink() {
				var ll = map.center.clone().transform(map.getProjectionObject(), epsg4326);
				var mt = map.baseLayer;
				var type = mt.gurlid;
				for (var key in ovltypes) {
					ot = ovltypes[key];
					var isvisible;
					if (mt != geo || ot == hills)
						isvisible = ot.getVisibility();
					else
						isvisible = ot.savedVisibility;
					if (isvisible)
						type += key;
				}
				var url = '?z=' + map.zoom
					+ '&t=' + type
					+ '&ll=' + ll.lat + ',' + ll.lon;
				if (map.user != 0) {
					url += '&u=' + map.user;
				}
				if (geosq.opacity != 0.5) {
					url += '&o=' + geosq.opacity;
				}
				if (hills.opacity != 1) {
					url += '&or=' + hills.opacity;
				}
				if (currentelement) {
					ll = new OpenLayers.LonLat(currentelement.geometry.x, currentelement.geometry.y);
					ll.transform(map.getProjectionObject(), epsg4326);
					url += '&mll=' + ll.lat + ',' + ll.lon;
				}
				var curlink = document.getElementById("maplink");
				curlink.setAttribute("href", url);
			}*/


			map.addLayers(geomaps);
			map.addLayers([dragmarkers]);

			/*var overview =  new OpenLayers.Control.OverviewMap({
				//maximized: true
			});
			map.addControl(overview);*/

			function moveMarker(e) {
				var coords = map.getLonLatFromViewPortPx(e.xy);
				if (currentelement) {
					currentelement.move(coords);
				} else {
					coords.transform(map.getProjectionObject(), epsg4326);
					currentelement = createMarker(coords, 0);
				}
				markerDrag(currentelement, null);
			}

			var dragFeature = new OpenLayers.Control.DragFeature(dragmarkers, {'onDrag': markerDrag, 'onComplete': markerCompleteDrag});
			map.addControl(dragFeature);
			dragFeature.activate();
			var click = new OpenLayers.Control.Click({'trigger': moveMarker});
			map.addControl(click);
			click.activate();

			var point = new OpenLayers.LonLat((curmap.lonmin+curmap.lonmax)*.5, (curmap.latmin+curmap.latmax)*.5);
			var zoom = 1;
			map.setBaseLayer(curmap);
			map.setCenter(point.transform(epsg4326, map.getProjectionObject()), zoom);
			/*if (inimlat < 90) {
				var mpoint = new OpenLayers.LonLat(inimlon, inimlat);
				currentelement = createMarker(mpoint, 0);
				markerDrag(currentelement, null);
			}*/

			function checkBounds() {
				var p = map.getCenter();
				var mt = map.baseLayer;
				var x = p.lon;
				var y = p.lat;

				//alert ( " "+x+ " "+y+ " "+mt.xmin+ " "+mt.ymin+ " "+mt.xmax+ " "+mt.ymax);
				if (x >= mt.xmin*1000 && x <= mt.xmax*1000 && y >= mt.ymin*1000 && y <= mt.ymax*1000) {
					return;
				}

				if (x < mt.xmin*1000)
					x = mt.xmin*1000;
				if (y < mt.ymin*1000)
					y = mt.ymin*1000;
				if (x > mt.xmax*1000)
					x = mt.xmax*1000;
				if (y > mt.ymax*1000)
					y = mt.ymax*1000;

				map.setCenter(new OpenLayers.LonLat(x, y));
			}

			map.events.on({'move' : checkBounds});
			map.events.on({'changebaselayer' : checkBounds});

			/*map.events.on({'zoomend': updateMapLink}); // == map.events.register("zoomend", map, updateMapLink);
			map.events.on({'moveend': updateMapLink});
			map.events.on({'dragend': updateMapLink});
			map.events.on({'changelayer': updateMapLink});
			updateMapLink();*/
		}


		AttachEvent(window,'load',loadmapO,false);

	// ]]>
	</script>
{/literal}

{if $ext}
<div class="interestBox" style="background-color:pink; color:black; border:2px solid red; padding:10px;">
<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px\"/>
<p>
This feature is still in development. Please use with care and try to avoid high server load.
</p>
<p>
Diese Kartenansicht ist noch in einem frühen Entwicklungsstadium! Bitte nicht übermäßig nutzen um zu hohe Serverlast zu vermeiden.
</p>
</div>
{/if}

<p>Bitte Karte anklicken um einen verschiebbaren Marker zu erzeugen...</p>

<form {if $submit2}action="/submit2.php?inner"{elseif $picasa}action="/puploader.php?inner"{elseif $ext}action="javascript:void()"{else}action="/submit.php" {if $inner} target="_top"{/if}{/if}name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">

{if !$ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Aktuelle Koordinate</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>

<input type="submit" value="Nächster Schritt &gt; &gt;"/> <span id="dist_message"></span></div>
<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>
{/if}

<div class="smallmap" id="map" style="width:600px; height:500px;border:1px solid blue"></div><!-- FIXME Karte wird geladen... (JavaScript nötig) -->

{if $ext}
<div style="width:600px; text-align:center;"><label for="grid_reference"><b style="color:#0018F8">Aktuelle Koordinate</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="updateMapMarker(this,false)" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/><br />

<input type="button" value="Planquadrat zeigen" onclick="openGeoWindow(5, '/gridref/');" />
<input type="button" value="Bild einreichen"    onclick="openGeoWindow(5, '/submit.php?gridreference=');" />
<input type="button" value="Bilder suchen"      onclick="openGeoWindow(5, '/search.php?q=');" />
<input type="button" value="Marker löschen"     onclick="clearMarker();" />
{*<a id="maplink" href="#">Link zur Karte</a>*}
<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>
{*<br />
{dynamic}
<input type="radio" name="mtradio" value="coverage" onclick="map.setUser(0);" {if $iniuser == 0}checked{/if} />Abdeckung |
<input type="radio" name="mtradio" value="depth" onclick="map.setUser(-1);" {if $iniuser == -1}checked{/if} />Dichte |
{if $userid}<input type="radio" name="mtradio" value="personal" onclick="map.setUser({$userid});" {if $iniuser == $userid}checked{/if} />Persönlich |{/if}
<input type="radio" name="mtradio" value="user" onclick="if(!map.trySetUserId(document.theForm.mtuser.value)){ldelim}document.theForm.mtradio[{if $userid}3{else}2{/if}].checked=false;document.theForm.mtradio[0].checked=true;map.setUser(0);{rdelim};"
{if $iniuser > 0 and $iniuser != $userid}checked{/if} />Nutzer:
<input type="text" size="5" name="mtuser"  value="{if $iniuser > 0}{$iniuser}{elseif $userid}{$userid}{/if}" />
{/dynamic}*}
</div>
{/if}


</form>
{*<form action="javascript:void()" onsubmit="return showAddress(this.address.value);" style="padding-top:5px">
<div style="width:600px; text-align:center;"><label for="addressInput">Enter Address:</label>
	<input type="text" size="50" id="addressInput" name="address" value="" />
	<input type="submit" value="Suchen"/><small><small><br/>
	(über Google Maps API Geocoder)</small></small>
</div>
</form>*}

{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
