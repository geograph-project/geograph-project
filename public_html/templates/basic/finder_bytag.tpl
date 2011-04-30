{assign var="page_title" value="By Tag Search"}
{if $inner}
{include file="_basic_begin.tpl"}
{else}
{include file="_std_begin.tpl"}
{/if}
{literal}
<style type="text/css">
	ul.explore tt {
		border:1px solid gray;
		padding:5px;
	}
</style>

  <script type="text/javascript">

  function focusBox() {
  	if (el = document.getElementById('fq')) {
  		el.focus();
  	}
  }
  AttachEvent(window,'load',focusBox,false);

  </script>

{/literal}

{if !$inner}
	<h2><a href="/finder/">Finder</a> :: By Tag</h2>

	<form action="{$script_name}" method="get" onsubmit="focusBox()">
		<p>
			<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</p>
	</form>

	{if count($results) eq 60}
		<p>
			<small>&middot; To refine the results simply add more keywords (view <a href="#cheatsheet">Cheatsheet</a>)</small>
		</p>
	{/if}
{/if}

	{if $results}
		<p>Tags on images matching your term, with one example image per tag; Click a tag to find other images with that tag.</p>
	{/if}

	{foreach from=$results item=image}
		<div class="photo33" style="float:left;padding:5px">
		<div class="interestBox" style="margin-bottom:4px"><span class="tag">{if $image->tag.prefix}{$image->tag.prefix|escape:'html'}:{/if}<a{if $image->count > 1} href="/search.php?searchtext={$q|escape:'url'}+tags:%22{if $image->tag.prefix}{$image->tag.prefix|escape:'html'}+{/if}{$image->tag.tag|escape:'html'}%22"{/if} class="taglink">{$image->tag.tag|escape:'html'}</a></span> <small>x</small><b>{$image->count}</b></div>
		<div style="height:{$thumbh}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
		<div class="caption"><div class="minheightprop" style="height:2.5em"></div>{if $mode != 'normal'}<a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {/if}<a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
		<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
		</div>
	{foreachelse}
		<i>No results</i>
	{/foreach}
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