{assign var="page_title" value="$month_name $year :: Calendar"}
{assign var="extra_css" value="/templates/germany32/css/calendar.css"}
{include file="_std_begin.tpl"}

<h2>Geograph Calendar :: {$month_name} {$year}</h2>

<form action="{$script_name}" class="no_print">
<p>Date: {html_select_date display_days=false prefix="" time=`$date` start_year="-100" reverse_years=true  month_empty="-whole year-" all_extra="onchange='this.form.submit()'"}
{dynamic}
{if $uid || $user->registered}
| User:
<select name="u" onchange="this.form.submit()">
<option value="0"{if $uid eq 0} selected="selected"{/if}>All users</option>
{if $user->registered && $user->user_id != $uid}
<option value="{$user->user_id}">{$user->realname|escape:'html'}</option>
{/if}
{if $uid}
<option value="{$profile->user_id}" selected="selected">{$profile->realname|escape:'html'}</option>
{/if}
</select>
{/if}{/dynamic}
<noscript><input type="submit" value="Update"/></noscript></p> 
{if $image}
<input type="hidden" name="image" value="{$image->gridimage_id}"/>
{/if}
</form>

{if !$blank}
<p class="no_print">Showing one example image from each day, click a more link to list all images taken that day. 
<br/><a href="/search.php?taken_endMonth={$month}&amp;taken_endYear={$year}&amp;taken_startMonth={$month}&amp;taken_startYear={$year}{if $uid}&amp;user_id={$uid}{/if}&amp;orderby=imagetaken&amp;do=1">Search all images taken {$month_name} {$year}</a>		
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
				(<a href="/search.php?taken_endDay={$day.number}&amp;taken_endMonth={$month}&amp;taken_endYear={$year}&amp;taken_startDay={$day.number}&amp;taken_startMonth={$month}&amp;taken_startYear={$year}{if $uid}&amp;user_id={$uid}{/if}&amp;orderby=imagetaken&amp;do=1">more</a>)</div>
				
				<div style="text-align:center;width:120px;height:120px;vertical-align:middle">
					<a title="{$day.image->grid_reference} : {$day.image->title|escape:'html'} by {$day.image->realname|escape:'html'} - click to view full size image" href="/photo/{$day.image->gridimage_id}">{$day.image->getThumbnail(120,120,false,false,'src',3)}</a>
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
	<li><a href="/statistics/leaderboard.php?when={$year}-{$month}&amp;date=taken" rel="nofollow">User leaderboard for {$month_name} {$year}</a> {if $year >= 2005}(<a href="/statistics/leaderboard.php?when={$year}-{$month}">Submitted</a>){/if}</li>
</ul>
{include file="_std_end.tpl"}
