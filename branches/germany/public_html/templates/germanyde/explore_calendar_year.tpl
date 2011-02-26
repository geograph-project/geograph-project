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

<h2>Geograph Calendar :: {$year}</h2>

<form action="{$script_name}" class="no_print">
<p>Date: {html_select_date display_days=false prefix="" time=`$date` start_year="-100" reverse_years=true  month_empty="-whole year-" all_extra="onchange='this.form.submit()'"}<noscript><input type="submit" value="Update"/></noscript></p> 
{if $image}
<input type="hidden" name="image" value="{$image->gridimage_id}"/>
{/if}
</form>

<p class="no_print">Click the month name for more detail. Key: <span style="font-family:arial;font-size:0.7em;color:green;">Geograph Images</span>.</p>

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

{foreach from=$months key=name item=weeks name=loop}
<div style="position:relative;float:left;width=340px;padding:10px;height:280px;">

<h2><a href="{$script_name}?Year={$year}&amp;Month={$smarty.foreach.loop.iteration}" rel="nofollow">{$name}</a></h2>

<table class="report" bordercolor="#eeeeee" border="1" cellspacing="0" cellpadding="1" style="position:relative">
<thead><tr>{foreach from=$days item=day}
<td>{$day}</td>
{/foreach}</tr></thead>
<tbody>

{foreach from=$weeks item=week}
<tr>
	{foreach from=$week item=day}
		{if $day.number}
		<td bgcolor="#{$day.image->images|colerize}" valign="top">
			<div style="font-size:0.8em;font-weight:bold;">{$day.number}</div>

			{if $day.image}
				<div align="center" style="font-family:arial;font-size:0.7em;color:green;">{$day.image->images-$day.image->supps}</div>
			{/if}
		{else}
			<td>&nbsp;
		{/if}
		</td>
	{/foreach}
</tr>
{/foreach}

</tbody>
</table>

</div>
{/foreach}
<br style="clear:both"/>
<ul class="no_print">
	<li><a href="/statistics/leaderboard.php?when={$year}&amp;date=taken">User leaderboard for {$year}</a> {if $year >= 2005}(<a href="/statistics/leaderboard.php?when={$year}">Submitted</a>){/if}</li>
</ul>
{include file="_std_end.tpl"}
