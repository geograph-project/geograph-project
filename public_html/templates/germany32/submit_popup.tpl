{assign var="page_title" value="Geograph Map"}

{include file="_basic_begin.tpl"}
{dynamic}
{if $error} 
	<p>ERROR: {$error}</p>
{else}
	{if $rastermap->enabled}
		<div style="float:left; position:relative; width: 350px">
		<div class="interestBox">Grid Reference: <b>{$gridref}</b></div>
	
		<div class="rastermap">
			<b>{$rastermap->getTitle($gridref)}</b><br/><br/>
			{$rastermap->getImageTag($gridref)}<br/>
			<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>

			</div>

			{$rastermap->getScriptTag()}
		</div>
		
	{else} 
		<script type="text/javascript" src="{"/mapping.js"|revision}"></script>
	{/if}
<div style="float:left; position:relative; font-size:0.8em;" class="interestBox">
<i>Placenames featured on this map:</i>
<ul>
{foreach from=$places item=place}
	<li>{$place.full_name}</li>
	
{/foreach}
</ul>
</div>
<br style="clear:both"/>
{if $rastermap->enabled}
	{$rastermap->getFooterTag()}
{/if}
{/if}
{/dynamic}
</body>
</html>

