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
		<dd><form enctype="multipart/form-data" action="/submit_competition.php" method="get" target="_blank">
			{if $show_comp}
			<div style="float:right; border:2px solid black; padding:5px;">Competition Code: <input type="hidden" name="id" value="{$result|replace:'ok:':''}"/> <input type="text" name="code" size="5"/> 
			<input type="submit" value="Enter Mapping News competitions"/></div>
			{/if}
			<p>Your photo has identification number [<a href="/photo/{$result|replace:'ok:':''}">{$result|replace:'ok:':''}</a>]</p>
		</form></dd>
	{else}
		<dd style="background-color:red">{$result|escape:"html"}</dd>
	{/if}
{/foreach}
</dl>
</div>

<br/><br/><br/><br/>

<p><i>If any have any failed you should only reupload those images, all images assigned a id number have been successfuly uploaded</i></p>

{if $show_comp}
<div class="interestBox" style="border:2px solid black">
<h3><span style="color:#000066">‘Bag the Most Grid Squares’ <br/>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &amp; ‘Best Photograph’</span> <sub>Mapping News Competitions</sub></h3>
	
<p>Note to enter these competitions, will need to enter the code above once per photo, and agree to the Terms individually</p>

<small>Opening Dates: <b><i>between 1 April 2008 and 30 September 2008</i></b>, <br/>
	Note: <i>Entry is only open to <b>UK permanent residents aged 18 and under, in full time education</b></i> ({external href="http://`$http_host`/help/competition_terms" text="full terms" target="_blank"}).</small>
</div>
{/if}
{/dynamic} 

{include file="_std_end.tpl"}