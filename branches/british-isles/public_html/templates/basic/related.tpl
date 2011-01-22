{assign var="page_title" value="Images related to `$image->title`"}
{include file="_std_begin.tpl"}

<a name="top"></a>

<div style="float:left; position:relative; padding-right:10px;"><h2><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img
alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" align="top" /></a> <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a> : </h2></div>

<h2 style="margin-bottom:0px" class="nowrap"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a></h2>
<div>by <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a>, taken: {$image->image_taken}</div>

<br style="clear:both;"/>

<div class="photoguide" style="margin-left:auto;margin-right:auto; ">
	<div style="float:left;width:213px">
		<a title="view full size image" href="/photo/{$image->gridimage_id}">
		{$image->getThumbnail(213,160)}
		</a><div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a> for <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></div>
	</div>
	<div style="float:left;padding-left:20px; width:400px;">
		<span style="font-size:0.7em">{$image->comment|escape:'html'|nl2br|geographlinks|default:"<tt>no description for this image</tt>"}</span><br/>
		<br/>
		<small><b>&nbsp; &copy; Copyright <a title="View profile" href="http://{$http_host}{$image->profile_link}">{$image->realname|escape:'html'}</a> and
		licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a></b></small>
	</div>
{if $image_taken && $image->imagetaken > 1}
<div class="keywords yeardisplay" title="year photo was taken">year taken <div class="year">{$image->imagetaken|truncate:4:''}</div></div>
{/if}
	<br style="clear:both"/>
</div>


<div class="tabHolder" style="margin-top:3px">
	{foreach from=$methods key=i item=m}
	{if $i == $method}
	<span class="tabSelected">{$m}</span>
	{else}
	<a class="tab nowrap" href="/related.php?id={$image->gridimage_id}&amp;method={$i}">{$m}</a>
	{/if}
	{/foreach}
</div>
<div class="interestBox">
<h2 style=margin:0>Related Images</h2>
</div>
<br/>


<ol start="{$offset}">
{foreach from=$results item=item}
{if count($results) > 1}
	<li>
	<div class="interestBox">

	{if $item.resultCount > 3 && $item.query}
		<div style="float:right"><a href="/search.php?gridref={$image->grid_reference}&amp;searchtext={$item.query|escape:'url'}&amp;do=1&amp;distance=3" title="{$item.query|escape:'html'}">View {$item.resultCount} matches</a></div>
	{/if}

	<b><a href="{if $item.link}{$item.link|escape:'html'}{else}/search.php?gridref={$image->grid_reference}&amp;searchtext={$item.query|escape:'url'}&amp;do=1&amp;distance=3{/if}">{$item.title|escape:'html'}</a></b>

	{if $item.resultCount}
		<small style="color:green">({$item.resultCount|thousends} images)</small>
	{/if}

	</div>
{/if}

	{foreach from=$item.images item=image}
		<div style="float:left;width:160px;height:160px" class="photo33"><div style="height:{$thumbh}px;vertical-align:middle"><a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail($thumbw,$thumbh,false,true)}</a></div>
		<div class="caption"><div class="minheightprop" style="height:2.5em"></div><a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a><div class="minheightclear"></div></div>
		<div class="statuscaption">by <a href="{$image->profile_link}">{$image->realname}</a></div>
		</div>
	{foreachelse}
		{if $item.skipped}
			<div><small><i>matching images in square not checked</i></small></div>
		{else}
			<div><small><i>no images found matching {$q|escape:'html'} in square</i></small></div>
		{/if}
	{/foreach}
	<br style="clear:left;"/>

{if count($results) > 1}
	</li>
{elseif $item.resultCount}
	1..25 of about <a href="/related.php?id={$image->gridimage_id}">{$item.resultCount|thousends} images</a>.
{/if}
{foreachelse}

		<li><i>There is no content to display at this time.</i></li>

{/foreach}

</ol>

<script type="text/javascript">
{literal}
function show_portals() {
	show_tree(101);
	var ele = document.getElementById('iframe');
	ele.src = ele.title;
	return false;
}
if (Math.random() * 10 > 9) {
	 AttachEvent(window,'load',function () { setTimeout('show_portals()',4000) },false);
}
{/literal}
</script>

<p id="hide101">&middot; <a href="javascript:void(show_portals())">Show portals for this image</a> <sup style=color:red>Experimental</sup></p>
<div id="show101" style="display:none" class="interestBox">
	<iframe src="about:blank" title="http://www.geographs.org/portals/tester.php?id={$image->gridimage_id}&amp;t={$image->title|escape:'url'}&amp;u={$image->user_id}&amp;g={$image->grid_reference}&amp;c={$image->comment|escape:'url'}&amp;i={$image->imageclass|escape:'url'}" width="100%" height="500" id="iframe"></iframe>
</div>


<br/><br/>
<div class="top"><a href="#top">back to top</a> | <a href="/photo/{$image->gridimage_id}">Return to photo page</a></div>



{include file="_std_end.tpl"}
