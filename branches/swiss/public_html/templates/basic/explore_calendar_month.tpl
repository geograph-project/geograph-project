{assign var="page_title" value="$month_name $year :: Calendar"}
{include file="_std_begin.tpl"}
<style type="text/css">
{literal}
@media print {
	.no_print {
		display: none;
	}
	
	/* no border on hyperlinked images*/
	a Img
	{
		border:0;
	}
	div.ccmessage
	{
		width:inherit;
		text-align:center;
		padding:1px;
		font-size:0.9em;
	}
	
	div.ccmessage img
	{
		vertical-align: middle;
	}
	
	/* styling for a full size portrait photo*/
	div.photoportrait
	{
		width:inherit;
		padding:10px;
		text-align:center;
		margin:10px;
	}
	
	/* styling for a full size landscape photo*/
	div.photolandscape
	{
		width:inherit;
		padding:10px;
		text-align:center;
		margin:10px;
	}
	
	.img-shadow img {
		border: 1px solid #a9a9a9;
		padding: 4px;
	}

}
@media screen {
	.print_only {
		display: none;
	}
}
{/literal}
</style>

<h2>Geograph Calendar :: {$month_name} {$year}</h2>

<form action="{$script_name}" class="no_print">
<p>Date: {html_select_date display_days=false prefix="" time=`$date` start_year="-100" reverse_years=true  month_empty="-whole year-" all_extra="onchange='this.form.submit()'"}<noscript><input type="submit" value="Update"/></noscript></p> 
{if $image}
<input type="hidden" name="image" value="{$image->gridimage_id}"/>
{/if}
</form>

{if !$blank}
<p class="no_print">Showing one example image from each day, click a more link to list all images taken that day. 
<br/><a href="/search.php?taken_endMonth={$month}&amp;taken_endYear={$year}&amp;taken_startMonth={$month}&amp;taken_startYear={$year}&amp;orderby=imagetaken&amp;do=1">Search all images taken {$month_name} {$year}</a>		
&nbsp;&nbsp;&nbsp;&nbsp; Key: <span style="font-family:arial;color:green;">G: Geograph Images, <small style="color:blue">(S: Supplemental)</small></span>.</p>
{/if}

{if $image}
	<div class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}">
		<div class="img-shadow"><a href="/photo/{$image->gridimage_id}">{$image->getFull()}</a></div>
		<div class="caption"><b><a href="/gridref/{$image->grid_reference|escape:'html'}">{$image->grid_reference|escape:'html'}</a> : {$image->title|escape:'html'}</b></div>
		{if $image->comment}
			<div class="caption">{$image->comment|escape:'html'|geographlinks}</div>
		{/if}
	</div>
	<!-- Creative Commons Licence -->
	<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
	alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> and  
	licensed for reuse <span class="no_print">under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</span> 
	<span class="print_only" style="font-size:0.9em"> <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">http://creativecommons.org/licenses/by-sa/2.0/</a></span></div>
	<!-- /Creative Commons Licence -->
	<br/>
{/if}

<table class="report" bordercolor="#eeeeee" border="1" cellspacing="0" align="center">
<thead><tr>{foreach from=$days item=day}
<td>{$day}</td>
{/foreach}</tr></thead>
<tbody>

{foreach from=$weeks item=week}
<tr>
	{foreach from=$week item=day}
		<td bgcolor="#{$day.image->images|colerize}" width="80" height="55" valign="top">
		{if $day.number}
			<div style="font-size:0.8em;"><b>{$day.number}</b> 
			
			{if $day.image}
				(<a href="/search.php?taken_endDay={$day.number}&amp;taken_endMonth={$month}&amp;taken_endYear={$year}&amp;taken_startDay={$day.number}&amp;taken_startMonth={$month}&amp;taken_startYear={$year}&amp;orderby=imagetaken&amp;do=1">more</a>)</div>
				
				<div style="text-align:center;width:120px;height:120px;vertical-align:middle">
					<a title="{$day.image->grid_reference} : {$day.image->title|escape:'html'} by {$day.image->realname|escape:'html'} - click to view full size image" href="/photo/{$day.image->gridimage_id}">{$day.image->getThumbnail(120,120)}</a>
				</div>

				<div align="center" style="font-family:arial;color:green;">G: {$day.image->images-$day.image->supps}
				{if $day.image->supps}
					<small style="color:blue">(S: {$day.image->supps})</small>
				{/if}</div>
			{else}
				</div>
			{/if}
		{else}
			<div style="background-color:#eeeeee;height:100%;width:100%">&nbsp;</div>
		{/if}
		</td>
	{/foreach}
</tr>
{/foreach}

</tbody>
</table>
 	
<ul class="no_print">
	<li><a href="/statistics/leaderboard.php?when={$year}-{$month}&amp;date=taken" rel="nofollow">User leaderboard for {$month_name} {$year}</a> {if $year >= 2005}(<a href="/leaderboard.php?when={$year}-{$month}">Submitted</a>){/if}</li>
</ul>
{include file="_std_end.tpl"}
