{assign var="page_title" value="Tags"}
{include file="_basic_begin.tpl"}
{dynamic}
<form method="post" action="{$script_name}?gr={$gr|escape:'url'}&amp;upload_id={$upload_id|escape:'url'}&amp;gridimage_id={$gridimage_id}" style="background-color:#f0f0f0;" name="theForm">
<div id="savebutton" style="float:right;display:none">
	<input type="submit" name="submit" value="Save Changes" style="font-size:1.2em"/>
</div>

<input type="hidden" name="gridimage_id" value="{$gridimage_id}" />
<input type="hidden" name="gr" value="{$gr|escape:'html'}" />

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
<i>none</i>
{/foreach}</span><br/>
<hr/>

Add new Tag: <input type="text" name="__newtag" size="20" maxlength="32" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions('q='+escape(this.value));}{/literal}"/> <input type="button" value="Add" onclick="addTag(this.form.elements['__newtag'].value);this.form.elements['__newtag'].value='';this.form.elements['__newtag'].focus();"/> (click X to remove tag{if $is_owner}, click tag to toggle public/private{/if})<br/>


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

function addTag(text,suggestion) {

	if (!text || text.length == 0) {
		alert('No tag specified');
		return;
	}

	var div = document.getElementById('tags');

	if (div.innerHTML.indexOf('<i>none') == 0) {
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
	document.getElementById("savebutton").style.display='';
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
	document.getElementById("savebutton").style.display='';
}

function toggleTag(text) {
	if (document.getElementById("tag"+text)) {
		var ele = document.getElementById("tag"+text);

		var newClass= (ele.className.indexOf('Public') > 1)?'Private':'Public';

		ele.className = "tag tag"+newClass;
		document.getElementById("tagm"+text).value = newClass;
	}
	document.getElementById("savebutton").style.display='';
}

</script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>

	function loadTagSuggestions(param) {
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


</body>
</html>
