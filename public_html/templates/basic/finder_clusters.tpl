{assign var="page_title" value="Image Cluster Search"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
	ul.explore tt {
		border:1px solid gray;
		padding:5px;
	}
</style>
{/literal}

<h2><a href="/finder/">Finder</a> :: Image Clusters</h2>

<div class="interestBox">
<p>Image clustering - assigning images labels - is an automated process, based on the image title/description. It's not totally accurate, and can sometimes assign images to odd clusters</p>
<p>Note: you are searching the words in the cluster label itself. Can also enter a 4figure grid-ref (like TF4878) as first word to get 'nearby' results. To get just clusters in that square prefix with ^ (like ^TF7052)
</div>

<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<p>
		<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/>
	</p>
	{if $popup}
		<input type="hidden" name="popup" value=""/>
	{/if}
</form>

{if count($results) eq 15}
	<p>
		<small>&middot; To refine the results simply add more keywords (view <a href="#cheatsheet">Cheatsheet</a>)</small>
	</p>
{/if}

<ol start="{$offset}">
{foreach from=$results item=item}
	<li>
	<b><a href="/stuff/list.php?label={$item.label|escape:'urlplus'}&amp;gridref={$item.grid_reference|escape:'url'}" target="_top">{$item.label|escape:'html'|default:'unknown'}</a></b> {$item.grid_reference}
	
	{if $item.images}
	<small><small style="color:gray">{$item.images} images</small></small>{/if}
	</li>
{foreachelse}
	{if $q}
		<li><i>There is no content to display at this time.</i></li>
	{/if}
{/foreach}

</ol>


<div style="margin-top:0px"> 
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>	

{if $query_info}
	<p>{$query_info}</p>
{/if}

{if $q} 
<p>&middot; can get similar results from <a href="/finder/groups.php?q=groups%3A{$q|escape:'urlplus'}&amp;group=group_ids">Group By Search</a> filtered to only show results from the cluster labels</p>
{/if}

<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>you can enter just the first few letters of a name</li>
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example : <tt>smith -john</tt></li>
		<li>can use OR (Uppercase only!) to match <b>either/or</b> keywords; example: <tt>john OR joan</tt></li>
	</ul>
</div>


{include file="_std_end.tpl"}
