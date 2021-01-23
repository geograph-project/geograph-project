{include file="_search_begin.tpl"}

{if $engine->resultCount}

{literal}
<style type="text/css">
#maincontent a img {
        margin:0 22px 22px 22px;
}
.content_photoblack #maincontent a img {
	padding:0;
}
#maincontent .votediv a img {
	margin:0;
	padding:2px;
	border:0;
        box-shadow: none;
}
#maincontent td {
	font-family:verdana,Georgia;
	padding-bottom:22px;
}
#maincontent span.year {
	display:block;
	padding:2px 10px;
	float:right;
	font-size:1.6em;
	color:#666;
	position:relative;
	z-index:2000;
}
.content_photogray #maincontent span.year {
	color:#eae8e8;
}
.content_photoblack #maincontent span.year {
	color:#a9a9a9;
}
#maincontent td b, 
#maincontent td a {
	color:black;
        text-decoration:none;
}
#maincontent td a:hover {
        text-decoration:underline;
}
.content_photogray #maincontent td b, .content_photogray #maincontent td a {
	color:silver
}
.content_photoblack #maincontent td b, .content_photoblack #maincontent td a {
	color:#a5a5a5
}

#maincontent td a.title {
	display:block;
	background-color:#ddd;
        font-size:1.5em;
	padding:5px;
	margin-left:-22px;
	padding-left:22px;
	position:relative;
	z-index:1000;
	box-shadow: 3px 3px 8px #999;
}
.content_photogray #maincontent td a.title {
	background-color:#969696;
	color:#dadada;
	box-shadow: 3px 3px 8px #333
}
.content_photoblack #maincontent td a.title {
	background-color:#444;
	color:#cccbcb;
	box-shadow: none;
}
#maincontent td div.comment {
	margin:20px 0 10px;
	color:#333;
	font-size:0.9em;
}
.content_photogray #maincontent td div.comment {
	color:#c7c6c6;
}
.content_photoblack #maincontent td div.comment {
        color:#9e9e9e;
}
#maincontent td div {
        font-size:0.9em;
}
#mapdiv {
        position:fixed;
        top:0;
        right:0;
        padding-top:20px;
	padding-right:20px;
	border-radius:2px;
	z-index:10000;
}
</style>

<script type="text/javascript">
        function showMap(geo) {
                bits = geo.split(/ /);
                url = "https://maps.googleapis.com/maps/api/staticmap?markers=size:med|"+bits[0]+","+bits[1]+"&zoom=7&size=200x200&style=feature:administrative.country%7Celement:labels%7Cvisibility:off&key={/literal}{$google_maps_api3_key}{literal}";

                document.images['map'].src= url;
                document.getElementById("mapdiv").style.display = '';
        }
        function hideMap() {
                 document.getElementById("mapdiv").style.display = 'none';
        }
</script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script>

jQuery(document).ready( function() {
        hideMap();
});

</script>
{/literal}


<script src="{"/js/lazy.js"|revision}" type="text/javascript"></script>
<div id="mapdiv"><img src="{$static_host}/img/blank.gif" name="map"/></div>

<table border="0" cellspacing="0" cellpadding="0" class="shadow shadow_large">
        {foreach from=$engine->results item=image}
        {searchbreak image=$image table=true}
    <tr>
        <td valign="top" align="right"><a href="/photo/{$image->gridimage_id}" title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname|escape:'html'} {$image->dist_string}{if $image->count} - {$image->count|thousends} images in group{/if}" onmouseover="showMap('{$image->wgs84_lat} {$image->wgs84_long}')" onmouseout="hideMap()">{$image->getFull()|replace:'src=':'src="/img/blank.gif" data-src='}</a></td>
        <td valign="top" align="left" class="lighter">
	    {if $image->imagetaken > 1 && $image->imagetaken < 2017}<span class="year" title="year photo taken"> {$image->imagetaken|truncate:4:''}</span>{/if}
            <a class=title href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
            {if $image->comment}
                <div class=comment>{$image->comment|escape:'html'|nl2br|geographlinks}</div>
            {/if}
	    {if $image->imagetaken > 1}
		<small><br/>Image taken: <b>{$image->imagetakenString}</b></small>
            {/if}
            <br/><br/>
            <div>&copy; Copyright <b><a href="{$image->profile_link}">{$image->realname|escape:'html'}</a></b> and licensed for reuse under <a href="http://creativecommons.org/licenses/by-sa/2.0/">a Creative Commons licence</a> </div>
            <br/><br/>

	    <div id="votediv{$image->gridimage_id}" class=votediv>Rate this image: {votestars id=$image->gridimage_id type="i`$i`"}</div>
	    <br/>

	    <div>[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>] Square: <a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></div>
        </td>
    </tr>
        {foreachelse}
                {if $engine->resultCount}
                        <div class="interestBox"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></div>
                {/if}
        {/foreach}
</table>
  
	<div style="position:relative">
	<br/><br/>
        <div class="interestBox" style="font-size:0.8em">
        <div style="float:right"><a href="/article/The-Mark-facility" class="about">About</a></div>
        <b>Marked Images</b><span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1&amp;displayclass={if $engine->temp_displayclass}{$engine->temp_displayclass}{else}{$engine->criteria->displayclass}{/if}">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
        &nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(markAllImages('Mark'))">Mark all images on <b>this</b> page</a> (<a href="javascript:void(markAllImages('marked'))" style="color:red">Unmark all on this page</a>)</div></div>
        <script>
        AttachEvent(window,'load',showMarkedImages,false);
        </script>


        {if $engine->results}
        <p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
        {/if}
{else}
        {include file="_search_noresults.tpl"}
{/if}


{include file="_search_end.tpl"}

