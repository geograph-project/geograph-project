{include file="_std_begin.tpl"}

<h2>Geograph Calender {$month_name} {$year}</h2>

<form action="{$script_name}">
<p>Date: {html_select_date display_days=false prefix="" time=`$date` start_year="-100" reverse_years=true  month_empty="-whole year-" all_extra="onchange='this.form.submit()'"}<noscript><input type="submit" value="Update"/></noscript></p> 
</form>

<p>Showing one example image from each day, click a more link to list all images taken that day.<br/> Key: <span style="font-family:arial;color:green;">G: Geograph Images, <small style="color:blue">(S: Supplemental)</small></span>.</p>

<table class="report" bordercolor="#eeeeee" border="1" cellspacing="0">
<thead><tr>{foreach from=$days item=day}
<td>{$day}</td>
{/foreach}</tr></thead>
<tbody>

{foreach from=$weeks item=week}
<tr>
	{foreach from=$week item=day}
		<td bgcolor="#{$day.image->images|colerize}">
		{if $day.number}
			<div style="font-size:0.8em;"><b>{$day.number}</b> 
			
			{if $day.image}
				(<a href="/search.php?taken_endDay={$day.number}&amp;taken_endMonth={$month}&amp;taken_endYear={$year}&amp;taken_startDay={$day.number}&amp;taken_startMonth={$month}&amp;taken_startYear={$year}&amp;orderby=imagetaken&amp;do=1">more</a>)</div>
				
				<div style="text-align:center;width:120px;height:120px;vertical-align:middle">
					<a title="{$day.image->title|escape:'html'} - click to view full size image" href="/photo/{$day.image->gridimage_id}">{$day.image->getThumbnail(120,120)}</a>
				</div>

				<div align="center" style="font-family:arial;color:green;">G: {$day.image->images-$day.image->supps}
				{if $day.image->supps}
					<small style="color:blue">(S: {$day.image->supps})</small>
				{/if}</div>
			{else}
				</div>
			{/if}
		{else}
			&nbsp;
		{/if}
		</td>
	{/foreach}
</tr>
{/foreach}

</tbody>
</table>
 		
{include file="_std_end.tpl"}
