{assign var="rss_url" value="/discuss/syndicator.php?forum=`$forum_id`&amp;topic=`$topic_id`"}
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

<div style="float:right"><a title="RSS Feed for {$topic_title}" href="/discuss/syndicator.php?forum={$forum_id}&amp;topic={$topic_id}" class="xml-rss">RSS</a></div>

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
		<td valign="top">{$item.post_text|GeographLinks:true|gallerytext}</td>
	</tr>
{foreachelse}
	<li><i>There are no posts to display at this time.</i></li>
{/foreach}
</table>
{if $pagesString}
	( Page {$pagesString})
{/if}

<br style="clear:both"/>


<p align=right>
<select onchange="window.open(this.value)" style="width:200px">
	<option>View images in search</option>
	<option value="/search.php?do=1&amp;topic_id={$topic}&amp;orderby=post_id,seq_id&amp;displayclass=full">Full details</option>
	<option value="/search.php?do=1&amp;topic_id={$topic}&amp;orderby=post_id,seq_id&amp;displayclass=thumbs">Thumbnails</option>
	<option value="/search.php?do=1&amp;topic_id={$topic}&amp;orderby=post_id,seq_id&amp;displayclass=thumbsmore">Thumbnails + links</option>
	<option value="/search.php?do=1&amp;topic_id={$topic}&amp;orderby=post_id,seq_id&amp;displayclass=bigger">Thumbnails - bigger</option>
	<option value="/search.php?do=1&amp;topic_id={$topic}&amp;orderby=post_id,seq_id&amp;displayclass=grid">Thumbnails grid</option>
	<option value="/search.php?do=1&amp;topic_id={$topic}&amp;orderby=post_id,seq_id&amp;displayclass=slide">Slideshow</option>
	<option value="/search.php?do=1&amp;topic_id={$topic}&amp;orderby=post_id,seq_id&amp;displayclass=map">Map</option>
  <option value="/search.php?do=1&amp;topic_id={$topic}&amp;orderby=post_id,seq_id&amp;displayclass=black">Georiver</option>
</select>  
  
 
- <a title="View these images in Google Earth" href="/search.php?do=1&amp;orderby=post_id,seq_id&amp;topic_id={$topic}&amp;kml" class="xml-kml">KML</a>
- <a title="RSS Feed for images" href="/search.php?do=1&amp;topic_id={$topic}&amp;orderby=post_id,seq_id&amp;reverse_order_ind=1&amp;rss" class="xml-rss">photo RSS</a>
- <a title="RSS Feed for this Topic" href="/discuss/syndicator.php?topic={$topic}" class="xml-rss">gallery RSS</a>
</p>

{dynamic}
{if $user->registered && $enable_forums}
	<div class="interestBox">
		<a href="/discuss/?action=vthread&amp;forum={$forum_id}&amp;topic={$topic_id}">Switch to edit mode</a> (registered users only)
	</div>
{/if}
{/dynamic}

{include file="_std_end.tpl"}
