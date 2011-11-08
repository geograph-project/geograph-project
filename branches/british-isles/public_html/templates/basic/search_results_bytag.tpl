{include file="_search_begin.tpl"}

{if $engine->resultCount}

{if $engine->fullText && $engine->criteria->sphinx.query && !$engine->criteria->sphinx.filters && !$engine->criteria->sphinx.impossible && !$engine->criteria->sphinx.x && !$engine->criteria->sphinx.bbox}

<div class="interestBox" style="width:200px;float:right">
	<div id="results" style="border-bottom:1px solid silver">
		Loading tags for {$engine->criteria->sphinx.query|escape:'html'} ...
	</div><br/>
	Tags are still a work in progress on the site, and many images still don't have tags, so the results here are <b>only approximate</b>.
</div>

<script>
var query = '{$engine->criteria->sphinx.query|escape:'javascript'}';
var iii = {$i};
{literal}

function redo(fragment) {
	if (fragment.indexOf('-') == 0) {
		fragment = fragment.replace(/^\-/,'-"').replace(/:/g,' ')+'"';
	} if (fragment.indexOf(':') > 0) {
		fragment = 'tags:"'+fragment.replace(/:/g,' ')+'"';
	}

	var url = "/search.php?text="+encodeURIComponent(query+" "+fragment)+"&i="+iii+"&redo=1&displayclass=bytag";
	window.location.href = url;
}


function startIt(query) {

	var url = "/finder/bytag.json.php?q="+encodeURIComponent(query)+"&callback=?";

	$.ajax({
		url: url,
		dataType: 'jsonp',
		jsonpCallback: 'serveCallback',
		cache: true,
		success: function(data) {
			if (data && data.length > 0) {
				str = "Click a Plus button to only show the images with that tag. Click the Minus to exclude. Hover over tag to see buttons.<br/><br/>";
				for(var tag_id in data) {
					text = data[tag_id].tag;
					if (data[tag_id].prefix)
						text = data[tag_id].prefix+':'+text;

					str = str + '<div style="position:relative;padding-left:2px;border-top:1px solid silver" onmouseover="showBtn('+tag_id+')" onmouseout="hideBtn('+tag_id+')"><div style="position:absolute;display:none;top:-2px;left:-40px" id="div'+tag_id+'"><input type="button" value="-" onclick="redo(\'-'+text+'\')" style="background-color:pink;font-size:0.6em"/><input type="button" value="+" onclick="redo(\''+text+'\')" style="background-color:lightgreen;font-size:0.6em"/></div> <span>'+text+"</span>"+((data[tag_id].count && data[tag_id].count > 1)?(" ["+data[tag_id].count+"]"):'')+"</div>";
				}
				str = str + "</ol>";
				$('#results').html(str);
			} else {
				$('#results').html("No Tags Found");
			}
		}
	});
}

function showBtn(tag_id) {
	$('#div'+tag_id).show().parent().css('backgroundColor','white');
}
function hideBtn(tag_id) {
	$('#div'+tag_id).hide().parent().css('backgroundColor','inherit');
}

jQl.loadjQ('https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js',function() {
	startIt(query);
});

{/literal}
</script>

{else}
<div class="interestBox" style="width:200px;float:right">
	Unfortunatly this search is not compatible with the tag browser right now. It works best with plain keyword searches.
</div>
{/if}


	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$engine->results item=image}
	{searchbreak image=$image table=true}
		<tr>
			<td align="center" valign="top">
				<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(120,120)}</a>
			</td>
			<td style="border-bottom:1px solid silver" valign="top">

	  {if $image->count}
	  	<div style="float:right;position:relative;width:130px;font-size:small;text-align:right">
	  		{$image->count|thousends} images in group
	  	</div>
	  {/if}

		<a title="view full size image" href="/photo/{$image->gridimage_id}"><b>{$image->title|escape:'html'}</b></a>
		by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a><br/>
		{if $image->moderation_status == 'geograph'}geograph{else}{if $image->moderation_status == 'pending'}pending{/if}{/if} for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		<i>{$image->dist_string}</i><br/>
		{if $image->imagetakenString}<small>Taken: {$image->imagetakenString}</small><br/>{/if}

		{if $image->excerpt}
		<div class="caption" title="{$image->comment|escape:'html'}" style="font-size:0.7em;">{$image->excerpt}</div>
		{elseif $image->imageclass}<small>Category: {$image->imageclass}</small>
		{/if}

			</td>
		</tr>
	{foreachelse}
	 	{if $engine->resultCount}
	 		<p style="background:#dddddd;padding:20px;"><a href="/search.php?i={$i}{if $engine->temp_displayclass}&amp;displayclass={$engine->temp_displayclass}{/if}"><b>continue to results</b> &gt; &gt;</a></p>
	 	{/if}
	{/foreach}
	</table>

	{if $engine->results}
	<p style="clear:both">Search took {$querytime|string_format:"%.2f"} secs, ( Page {$engine->pagesString()})
	{/if}
{else}
	{include file="_search_noresults.tpl"}
{/if}

{include file="_search_end.tpl"}
