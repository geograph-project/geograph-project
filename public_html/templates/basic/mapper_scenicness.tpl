{assign var="page_title" value="Predicted Scenicness of London"}
{include file="_std_begin.tpl"}

<h2>Beauty of Outdoor Places of London</h2>

<p>A sample of ~240,000 Geograph photos of outdoor places, have been run though a 
{external href="http://www.bbc.co.uk/news/av/technology-40805739/algorithm-learns-to-understand-natural-beauty" text="Computer Algorithm"} to predict their beauty, on a scale of 1-10. 
The Algorithm is trained on data from <a href="http://scenicornot.datasciencelab.co.uk/">ScenicOrNot</a></i>, where visitors rated a sample of some 200,000 all over Great Britain.</p>

<p>This map plots the aggregate prediction, rather than that of the individiual photos, to show the general area regardless of photo density. </p>

<p><i>Tip: Single left click on the map, to view geograph images of that area.</i>  (or <a href="?column={$column|escape:'url'}&amp;photos=1">turn on display photos below</a>)</p>

<p>Choose variation: &middot;
{foreach from=$columns key=key item=value}
	{if $column == $value}
	         <b>{$value}</b> &middot;
	{else}
		<a href="?column={$value}">{$value}</a> &middot;
	{/if}
{/foreach} We also have a <a href="/search.php?i=75622014&amp;displayclass=map">map of just the most scenic photos</a></p>

<div id="map" style="width:800px;height:600px;"></div>
{literal}<script>

var longpress = false;
var update_timeout = null;
var map;

      function initMap() {

var styles = [
  {
    "featureType": "poi",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "poi.park",
    "stylers": [
      {
        "visibility": "simplified"
      }
    ]
  }
];

        map = new google.maps.Map(document.getElementById('map'), {
          zoom: 10,
          center: {lat: 51.5, lng: -0.1},
	  styles: styles,
          gestureHandling: "greedy"
        });

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
					mapCenter = new google.maps.LatLng(parseFloat(bits[0]),parseFloat(bits[1]));
					map.setCenter(mapCenter);
				}
				
				if (argname == "t") {
					if (value == "h") {mapType = google.maps.MapTypeId.HYBRID;}
					if (value == "r") {mapType = google.maps.MapTypeId.ROADMAP;}
					if (value == "t") {mapType = google.maps.MapTypeId.TERRAIN;}
					if (value == "s") {mapType = google.maps.MapTypeId.SATELLITE;}

					if (mapType)
						map.setMapTypeId(mapType);
				}

				if (argname == "z") {
					map.setZoom(parseInt(value));
				}
			}
		}

                        var mapTypeOptions = {
                                getTileUrl: function(coord, zoom) {
                                        if (zoom < 10 || zoom > 20)
                                                return 'https://s1.geograph.org.uk/img/blank.gif';

                                        var group = 100000;
                                        switch(zoom) {
                                                case 10: group = 3000; break;
                                                case 11: group = 5000; break;
                                                case 12: group = 10000; break;
                                                case 13: group = 20000; break;
                                                case 14: group = 40000; break;
                                                case 15: group = 80000; break;
                                                case 16: group = 100000; break;
                                                case 17: group = 200000; break;
                                                case 18: group = 300000; break;
                                                case 19: group = 400000; break;
                                                case 20: group = 500000; break;
                                        }

                                        return 'https://t0.geograph.org.uk/tile/tilescenic.php?z='+zoom+'&x='+coord.x+'&y='+coord.y+'&l=1&group='+group+'&column={/literal}{$column}{literal}&text=2';
                                },
                                tileSize: new google.maps.Size(256, 256),
                                maxZoom: 20,
                                minZoom: 16,
                                opacity: 0.7,
                                name: "All Dots"
                        };

                        var imageMapType = new google.maps.ImageMapType(mapTypeOptions);


                        map.overlayMapTypes.push(imageMapType);

		google.maps.event.addListener(map, 'click', function(event){
		    update_timeout = setTimeout(function(){
		        window.open("/near/"+event.latLng.toUrlValue(6));
		        update_timeout = null;
		    }, 200);
		});

		google.maps.event.addListener(map, 'dblclick', function(event) {
		    if (update_timeout)
		            clearTimeout(update_timeout);
		});

		google.maps.event.addListener(map, 'idle', makeHash);
		google.maps.event.addListener(map, 'maptypeid_changed', makeHash);
      }

	function makeHash() {
		var ll = map.getCenter().toUrlValue(6);
		var z = map.getZoom();
		var t = map.getMapTypeId().substr(0,1);
		window.location.hash = '#ll='+ll+'&z='+z+'&t='+t;
	}

    </script>{/literal}
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={$google_maps_api3_key}&amp;callback=initMap"></script>

<p>Paper:</p>
<ul>
	<li>Seresinhe CI, Preis T, Moat HS (2017) Using deep learning to quantify the beauty of outdoor places. Royal Society Open Science 4(7): 170170.
	{external href="http://dx.doi.org/10.1098/rsos.170170" text="dx.doi.org/10.1098/rsos.170170"}</li>
</ul>

<p>Download Data:</p>
<ul>
	<li>Seresinhe CI, Preis T, Moat HS (2017) Data from: Using deep learning to quantify the beauty of outdoor places. Dryad Digital Repository.
	{external href="http://dx.doi.org/10.5061/dryad.rq4s3" text="http://dx.doi.org/10.5061/dryad.rq4s3"}</li>
</ul>
	

{include file="_std_end.tpl"}

