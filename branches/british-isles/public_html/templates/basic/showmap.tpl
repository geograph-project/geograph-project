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
				<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
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
				<script type="text/javascript">

				var digits = 5;
				var enabled = true;
				var marker = null;
				var pt;

				function loadmap2() {
					map.events.register("mousemove", map, function(e) {
						if (enabled || !e) {
							if (e)
								pt = map.getLonLatFromViewPortPx(e.xy);

							var gro = new GT_OSGB();
							gro.northings = pt.lat;
							gro.eastings = pt.lon;

							curgr = gro.getGridRef(digits);

							//hack alert
							document.getElementById('mapTitleOS50k').style.display='';
							document.getElementById('mapTitleOS50k').innerHTML = curgr+" <small>E: "+pt.lon+" N: "+pt.lat+"</small>";
						}
					});
					map.events.register("click", map, function(e) {
						if (enabled) {
							var pt = map.getLonLatFromViewPortPx(e.xy);
							marker = createPMarker(pt);
						} else {
							if (marker)
								map.removeMarker(marker);
							marker = null;
						}
						enabled = !enabled;
					});
					document.getElementById('mapTitleOS50k').onclick = function() {
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
{/dynamic}
</body>
</html>

