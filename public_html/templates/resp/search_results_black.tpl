{include file="_search_begin.tpl"}

{if $engine->resultCount}

{literal}
<style type="text/css">
#maincontent *{
	  box-sizing:border-box;
}
#maincontent a img {
    box-shadow: 3px 3px 8px #999;
    border: 0;
    max-width: 100%;
    height: auto;
}
#maincontent a.title {
		color:inherit;
    text-decoration:none;
    font-size:1.2em;
    font-weight: bold;
}
#maincontent a.title:hover {
    text-decoration:underline;
}
#maincontent hr{
    color:silver;
}
#mapdiv {
    position:fixed;
    top:0;
    right:0;
    padding-top:20px;
    padding-right:20px;
    border-radius:2px;
}
.georivercols{
	box-sizing:border-box;
}
.georiver{
	float:left;
	width:100%;
	padding:18px 0px;
	text-align: center;
}
.georiver:first-child {
	text-align:right;
}

@media (min-width:800px){
        .georiver:first-child {
                width:60%;
        }
        .georiver:last-child {
                width:40%;
        }
	#maincontent hr{
		display:none;
	}
}
@media (min-width:1000px){
	.georiver{
		width:50% !important;
		padding:18px 8px;
        }
}

.copyrightmessage {
    padding-top: 10px;
    padding-bottom: 10px;
}
.yeartaken {
    float:right;
    font-family:verdana;
    font-size:1.0em;
}
.gridref {
    float:left;
    font-family:verdana;
    font-size:1.0em;
}
.gridref a {
    color:black;
    text-decoration:none;
}
.gridref a:hover {
    text-decoration:underline;
}
.takendate {
    padding-top: 10px;
    padding-bottom: 10px;
}
.comment {
    font-size:1.0em;
    padding-top: 10px;
    padding-bottom: 10px;
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
{/literal}

<div id="mapdiv" style="display:none"><img src="{$static_host}/img/blank.gif" name="map"/></div>


{foreach from=$engine->results item=image}
{searchbreak image=$image}


<div class="georivercols">
<div class="georiver">

<a href="/photo/{$image->gridimage_id}" title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname|escape:'html'} {$image->dist_string}{if $image->count} - {$image->count|thousends} images in group{/if}" onmouseover="showMap('{$image->wgs84_lat} {$image->wgs84_long}')" onmouseout="hideMap()">{$image->getFull()|replace:'src=':'loading="lazy" src='}</a>


</div>

<div class="georiver">


<div class="gridref"><a href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a></div>

{if $image->imagetaken > 1}<div class="yeartaken" title="year taken">{$image->imagetaken|truncate:4:''}</div>{/if}

<br style="clear:both; margin: 6px;"/>

<div><a href="/photo/{$image->gridimage_id}" class=title>{$image->title|escape:'html'}</a></div>



{if $image->comment}
<div class="comment">{$image->comment|escape:'html'|nl2br|geographlinks}</div>
{/if}



<div class="copyrightmessage">&copy; Copyright <b><a href="{$image->profile_link}">{$image->realname|escape:'html'}</a></b> and licensed for reuse under a <a href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons licence</a></div>

{if $image->imagetaken > 1}
<div class="takendate">Image taken: {$image->imagetakenString}</div>
{/if}

<div class="mark">[<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]</div>




</div>
</div>

<br style="clear:both"/>
<hr/>

{foreachelse}
{if $engine->resultCount}
<div class="interestBox"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></div>
{/if}
{/foreach}

  
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

