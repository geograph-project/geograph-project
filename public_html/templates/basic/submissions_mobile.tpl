{if !$inner}
{assign var="page_title" value="Recent Uploads"}
{assign var="meta_description" value="Lists your most recent submissions for easy editing and review"}
{include file="_basic_begin.tpl"}

<style>
{literal}
.tabHolder {
	text-align:center;
}
.tab {
	border-bottom:1px solid gray;
	border-radius:5px;
}
@media screen and (max-width: 600px) {
	input, textarea {
	     width:100%;
	     display:block;
	}
	div.shadow {
	     width:100% !important;
	}
}
@media screen and (min-width: 1100px) {
	input[type=text], textarea {
		width:900px;
		font-size:1em; /* to match the text on photo page */
		max-width:90vw;
	}
	input[type=text] {
		font-size:1.2em;
	}
}
h3 {
	margin:0;
}
{/literal}
</style>

<h3>Recent Uploads{if $criteria}<small style="font-weight:normal">, before: {$criteria|escape:'html'}</small>{/if}</h3>

	<div id="results">
{/if}

	{foreach from=$images item=image}
	  <form action="/editimage.php?id={$image->gridimage_id}&amp;thumb=1" method="post" name="form{$image->gridimage_id}" target="editor" style="clear:both;border-top:1px solid blue; padding:8px 0; background-color:#eee">
	  <div class="shadow" style="float:left; position:relative; width:226px; text-align:center">
		<a title="{$image->title|escape:'html'} - click to view full size image" href="/photo/{$image->gridimage_id}">{$image->getThumbnail(213,160,false,true,$src)}</a><br/>

		<div style="padding-top:7px">
			[[[{$image->gridimage_id}]]]
			Status: {$image->moderation_status}
			 [<a href="javascript:void(markImage({$image->gridimage_id}));" id="mark{$image->gridimage_id}">Mark</a>]
		</div>

	  </div>
	  <div style="float:left; position:relative; text-align:center">
		<a name="{$image->gridimage_id}"></a><input type="text" name="title" size="64" value="{$image->title|escape:'html'}" spellcheck="true" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">
		for <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
		{if $image->imagetakenString}&middot; Taken: {$image->imagetakenString}<br/>{/if}
		{if $image->imageclass}&middot; Category: {$image->imageclass}{/if}

		<div><textarea name="comment" {if $image->comment|strlen > 300}rows="10"{else}rows="4"{/if} cols="70" spellcheck="true" placeholder="[image description]" onchange="this.style.backgroundColor=(this.value!=this.defaultValue)?'pink':''">{$image->comment|escape:'html'}</textarea><input type="submit" name="create" value="Continue &gt;" onclick="mark_color(this.form,'yellow')"/>{if $image->moderation_status == 'pending' || $user->stats.images > 100}<input type="submit" name="apply" value="Apply changes" onclick="mark_color(this.form,'lightgreen')"/>{/if}

		<div class="tabHolder" style="font-size:1em;padding-top:10px">		
			<a class="tab nowrap" id="tab{$image->gridimage_id}1" onclick="if(tabClick2('tab{$image->gridimage_id}','div{$image->gridimage_id}',1,3)) open_tagging({$image->gridimage_id},'{$image->grid_reference}','');">Tags</a>&nbsp;
                        <a class="tab nowrap" id="tab{$image->gridimage_id}2" onclick="if(tabClick2('tab{$image->gridimage_id}','div{$image->gridimage_id}',2,3)) open_shared({$image->gridimage_id},'{$image->grid_reference}','');">Shared Descriptions<span id="c{$image->gridimage_id}"></span></a>
                        {if $image->grid_reference}
                                <a class="tab nowrap" id="tab{$image->gridimage_id}3" onclick="if (tabClick2('tab{$image->gridimage_id}','div{$image->gridimage_id}',3,3)) open_nearby({$image->gridimage_id},'{$image->grid_reference}','');">Used Nearby</a>&nbsp;
                        {/if}
		</div>

		</div>
	  </div><br style="clear:both;"/>
		<div class="interestBox" id="div{$image->gridimage_id}1" style="display:none">
			<iframe src="about:blank" height="300" width="100%" id="tagframe{$image->gridimage_id}">
			</iframe>
		</div>

		<div class="interestBox" id="div{$image->gridimage_id}2" style="display:none">
			<iframe src="about:blank" height="400" width="100%" id="shareframe{$image->gridimage_id}">
			</iframe>
		</div>

		<div class="interestBox" id="div{$image->gridimage_id}3" style="display:none">
			<iframe src="about:blank" height="300" width="100%" id="nearframe{$image->gridimage_id}">
			</iframe>
		</div>

	  </form>

	{foreachelse}
		nothing to see here
	{/foreach}

{if $inner}
	{if $next}
		<a href="{$script_name}?next={$next|escape:'url'}&amp;inner=1&amp;mobile=1" onclick="return loadMore(this)" style=font-size:1.1em>Load more...</a>
	{/if}

{else}

	</div>

	<script>
	AttachEvent(window,'load',showMarkedImages,false);
	</script>


<br/><br/>
{if $prev || $next}
	<div class="interestBox navigation">Navigation: <b>|
	{if $prev == 1}
		<a href="{$script_name}?mobile=1">Previous</a> |
	{elseif $prev}
		<a href="{$script_name}?next={$prev|escape:'url'}&amp;mobile=1">Previous</a> |
	{/if}
	{if $next}
		<a href="{$script_name}?next={$next|escape:'url'}&amp;mobile=1">Next</a> |

		- or - 
		<a href="{$script_name}?next={$next|escape:'url'}&amp;inner=1&amp;mobile=1" onclick="return loadMore(this)">Load more...</a>
		

	{/if}</b>
	</div>
{/if}

<b>Key</b>
<ul>
	<li>White, the box hasn't been changed</li>
	<li><span style="background-color:pink">Pink</span>, the contents of the box has been changed (but not saved)</li>
	<li><span style="background-color:yellow">Yellow</span>, you've opened the edit page (unknown if has been submitted)</li>
	<li><span style="background-color:lightgreen">Green</span>, the contents of the box been saved</li>
</ul>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript">
{literal}

function loadMore(that) {
	//$('#results').load(that.href); //does a .html(..) not .append(..)

        jQuery.ajax({
            url: that.href,
            type: 'GET',
            dataType: "html",
        }).done(function (responseText) {
	    $('#results').append(responseText);
            if (initLazy && typeof initLazy == 'function') {
		initLazy();
            }
	});

	$(that).remove();
	$('div.interestBox.navigation').hide(); //they dont work any more!
	return false;
}

function tabClick2(tabname,divname,num,count) {
	var ret = true;
	if (document.getElementById(tabname+num) && document.getElementById(tabname+num).className == 'tabSelected') {
		num = 99;
		ret = false;
	}
	tabClick(tabname,divname,num,count);
	return ret;
}

function mark_color(form,color) {
	if (form.elements['title'].value!=form.elements['title'].defaultValue)
		form.elements['title'].style.backgroundColor = color;
	if (form.elements['comment'].value!=form.elements['comment'].defaultValue)
		form.elements['comment'].style.backgroundColor = color;
}
function open_shared(gid,gr,extra) {

	if (extra == '&tab=suggestions') {
		var thatForm = document.forms['form'+gid];

		if (thatForm.elements['title']) {
			str = thatForm.elements['title'].value;
		}
		if (thatForm.elements['comment']) {
			str = str + ' '+ thatForm.elements['comment'].value;
		}
		if (thatForm.elements['imageclass']) {
			str = str + ' '+ thatForm.elements['imageclass'].value;
		}

		extra= extra + "&corpus="+encodeURIComponent(str.replace(/[\r\n]+/,' '));
	}


	document.getElementById('shareframe'+gid).src='/submit_snippet.php?gridimage_id='+gid+'&gr='+gr+extra;
	return false;
}
function open_tagging(gid,gr,extra) {

        var thatForm = document.forms['form'+gid];
        if (thatForm.elements['title'] && thatForm.elements['title'].value && thatForm.elements['title'].value.length > 0) {
                extra = extra + "&form=submissions&title="+encodeURIComponent(thatForm.elements['title'].value);
        }
        if (thatForm.elements['comment'] && thatForm.elements['comment'].value && thatForm.elements['comment'].value.length > 0) {
                extra = extra + "&form=submissions&comment="+encodeURIComponent(thatForm.elements['comment'].value);
        }
	document.getElementById('tagframe'+gid).src='/tags/tagger.php?gridimage_id='+gid+'&gr='+gr+extra;
	return false;
}
function open_nearby(gid,gr,extra) {
        var thatForm = document.forms['form'+gid];
	if (document.getElementById('nearframe'+gid).src=='about:blank')
		document.getElementById('nearframe'+gid).src='/finder/used-nearby.php?gridimage_id='+gid+'&gr='+gr+extra;
	return false;
}

function loadSnippetCount(random) {
	var ids = new Array();
	for(q=0;q<document.forms.length;q++) {
		if (document.forms[q] && document.forms[q].name && (match = document.forms[q].name.match(/form(\d+)/)))
			ids.push(match[1]);
	}
	var script = document.createElement("script");
	script.setAttribute("src", "/api/Snippet/"+ids.join(',')+'?output=json&callback=showSnippetCount'+(random?"&rnd="+Math.random():''));
	script.setAttribute("type", "text/javascript");
	document.documentElement.firstChild.appendChild(script);
}
function showSnippetCount(data) {
	if (data.error) {
		alert(data.error);
	} else {

		for(var gid in data) {
			document.getElementById('c'+gid).innerHTML = " ["+data[gid]+"]";
			//document.getElementById('c'+gid).style.backgroundColor='pink';
		}
	}

}
 AttachEvent(window,'load',loadSnippetCount,false);
{/literal}
</script>

{if $src == 'data-src'}
	 <script src="{"/js/lazy.js"|revision}" type="text/javascript"></script>
{/if}

{/if}
