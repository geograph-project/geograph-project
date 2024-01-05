<html>
<head>
<title>Photos{$engine->criteria->searchdesc|escape:"html"}</title>
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />

<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>

<style>
{literal}
*{
	  box-sizing:border-box;
}
a img {
    box-shadow: 3px 3px 8px #999;
    border: 0;
    max-width: 100%;
    height: auto;
}
a.title {
		color:inherit;
    text-decoration:none;
    font-size:1.2em;
    font-weight: bold;
}
a.title:hover {
    text-decoration:underline;
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
.twocolsetup{
	box-sizing:border-box;
}
.twocolumn{
	float:left;
	width:100%;
	padding:0 8px;
}
.twocolumn h3{
	color: black;
  font-weight:bold;
	text-align: center;
	background: silver;
  border-radius: 10px;
  padding: 2px;
}
@media (min-width:800px){
	.twocolumn{
		width:50%;
	}
}
.likemenu {
  display: inline-block;
  position: relative;
  float:right;
  margin-bottom:10px;
}
.likemenu-content {
  display: none;
  position: absolute;
  right: 0;
  top: 0;
  white-space: nowrap;
  background-color: white;
  width: auto;
  overflow: auto;
  box-shadow: 0px 10px 10px 0px silver;
}
.likemenu:hover .likemenu-content {
  display: block;
}
.likemenu-content a {
  display: block;
  color: black;
  padding: 5px;
  text-decoration: none;
}
.likemenu-content a:hover {
  color: white;
  background-color: grey;
}
{/literal}
</style>

<script src="{"/slideshow.js"|revision}"></script>

</head>
{dynamic}
<body {if $maincontentclass}class="{$maincontentclass}" style="margin:2;border:0"{/if}>
{/dynamic}


<div class="twocolsetup">
<div class="twocolumn">
<div style="margin-left:2px">{if $engine->pageOneOnly && $engine->resultCount == $engine->numberofimages}{elseif $engine->islimited}<b>{$engine->resultCount|number_format}</b> {/if} Images<b>{$engine->criteria->searchdesc|escape:"html"}</b><br>Page {$engine->pagesString()}</div>
</div>
<div class="twocolumn">
<div style="float:right; margin-right: 2px">To normal <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass=slide" target="_top">Slide Show</a> mode</div>
</div>
</div>
<br style="clear:both; margin: 6px;"/>


{if $engine->resultCount}







<form class="buttons"><p align="center"><input type="button" id="prevautobutton" value="&lt; Auto" disabled="disabled" onclick="auto_slide_go(-1)"/>
<input type="button" id="prevbutton" value="&lt; Prev" disabled="disabled" onclick="slide_go(-1)"/>
<input type="button" id="stopbutton" value="stop" onclick="slide_stop()" disabled="disabled"/>
<input type="button" id="nextbutton" value="Next &gt;" onclick="slide_go(1)"/>
<input type="button" id="nextautobutton" value="Auto &gt;" onclick="auto_slide_go(1)"/></p>
</form>

{foreach from=$engine->results item=image name=results}

<div id="result{$smarty.foreach.results.iteration}"{if !$smarty.foreach.results.first} style="display:none;"{/if} class="{if $image->isLandscape()}photolandscape{else}photoportrait{/if}" style="position:relative">


<div style="float:left; margin-bottom:10px">{$smarty.foreach.results.iteration}/{$engine->numberofimages}</div>

<div style="float:right" class="likemenu"><img src="{$static_host}/img/thumbs.png"/>
<div class="likemenu-content">
  <div id="votediv{$image->gridimage_id}img"><a href="javascript:void(record_vote('img',{$image->gridimage_id},5,'img'));">Like image</a></div>
  {if $image->comment}<div id="votediv{$image->gridimage_id}desc"><a href="javascript:void(record_vote('desc',{$image->gridimage_id},5,'desc'));">Like description</a></div>{/if}
  </div></div>

<div class="shadow"><a title="{$image->title|escape:'html'} - click to view image page" href="/photo/{$image->gridimage_id}">{$image->getFull(true,true,true)|replace:'src=':"name=image`$smarty.foreach.results.iteration` src="}</a></div>


<div style="max-width:1024px; text-align:center; margin:auto">
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
{foreachelse}

{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	{if $engine->results}

		<div id="marker_start" style="display:none; text-align:center; background-color:#dddddd; padding:10px;">
		You have reached the beginning of this page of results.
		{if $engine->currentPage > 1}<br/><br/>
		<a href="/search.php?i={$i}&amp;page={$engine->currentPage-1}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}">&lt; &lt; previous page</a>
		{/if}</div>
		<div id="marker_end" style="display:none; text-align:center; background-color:#dddddd; padding:10px;">
		You have reached the end of this page of results.
		{if $engine->numberOfPages > $engine->currentPage}<br/><br/>
		<a href="/search.php?i={$i}&amp;page={$engine->currentPage+1}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}">next page &gt; &gt;</a>
		{/if}</div>
<script>//<![CDATA[
var resultcount = {$engine->numberofimages};
var hasnextpage = {if $engine->numberOfPages > $engine->currentPage}1{else}0{/if};
setTimeout("document.images[1].removeAttribute('loading')",600); //remove off second image, so it will start loading
{dynamic}
var delayinsec = {$user->slideshow_delay|default:5};
{/dynamic}
{literal}
if (window.location.hash == '#autonext') {
	setTimeout("auto_slide_go(1)",500);
}
{/literal}
 //]]></script>
 	<br style="clear:both"/>
	<div style="font-size:0.9em">&nbsp;Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})</div>
	{/if}
        <script>
        AttachEvent(window,'load',showMarkedImages,false);
        </script>
{else}
	{include file="_search_noresults.tpl"}
{/if}

<div style="font-size:0.8em;border-top:1px solid silver;margin-top:2px;padding-top:2px">| <a href="/">Geograph Britain and Ireland</a> homepage | Back to normal <a href="/search.php?i={$i}{if $engine->currentPage > 1}&amp;page={$engine->currentPage}{/if}&amp;displayclass=slide">Slide Show</a> mode | {if $engine->criteria->searchclass != 'Special'}<a href="/search.php?i={$i}&amp;form=advanced">Refine this search</a> |{/if} </div>


</body>
</html>
