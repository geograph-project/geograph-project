{assign var="maincontentclass" value="content_photoblack"}
{include file="_search_begin.tpl"}

{if $engine->resultCount}

{literal}
<style type="text/css">
#maincontent {
        background-color:black;
        color:silver;
        font-family:verdana;
}
#maincontent a {
        color:white;
        text-decoration:none;
}
#maincontent a:hover {
        color:cyan;
}
#maincontent a img {
        border:1px solid #666666;
        border-radius: 7px;
        padding:8px;
        margin:22px;
}
#maincontent td {
        background-color:black;
}
#maincontent td div {
        font-size:0.8em;
        width:250px;
        margin-left:auto;
        margin-right:auto;
}
#mapdiv {
        position:fixed;
        top:0;
        right:0;
        padding-top:20px;
	padding-right:20px;
	border-radius:2px;
}
#geograph body {
	background-color:black;
}
#maincontent_block {
	border-left:0;
}
</style>

<script type="text/javascript">
        function showMap(geo) {
                bits = geo.split(/ /);
                url = "http://maps.google.com/maps/api/staticmap?markers=size:med|"+bits[0]+","+bits[1]+"&zoom=7&size=200x200&sensor=false&style=feature:administrative.country%7Celement:labels%7Cvisibility:off";

                document.images['map'].src= url;
                document.getElementById("mapdiv").style.display = '';
        }
        function hideMap() {
                 document.getElementById("mapdiv").style.display = 'none';
        }
</script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script src="/js/lazy.v2.js" type="text/javascript"></script>
<script>

jQuery(document).ready( function() {
        hideMap();
});

</script>
<div id="mapdiv"><img src="/img/blank.gif" name="map"/></div>
{/literal}

<table border="0" cellspacing="0" cellpadding="5">
        {foreach from=$engine->results item=image}
        {searchbreak image=$image table=true}
    <tr>
        <td valign="middle" align="center"><a href="/photo/{$image->gridimage_id}" title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname|escape:'html'} {$image->dist_string}{if $image->count} - {$image->count|thousends} images in group{/if}" onmouseover="showMap('{$image->wgs84_lat} {$image->wgs84_long}')" onmouseout="hideMap()">{$image->getFull()|replace:'src=':'src="/img/blank.gif" data-src='}</a></td>
        <td valign="middle" align="center">
            <a href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>{if $image->imagetaken > 1 && $image->imagetaken < 2012}<span class="year" title="year taken" style="color:gray;font-size:1.5em">, {$image->imagetaken|truncate:4:''}</span>{/if}<br/>
            {if $image->comment}
                <br/><small>{$image->comment|escape:'html'|nl2br|geographlinks}</small>
            {/if}
	    {if $image->imagetaken > 1}
		<br/><small><br/>Image taken: {$image->imagetaken|date_format:"%e %b, %Y"}</small>
            {/if}
            <br/><br/>
            <div>&copy; Copyright <b><a href="{$image->profile_link}">{$image->realname|escape:'html'}</a></b> and licensed for reuse under <a href="http://creativecommons.org/licenses/by-sa/2.0/">a Creative Commons licence</a> </div>
            <br/><br/>
	    <div>[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]</div>
        </td>
    </tr>
        {foreachelse}
                {if $engine->resultCount}
                        <p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
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

