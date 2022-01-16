{assign var="page_title" value="Geograph Map"}

{include file="_basic_begin.tpl"}
{dynamic}
	{if $error}
		<p>ERROR: {$error}</p>
	{else}
		{if $rastermap->enabled}
			<div style="float:left; position:relative; width: 412px">
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

				function createNMarker(npoint) {
					var nicon = L.icon({
					    iconUrl: static_host+"/img/icons/marker.png",
					    iconSize:     [29, 29], // size of the icon
					    iconAnchor:   [14, 14] // point of the icon which will correspond to marker's location
					});
					return createMarker(npoint,nicon);
				}

				function loadmap2() {
					var bounds = map.getBounds();
					map.setMaxBounds(bounds.pad(3));
					map.on("mousemove", function(e) {
						if (enabled || !e) {
							if (e)
								pt = e.latlng;

							var gro=gmap2grid(pt);

							curgr = gro.getGridRef(digits);

							document.getElementById('coordoutput').innerHTML = curgr+" <small>E: "+gro.eastings+" N: "+gro.northings+"</small>";

							document.getElementById('iomlink').style.display = 
								(gro.eastings > 214900 && gro.eastings < 249900
								&& gro.northings > 464530 && gro.northings < 505100)?'':'none';
						}
					});
					map.on("click", function(e) {
						if (enabled) {
							var pt = e.latlng;
							marker = createNMarker(pt);

							var img = static_host+"/img/icons/marker.png";

							document.getElementById('coordoutput').innerHTML = "<img src=\""+img+"\" height=\"12\" width=\"12\"/> " + document.getElementById('coordoutput').innerHTML;

						} else {
							if (marker)
								map.removeLayer(marker);
							marker = null;
							map.fire("mousemove");
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

	<div id="iomlink" style="display:none">
		Not all scales of maps work in Isle of Man due to no longer been mapped by Ordnance Survey Great Britain; if don't see map above, try zooming out.
		 You can find high resolution mapping for Isle of Man on the <a href="https://www.gov.im/maps/" target="_blank">Manngis website</a>
	</div>
</body>
</html>

