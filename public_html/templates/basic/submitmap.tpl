{assign var="page_title" value="Grid Ref Finder"}
{if $inner}
	{include file="_basic_begin.tpl"}
{else}
	{include file="_std_begin.tpl"}
{/if}

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="{"/js/mappingLeaflet.css"|revision}" />

        <link rel="stylesheet" href="https://www.geograph.org/leaflet/leaflet-search-master/src/leaflet-search.css" />

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.67.0/dist/L.Control.Locate.min.css" />

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.css">

        <script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
        <script src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>

        <!--script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.63.0/dist/L.Control.Locate.min.js" charset="utf-8"></script-->
        <script src="https://www.geograph.org/leaflet/L.Control.Locate.js"></script> <!-- fork at https://github.com/barryhunter/leaflet-locatecontrol/blob/gh-pages/ -->


        <script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.js"></script>

        <script src="{"/mapper/geotools2.js"|revision}"></script>

        <script src="{"/js/Leaflet.base-layers.js"|revision}"></script>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script src="{"/js/jquery.storage.js"|revision}"></script>

        <script src="https://www.geograph.org/leaflet/leaflet-search-master/src/leaflet-search.js"></script>
        <script src="https://www.geograph.org/leaflet/Leaflet.GeographGeocoder.js"></script>



{literal}
	<script type="text/javascript">

		var map;
		var marker;

////////////////////////////////////////////

		function loadmap() {
		        var mapOptions =  {
		                center: [54.55,-3.88], zoom: 5,
		                minZoom: 5, maxZoom: 18
		        };
		        var map = L.map('map', mapOptions);

////////////////////////////////////////////

	if ($.localStorage && $.localStorage('SubmitMap')) {
		var view = $.localStorage('SubmitMap');
		map.setView(view, view.zoom, {animate:false});
		var grid=gmap2grid(view);
                var gridref = grid.getGridRef(2);
		document.theForm.grid_reference.value = gridref;
	}

        if ($.localStorage && $.localStorage('LeafletBaseMap')) {
                basemap = $.localStorage('LeafletBaseMap');
                if (baseMaps[basemap] && basemap != "Ordnance Survey GB" && (
                                //we can also check, if the baselayer covers the location (not ideal, as it just using bounds, eg much of Ireland are on overlaps bounds of GB.
                                !(baseMaps[basemap].options)
                                 || typeof baseMaps[basemap].bounds == 'undefined'
                                 || L.latLngBounds(baseMaps[basemap].bounds).contains(mapOptions.center)     //(need to construct, as MIGHT be object liternal!
                        ))
                        map.addLayer(baseMaps[basemap]);
                else
                        map.addLayer(baseMaps["OpenStreetMap"]);
        } else {
                map.addLayer(baseMaps["OpenStreetMap"]);
        }
        if ($.localStorage) {
                map.on('baselayerchange', function(e) {
                        $.localStorage('LeafletBaseMap', e.name);
                        reinstateOS = false;
                });
        }


        map.addLayer(overlayMaps["OS National Grid"]);

        addOurControls(map);

	map.on('moveend', function (e) {
		if (!map._loaded || !$.localStorage) return;
		
		var view = map.getCenter();
		view.zoom = map.getZoom();
		$.localStorage('SubmitMap', view); //auto json encodes
        });

////////////////////////////////////////////

			map.on('click',function(event) {
				if (marker) {
					marker.setLatLng(event.latlng);
					return
				}
				marker = L.marker(event.latlng, {draggable:true}).addTo(map);

				marker.on('move',function() {
					var grid=gmap2grid(marker.getLatLng());

					 //get a grid reference with 4 digits of precision
		                        var gridref = grid.getGridRef(4);

					 document.theForm.grid_reference.value = gridref;
				}).fire('move');
			});

			geocoder.on('search:locationfound',function(event) {
				var grid=gmap2grid(event.latlng)
	                        var gridref = grid.getGridRef(4);
                                document.theForm.grid_reference.value = gridref;
			});

////////////////////////////////////////////
			
		}


function gmap2grid(point) {
        //create a wgs84 coordinate
        wgs84=new GT_WGS84();
        wgs84.setDegrees(point.lat, point.lng);

        if (wgs84.isIreland2()) {
                //convert to Irish
                var grid=wgs84.getIrish(true);

        } else if (wgs84.isGreatBritain()) {
                //convert to OSGB
                var grid=wgs84.getOSGB();
        }
        return grid;
}


{/literal}

		AttachEvent(window,'load',loadmap,false);


		{dynamic}
		{if $container}
			{literal}

			function resizeContainer() {
				if (!window.parent || !!window.parent.document)
					return;
				var FramePageHeight =  document.body.offsetHeight + 10;
				window.parent.document.getElementById('{/literal}{$container|escape:'javascript'}{literal}').style.height=FramePageHeight+'px';
			}

			AttachEvent(window,'load',resizeContainer,false);
			{/literal}
		{/if}
		{/dynamic}

	</script>


<p>Click on the map to create a point, pick it up and drag to move to better location...</p>

<form {if $submit2}action="/submit2.php?inner"{elseif $picasa}action="/puploader.php?inner"{else}action="/submit.php" {if $inner} target="_top"{/if}{/if}name="theForm" method="post" style="background-color:#f0f0f0;padding:5px;margin-top:0px; border:1px solid #d0d0d0;">


<div style="width:600px; text-align:center;">
<label for="grid_reference"><b style="color:#0018F8">Selected Grid Reference</b></label> <input id="grid_reference" type="text" name="grid_reference" value="{dynamic}{if $grid_reference}{$grid_reference|escape:'html'}{/if}{/dynamic}" size="14" onkeyup="updateMapMarker(this,false)" onpaste="{literal}that=this;setTimeout(function(){updateMapMarker(that,false);},50){/literal}" onmouseup="updateMapMarker(this,false)" oninput="updateMapMarker(this,false)"/>
<input type="submit" value="Next Step &gt; &gt;"/> <span id="dist_message"></span></div>

<div id="map" style="width:600px; height:500px;border:1px solid blue">Loading map...</div>

<input type="hidden" name="gridsquare" value=""/>
<input type="hidden" name="setpos" value=""/>

</form>


{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
