{assign var="page_title" value="2014"}
{include file="_std_begin.tpl"}


<div class="interestBox">
	<h2>Your 2014</h2>
</div>

{if $image}

{literal}
<style>
div.thumbs {
	padding:20px;
}
div.thumbs div.thumb {
	float:left;position:relative;
	width:393px;
        height:300px;
	margin:5px;
}
div.thumbs div.image {
	position:absolute;
        top:0;left:0;
	width:393px;
	height:300px;
}
div.thumbs a.text {
	position:absolute;
	top:0;left:0;
	width:393px;
	height:300px;
	padding-bottom:10px;
	display:block;
	text-shadow: 0px 0px 13px rgba(0, 0, 0, 1);
	color:white;
	font-weight:bold;
	font-size:2em;
	text-align:center;
	text-decoration:none;
	opacity:0.6;
}
div.thumbs a.credit {
        position:absolute;
        top:280px;left:0;
        width:393px;
        display:block;
	text-shadow: 0px 0px 13px rgba(0, 0, 0, 1);
	color:white;
        font-weight:bold;
        text-decoration:none;
	text-align:right;
	padding-right:5px;
        opacity:0.7;
}

div.thumbs a.credit::before {
	content: "by ";
}

div.thumbs a.date {
        position:absolute;
        top:280px;left:0;
        width:393px;
        display:block;
        text-shadow: 0px 0px 13px rgba(0, 0, 0, 1);
        color:white;
        text-decoration:none;
        text-align:left;
	text-size:0.8em;
	padding-left:5px;
        opacity:0.9;
}

div.thumbs a:hover {
	opacity:1;
}


</style>
{/literal}

<p style="padding-left:10px;"> {$stats.images|thousends} images submitted in 2014{if $stats.myriads > 1}, 
	taken in <a href="/browser/#!/q=user{$user_id}/takenyear+%222014%22/display=group/group=myriad/n=4/gorder=images%20desc">{$stats.myriads} myriads</a>{/if}{if $stats.days > 1}, 
	on <a href="/browser/#!/q=user{$user_id}/takenyear+%222014%22/display=group/group=takenday/n=4/gorder=images%20desc">{$stats.days} days</a>{/if}{if $stats.personal}, 
	scoring <a href="/search.php?do=1&user_id={$user_id}&searchtext=takenyear:2014+-ftf:0">{$stats.personals} personal points</a>{/if}{if $stats.tpoints}, and
	<a href="/search.php?do=1&user_id={$user_id}&searchtext=takenyear:2014+points:tpoint">{$stats.tpoints|thousends} TPoints</a>{/if}.

	{if $stats.days > 3}
		<button onclick="location.href='/stuff/your-year.php?2014'">View Larger Selection</button>
	{/if}
</p>

<div class="thumbs">

	<div class="thumb">
	  <div class="inner">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname}" href="/photo/{$image->gridimage_id}" target="_parent">{$image->getFixedThumbnail(393,300)|replace:'src=':"`$src`="}</a></div>
	  <a href="/photo/{$image->gridimage_id}" class="text">{$image->title|escape:'html'}</a>
	  <a href="/photo/{$image->gridimage_id}" class="date">{$image->showday|date_format:"%a, %e %b"}</a>
	  <a href="/profile/{$image->user_id}" class="credit">{$image->realname|escape:'html'}</a>
	</div>


	{if $hits}
		{if $taken}
			Your most viewed 2014 images<br/><br/>
		{else}
			Your most viewed images in 2014 (show <a href="?taken=1">only taken in 2014</a>)<br/><br/>
		{/if}
		{foreach from=$hits item=image}
			<div style="float:left;position:relative; width:130px; height:130px">
			<div align="center">
			<a title="{$image->grid_reference} : {$image->title|escape:'html'} :: {$image->hits|thousends} hits" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120,false,true)}</a></div>
			</div>
		{/foreach}
	{/if}

</div>

<br style="clear:both"/>
<p style="padding-left:10px;">A zoomable map of your 2014 images (<a href="/mapper/quick.php?q=user{$user_id}+@takenyear+2014">Open Full-Screen</a>)</p>
<iframe src="http://www.geograph.org/leaflet/zoom.php?q=user{$user_id}+@takenyear+2014" style="border:0;width:100%;height:550px"></iframe>

<div class="interestBox">
	&middot; <a href="/browser/#!/q=user{$user_id}/takenyear+%222014%22">Browse Images</a>
	(<a href="/browser/#!/q=user{$user_id}/takenyear+%222014%22/display=group/group=monthname/n=4/gorder=alpha%20asc">By Month</a>, 
	<a href="/browser/#!/q=user{$user_id}/takenyear+%222014%22/display=group/group=county/n=4/gorder=images%20desc">By County</a>,
	<a href="/finder/groups.php?q=user{$user_id}&group=context_ids">By Context</a>);

	&middot; <a href="http://www.nearby.org.uk/geograph/trips.php?q=user{$user_id}+@takenyear+2014&images=1">Trip Log</a>
	(<a href="http://www.nearby.org.uk/geograph/trips-map.php?q=user{$user_id}+@takenyear+2014&images=1">Map</a>)

	&middot; <a href="/content/?user_id={$user_id}&scope=all">Collections</a>
</div>



<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
{if $src == 'data-src'}
	<script src="{"/js/lazy.js"|revision}" type="text/javascript"></script>
{/if}
<script src="/preview.js.php?d=preview" type="text/javascript"></script>

{else}
	<p>No images available</p>
{/if}

{include file="_std_end.tpl"}
