{assign var="page_title" value="Geograph Map"}

{include file="_basic_begin.tpl"}
{dynamic}
	{if $error}
		<p>ERROR: {$error}</p>
	{else}
		{if $rastermap->enabled}
			<div style="float:left; position:relative; width: 372px">
			<div class="interestBox">Grid Reference: <b>{$gridref}</b></div>

			<div class="rastermap">
				<span id="coordoutput"></span><br/><br/>
				{$rastermap->getImageTag($gridref)}{if $rastermap->getFootNote()}<br/>
				<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>{/if}

				</div>

				{$rastermap->getScriptTag()}
			</div>
		{else}
			<script type="text/javascript" src="{"/mapping.js"|revision}"></script>
		{/if}
		<br style="clear:both"/>
		{if $rastermap->enabled}
			{$rastermap->getFooterTag()}

			{if $square->reference_index == 1}
				{literal}
				<style type="text/css"> 
					#coordoutput {
						font-family:verdana, arial, sans-serif;
						font-weight:bold;
					}
					#coordoutput small {
						font-size:x-small;
						margin-left:40px;
						font-weight:normal;
					}
				</style>
				<script type="text/javascript">

				var digits = 5;
				var enabled = true;
				var marker = null;
				var pt;

				var nicon;
				function createNMarker(npoint) {

					var size = new OpenLayers.Size(29, 29);
					var offset = new OpenLayers.Pixel(-14, -15);
					nicon = new OpenSpace.Icon(static_host+"/img/icons/marker.png", size, offset);

					return createMarker(npoint,nicon);
				}

				function loadmap2() {
					map.events.register("mousemove", map, function(e) {
						if (enabled || !e) {
							if (e)
								pt = map.getLonLatFromViewPortPx(e.xy);

							var gro = new GT_OSGB();
							gro.northings = parseInt(pt.lat,10);
							gro.eastings = parseInt(pt.lon,10);

							curgr = gro.getGridRef(digits);

							document.getElementById('coordoutput').innerHTML = curgr+" <small>E: "+gro.eastings+" N: "+gro.northings+"</small>";
						}
					});
					map.events.register("click", map, function(e) {
						if (enabled) {
							var pt = map.getLonLatFromViewPortPx(e.xy);
							marker = createNMarker(pt);

							var img = static_host+"/img/icons/marker.png";

							document.getElementById('coordoutput').innerHTML = "<img src=\""+img+"\" height=\"12\" width=\"12\"/> " + document.getElementById('coordoutput').innerHTML;

						} else {
							if (marker)
								map.removeMarker(marker);
							marker = null;
							map.events.triggerEvent("mousemove",'');
						}
						enabled = !enabled;
					});
					document.getElementById('coordoutput').ondblclick = function() {
						digits = digits-1;
						if (digits == 1)
							digits = 5;
						map.events.triggerEvent("mousemove",'');
					}

				}
				function loadmap2_loader() {
					setTimeout(loadmap2,500);
				}
				AttachEvent(window,'load',loadmap2_loader,false);

				</script>
				{/literal}
				<script type="text/javascript" src="{"/mapper/geotools2.js"|revision}"></script>
			{/if}
		{/if}


	{/if}

        {if $square->reference_index == 1}
                <p>Double click the dynamic grid-reference to change the resolution.</p>
        {/if}

{/dynamic}
</body>
</html>

