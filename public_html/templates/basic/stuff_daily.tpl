{assign var="page_title" value="Recent Images"}
{include file="_std_begin.tpl"}

{if $gallery}
<div style="width:200px;float:right;position:relative;text-align:center">
	<a href="/gallery.php?tab=daily">View in Gallery</a>
</div>

<div class="tabHolder">
	<a href="daily.php" class="tab">From the Homepage</a>
	<a class="tabSelected">From the Gallery</a>
</div>
{else}
<div style="width:200px;float:right;position:relative;text-align:center">
	<a href="/results/2087426">View as search result</a>
</div>

<div class="tabHolder">
	<a class="tabSelected">From the Homepage</a>
	<a href="?gallery=1" class="tab">From the Gallery</a>
</div>
{/if}
<div class="interestBox">
	<h2>Recently Featured Images</h2>
</div>

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

<div class="thumbs">

{foreach from=$results item=image}

	<div class="thumb">
	  <div class="inner">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname}" href="/photo/{$image->gridimage_id}" target="_parent">{$image->getFixedThumbnail(393,300)|replace:'src=':"`$src`="}</a></div>
	  <a href="/photo/{$image->gridimage_id}" class="text">{$image->title|escape:'html'}</a>
	  <a href="/photo/{$image->gridimage_id}" class="date">{$image->showday|date_format:"%a, %e %b"}</a>
	  <a href="/profile/{$image->user_id}" class="credit">{$image->realname|escape:'html'}</a>
	</div>

{foreachelse}
	{if $q}
		<p><i>There is no content to display at this time.</i></p>
	{/if}
{/foreach}

</div>

<br style="clear:both"/>
<br/>
<div class="interestBox">
        <a href="{if $gallery}/gallery.php?tab=daily{else}/results/2087426{/if}">View more...</a>
</div>


<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
{if $src == 'data-src'}
	<script src="http://{$static_host}/js/lazy.v2.js" type="text/javascript"></script>
{/if}
<script src="/preview.js.php?d=preview" type="text/javascript"></script>

{include file="_std_end.tpl"}
