{dynamic}
{assign var="page_title" value="$gridrefraw :: PhotoMap"}
{include file="_std_begin.tpl"}



<h2>PhotoMap for {$gridrefraw} <sup>[{$square->imagecount} images]</sup></h2>
<span class="nowrap"><img src="http://{$static_host}/img/geotag_16.png" width="16" height="16" align="absmiddle" alt="geotagged!"/> <a href="/gridref/{$gridrefraw}.links"><b>More Links for this square</b></a></span>
<hr style="margin-top:5px"/>


<div class="interestBox" style="float: right; position:relative; padding:2px; margin-right:25px; margin-bottom:200px">
	<table border="0" cellspacing="0" cellpadding="2">
	<tr><td><a href="{$script_name}?p={math equation="900*(y+1)+900-(x-1)" x=$x y=$y}">NW</a></td>
	<td align="center"><a href="{$script_name}?p={math equation="900*(y+1)+900-(x)" x=$x y=$y}">N</a></td>
	<td><a href="{$script_name}?p={math equation="900*(y+1)+900-(x+1)" x=$x y=$y}">NE</a></td></tr>
	<tr><td><a href="{$script_name}?p={math equation="900*(y)+900-(x-1)" x=$x y=$y}">W</a></td>
	<td><b>Go</b></td>
	<td align="right"><a href="{$script_name}?p={math equation="900*(y)+900-(x+1)" x=$x y=$y}">E</a></td></tr>
	<tr><td><a href="{$script_name}?p={math equation="900*(y-1)+900-(x-1)" x=$x y=$y}">SW</a></td>
	<td align="center"><a href="{$script_name}?p={math equation="900*(y-1)+900-(x)" x=$x y=$y}">S</a></td>
	<td align="right"><a href="{$script_name}?p={math equation="900*(y-1)+900-(x+1)" x=$x y=$y}">SE</a></td></tr>
	</table>
</div>


{if $rastermap->enabled}
	<div class="rastermap" style="width:600px;position:relative;font-size:0.8em; ">
	{$rastermap->getImageTag()}
	<span style="color:gray"><small>{$rastermap->getFootNote()}</small></span>
	{$rastermap->getScriptTag()}	
	</div>
{/if}

<script src="http://gmaps-utility-library.googlecode.com/svn/trunk/mapiconmaker/1.1/src/mapiconmaker.js" type="text/javascript"></script> 
{literal}
<script>
	function load_p2() {
		var ele = document.getElementById("map");
		ele.style.width = "600px";
		ele.style.height = "400px";
		map.checkResize();
		
		
	}
	AttachEvent(window,'load_p2',loadmap,false);
</script>
{literal}

<br style="clear:both"/>
<br/>
	{if $rastermap->enabled}
		{$rastermap->getFooterTag()}
	{/if}

{/if}
{include file="_std_end.tpl"}
{/dynamic}
