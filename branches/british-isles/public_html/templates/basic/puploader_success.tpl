{assign var="page_title" value="Upload Results"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">


</style>{/literal}
{dynamic}
<h2>Image Upload <sub>at {$smarty.now|date_format:"%Y-%m-%d %H:%M:%S"}</sub></h2>
<p>Here is the results of the latest upload:</p>


<div class="interestBox" style="background-color:lightgrey">
<dl class="picinfo">
{foreach from=$status key=key item=result}
	<dt>{$filenames.$key|escape:"html"}</dt>
	{if strpos($result,'ok:') === 0}
		<dd>Your photo has identification number [<a href="/photo/{$result|replace:'ok:':''}">{$result|replace:'ok:':''}</a>]</dd>
	{else}
		<dd style="background-color:red">{$result|escape:"html"}</dd>
	{/if}
{/foreach}
</dl>
</div>


{if $nofrills}
	<p><a href="/submit-nofrills.php?letmein=1{if $grid_reference}#gridref={$grid_reference|escape:"url"}{/if}">Submit another image</a></p>
{elseif $submit2}
	<p><a href="/submit2.php{if $new}?new={$new}{/if}{if $grid_reference}#gridref={$grid_reference|escape:"url"}{/if}">Submit another image</a></p>
{/if}
<ul>
<li><a href="/submissions.php" rel="nofollow">Edit My Recent Submissions</a></li>
</ul>

<br/><br/><br/><br/>

<p><i>All images assigned an ID number have been successfully uploaded. Please do not resubmit those images, only the failures.</i></p>


{/dynamic}

{include file="_std_end.tpl"}