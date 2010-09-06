{assign var="page_title" value=$topic_title}
{assign var="rss_url" value="/discuss/syndicator.php?forum=11&amp;topic=`$topic_id`"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
#maincontent h1 { padding: 5px; margin-top:0px; background-color: black; color:white}
#maincontent h2 { padding: 5px; background-color: lightgrey}
#maincontent h3 { padding: 5px; margin-top:20px; border: 1px solid lightgrey; background-color: #eeeeee}
#maincontent h4 { padding: 5px; margin-top:20px; border: 1px dashed lightgrey; background-color: #eeeeee}

#gallery {
	font-size:0.75em;
}
#gallery th {
	font-weight:normal;
	color:gray;
}
#gallery th a {
	border-top:1px solid lightgrey;
	margin-top:10px;
	display:block;
}
#gallery td {
	border-top:1px solid lightgrey;
	border-left:1px solid lightgrey;
	padding:5px;
}

@media print {
	.no_print {
		display: none;
	}
}
</style>{/literal}

<div style="float:right"><a title="RSS Feed for {$topic_title}" href="/discuss/syndicator.php?forum=11&amp;topic={$topic_id}" class="xml-rss">RSS</a></div>

<h1 style="margin-bottom:0px;">{$topic_title}</h1>
<div style="margin-top:0px">
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>

<br/>

<table cellspacing="0" cellpadding="3" border="0" id="gallery">
{foreach from=$list item=item}
	<tr bgcolor="{cycle values="#E9EFF4,#F6F9FB"}">
		<th valign="top">{$item.post_time|date_format:"%a, %e %b %Y %H:%M"}<br/>
		<a href="/profile/{$item.poster_id}" title="View Geograph Profile for {$item.poster_name}" style="color:#6699CC">{$item.poster_name}</a></th>
		<td valign="top">{$item.post_text|gallerytext|GeographLinks:true}</td>
	</tr>
{foreachelse}
	<li><i>There are no posts to display at this time.</i></li>
{/foreach}
</table>
{if $pagesString}
	( Page {$pagesString})
{/if}

<br style="clear:both"/>

<div style="text-align:right"><a title="RSS Feed for {$topic_title}" href="/discuss/syndicator.php?forum=11&amp;topic={$topic_id}" class="xml-rss">RSS</a></div>

{dynamic}
{if $user->registered && $enable_forums}
	<div class="interestBox">
		<a href="/discuss/?action=vthread&amp;forum=11&amp;topic={$topic_id}">Switch to Edit mode</a> (Registered Users Only)
	</div>
{/if}
{/dynamic}

{include file="_std_end.tpl"}
