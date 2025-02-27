{assign var="page_title" value="Search"}
{assign var="meta_description" value="Search and browse Geograph images, by keyword, location and more"}
{assign var="right_block" value="_block_recent.tpl"}
{include file="_std_begin.tpl"}
{dynamic}

{if $errormsg}
<p><b>{$errormsg}</b></p>
{/if}

<form method="get" action="/search.php">
        <input type="hidden" name="form" value="basic"/>
	<div class="tabHolder" style="text-align:right">
		<a href="/of/" class="tab">Quick search</a>
		<span class="tabSelected">Simple search</span>
		<a href="/search.php?form=text" class="tab">Advanced search</a>
		{if $user->registered}
			<a href="/search.php?form=check" class="tab">Check submissions</a>
		{/if}
		<a href="/finder/" class="tab">more...</a>

	</div>
	<div style="position:relative;" class="interestBox">
		<div style="position:relative;">
			<label for="searchq" style="line-height:1.8em"><b>Search</b>:</label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<small>(enter multiple keywords separated by spaces)</small> <a href="/article/Searching-on-Geograph" class="about" title="More details about Keyword Searching">About</a><br/>
			&nbsp;&nbsp;&nbsp;<i>For&nbsp;&nbsp;</i> <input id="searchq" type="text" name="q" value="{$searchtext|escape:"html"|default:"(anything)"}" size="30" style="font-size:1.3em" onfocus="if (this.value=='(anything)') this.value=''" onblur="if (this.value=='') this.value='(anything)'"/>
		</div>
		<div style="position:relative;margin-top:10px;margin-bottom:5px">
			<label for="searchlocation" style="line-height:1.8em">and/or a <b>Placename, Postcode, Grid Reference</b>:</label> <span id="placeMessage"></span> <br/>
			&nbsp;&nbsp;&nbsp;<i>near</i> <input id="searchlocation" type="text" name="location" value="{$searchlocation|escape:"html"|default:"(anywhere)"}" size="30" style="font-size:1.3em" onfocus="if (this.value=='(anywhere)') this.value=''" onblur="if (this.value=='') this.value='(anywhere)'"/>&nbsp;&nbsp;&nbsp;
			<input id="searchgo" type="submit" name="go" value="Search..." style="font-size:1.3em"/>
		</div>
	</div>
	<div style="font-size:0.8em;text-align:right"><a href="/browser/">Try the new Geograph Browser (includes search)</a></div>
</form>
<!--small>TIP: Search by tags, by surrounding by [...] in the 'for' box above. Eg enter <tt style="border:1px solid gray;padding:3px;">[footpath]</tt> to search for "footpath" tagged images.</small><br/-->

{/dynamic}
<ul style="margin-left:0;padding:0 0 0 1em;">

<li>Here are a couple of example searches:<br/>
<div style="float:left; margin-top:3px;  width:60%; position:relative">
	<ul style="margin-left:0;padding:0 0 0 1em;" class="touchPadding">
	{foreach from=$featured key=id item=row}
	<li><a href="/search.php?i={$row.id|escape:url}">{$row.searchdesc|regex_replace:'/^, /':''|escape:html}</a></li>
	{/foreach}
	<li><a href="/explore/searches.php" title="Show Featured Searches"><i><b>more examples...</b></i></a></li>
	</ul>
</div>
<div style="float:left; margin-top:3px;  width:40%; position:relative">
	<ul class="touchPadding">
        {foreach from=$taglist key=id item=count}
        <li>[<a href="/search.php?searchtext=[{$id|escape:url}]&amp;do=1&amp;displayclass=full" title="Show images tagged with {$id|escape:html}">{$id|escape:html}</a>] x{$count}</li>
        {/foreach}
        <li><a href="/tags/" title="Browse Tags"><i><b>more tags...</b></i></a></li>
	</ul>
</div><br style="clear:both;"/><br/>
</li>

{dynamic}
{if $user->registered}
	{if $recentsearchs}
	<li>And a list of your recent searches:
	<ul style="margin-left:-10px; margin-top:3px; padding:0 0 0 0em; list-style-type:none" class="touchPadding">
	{foreach from=$recentsearchs key=id item=obj}
	<li>{if $obj.favorite == 'Y'}<a href="/search.php?i={$id}&amp;fav=0" title="remove favorite flag"><img src="{$static_host}/img/star-on.png" width="14" height="14" alt="remove favorite flag" onmouseover="this.src='{$static_host}/img/star-light.png'" onmouseout="this.src='{$static_host}/img/star-on.png'"></a> <b>{else}<a href="/search.php?i={$id}&amp;fav=1" title="make favorite - starred items stay near top"><img src="{$static_host}/img/star-light.png" width="14" height="14" alt="make favorite" onmouseover="this.src='{$static_host}/img/star-on.png'" onmouseout="this.src='{$static_host}/img/star-light.png'"></a> {/if}{if $obj.searchclass == 'Special'}<i>{/if}<a href="/search.php?i={$id}" title="Re-Run search for images{$obj.searchdesc|escape:"html"}{if $obj.use_timestamp != '0000-00-00 00:00:00'}, last used {$obj.use_timestamp}{/if} (Display: {$obj.displayclass})">{$obj.searchdesc|escape:"html"|regex_replace:"/^, /":""|regex_replace:"/(, in [\w ]+ order)/":'</a><small>$1</small>'}</a>{if !is_null($obj.count)} [{$obj.count}]{/if}{if $obj.searchclass == 'Special'}</i>{/if}{if $obj.favorite == 'Y'}</b>{/if} {if $obj.edit}<a href="/refine.php?i={$id}" style="color:red">Edit</a>{/if}</li>
	{/foreach}
	{if !$more && !$all}
	<li><a href="/search.php?more=1" title="View More of your recent searches" rel="nofollow"><i>view more...</i></a></li>
	{/if}
	</ul><br/>
	</li>
	{elseif $recentlink}
		<li><a href="?recent=1">Click here to view your recent searches</a><br/><br/></li>
	{/if}
	<li>
	<div id="hidemarked">
		 <small>Marked Images <input type=button value="expand" onclick="show_tree('marked')"/></small>
	</div>
	<div style="position:relative; padding:10px; background-color:#eeeeee;display:none" id="showmarked">
	<div style="float:right"><a href="/article/The-Mark-facility" class="about">About</a></div>
	<small>Marked Images <span id="marked_number"></span>: <a href="javascript:void(displayMarkedImages())"><b>Display</b>/Export</a> &nbsp; <a href="/search.php?marked=1">View as Search Results</a> &nbsp; <a href="javascript:void(importToMarkedImages())">Import to List</a> &nbsp; (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear List</a>)<br/>
	</small><small style="font-size:0.6em">TIP: Add images to your list by using the [Mark] buttons on the "full + links" and "thumbnails + links"<br/> search results display formats, and the full image page.<br/><br/>
	<span style="color:red">Note: The Marked list is stored in a <b>temporary</b> cookie in your browser, and limited to about 500 images.<br/>
	You can use the 'View as Search Results' to save your current list to the server permanently.</small></div>
	</li>
	<script>
		AttachEvent(window,'load',showMarkedImages,false);
		{literal}
		function showMarkedDiv() {
			current = readCookie('markedImages');
			if (current && current != '') {
				show_tree('marked');
			}
		}
		{/literal}
		AttachEvent(window,'load',showMarkedDiv,false);
	</script>
{else}
	<li><i><a href="/login.php">Login</a> to see your recent and favorite searches.</i><br/></li>
{/if}
{/dynamic}
</ul>

{if $loadSearchesAsync}
<div id="recent-searches">
    <h3>Recent Searches</h3>
    <div id="searches-loading">Loading recent searches...</div>
    <ul id="searches-list" style="display:none">
    </ul>
    <div id="show-more" style="display:none">
        <a href="#" onclick="loadSearches(true); return false;">Show all searches</a>
    </div>
</div>

<script>
let searchesLoaded = false;

function loadSearches(all = false) {
    const list = document.getElementById('searches-list');
    const loading = document.getElementById('searches-loading');
    const showMore = document.getElementById('show-more');
    
    loading.style.display = 'block';
    list.style.display = 'none';
    
    fetch('/get_searches.php' + (all ? '?all=1' : ''), {
        headers: searchesLoaded ? {
            'If-Modified-Since': lastLoadTime
        } : {}
    })
    .then(response => {
        if (response.status === 304) {
            loading.style.display = 'none';
            list.style.display = 'block';
            return;
        }
        lastLoadTime = response.headers.get('Last-Modified');
        return response.json();
    })
    .then(searches => {
        if (!searches) return;
        
        list.innerHTML = searches.map(search => `
            <li>
                <a href="/search.php?i=${search.id}">${search.searchdesc}</a>
                ${search.favorite === 'Y' ? '⭐' : ''}
                ${search.edit ? '[<a href="/search.php?i=${search.id}&amp;edit=1">edit</a>]' : ''}
            </li>
        `).join('');
        
        loading.style.display = 'none';
        list.style.display = 'block';
        showMore.style.display = all ? 'none' : 'block';
        searchesLoaded = true;
    });
}

// Load searches when page loads
loadSearches();

// Refresh periodically
setInterval(() => loadSearches(), 60000);
</script>
{/if}

<div class="interestBox">
<ul class="lessIndent" style="margin-top:5px">

<li>If you are unable to find your location in our search above try {getamap} and return here to enter the <acronym style="border-bottom: red dotted 1pt; text-decoration: none;" title="look for something like 'Grid reference at centre - NO 255 075 GB Grid">grid reference</acronym>.<br/><br/></li>

<li><b>If you have a WGS84 latitude &amp; longitude coordinate</b>
		(e.g. from a GPS receiver, or from multimap site), then see our
		<a href="/latlong.php">Lat/Long to Grid Reference Convertor</a><br/><br/></li>


<li>You may prefer to browse images on a <a title="Geograph Map Browser" href="/mapbrowse.php">map of the British Isles</a>.<br/><br/></li>


<li>If you have just want to view a particular square, enter the Grid-Reference directly in the search box top right of the page.</li>

{if $enable_forums}
<li>Registered users can also <a href="/finder/discussions.php">search the forum</a>.</li>
{/if}
</ul>
</div>

   <br/><br/>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>

<script>{literal}
	function setLocationBox(value,wgs84,skipautoload) {
		 $("#searchlocation").val(value);
	}

$(function () {
	$("#searchlocation").autocomplete({
		minLength: 2,
                search: function(event, ui) {
                        if (this.value.search(/^\s*\w{1,2}\d{2,10}\s*$/) > -1) {
				ok = getWgs84FromGrid(this.value);
		                if (ok) {
					setLocationBox(this.value,ok);
				} else {
					$("#message").html("Does not appear to be a valid grid-reference '"+this.value+"'");
                                        $("#placeMessage").show().html("Does not appear to be a valid grid-reference '"+this.value+"'");
                                        setTimeout('$("#placeMessage").hide()',3500);
				}
                                $( "#location" ).autocomplete( "close" );
                                return false;
                        }
                },
                source: function( request, response ) {
			$.ajax('/finder/places.json.php?q='+encodeURIComponent(request.term), {
				success: function(data) {
					if (!data || !data.items || data.items.length < 1) {
						$("#message").html("No places found matching '"+request.term+"'");
			                        $("#placeMessage").show().html("No places found matching '"+request.term+"'");
				                setTimeout('$("#placeMessage").hide()',3500);
					        return;
					}
		                        var results = [];
					$.each(data.items, function(i,item){
				                results.push({value:item.gr+' '+item.name,label:item.name,gr:item.gr,title:item.localities});
					});
					results.push({value:'',label:'',title:data.query_info});
					results.push({value:'',label:'',title:data.copyright});
					response(results);
				}
			});
		},
                select: function(event,ui) {
                        setLocationBox(ui.item.value,false,false);
                        return false;
                }
	})
        .data( "autocomplete" )._renderItem = function( ul, item ) {
                var re=new RegExp('('+$("#location").val()+')','gi');
		return $( "<li></li>" )
			.data( "item.autocomplete", item )
			.append( "<a>" + item.label.replace(re,'<b>$1</b>') + " <small> " + (item.gr||'') + "<br>" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
			.appendTo( ul );
	};  
});

</script>{/literal}

{include file="_std_end.tpl"}
