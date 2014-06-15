{assign var="page_title" value="DBlock View"}
{if $inner}
{include file="_basic_begin.tpl"}
{else}
{include file="_std_begin.tpl"}
{/if}
{literal}

  <script type="text/javascript">

  function focusBox() {
  	if (el = document.getElementById('fq')) {
  		el.focus();
  	}
  }
  AttachEvent(window,'load',focusBox,false);

  </script>

{/literal}

{if $dblock && $x && $y}
	<div style="float:right">
		<table border="0" cellspacing="0" cellpadding="2">
		<tr><td><a href="{$script_name}?p={math equation="900*(y+3)+900-(x-4)" x=$x y=$y}">NW</a></td>
		<td align="center"><a href="{$script_name}?p={math equation="900*(y+3)+900-(x)" x=$x y=$y}">N</a></td>
		<td><a href="{$script_name}?p={math equation="900*(y+3)+900-(x+4)" x=$x y=$y}">NE</a></td></tr>
		<tr><td><a href="{$script_name}?p={math equation="900*(y)+900-(x-4)" x=$x y=$y}">W</a></td>
		<td><b>Go</b></td>
		<td align="right"><a href="{$script_name}?p={math equation="900*(y)+900-(x+4)" x=$x y=$y}">E</a></td></tr>
		<tr><td><a href="{$script_name}?p={math equation="900*(y-3)+900-(x-4)" x=$x y=$y}">SW</a></td>
		<td align="center"><a href="{$script_name}?p={math equation="900*(y-3)+900-(x)" x=$x y=$y}">S</a></td>
		<td align="right"><a href="{$script_name}?p={math equation="900*(y-3)+900-(x+4)" x=$x y=$y}">SE</a></td></tr>
		</table>
	</div>
{/if}

{if !$inner}
	<h2><a href="/finder/">Finder</a> :: D-Block {$dblock}</h2>

	<form action="{$script_name}" method="get" onsubmit="focusBox()" class="interestBox" style="width:660px">
			<label for="fgridref">Gridref</label>: <input type="text" name="gridref" id="fgridref" size="6"{dynamic}{if $gridref} value="{$gridref|escape:'html'}"{/if}{/dynamic}/>
			<label for="fq">Optional Keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
	</form>

{/if}

	<p>The Domesday Project, published by the BBC, captured 23,225 photos of the United Kingdom, in 1986, {external href="http://www.bbc.co.uk/history/domesday/story" text="read more here"}. <i>The whole of the UK - including the Channel Islands and Isle of Man - was divided into 23,000 4x3km areas called Domesday Squares or "D-Blocks".</i></p>
	<p>This page shows the images Geograph has for each D-Block. Can enter a Grid-Reference above, to jump to the corresponding D-Block, and optionally filter the images by keyword.</p>

{if $dblock}
	<ul>
		<li>{external href="http://www.bbc.co.uk/history/domesday/dblock/`$dblock`" text="View D-Block <b>`$dblock`</b> on Domesday Reloaded" title="view on Domesday Reloaded provided by BBC History"} (Photos in 1986 and 2011)
		<li>Go: <a href="{$script_name}?p={math equation="900*(y+3)+900-(x)" x=$x y=$y}{if $q}&amp;q={$q|escape:'url'}{/if}">North</a> | <a href="{$script_name}?p={math equation="900*(y)+900-(x-4)" x=$x y=$y}{if $q}&amp;q={$q|escape:'url'}{/if}">West</a> | <a href="{$script_name}?p={math equation="900*(y-3)+900-(x)" x=$x y=$y}{if $q}&amp;q={$q|escape:'url'}{/if}">South</a> | <a href="{$script_name}?p={math equation="900*(y)+900-(x+4)" x=$x y=$y}{if $q}&amp;q={$q|escape:'url'}{/if}">East</a></li>
	</ul>
{/if}

{if $years}
	<p>Year Filter:
	{foreach from=$years key=y item=count}
		{if $year eq $y}
			<b class="nowrap">{$y} <small>({$count} images)</small></b> &middot;
		{else}
			<span class="nowrap"><a href="{$script_name}?gridref={$gridref|escape:'url'}{if $q}&amp;q={$q|escape:'url'}{/if}&amp;year={$y}">{$y}</a> <small>({$count})</small></span> &middot;
		{/if}
	{/foreach}</p>
{/if}

	{if $results}

		<table border="1" cellspacing="0" cellpadding="4">
			{foreach from=$yarr item=y}
				<tr>
					{foreach from=$xarr item=x}
						{if $results.$x.$y}
							{assign var="image" value=$results.$x.$y}
							<td align="center" width="25%">

								<div class="interestBox" style="margin-bottom:4px"><a href="/search.php?searchtext={$q|escape:'url'}+gridref:{$image->grid_reference}{if $year}+takenyear:{$year}{/if}&amp;do=1">{$image->grid_reference}</a> <b>{$image->count}</b><small> images</small></div>
								<div style="height:{$thumbh}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
								<div class="caption"><div class="minheightprop" style="height:2.5em"></div><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
								<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>

							</td>
						{else}
							<td>no photos for<br/>
							this <a href="/browse.php?p={math equation="900*y+900-x" x=$x y=$y}">square</a></td>
						{/if}
					{/foreach}
				<tr>
			{/foreach}
		</table>
	{else}
		{if $dblock}
			No Images found for this D-Block.
		{else}
			UNKNOWN D-BLOCK
		{/if}

		Note can also enter a dblock (eg NI-332000-372000) directly, but remember does not cover Southern Ireland.
	{/if}

	<br style="clear:both"/>

<div style="margin-top:0px">
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>

{if $query_info}
	<p>{$query_info}</p>
{/if}

{if $inner}
</body>
</html>
{else}
<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example : <tt>smith -john</tt></li>
		<li>can use OR (Uppercase only!) to match <b>either/or</b> keywords; example: <tt>john OR joan</tt></li>
	</ul>
</div>


{include file="_std_end.tpl"}
{/if}
