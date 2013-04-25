{assign var="page_title" value="My votes"}
{include file="_std_begin.tpl"}
{dynamic}
{*TODO link "drop filters"*}
{*TODO text field for user id*}
<h2>My recent votes{if $type} of type "{$type}"{/if}{if $vote} with value {$vote}{/if}{if $userimg} on images by {$realname|escape:'html'}{/if}</h2>
<h3>Statistics</h3>
	<table>{* TODO: formatting *}
	<thead>
	<tr>
	<th style="text-align:right;background-color:#ccc">Type</th>
	<th style="text-align:center;background-color:#fff"><a class="voteneg" href="imgvote.php?vote=1{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">--</a></th>
	<th style="text-align:center;background-color:#fff"><a class="voteneg" href="imgvote.php?vote=2{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">-</a></th>
	<th style="text-align:center;background-color:#fff"><a class="voteneu" href="imgvote.php?vote=3{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">o</a></th>
	<th style="text-align:center;background-color:#fff"><a class="votepos" href="imgvote.php?vote=4{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">+</a></th>
	<th style="text-align:center;background-color:#fff"><a class="votepos" href="imgvote.php?vote=5{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">++</a></th>
	<th style="text-align:right;background-color:#ccc">Total</th>
	</tr>
	</thead>
	<tbody>
	{foreach from=$types item=curtype}
	<tr>
	<th style="text-align:right;background-color:#ccc">{$typenames.$curtype}</th>
	{if $votestat.$curtype}
	<td style="text-align:right;background-color:#ddd"><a href="imgvote.php?vote=1&amp;type={$curtype}{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">{$votestat.$curtype.1}</a></td>
	<td style="text-align:right;background-color:#ddd"><a href="imgvote.php?vote=2&amp;type={$curtype}{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">{$votestat.$curtype.2}</a></td>
	<td style="text-align:right;background-color:#ddd"><a href="imgvote.php?vote=3&amp;type={$curtype}{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">{$votestat.$curtype.3}</a></td>
	<td style="text-align:right;background-color:#ddd"><a href="imgvote.php?vote=4&amp;type={$curtype}{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">{$votestat.$curtype.4}</a></td>
	<td style="text-align:right;background-color:#ddd"><a href="imgvote.php?vote=5&amp;type={$curtype}{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">{$votestat.$curtype.5}</a></td>
	<td style="text-align:right;background-color:#ddd"><a href="imgvote.php?type={$curtype}{if $userimg}&amp;user={$userimg}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">{$votestat.$curtype.0}</a></td>
	{else}
	<td style="text-align:center;background-color:#ddd"" colspan="6">0</td>
	{/if}
	</tr>
	{/foreach}
	<tbody>
	</table>
<h3>Images</h3>

	{foreach from=$images item=image}
	 <div style="border-top: 2px solid lightgrey; padding-top:3px;">
	  <div style="float:left; position:relative; width:130px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a><br/>
		<div class="caption">{if $image->moderation_status eq "accepted"}supplemental{else}{$image->moderation_status}{/if}</div>
		<br/><div style="font-size:0.6em;">[[[{$image->gridimage_id}]]]</div>
	  </div>
	  <div style="float:left; position:relative">
		{$image->title1|escape:'html'}{* TODO *}<br />
		{$image->title2|escape:'html'}{* TODO *}
		<br/>
		for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>{if $image->realname} by <a title="view user profile" href="/profile/{$image->user_id}?a={$image->realname|escape:'url'}">{$image->realname|escape:'html'}</a>{else} by <a title="view user profile" href="/profile/{$image->user_id}">{$image->contributorname|escape:'html'}</a>{/if} [<a href="imgvote.php?user={$image->user_id}{if $vote}&amp;vote={$vote}{/if}{if $type}&amp;type={$type}{/if}{if $uservote != $user->user_id}&amp;u={$uservote}{/if}">Filter</a>]
		[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]
		<br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}
		{if $image->imageclass}<small>Category: {$image->imageclass|escape:'html'}</small>{/if}
		<div>{* TODO *}{$image->comment1|escape:'html'|nl2br|geographlinks}<br/>{$image->comment2|escape:'html'|nl2br|geographlinks}</div>
		<br/>
		{foreach from=$types item=curtype name=imgvoteloop}
		{$typenames.$curtype}:
		{if $image->votes[$curtype]==1}
		<span class="votenegactive">--</span>{if not $smarty.foreach.imgvoteloop.last} |{/if}
		{elseif $image->votes[$curtype]==2}
		<span class="votenegactive">-</span>{if not $smarty.foreach.imgvoteloop.last} |{/if}
		{elseif $image->votes[$curtype]==3}
		<span class="voteneuactive">o</span>{if not $smarty.foreach.imgvoteloop.last} |{/if}
		{elseif $image->votes[$curtype]==4}
		<span class="voteposactive">+</span>{if not $smarty.foreach.imgvoteloop.last} |{/if}
		{elseif $image->votes[$curtype]==5}
		<span class="voteposactive">++</span>{if not $smarty.foreach.imgvoteloop.last} |{/if}
		{else}
		<i>none</i>{if not $smarty.foreach.imgvoteloop.last} |{/if}
		{/if}
		{/foreach}
	  </div><br style="clear:both;"/>
{* TODO:next/prev? *}

	  <br/>
	 </div>
	{foreachelse}
	 	nothing to see here
	{/foreach}

	<div style="position:relative">
	<br/><br/>
	<div class="interestBox" style="font-size:0.8em">
	<div style="float:right"><a href="http://www.geograph.org.uk/article/The-Mark-facility" class="about">About</a></div>
	<b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={if $engine->temp_displayclass}{$engine->temp_displayclass}{else}{$engine->criteria->displayclass}{/if}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	&nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>

{/dynamic}

{include file="_std_end.tpl"}
