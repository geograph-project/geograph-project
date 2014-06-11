{assign var="page_title" value="Recent Images"}
{if $inner}
{include file="_basic_begin.tpl"}
{else}
{include file="_std_begin.tpl"}
{/if}

<div style="width:200px;float:right;position:relative;text-align:center">
	<a href="/results/1522">View as search result</a>
</div>

<h2>Recently Submitted Images</h2>

<div style="width:200px;float:right;position:relative">
	<div class="interestBox">
	<b>Popular Tags</b><br/>
	<div id="results" style="border-bottom:1px solid silver">
		Loading tags...
	</div><br/>
	Tags are still a work in progress on the site, and many images still don't have tags, so the results here are <b>only approximate</b>.
	</div>
	<br/><br/>
	&middot; <a href="/browser/">Browse images more fully</a> &middot;
</div>

<div style="margin-right:220px;">

{foreach from=$results item=image}

	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}" target="_parent">{$image->getThumbnail(120,120,false,true)|replace:'src=':'src="/img/blank.gif" data-src='}</a></div>
	  </div>

{foreachelse}
	{if $q}
		<p><i>There is no content to display at this time.</i></p>
	{/if}
{/foreach}

</div>

<div style="margin-top:0px;clear:both"> 
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>	

{if $query_info}
	<p>{$query_info}</p>
{/if}


<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script src="/js/lazy.v1.js" type="text/javascript"></script>
<script src="/preview.js.php" type="text/javascript"></script>
<script>
var query = '';
var iii = 1522;
{literal}

$(function() {
	startIt(query);
});

function redo(fragment) {
	if (fragment.indexOf('-') == 0) {
		fragment = fragment.replace(/^\-/,'-"').replace(/:/g,' ')+'"';
	} if (fragment.indexOf(':') > 0) {
		fragment = 'tags:"'+fragment.replace(/:/g,' ')+'"';
	}

	var url = "/search.php?text="+encodeURIComponent(query+" "+fragment)+"&i="+iii+"&redo=1";
	window.location.href = url;
}

function startIt(query) {

	var url = "/finder/bytag.json.php?q="+encodeURIComponent(query)+"&callback=?&recent=1";

	$.ajax({
		url: url,
		dataType: 'jsonp',
		jsonpCallback: 'serveCallback',
		cache: true,
		success: function(data) {
			if (data && data.length > 0) {
				str = "";
				for(var tag_id in data) {
					text = data[tag_id].tag;
					if (data[tag_id].prefix)
						text = data[tag_id].prefix+':'+text;

					str = str + '<div style="position:relative;padding-left:2px;padding-top:2px;border-top:1px solid silver;cursor:pointer" onclick="redo(\''+text+'\')"><span>'+text+"</span>"+((data[tag_id].count && data[tag_id].count > 1)?(" ["+data[tag_id].count+"]"):'')+"</div>";
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

{/literal}
</script>


{if $inner}
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
