{assign var="page_title" value="Tags"}
{include file="_basic_begin.tpl"}
<div style="padding:6px">
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

Current Tags: <span id="tags">{foreach from=$used item=item name=used}
	<span class="tag {if !$item.is_owner}tagGeneral{elseif $item.status eq 2}tagPublic{else}tagPrivate{/if}" id="tag{$item.tag_id}"{if $is_owner} onclick="toggleTag('{$item.tag_id}');"{/if}>
	<span>{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}</span>
	{if $item.is_owner}
		<input type="hidden" name="tag_id[]" id="tagi{$item.tag_id}" value="id:{$item.tag_id}"/>
		<input type="hidden" name="mode[]" id="tagm{$item.tag_id}" value="{$item.status}"/>
		<a href="javascript:removeTag({$item.tag_id})" class="delete">X</a>
	{/if}
	</span>&nbsp;
{foreachelse}
{if $gridimage_id}<i>none</i>{else}<i>unknown</i>{/if}
{/foreach}</span><br/>
</form>
<hr/>
<form>

Add new Tag: <input type="text" name="__newtag" size="20" maxlength="32" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} clearAutoSave(); {/literal}"/> <input type="button" value="Add" onclick="useTag(this.form.elements['__newtag'])"/> (click X to remove tag{if $is_owner}, click tag to toggle public/private{/if})<br/>


<div id="suggestions">{if $suggestions}Suggestions: {/if}{foreach from=$suggestions item=item name=used}
	<span class="tag" id="suggestion{$item.tag|escape:'html'}">
	<span>{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}</span>
	<a href="javascript:addTag('{if $item.prefix}{$item.prefix|escape:'html'}:{/if}{$item.tag|escape:'html'}','{$item.tag|escape:'html'}');" class="use">Use</a>
	</span>&nbsp;
{/foreach}</div>


<input type="text" value="" style="display:none"/>
</form>

{/dynamic}

<div class="interestBox" style="font-size:0.7em; border-top:2px solid gray">{newwin href="/article/Tags" text="Article about Tags"} Colour key: <span class="tags"><span class="tag tagPublic">Public Tag</span> <span class="tag tagPrivate">Private Tag</span> <span class="tag tagGeneral">General Tag</span></span></div>

{literal}<script type="text/javascript">

var autoSaveTimer = null;
var autoSaveCounter = 0;
var autoSaveCounterTimer = null;

function useTag(ele) {
	addTag(ele.value);
	ele.value='';
	ele.focus();
}

function addTag(text,suggestion) {

	if (!text || text.length == 0) {
		alert('No tag specified');
		return;
	}

	if (text.indexOf(';') > -1 || text.indexOf(',') > -1) {
		var arr = text.split(/\s*[,;]+\s*/);
		for(q=0;q<arr.length;q++)
			if (arr[q].length>1)
				addTag(arr[q],suggestion);
		return void('');
	}

	var div = document.getElementById('tags');

	if (div.innerHTML.indexOf('<i>') == 0) {
		div.innerHTML = '';
	}

	text = text.replace(/<[^>]*>/ig, "");
	text = text.replace(/['"]+/ig, " ");
	//todo - split on comma so can enter multiple tags at once.

{/literal}{dynamic}{if $is_owner}
	str = "<span class=\"tag tagPublic\" id=\"tag"+text+"\" onclick=\"toggleTag('"+text+"');\">";
	str += "<input type=\"hidden\" name=\"mode[]\" id=\"tagm"+text+"\" value=\"Public\"/>";
{else}
	str = "<span class=\"tag tagPrivate\" id=\"tag"+text+"\">";
	str += "<input type=\"hidden\" name=\"mode[]\" id=\"tagm"+text+"\" value=\"Private\"/>";
{/if}{/dynamic}{literal}
	str += "<input type=\"hidden\" name=\"tag_id[]\" id=\"tagi"+text+"\" value=\""+text+"\"/>";
	str += "<span>"+text+"</span>";
	str += "<a href=\"javascript:removeTag('"+text+"')\" class=\"delete\">X</a>";
	str += "</span>&nbsp; ";
	div.innerHTML = div.innerHTML + str;

	if (suggestion) {
		document.getElementById('suggestion'+suggestion).style.display='none';
	}
	showSaveButton();
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
	showSaveButton();
}

function toggleTag(text) {
	if (document.getElementById("tag"+text)) {
		var ele = document.getElementById("tag"+text);

		var newClass= (ele.className.indexOf('Public') > 1)?'Private':'Public';

		ele.className = "tag tag"+newClass;
		document.getElementById("tagm"+text).value = newClass;
	}
	showSaveButton();
}

function showSaveButton() {
	document.getElementById("savebutton").style.display='';
	if (autoSaveTimer) {
		clearTimeout(autoSaveTimer);
	}
	if (autoSaveCounterTimer) {
		clearInterval(autoSaveCounterTimer);
	}
	autoSaveTimer = setTimeout("autoSaveTimer = null;document.forms['theForm'].elements['save'].click()", 10000);

	autoSaveCounter = 10;
	autoSaveCounterTimer = setInterval("autoSaveCountdown()", 1000);
	document.getElementById("autoSave").innerHTML = "Auto save in "+autoSaveCounter+" seconds";
}
function autoSaveCountdown() {
	autoSaveCounter = autoSaveCounter - 1;
	document.getElementById("autoSave").innerHTML = "Auto save in "+autoSaveCounter+" seconds";
}
function clearAutoSave() {
	if (autoSaveTimer) {
		clearTimeout(autoSaveTimer);
	}
	if (autoSaveCounterTimer) {
		clearInterval(autoSaveCounterTimer);
	}
	autoSaveTimer = autoSaveCounterTimer = null;
	document.getElementById("autoSave").innerHTML = '';
}


function unloadMess() {
	if (!autoSaveTimer) {
		return;
	}
	return "**************************\n\nYou have unsaved changes in the Tagging box.\n\n**************************\n";
}
//this is unreliable with AttachEvent
window.onbeforeunload=unloadMess;
function cancelMess() {
	window.onbeforeunload=null;
}
function setupSubmitForm() {
	AttachEvent(document.forms['theForm'],'submit',cancelMess,false);
}
AttachEvent(window,'load',setupSubmitForm,false);


</script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>

	function loadTagSuggestions(that,event) {

		var unicode=event.keyCode? event.keyCode : event.charCode;
		if (unicode == 13) {
			useTag(that);
			return;
		}

		param = 'q='+escape(that.value);

		$.getJSON("/tags/tags.json.php?"+param+"&callback=?",

		// on search completion, process the results
		function (data) {
			if (data) {
				var div = document.getElementById('suggestions');

				str = 'Suggestions: ';
				for(var tag_id in data) {
					var text = data[tag_id].tag;
					if (data[tag_id].prefix) {
						text = data[tag_id].prefix+':'+text;
					}
					text = text.replace(/<[^>]*>/ig, "");
					text = text.replace(/['"]+/ig, " ");

					str += "<span class=\"tag\" id=\"suggestion"+tag_id+"\">";
					str += "<span>"+text+"</span>";
					str += "<a href=\"javascript:addTag('"+text+"',"+tag_id+");\" class=\"use\">Use</a>";
					str += "</span>&nbsp; ";
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
