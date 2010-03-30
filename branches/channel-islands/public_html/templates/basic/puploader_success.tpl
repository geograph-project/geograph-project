{assign var="page_title" value="Picasa Upload Results"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">


</style>{/literal}
<h2>Picasa Upload <sub>at {$smarty.now|date_format:"%Y-%m-%d %H:%M:%S"}</sub></h2>
<p>Here is the results of the latest upload:</p>

{dynamic}

<div class="interestBox" style="background-color:lightgrey">
<dl class="picinfo">
{foreach from=$status key=key item=result}
	<dt>{$filenames.$key|escape:"html"}</dt>
	{if strpos($result,'ok:') === 0} 
		<dd>
			<p>Your photo has identification number [<a href="/photo/{$result|replace:'ok:':''}">{$result|replace:'ok:':''}</a>]</p>
		</dd>
	{else}
		<dd style="background-color:red">{$result|escape:"html"}</dd>
	{/if}
{/foreach}
</dl>
</div>

<br/><br/><br/><br/>

<p><i>If any have any failed you should only reupload those images, all images assigned a id number have been successfuly uploaded</i></p>

{/dynamic} 

{include file="_std_end.tpl"}