{assign var="page_title" value="$month_name $year :: Kalender"}
{assign var="extra_css" value="/templates/germanyde/css/calendar.css"}
{include file="_std_begin.tpl"}

<h2>Geograph-Kalender :: {$month_name} {$year}</h2>

<form action="{$script_name}" class="no_print">
<p>Datum: {html_select_date display_days=false prefix="" time=`$date` start_year="-100" reverse_years=true  month_empty="-gesamtes Jahr-" all_extra="onchange='this.form.submit()'"}
{dynamic}
{if $uid || $user->registered}
| Teilnehmer:
<select name="u" onchange="this.form.submit()">
<option value="0"{if $uid eq 0} selected="selected"{/if}>Alle</option>
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
<p class="no_print">F�r jeden Tag wird ein Beispielbild angezeigt. �ber den "mehr"-Link kann eine Auflistung aller an diesem Tag aufgenommenen Bilder erreicht werden.
<br/><a href="/search.php?taken_endMonth={$month}&amp;taken_endYear={$year}&amp;taken_startMonth={$month}&amp;taken_startYear={$year}{if $uid}&amp;user_id={$uid}{/if}&amp;orderby=imagetaken&amp;do=1">Alle Bilder vom {$month_name} {$year} suchen</a>		
&nbsp;&nbsp;&nbsp;&nbsp; Legende: <span style="font-family:arial;color:green;">G: Geobilder, <small style="color:blue">(E: Extrabilder)</small></span>.</p>
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
	alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="{$image->profile_link}">{$image->realname|escape:'html'}</a> und  
	lizensiert unter <span class="no_print"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">dieser Creative Commons Licence</a>.</span> 
	<span class="print_only" style="font-size:0.9em"> <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">dieser Creative Commons Licence: http://creativecommons.org/licenses/by-sa/2.0/</a></span></div>
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
				(<a href="/search.php?taken_endDay={$day.number}&amp;taken_endMonth={$month}&amp;taken_endYear={$year}&amp;taken_startDay={$day.number}&amp;taken_startMonth={$month}&amp;taken_startYear={$year}{if $uid}&amp;user_id={$uid}{/if}&amp;orderby=imagetaken&amp;do=1">mehr</a>)</div>
				
				<div style="text-align:center;width:120px;height:120px;vertical-align:middle">
					<a title="{$day.image->grid_reference} : {$day.image->title|escape:'html'} von {$day.image->realname|escape:'html'} - zum Vergr��ern anklicken" href="/photo/{$day.image->gridimage_id}">{$day.image->getThumbnail(120,120)}</a>
				</div>

				<div align="center" style="font-family:arial;color:green;">G: {$day.image->images-$day.image->supps}
				{if $day.image->supps}
					<small style="color:blue">(E: {$day.image->supps})</small>
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
	<li><a href="/statistics/leaderboard.php?when={$year}-{$month}&amp;date=taken" rel="nofollow">Rangliste f�r {$month_name} {$year}</a> {if $year >= 2005}(<a href="/statistics/leaderboard.php?when={$year}-{$month}">Einreichdatum</a>){/if}</li>
</ul>
{include file="_std_end.tpl"}
