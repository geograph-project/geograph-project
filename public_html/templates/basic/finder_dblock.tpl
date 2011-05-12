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
{/if}

{if !$inner}
	<h2><a href="/finder/">Finder</a> :: D-Block</h2>

	<form action="{$script_name}" method="get" onsubmit="focusBox()" class="interestBox" style="width:660px">
		<p>
			<label for="fgridref">Gridref</label>: <input type="text" name="gridref" id="fgridref" size="6"{dynamic}{if $gridref} value="{$gridref|escape:'html'}"{/if}{/dynamic}/>
			<label for="fq">Optional Keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</p>
	</form>

{/if}

{if $dblock}

	<ul>
		<li>{external href="http://www.bbc.co.uk/history/domesday/dblock/`$dblock`" text="View D-Block <b>`$dblock`</b> on Domesday Reloaded" title="view on Domesday Reloaded provided by BBC History"}
		<li>Go: <a href="{$script_name}?p={math equation="900*(y+3)+900-(x)" x=$x y=$y}">North</a> | <a href="{$script_name}?p={math equation="900*(y)+900-(x-4)" x=$x y=$y}">West</a> | <a href="{$script_name}?p={math equation="900*(y-3)+900-(x)" x=$x y=$y}">South</a> | <a href="{$script_name}?p={math equation="900*(y)+900-(x+4)" x=$x y=$y}">East</a></li>
	</ul>
{/if}


	{if $results}

		<table border="1" cellspacing="0" cellpadding="4">
			{foreach from=$yarr item=y}
				<tr>
					{foreach from=$xarr item=x}
						{if $results.$x.$y}
							{assign var="image" value=$results.$x.$y}
							<td align="center">

								<div class="interestBox" style="margin-bottom:4px"><a href="/search.php?searchtext={$q|escape:'url'}+gridref:{$image->grid_reference}">{$image->grid_reference}</a> <b>{$image->count}</b><small> images</small></div>
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