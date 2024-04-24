{include file="_std_begin.tpl"}

{literal}
<style>
#mapcontainer {
	position:relative; float:left; width:800px; height:500px; max-width:49%;
}
#firstcontainer {
	position:relative; float:left; width:800px; height:500px; max-width:49%;
	padding:2px;
}
#firstcontainer img {
	max-width:95%;
}

h2 span {
	color:gray;
}

div.snippet640 {
	max-width:1004px;
	width:inherit;
	margin:0;
	font-size:1em;
}

.tagbar {
	border:1px dashed silver;
	border-radius:20px;
	max-width:1004px;
	padding:6px;
}

.gridded.med {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-gap: 18px;
    grid-row-gap: 20px;
}
.gridded > div {
	text-align:center;
	float:left; /* ignored in grid, but to support older browsers! */
}
.gridded img {
	width:100%;
	min-width:80px;
	min-height:80px;
}

.gridded .shadow {
	position:relative;
}
.gridded .floater {
	position:absolute;
	top:0;
	left:0;
	background-color:white;
	padding:10px;
	z-index:1000;
	display:none;
}

.gridded .shadow:hover .floater {
	display:block;
}

@media only screen and (min-width: 1250px) {
	.gridded.cols1 .part1 {
		clear:both;
		float:left;
		position:relative;
		width:650px;
		text-align:right;
	}
	.gridded.cols1 .part1 .floater {
		right:0;
		left:inherit;
	}

	.gridded.cols1 .part2 {
		float:left;
		text-align:left;
		margin-left:10px;
		line-height:1.4em;
		min-width:400px;
		max-width:calc( 100vw - 850px );
	}
	.gridded.cols1 .part2 span.title {
		display:block;
		margin-left:-18px;
		font-size:1.4em;
		background-color:silver;
		padding:10px;
		padding-left:18px;
		margin-bottom:20px;
		box-shadow: 3px 3px 8px #999;
	}
	.gridded.cols1 .part2 b {
		font-size:1.1em;
	}
}

.gridded.cols6 {
	font-size:0.9em;
}
.gridded.cols8 {
	font-size:0.7em;
}

.progress-bar {
        height:10px;
        background-color:lightgreen;
}
.hide {
	display:none;
}
p.alert-success {
	color:green;
}
p.alert-danger {
	color:red;
}

</style>
{/literal}

{if $year}
	<div style="float:right;color:gray;font-size:2.2em;font-family:verdana">
		{$year}
	</div>
{/if}

<h2>{$page_title|escape:'html'|replace:'(set of':'<span>(set of'}</span></h2>

{if $description}
	<p>{$description|escape:'html'}</p>
{/if}

	{if $first->snippet_count}
		{if !$first->comment && $first->snippet_count == 1}
			{assign var="item" value=$first->snippets[0]}
			<div class="caption">
			{$item.comment|escape:'html'|nl2br|geographlinks}{if $item.title}<br/><br/>
			<small>See other images of <a href="/snippet/{$item.snippet_id}" title="See other images in {$item.title|escape:'html'|default:'shared description'}{if $item.realname && $item.realname ne $first->realname}, by {$item.realname}{/if}">{$item.title|escape:'html'}</a></small>{/if}
			</div>
		{else}
			{foreach from=$first->snippets item=item name=used}
				{if !$first->snippets_as_ref && !$item.comment}
					<div class="caption640 searchresults"><br/>
					<small>See other images of <a href="/snippet/{$item.snippet_id}" title="See other images in {$item.title|escape:'html'|default:'shared description'}{if $item.realname && $item.realname ne $first->realname}, by {$item.realname}{/if}">{$item.title|escape:'html'}</a></small>
					</div>
				{else}
					<div class="snippet640 searchresults" id="snippet{$smarty.foreach.used.iteration}">
					{if $first->snippets_as_ref}{$smarty.foreach.used.iteration}. {/if}<b><a href="/snippet/{$item.snippet_id}" title="See other images in {$item.title|escape:'html'|default:'shared description'}{if $item.realname && $item.realname ne $first->realname}, by {$item.realname}{/if}">{$item.title|escape:'html'|default:'untitled'}</a></b> {if $item.grid_reference && $item.grid_reference != $first->grid_reference}<small> :: <a href="/gridref/{$item.grid_reference}">{$item.grid_reference}</a></small>{/if}
					<blockquote><p>{$item.comment|escape:'html'|nl2br|geographlinks}</p></blockquote>
					</div>
				{/if}
			{/foreach}
		{/if}
	{/if}


	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

{if $label}
	<p><i>We have curated some Geography related images here. Free for any use, even commerical, in return for attribution. Creative Commons BY-SA.

	This is only a small selection of our entire collection. To explore more images on Geograph, <a href="/curated/sample.php">View Curated Images</a> or enter keywords in the search box above.</i>

	{if $loc || $region || $totalimagecount>50}
		<hr>
		{include file="_location-selector.tpl"}
		<hr>
	{/if}

{/if}

<p style=font-size:1.1em>
{if $headlinks_html}All images {$headlinks_html}{if $place}, {/if}{/if}
{if $place}
	<small>{place place=$place}</small>
{/if}</p>

{if $first->tags || $first->imageclass}
	<div class=tagbar>
		{if $first->tag_prefix_stat.top}
			<span class="nowrap">Geographical Context:
				{foreach from=$first->tags item=item name=used}{if $item.prefix eq 'top'}
				<span class="tag">
				<a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$first->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|escape:'html'}</a></span></span> <span class="nowrap">
			{/if}{/foreach}
			</span>
		{/if}

		{foreach from=$first->tag_prefix_stat key=prefix item=count}
			{if $prefix ne 'top' && $prefix ne '' && $prefix ne 'term' && $prefix ne 'cluster' && $prefix ne 'wiki' && $prefix ne 'type'}
				<span class="nowrap">
				{if $prefix == 'bucket'}
					Image Buckets <sup><a href="/article/Image-Buckets" class="about" style="font-size:0.7em">?</a></sup>:
				{elseif $prefix == 'subject'}
					Primary Subject:
				{else}
					{$prefix|capitalize|escape:'html'}:
				{/if}
				{foreach from=$first->tags item=item name=used}{if $item.prefix == $prefix}
					<span class="tag">
					<a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$first->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|capitalizetag|escape:'html'}</a></span></span> <span class="nowrap">
				{/if}{/foreach}
				</span>
			{/if}
		{/foreach}

		{if $first->imageclass}
			<span class="nowrap">Category:

			{if $first->canonical}
				<a href="/search.php?gridref={$first->grid_reference}&amp;canonical={$first->canonical|escape:'url'}&amp;do=1">{$first->canonical|escape:'html'}</a> &gt;
			{/if}
			<a title="pictures near {$first->grid_reference} of {$first->imageclass|escape:'html'}" href="/search.php?gridref={$first->subject_gridref|escape:'url'}&amp;imageclass={$first->imageclass|escape:'url'}" rel="nofollow">{$first->imageclass|escape:'html'}</a>
			</span>
		{/if}

		{if $first->tags && ($first->tag_prefix_stat.$blank || $first->tag_prefix_stat.term || $first->tag_prefix_stat.cluster || $first->tag_prefix_stat.wiki)}
			<span class="nowrap">{if count($first->tag_prefix_stat) > 1}
				other tags:
			{/if}
			{foreach from=$first->tags item=item name=used}{if $item.prefix eq '' || $item.prefix eq 'term' || $item.prefix eq 'cluster' || $item.prefix eq 'wiki'}
				<span class="tag"><a href="/tagged/{if $item.prefix}{$item.prefix|escape:'urlplus'}:{/if}{$item.tag|escape:'urlplus'}#photo={$first->gridimage_id}" class="taglink" title="{$item.description|escape:'html'}">{$item.tag|capitalizetag|escape:'html'}</a></span></span> <span class="nowrap">
			{/if}{/foreach}</span>

			<small>Click a tag, to view other nearby images.</small>
		{/if}
	</div>
{/if}


	{if $map}
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" rel="stylesheet" />

	<link rel="stylesheet" href="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.css" />

	<div id="mapbar" style="display:none" data-nosnippet>
		<div id="mapcontainer">
			<div id="map" style="width:100%; height:500px"></div>
			<div id="message" style="z-index:10000;position:absolute;top:0;left:50px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8"></div>
			<div id="gridref" style="z-index:10000;position:absolute;top:0;right:180px;background-color:white;font-size:1em;font-family:sans-serif;opacity:0.8;padding:1px;"></div>
		</div>

		<div id="firstcontainer">
			 <a title="{$first->grid_reference} : {$first->title|escape:'html'} by {$first->realname|escape:'html'} {$first->dist_string} - click to view full size image" href="/photo/{$first->gridimage_id}">{$first->getResponsiveImgTag(120,640)}</a>	
		</div>
		<br style=clear:both>
	</div>
	{/if}
	<div id="buttonbar" data-nosnippet>
		{if $map}
			<input type=button value="Open Map Viewer for these images" onclick="showMap()">
		{/if}
		{if $label && $totalimagecount > 50}
			<input type=button value="View all {$totalimagecount} Curated Images" onclick="location.href='/curated/sample.php?label={$label|escape:'urlplus'}'">
			{if !$place}
				<input type=button value="View images by Region" onclick="location.href='/curated/sample.php?label={$label|escape:'urlplus'}&amp;region=Group+By'">
			{/if}
		{/if}
		{if $label}
			<input type=button value="View {$keywordcount} Keyword Matches" onclick="location.href='/browser/#!/q={$label|escape:'url'}'">
			<a href="/photoset/labels.php">Other Topics...</a>
		{/if}
	</div>

	<br><br>

	<div style="text-align:right;margin-top:-2em;padding-bottom:8px" data-nosnippet>
		Columns: 
		<a href="#1" onclick="return setColumns(this.text)">1</a>
		<a href="#2" onclick="return setColumns(this.text)">2</a>
		{if $imagecount >= 3}<a href="#3" onclick="return setColumns(this.text)">3</a>{/if}
		{if $imagecount >= 4}<a href="#4" onclick="return setColumns(this.text)">4</a>{/if}
		{if $imagecount >= 6}<a href="#6" onclick="return setColumns(this.text)">6</a>{/if}
		{if $imagecount >= 8}<a href="#8" onclick="return setColumns(this.text)">8</a>{/if}
	</div>

	<div class="gridded med cols4" id="gridcontainer">
	{foreach from=$images item=image}
		<div class="shadow">
			<div class="part1">
				<div class="floater">
					<a href="#" onclick="markImage({$image->gridimage_id});remarkImage({$image->gridimage_id}); return false" id="mark{$image->gridimage_id}">Mark this Image</a><br>
					<a href="/reuse.php?id={$image->gridimage_id}">Reuse Options</a><br>
					{if $image->links}
						{$image->links}
					{/if}
				</div>
				<a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getResponsiveImgTag(120,$maxsize,true)}</a>
			</div>
			{if $image->htmltext}
				<div class="part2">
					{$image->htmltext}
				</div>
			{/if}
		</div>
	{/foreach}
	<br style="clear:both"/>
	</div>

	{if $json}
	<script type="application/ld+json">{$json}</script>
	{/if}

	{if $gridref}
        <script type="application/ld+json">
	{literal}
        {
              "@context": "https://schema.org",
              "@type": "BreadcrumbList",
              "itemListElement": [{
                "@type": "ListItem",
                "position": 1,
                "name": "Photos",{/literal}
                "item": "{$self_host}/" {literal}
              },{
                "@type": "ListItem",
                "position": 2,{/literal}
                "name": {"in `$gridref`"|json_encode},
                "item": "{$self_host}/gridref/{$gridref}" {literal}
              },{
                "@type": "ListItem",
                "position": 3,{/literal}
                "name": {$page_title|latin1_to_utf8|json_encode} {literal}
              }]
        }
	{/literal}
        </script>
	{/if}

	{if $same_title} 
		<p><i>These are {$imagecount} of <a href="/stuff/list.php?title={$first->title|escape:'urlplus'}&amp;gridref={$first->grid_reference}">{$same_title} images, with title {$first->title|escape:'html'}</a> in this square</i></p>
	{/if}

	<hr><p><img src="{$static_host}/img/80x15.png" alt="Attribution-ShareAlike 2.0 Generic (CC BY-SA 2.0)"> &nbsp; All images 
	{if $singlename}
		are <b>&copy; {$singlename|escape:'html'}</b> and
	{/if}
	licensed for reuse under this <a href="https://creativecommons.org/licenses/by-sa/2.0/" target="_blank">Creative Commons Licence</a>
	{if !$singlename}
		(see each image for individual credits)
	{/if}</p>

	<script>{literal}
	window.lazySizesConfig = window.lazySizesConfig || {};
	lazySizesConfig.init = false; //we going to run the init outselfs, AFTER we've set the number of columns from cookie) 
	{/literal}</script>

        <script src="{$static_host}/js/lazysizes.min.js"></script>


	<p data-nosnippet>Click an image to view more details, including the exact location on a map (may be different for each image)</p>

{if $label}

	{include file="_download-function.tpl"}

{/if}


{literal}
<script>

//todo, this needs increating to the functions in geograph.js, not seperate functions!
function remarkImage(image) {
	ele = document.getElementById('mark'+image);
	if(ele.innerText != undefined) {
		newtext = ele.innerText;
	} else {
		newtext = ele.textContent;
	}
	ele = document.getElementById('img'+image);
	if (newtext == 'marked') {
		ele.style.border = "2px solid red";
	} else {
		ele.style.border = "none";
	}
}
function remarkAllImages() {
	setTimeout(function() { //this is ugly, the original function might not of run yet, so need more delay!
	var str = 'marked';
	for(var q=0;q<document.links.length;q++) {
		if (document.links[q].text == str) {
			remarkImage(document.links[q].id.substr(4));
		}
	}
	}, 1000);
}

function setColumns(num,skip_cookie) {
	document.getElementById("gridcontainer").style.gridTemplateColumns = 'repeat('+num+', 1fr)';

	if (m = $("#gridcontainer").attr('class').match(/(cols\d+)/))
		$("#gridcontainer").removeClass(m[1]);
	$("#gridcontainer").addClass("cols"+num);

	if (skip_cookie)
		return false;

	if (lazySizes && lazySizes.autoSizer)
		lazySizes.autoSizer.checkElems();

	createCookie('GridCols',num,10);
	return false;
}

function loadColumnsFromCookie() {
	var num = readCookie('GridCols');
	//todo, check if num > $imagecount, then might as well just clamp to $imagecount
	if (num && num > 0)
		setColumns(num,true);

	if (lazySizesConfig && typeof lazySizesConfig.init !== 'undefined' && !lazySizesConfig.init)
		lazySizes.init();
}

AttachEvent(window,'load',remarkAllImages,false);

loadColumnsFromCookie(); //inline, not async. But needs be here, AFTER gridcontainer created in DOM.

/////////////////////////////////////////////////////

        $(function() {
                $(".shadow img").contextmenu(function() {
                        if (this.currentSrc && (m = this.currentSrc.match(/\/(\d{6,}_\w{8}\.jpg)/))) {
                                $(this).attr('srcset',"https://t0.geograph.org.uk/stamped/"+m[1]);
                        }
                });
        });

{/literal}
</script>

{if $map}
	<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.5.0/proj4.js"></script>
	<script src="{"/js/Leaflet.MetricGrid.js"|revision}"></script>

	<script src="https://www.geograph.org/leaflet/leaflet-hash.js"></script>

	<script src="{"/mapper/geotools2.js"|revision}"></script>

	<script src="{"/js/Leaflet.base-layers.js"|revision}"></script>

	<script src="{"/js/jquery.storage.js"|revision}"></script>

	<script src="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.min.js"></script>

{literal}
<script>

/////////////////////////////////////////////////////

function showMap() {
	$("#mapbar").show();
	setupMap();
	$("#buttonbar input:first-child").hide();
}

var map;
var geotagPhotoCamera = new Array();

function setupMap() {

	var mapOptions =  {
              //  center: [54.4266, -3.1557], zoom: 13,
                minZoom: 5, maxZoom: 21
        };
        var bounds = L.latLngBounds();

{/literal}

	{foreach from=$images item=image}
	        bounds.extend([{$image->lat1},{$image->long1}]);
		bounds.extend([{$image->lat2},{$image->long2}]);
	{/foreach}

{literal}

	map = L.map('map', mapOptions);
        var hash = new L.Hash(map);

        map.fitBounds(bounds, {padding:[30,30], maxZoom: 14});


/////////////////////////////////////////////////////


	if ($.localStorage && $.localStorage('LeafletBaseMap')) {
		basemap = $.localStorage('LeafletBaseMap');
		if (baseMaps[basemap] && (
				//we can also check, if the baselayer covers the location (not ideal, as it just using bounds, eg much of Ireland are on overlaps bounds of GB.
				!(baseMaps[basemap].options)
				 || typeof baseMaps[basemap].bounds == 'undefined'
				 || L.latLngBounds(baseMaps[basemap].bounds).contains(mapOptions.center)     //(need to construct, as MIGHT be object liternal!
			)) {

			if (basemap == "Ordnance Survey GB") {
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

	//layerswitcher.expand();

/////////////////////////////////////////////////////

var cameraOptions = {
	draggable: false, //can be enabled on lcick!
	control: false, //have many, so control wont work!	
        minAngle: 2,
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
};

      var cameraPoint = [0, 0]; //long,lat as set setting geojson
      var targetPoint = [0, 0]; //these are just placeholders!

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

{/literal}

        {foreach from=$images key=idx item=image}

		//cameraPoint
		points.geometry.geometries[0].coordinates = [{$image->long2},{$image->lat2}]; //long,lat as set setting geojson
		
		//targetPoint
		points.geometry.geometries[1].coordinates = [{$image->long1},{$image->lat1}];

		geotagPhotoCamera[{$idx}] = L.geotagPhoto.camera(points,cameraOptions).addTo(map);

		geotagPhotoCamera[{$idx}]._cameraMarker.on('click',{literal}function() { {/literal}
			$('#firstcontainer a').attr('href','/photo/{$image->gridimage_id}');
			$('#firstcontainer img').attr('srcset',null).attr('src','{$image->_getFullpath(true,true)}').css('max-height','500px');
		{literal} }); {/literal}

	{/foreach}

{literal}}{/literal}

</script>
{/if}

{include file="_std_end.tpl"}
