{include file="_std_begin.tpl"}


<h2>Relocate Image</h2>


{dynamic}
<a href="/photo/{$image->gridimage_id}">{$image->getFull()}</a>


	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" rel="stylesheet" />

	<link rel="stylesheet" href="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.css" />

<div style="font-size:1.6em">
{if $inaccurate_subject} 
	<p style=color:red>Note: The photo doesn't have <u>explicit subject location</u> set, the <b>subject dot is currently placed arbitarily</b> within the square. You should drag it to the more apprroate location!
{/if}

{if $fake_viewpoint}
	<p style=color:red>Note: The photo doesn't have <u>explicit camera location</u> set, the <b>camera icon is currently placed arbitarily</b>. You should drag it to the correct location!
{elseif $inaccurate_viewpoint}
	<p style=color:red>Note: The photo doesn't have <u>an exact camera location</u> set, the <b>camera icon is currently placed arbitarily</b> within the square. You should drag it to the correct location!
{/if}
</div>




	<div style="position:relative; width:800px; height:600px">
		<div id="map" style="width:800px; height:600px"></div>
		<div id="message" style="z-index:10000;position:absolute;top:0;left:50px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8"></div>
		<div id="gridref" style="z-index:10000;position:absolute;top:0;right:180px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8;padding:1px;"></div>
	</div>


<form method="post" name="theForm" style="background-color:silver;padding:10px;margin-top:10px;">


<p>
<label for="grid_reference"><b style="color:#0018F8">Subject Grid Reference</b> {if $moderated.grid_reference}<span class="moderatedlabel">(moderated{if $isowner} for gridsquare changes{/if})</span>{/if}</label><br/>
{if $error.grid_reference}<span class="formerror">{$error.grid_reference}</span><br/>{/if}
<input type="text" id="grid_reference" name="grid_reference" size="14" value="{$image->subject_gridref|escape:'html'}"/>


<p>
<label for="photographer_gridref"><b style="color:#002E73">Camera Grid Reference</b> - Optional {if $moderated.photographer_gridref}<span class="moderatedlabel">(moderated)</span>{/if}</label><br/>
{if $error.photographer_gridref}<span class="formerror">{$error.photographer_gridref}</span><br/>{/if}
<input type="text" id="photographer_gridref" name="photographer_gridref" size="14" value="{$image->photographer_gridref|escape:'html'}"/>
<br/>



        <br/><input type="checkbox" name="use6fig" id="use6fig" {if $image->use6fig} checked="checked"{/if} value="1"/> <label for="use6fig">Only display 6 figure grid reference ({newwin href="/help/map_precision" text="Explanation"})</label>
</p>


<p><label for="view_direction"><b>View Direction</b>  {if $moderated.view_direction}<span class="moderatedlabel">(moderated)</span>{/if}
</label> <small>(camera facing)</small><br/>
<select id="view_direction" name="view_direction" style="font-family:monospace" onchange="updateCamIcon(this);">
        {foreach from=$dirs key=key item=value}
                <option value="{$key}"{if $key%45!=0} style="color:gray"{/if}{if $key==$image->view_direction} selected="selected"{/if}>{$value}</option>
        {/foreach}
</select></p>


<p><label for="tag"><b>Field of View Tag</b><br>
<input type=text name="tag" size="10" value="">
<hr>

<input type=submit value="Save Suggestion" disabled> (submission not available yet in demo!)
</form>

{/dynamic}


	<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>

	<script src="{"/mapper/geotools2.js"|revision}"></script>

	<script src="{"/js/Leaflet.base-layers.js"|revision}"></script>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="{"/js/jquery.storage.js"|revision}"></script>

	<script src="https://unpkg.com/togeojson@0.16.0/togeojson.js"></script>
	<script src="https://unpkg.com/leaflet-filelayer@1.2.0/src/leaflet.filelayer.js"></script>

	<script src="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.min.js"></script>

<script>{literal}

/////////////////////////////////////////////////////

	var mapOptions =  {
              //  center: [54.4266, -3.1557], zoom: 13,
                minZoom: 5, maxZoom: 21
        };
        var bounds = L.latLngBounds();

{/literal}
{dynamic}

      var cameraPoint = [{$long2}, {$lat2}]; //long,lat as set setting geojson
      var targetPoint = [{$long1}, {$lat1}];

        bounds.extend([{$lat1},{$long1}]);
        bounds.extend([{$lat2},{$long2}]);

{/dynamic}
{literal}

	var map = L.map('map', mapOptions);
        var hash = new L.Hash(map);

        map.fitBounds(bounds, {padding:[30,30], maxZoom: 19});


/////////////////////////////////////////////////////

	var reinstateOS = false;
	if (baseMaps["Ordnance Survey GB"]) {
		//temporally bodge!

		map.on('zoom', function(e) {
			if (map.hasLayer(baseMaps["Ordnance Survey GB"])) {
				var zoom = map.getZoom();
				if (zoom <12 || zoom > 17) {
					map.addLayer(baseMaps["OpenStreetMap"]);
					map.removeLayer(baseMaps["Ordnance Survey GB"]);
					reinstateOS = true;
				}
			} else if (reinstateOS && map.hasLayer(baseMaps["OpenStreetMap"])) {
				var zoom = map.getZoom();
				if (zoom >= 12 && zoom <= 17) {
					map.addLayer(baseMaps["Ordnance Survey GB"]);
					map.removeLayer(baseMaps["OpenStreetMap"]);
					reinstateOS = false;
				}
			}
		});
		//need on('baselayerchange' to set  reinstateOS = false;, use the storage one, below rather than two methods!
	}

	if ($.localStorage && $.localStorage('LeafletBaseMap')) {
		basemap = $.localStorage('LeafletBaseMap');
		if (baseMaps[basemap] && (
				//we can also check, if the baselayer covers the location (not ideal, as it just using bounds, eg much of Ireland are on overlaps bounds of GB.
				!(baseMaps[basemap].options)
				 || typeof baseMaps[basemap].bounds == 'undefined'
				 || L.latLngBounds(baseMaps[basemap].bounds).contains(mapOptions.center)     //(need to construct, as MIGHT be object liternal!
			)) {

			var zoom = map.getZoom();
			if (basemap == "Ordnance Survey GB" && (zoom <12 || zoom > 17)) {
				map.addLayer(baseMaps["OpenStreetMap"]);
				reinstateOS = true;				
			} else {
				map.addLayer(baseMaps[basemap]);
			}
		} else
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

/////////////////////////////////////////////////////

	addOurControls(map);

	layerswitcher.expand();

/////////////////////////////////////////////////////



      var points = {
        type: 'Feature',
        properties: {
          angle: 20
        },
        geometry: {
          type: 'GeometryCollection',
          geometries: [
            {
              type: 'Point',
              coordinates: cameraPoint
            },
            {
              type: 'Point',
              coordinates: targetPoint
            }
          ]
        }
      }

      var prevPov = parseInt(points.properties.angle,10);

      var geotagPhotoCamera = L.geotagPhoto.camera(points, {
        minAngle: 2,
controlCameraImg: 'https://unpkg.com/leaflet-geotag-photo@0.5.1/images/camera-icon.svg',
controlCrosshairImg: 'https://unpkg.com/leaflet-geotag-photo@0.5.1/images/crosshair-icon.svg',
  cameraIcon: L.icon({
    iconUrl: 'https://data.geograph.org.uk/camera.svg',
    iconSize: [38, 38],
    iconAnchor: [19, 19]
  }),

  targetIcon: L.icon({
    iconUrl: 'https://data.geograph.org.uk/marker-subject.svg',
    iconSize: [32, 32],
    iconAnchor: [16, 16]
  }),

  angleIcon: L.icon({
    iconUrl: 'https://unpkg.com/leaflet-geotag-photo@0.5.1/images/marker.svg',
    iconSize: [32, 32],
    iconAnchor: [16, 16]
  })
      }).addTo(map)
        .on('change', function (event) {
          updatePositions()
        })
        .on('input', function (event) {
          updatePositions()
        })

function updatePositions() {
	var form = document.forms['theForm'];

	var fov = geotagPhotoCamera.getFieldOfView();

	//Subject
		setGridRef(geotagPhotoCamera.getTargetLatLng(), form.elements['grid_reference']);

	//Viewpoint
		setGridRef(geotagPhotoCamera.getCameraLatLng(), form.elements['photographer_gridref']);

	//Direction!
		var bearing = fov.properties.bearing;

                        if (bearing < 0)
                                bearing = bearing + 360.0;

                        var jump = 360.0/16.0;

                        var newangle = Math.floor(Math.round(bearing/jump)*jump);
                        if (newangle == 360)
                                newangle = 0;

                        var ele = form.view_direction;
                        for(q=0;q<ele.options.length;q++)
                                if (ele.options[q].value == newangle)
                                        ele.selectedIndex = q;

	//FOV Tag
	var angle = parseInt(fov.properties.angle,10);
	if (angle != prevPov) { //only set tag if changed! (prevents creating a tag, if user hasnt changed it!) 
		form.elements['tag'].value = "fov:"+angle;
		prevPov = angle;
	}
}

function setGridRef(ll,ele) {
               var wgs84=new GT_WGS84();
                wgs84.setDegrees(ll.lat,ll.lng);

                if (wgs84.isIreland() && wgs84.isIreland2()) //isIsland is a quick BBOX test, so do that first!
                        grid=wgs84.getIrish(true);
                else if (ll.lat > 49.8 && wgs84.isGreatBritain()) // the isGB test is not accurate enough!
                        grid=wgs84.getOSGB();
                else
                        grid = null;

                if (grid && grid.status && grid.status == 'OK') {
                        var z = map.getZoom();
                        if (z > 15) precision = 5;
                        else if (z > 12) precision = 4;
                        else if (z > 9) precision = 3;
                        else precision = 2;

                        gr = grid.getGridRef(precision).replace(/ /g,'');
			ele.value = gr;
                };
}


</script>
{/literal}


{include file="_std_end.tpl"}
