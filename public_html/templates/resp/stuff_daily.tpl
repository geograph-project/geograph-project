{assign var="page_title" value="Recent Images"}
{include file="_std_begin.tpl"}

<br>
<div class="tabHolder">
	<a href="daily.php" class="tab{if $tab eq 'potd'}Selected{/if}">From the Homepage</a>
	<a href="?tab=gallery" class="tab{if $tab eq 'gallery'}Selected{/if}">From the Gallery</a>
	<a href="?tab=mixed" class="tab{if $tab eq 'mixed'}Selected{/if}">Mixed Selection</a>
	{if $tab eq 'gallery'}
		<a href="/gallery.php?tab=daily" class="tab">View in Gallery</a>
	{elseif $tab eq 'potd'}
		<a href="/results/2087426" class="tab">View as search result</a>
	{/if}
</div>

<div class="interestBox">
	<h2>Recently Featured Images</h2>
</div>

{literal}
<style>
div.thumbs {
	padding:20px;
	box-sizing:border-box;
}
@media only screen and (max-width: 612px) {
	div.thumbs {
		padding:2px;
	}
}
div.thumbs div.thumb {
	float:left;position:relative;
	width:393px;
	max-width:100%;
	margin:5px;
}
div.thumbs div.thumb img {
	max-width:100%;
	height:auto;
}
div.thumbs a.text {
	position:absolute;
	top:0;left:0;
	width:393px;
	max-width:100%;
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
        bottom:15px; right:5px;

        display:block;
	text-shadow: 0px 0px 13px rgba(0, 0, 0, 1);
	color:white;
        font-weight:bold;
        text-decoration:none;
	text-align:right;
        opacity:0.7;
}

div.thumbs a.credit::before {
	content: "by ";
}

div.thumbs a.date {
        position:absolute;
        bottom:15px;left:5px;

        display:block;
        text-shadow: 0px 0px 13px rgba(0, 0, 0, 1);
        color:white;
        text-decoration:none;
        text-align:left;
        opacity:0.9;
}

div.thumbs a:hover {
	opacity:1;
}


</style>
{/literal}

<div class="thumbs shadow">

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

{if $tab eq 'potd'}
<div class="interestBox">
        <a href="{if $gallery}/gallery.php?tab=daily{else}/results/2087426{/if}">View more...</a>
</div>
{/if}

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
{if $src == 'data-src'}
	<script src="{"/js/lazy.js"|revision}" type="text/javascript"></script>
{/if}
<script src="/preview.js.php?d=preview" type="text/javascript"></script>

{include file="_std_end.tpl"}
