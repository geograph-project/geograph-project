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

	{if $gridref}
		{include file="_bar_location.tpl"}
		<div class="interestBox">
			<h2><a href="/finder/">Finder</a> :: By Tag</h2>
		</div>
	{else}
		<h2><a href="/finder/">Finder</a> :: By Tag</h2>
	{/if}

	<form action="{$script_name}" method="get" onsubmit="focusBox()">
		<p>
			<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
			{if $user_id}
				<input type="hidden" name="user_id" value="{$user_id}"/>
			{/if}
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

<div style="margin-top:0px;font-size:0.7em;margin-bottom:7px;text-align:right">
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>


	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$results item=image}
		<tr>
			<td valign="top">
				<div class="interestBox" style="font-weight:bold;color:white;background-color:black">
					<div style="float:right;color:yellow"><small>x</small><b>{$image->count}</b></div>
				{if $image->tag.prefix}{$image->tag.prefix|escape:'html'}:{/if}<a{if $image->count > 1} href="/search.php?searchtext={$q|escape:'url'}+tags:%22{if $image->tag.prefix}{$image->tag.prefix|escape:'html'}+{/if}{$image->tag.tag|escape:'html'}%22{if $user_id}&amp;user_id={$user_id}{/if}&amp;do=1"{/if} style="color:cyan">{$image->tag.tag|escape:'html'}</a></div>

				<div class="caption" style="margin-top:10px;">Example Image: {if $mode != 'normal'}<a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : {/if}<a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></div>
				<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
			</td>
			<td valign="top" align="center">
				<a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a>
			</td>
		</tr>
	{foreachelse}
		<i>No results</i>
	{/foreach}
	</table>

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
