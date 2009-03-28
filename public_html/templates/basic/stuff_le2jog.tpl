{assign var="page_title" value="Land's End to John O' Groats - a very British walk."}
{include file="_std_begin.tpl"}

<h2>Land's End to John O' Groats - a very British walk.</h2>

{if $hide}
	<p><a href="?">Show all locations</a>/<b>Show only locations with potential issues</b></p>
{else}
	<p><b>Show all locations</b>/<a href="?hide">Show only locations with potential issues</a></p>
{/if}

<p>Colour Key: <span style="background-color:pink">Without Photos</span> - <span style="background-color:red">abnormally long jump</span>, this page only updates once every 24 hours, last time: {$smarty.now|date_format:"%H:%M"}</p>

{foreach from=$posts key=id item=row}
	<h4>{$row.poster_name} <sup>{$row.post_time}</sup> <a href="/discuss/?action=vpost&amp;forum=6&amp;topic=822&amp;post={$id}">Open &gt;&gt;</a></h4>

	<table border="1" cellspacing="0" cellpadding="3">
		<tr><th>GridSquare</th><th>Mentions</th><th>Photos</th><th>Submitted</th><th>Distance</th></tr>

		{foreach from=$row.gridsquares item=square}
			<tr {if $square->images == 0 and $square->imagecount > 0} style="background-color:pink"{/if}>
				<td><a href="/gridref/{$square->grid_reference}">{$square->grid_reference}</a></td>
				<td>{$square->mentions}</td>
				<td>{$square->images}</td>
				<td>{$square->imagecount}</td>
				<td {if $square->distance>1.5} style="background-color:red"{/if}>{$square->distance}km</td>
			</tr>
		{/foreach}
	</table>

{/foreach}

{include file="_std_end.tpl"}
