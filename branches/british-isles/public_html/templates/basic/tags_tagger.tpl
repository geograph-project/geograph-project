{assign var="page_title" value="Tags"}
{include file="_basic_begin.tpl"}
<div style="padding:6px">
<style>{literal}
.tagPublic span,.tagPublic a.taglink {
	background-color:lightgreen !important;
}

.tagPrivate span,.tagPrivate a.taglink {
	background-color:pink !important;
}
.tagGeneral span,.tagGeneral a.taglink {
	background-color:yellow !important;
}
{/literal}</style>
{dynamic}

	{if $message}
		<p style="color:red">{$message|escape:"html"}</p>
	{/if}

<form method="post" action="{$script_name}?gr={$gr|escape:'url'}&amp;upload_id={$upload_id|escape:'url'}&amp;gridimage_id={$gridimage_id}" style="background-color:#f0f0f0;" name="theForm">
	<div id="savebutton" style="float:right;display:none">
		<input type="submit" name="save" value="Save Changes" style="font-size:1.2em"/>
		<div id="autoSave" style="font-size:0.7em"></div>
	</div>

	{if $gridimage_id}<input type="hidden" name="gridimage_id" value="{$gridimage_id}" />{/if}
	{if $ids}<input type="hidden" name="ids" value="{$ids|escape:'html'}" />{/if}
	{if $gr}<input type="hidden" name="gr" value="{$gr|escape:'html'}" />{/if}

	<div style="float:left;width:40%;font-size:0.9em">

			<b>New</b>: <input type="text" name="__newtag" size="22" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} {/literal}"/> <input type="button" value="Add" onclick="useTags(this.form.elements['__newtag'])"/><br/>
			<input type=checkbox id="mine_checkbox" onclick="loadTagSuggestions(this.form.elements['__newtag'],event);"/> <label for="mine_checkbox">Only suggest tags I have already used</label><br/><br/>

			<b>Current Tags</b>:<br/>
			<span id="tags">{foreach from=$used item=item name=used}
			<span class="tag {if !$item.is_owner}tagGeneral{elseif $item.status eq 2}tagPublic{else}tagPrivate{/if}" id="tagid:{$item.tag_id}"{if $is_owner} onclick="toggleTag('id:{$item.tag_id}');" ondblclick="editTag('id:{$item.tag_id}','{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'quotes'}');"{/if}>
			<span>{if $item.prefix && $item.prefix!='term' && $item.prefix != 'wiki' && $item.prefix != 'cluster'}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}</span>
			{if $item.is_owner}
				<input type="hidden" name="tag_id[]" id="tagiid:{$item.tag_id}" class="tagi" value="id:{$item.tag_id}"/>
				<input type="hidden" name="mode[]" id="tagmid:{$item.tag_id}" value="{$item.status}"/>
				<a href="javascript:removeTag('id:{$item.tag_id}')" class="delete">X</a>
			{/if}
			</span>&nbsp;
			{foreachelse}
				{if $gridimage_id}{if $is_owner}<i><big>No tags for this image yet</big> - <b>Please add at least one</b></i>{else}<i>none</i>{/if}{else}<i>unknown</i>{/if}
			{/foreach}</span>

	</div>

</form>




	<div style="float:left;width:59%;font-size:0.9em">
		{assign var="tab" value="1"}
		{if $topics && !$suggestions}
			{assign var="tab" value="2"}
		{/if}
		{assign var="ctab" value="1"}
		<div class="tabHolder">
			{if $tree}
				<div style="margin-left:7em"><b>Geographical Context</b>: <small>(pick at <B>least one</b>)</small></div>
			{/if}
			<a class="tab{if $tab == 1}Selected{/if} nowrap" id="tab1" onclick="tabClick('tab','div',1,10)">Suggestions</a>&nbsp;
			{if $topics || $topicstring}
				{assign var="ctab" value=$ctab+1}
				<a class="tab{if $tab == $ctab}Selected{/if} nowrap" id="tab{$ctab}" onclick="tabClick('tab','div',{$ctab},10)">Topics</a>&nbsp;
			{/if}
			{if $recent}
				{assign var="ctab" value=$ctab+1}
				<a class="tab{if $tab == $ctab}Selected{/if} nowrap" id="tab{$ctab}" onclick="tabClick('tab','div',{$ctab},10)">Recent</a>&nbsp;
			{/if}
			{if $gr}
				{assign var="ctab" value=$ctab+1}
				<a class="tab{if $tab == $ctab}Selected{/if} nowrap" id="tab{$ctab}" onclick="tabClick('tab','div',{$ctab},10)">Nearby</a>&nbsp;
			{/if}
			{foreach from=$tree key=key item=item name=tree}
				{assign var="ctab" value=$ctab+1}
				<a class="tab{if $tab == $ctab}Selected{/if} nowrap" id="tab{$ctab}" onclick="tabClick('tab','div',{$ctab},10)">{$key}</a>&nbsp;
			{/foreach}
			{assign var="ctab" value=$ctab+1}
			<a class="tab{if $tab == $ctab}Selected{/if} nowrap" id="tab{$ctab}" onclick="tabClick('tab','div',{$ctab},10)">Buckets</a>&nbsp;

		</div>
		{assign var="ctab" value="1"}
		<div id="div1" class="interestBox" style="{if $tab != $ctab}display:none{/if}">
			{foreach from=$suggestions item=item name=used}
				<span class="tag" id="suggestion{$item.tag|escape:'html'}">
				<a href="javascript:addTag('{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}','{$item.tag|escape:'html'}');" class="taglink">{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}</a>
				</span> &nbsp;
			{foreachelse}
				<i>none</i>
			{/foreach}
			{if $suggestions}<br/><small>These are guessed tags based on words in title/description</small>{/if}
		</div>

		{if $topicstring}
                        {assign var="ctab" value=$ctab+1}
                        <div id="div{$ctab}" class="interestBox" style="{if $tab != $ctab}display:none{/if}">
				<div id="topics">Loading...</div>
                                <small>These are guessed tags based on words in title/description</small>
                        </div>
			<script>{literal}
			AttachEvent(window,'load',function() {
				loadTagTopics({/literal}'{$topicstring|escape:'quotes'}'{literal});
			},false);
			{/literal}</script>
		{elseif $topics}
			{assign var="ctab" value=$ctab+1}
			<div id="div{$ctab}" class="interestBox" style="{if $tab != $ctab}display:none{/if}">
				{foreach from=$topics item=item name=used}
					<span class="tag" id="suggestion{$item.tag|escape:'html'}">
					<a href="javascript:addTag('{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}','{$item.tag|escape:'html'}');" class="taglink">{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}</a>
					</span> &nbsp;
				{/foreach}
				<br/><small>These are guessed tags based on words in title/description</small>
			</div>
		{/if}

		{if $recent}
			{assign var="ctab" value=$ctab+1}
			<div id="div{$ctab}" class="interestBox" style="{if $tab != $ctab}display:none{/if}">
				{foreach from=$recent item=item name=used}
					<span class="tag" id="suggestion{$item.tag|escape:'html'}">
					<a href="javascript:addTag('{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}','{$item.tag|escape:'html'}');" class="taglink">{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}</a>
					</span> &nbsp;
				{/foreach}
				<br/><small>These are your recently used tags</small>
			</div>
		{/if}

		{if $gr}
			{assign var="ctab" value=$ctab+1}
			<div id="div{$ctab}" class="interestBox" style="{if $tab != $ctab}display:none{/if}">
				<div id="topics">Loading...</div>
				<br/><small>These are tags from surrounding images</small>
			</div>
			<script>{literal}
						AttachEvent(window,'load',function() {
							loadNearbyTags({/literal}'{$topicstring|escape:'quotes'}'{literal});
						},false);
			{/literal}</script>
		{/if}

		{if $tree}
			{foreach from=$tree key=key item=item name=tree}
				{assign var="ctab" value=$ctab+1}
				<div style="position:relative;{if $tab != $ctab}display:none{/if}"  class="interestBox" id="div{$ctab}">
					{foreach from=$item item=value}
						<span class="tag" id="suggestion{$value|escape:'html'}">
						<a href="javascript:addTag('top:{$value|escape:'html'}','{$value|escape:'html'}');" class="taglink">{$value|escape:'html'}</a>
						</span> &nbsp;
					{/foreach}
					<br/><small>Click a Geographical Context tag to add it to this image</small>
				</div>
			{/foreach}
		{/if}

		{assign var="ctab" value=$ctab+1}
		<div id="div{$ctab}" class="interestBox" style="{if $tab != $ctab}display:none{/if}">
			<br/><small>IMPORTANT: Please read the {newwin href="/article/Image-Buckets" title="Article about Buckets" text="Buckets Article"} before picking from this list</small><br/><br/>
			{foreach from=$buckets item=item}
				<span class="tag" id="suggestion{$item|escape:'html'}">
				<a href="javascript:addTag('bucket:{$item|escape:'html'}','{$item|escape:'html'}');" class="taglink">{$item|escape:'html'}</a>
				</span> &nbsp;
			{foreachelse}
				<i>none</i>
			{/foreach}
			<br/><small>Click a bucket to add it to this image</small>
		</div>

		<br/><br/>
	</div>

{/dynamic}

<br style="clear:both"/>
<div class="interestBox" style="font-size:0.7em; border-top:2px solid gray">{newwin href="/article/Tags" text="Article about Tags"} Colour key: <span class="tags"><span class="tag tagPublic">Public Tag (Your own)</span> <span class="tag tagPrivate">Private (your own)</span> <span class="tag tagGeneral">Public (by others)</span></span> (click X to remove tag)<br/>
{if $is_owner}<b>Single click</b> tag to toggle public/private. {/if}<b>Double click</b> the tag, to return it to Add box; useful for editing a tag.
</div>

{literal}<style type="text/css">
.interestBox .tag {
	margin-left:5px;
	white-space:nowrap;
}
</style><script type="text/javascript">

function useTags(ele) {

	if (ele.value.indexOf(';') > -1 || ele.value.indexOf(',') > -1) {
		var arr = ele.value.split(/\s*[,;]+\s*/);
		for(q=0;q<arr.length;q++)
			if (arr[q].length>1)
				addTag(arr[q].replace(/^\s*(top|bucket):/i,''));
	} else {
		addTag(ele.value.replace(/^\s*(top|bucket):/i,''));
	}

	ele.value='';
	ele.focus();
}

function addTag(text,suggestion,clearText) {

	if (!text || text.length == 0) {
		alert('No tag specified');
		return;
	}

	if (clearText) {
		var ele = document.forms['theForm'].elements['__newtag'];
		ele.value='';
		ele.focus();
	}

	var div = document.getElementById('tags');

	if (div.innerHTML.indexOf('<i>') > -1) {
		$('#tags i').remove();
	}

	text = text.replace(/<[^>]*>/ig, "");
	text = text.replace(/['"]+/ig, " ");
	//todo - split on comma so can enter multiple tags at once.

{/literal}{dynamic}{if $is_owner}
	str = "<span class=\"tag tagPublic\" id=\"tag"+text+"\" onclick=\"toggleTag('"+text+"');\" ondblclick=\"editTag('"+text+"');\">";
	str += "<input type=\"hidden\" name=\"mode[]\" id=\"tagm"+text+"\" value=\"Public\"/>";
{else}
	str = "<span class=\"tag tagPrivate\" id=\"tag"+text+"\" ondblclick=\"editTag('"+text+"');\">";
	str += "<input type=\"hidden\" name=\"mode[]\" id=\"tagm"+text+"\" value=\"Private\"/>";
{/if}{/dynamic}{literal}
	str += "<input type=\"hidden\" name=\"tag_id[]\" id=\"tagi"+text+"\" class=\"tagi\" value=\""+text+"\"/>";
	str += "<span>"+text+"</span>";
	str += "<a href=\"javascript:removeTag('"+text+"')\" class=\"delete\">X</a>";
	str += "</span>&nbsp; ";
	div.innerHTML = div.innerHTML + str;

	if (suggestion) {
		document.getElementById('suggestion'+suggestion).style.display='none';
	}
	submitTag(text,{/literal}{dynamic}{if $is_owner}2{else}1{/if}{/dynamic}{literal});
	return void('');
}
function removeTag(text) {
	if (document.getElementById("tag"+text)) {
		document.getElementById("tag"+text).style.textDecoration = "line-through";
		document.getElementById("tag"+text).style.fontSize = "0.6em";
	}
	if (document.getElementById("tagi"+text)) {
		document.getElementById("tagi"+text).value="-deleted-";
	}
	submitTag(text,0);

	count = 0;
	$('#tags .tagi').each(function(i) {
		if ($(this).attr('value') != '-deleted-')
			count++;
	});

{/literal}{dynamic}{if $is_owner}
	if (count == 0)
		$('#tags').prepend('<i><big>No tags for this image</big> - <b>Please add at least one</b><br/></i>');
{/if}{/dynamic}{literal}

}

function editTag(name,text) {
	removeTag(name);
 	var ele = document.forms['theForm'].elements['__newtag'];
        ele.value=text?text:name;
}

function toggleTag(text) {
	if (document.getElementById("tag"+text)) {
		var ele = document.getElementById("tag"+text);

		var newClass= (ele.className.indexOf('Public') > 1)?'Private':'Public';

		ele.className = "tag tag"+newClass;
		document.getElementById("tagm"+text).value = newClass;

		submitTag(text,(newClass == 'Private')?1:2);
	}
}

</script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>


	function submitTag(tag,status) {
		var data = new Object;
		data['tag'] = tag;
		data['status'] = status;

		var form= document.forms['theForm'];
		if (form.gridimage_id)
			data['gridimage_id'] = form.gridimage_id.value;
		if (form.ids)
			data['ids'] = form.ids.value;

		$.ajax({
			url: "/tags/tagger.json.php",
			data: data
		});
	}

	function loadTagSuggestions(that,event) {

		var unicode=event.keyCode? event.keyCode : event.charCode;
		if (unicode == 13) {
			useTags(that);
			return;
		}

		param = 'q='+encodeURIComponent(that.value);
		
		if (document.getElementById('mine_checkbox') && document.getElementById('mine_checkbox').checked == true) {
			param = param + '&mine=1';
		}


		$.getJSON("/tags/tags.json.php?"+param+"&callback=?",

		// on search completion, process the results
		function (data) {
			if (data && data.length > 0) {
				tabClick('tab','div',1,10);

				var div = document.getElementById('div1');

				str = '';
				for(var tag_id in data) {
					var text = data[tag_id].tag;
					if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
						text = data[tag_id].prefix+':'+text;
					}
					text = text.replace(/<[^>]*>/ig, "");
					text = text.replace(/['"]+/ig, " ");

					str += "<span class=\"tag\" id=\"suggestiond"+tag_id+"\">";
					str += "<a href=\"javascript:addTag('"+text+"','d"+tag_id+"',true);\" class=\"taglink\">"+text+"</a>";
					str += "</span> ";
				}
				str = str + "<br/><small>These are suggestions based on what you typed so far. Click one to use</small>";

			} else {
				str = '<i>no suggestions</i>';
			}
			div.innerHTML = str;
		});
	}

	function loadTagTopics(value) {

		param = 'string='+encodeURIComponent(value);

		$.getJSON("/tags/topics.json.php?"+param+"&callback=?",

		// on search completion, process the results
		function (data) {
			if (data) {
				var div = document.getElementById('topics');

				str = '';
				for(var tag_id in data) {
					var text = data[tag_id].tag;
					if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
						text = data[tag_id].prefix+':'+text;
					}
					text = text.replace(/<[^>]*>/ig, "");
					text = text.replace(/['"]+/ig, " ");

					str += "<span class=\"tag\" id=\"suggestiontt"+tag_id+"\">";
					str += "<a href=\"javascript:addTag('"+text+"','tt"+tag_id+"',true);\" class=\"taglink\">"+text+"</a>";
					str += "</span> ";
				}
				div.innerHTML = str;
			}
		});
	}
</script>
{/literal}

</div>
</body>
</html>
