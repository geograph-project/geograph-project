{assign var="page_title" value="Map Browsing"}
{include file="_std_begin.tpl"}

    <h2>Map Browsing</h2>
 
 <div style="float:right">

 <a href="/mapbrowse.php?t={$token_zoomout}">Zoom out</a><br>
 
 &nbsp;&nbsp;&nbsp;&nbsp;<a href="/mapbrowse.php?t={$token_north}">N</a><br>
 <a href="/mapbrowse.php?t={$token_west}">W</a> &nbsp;
 <a href="/mapbrowse.php?t={$token_east}">E</a> <br>
 &nbsp;&nbsp;&nbsp;&nbsp;<a href="/mapbrowse.php?t={$token_south}">S</a><br>


 <table>
 <tr><td>map_x</td><td>{$mosaicobj->map_x}</td></tr>
 <tr><td>map_y</td><td>{$mosaicobj->map_y}</td></tr>
 <tr><td>image_w</td><td>{$mosaicobj->image_w}</td></tr>
 <tr><td>image_h</td><td>{$mosaicobj->image_h}</td></tr>
 <tr><td>pixels_per_km</td><td>{$mosaicobj->pixels_per_km}</td></tr>
 <tr><td>mosaic_factor</td><td>{$mosaicobj->mosaic_factor}</td></tr>
 <tr><td>caching</td><td>{$mosaicobj->caching}</td></tr>
 <tr><td colspan="2">{$mosaicobj->debugtrace}</td></tr>
 </table>
 </div>
 
 
 
 	<div style="width:{$mosaic_width}px;height:{$mosaic_height}px;background:#6574FF;">
 	{foreach from=$mosaic key=y item=maprow}
 		{foreach from=$maprow key=x item=mapcell}
 		<a href="/mapbrowse.php?t={$token}&i={$x}&j={$y}&zoomin="><img 
 		ismap="ismap" title="{$mapcell->getImageUrl()}" align="left" src="{$mapcell->getImageUrl()}" width="{$mapcell->image_w}" height="{$mapcell->image_h}" border="0"/></a>
 		{/foreach}
 	{/foreach}
 	</div>
 
{if $is_admin}
<p><a href="mapbrowse.php?expireAll=0">Clear cache</a>
<a href="mapbrowse.php?expireAll=1">(clear basemaps too)</a></p>

{/if}
 
 
{include file="_std_end.tpl"}
