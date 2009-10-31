{assign var="page_title" value="Upload Results"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">


</style>{/literal}
<h2>Image Upload <sub>at {$smarty.now|date_format:"%Y-%m-%d %H:%M:%S"}</sub></h2>
<p>Here is the results of the latest upload:</p>

{dynamic}

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
	<p><a href="/submit2.php{if $grid_reference}#gridref={$grid_reference|escape:"url"}{/if}">Submit another image</a></p>
{/if}
<br/><br/><br/><br/>

<p><i>If any have any failed you should only reupload those images, all images assigned a id number have been successfuly uploaded.</i></p>

{/dynamic} 

{include file="_std_end.tpl"}