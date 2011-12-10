{include file="_std_begin.tpl"}
{dynamic}
<form class="simpleform" action="{$script_name}?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$tag|escape:"url"}" method="post" name="theForm">



<input type="hidden" name="id" value="{$id|escape:"html"}"/>



<fieldset style="width:800px">
<legend>Synonym Suggestion Form</legend>

<p>Use this form to suggest that a tag is suffienctly similar to another tag, that the two tags can be "merged". ie they are synonums of each other. The 'Primary' tag will be the one shown in preference, so should be the main prefered version of the tag. Once we receive your suggestion, it will be reviewed, and hopefully applied shortly. If there is demand will open a voting process for moderating suggestions.</p>

<p>Note: This form is not for correcting typos or other issues - <a href="report.php?tag={if $theprefix}{$theprefix|escape:'url'}:{/if}{$tag|escape:"url"}">there is a special form for that</a> - but when the two forms are perfectly valid (just different).</p>

<hr/>

{if $message}
	<p>{$message}</p>
{/if}

<div class="field">
	{if $errors.tag}<div class="formerror"><p class="error">{$errors.tag}</p>{/if}

	<label for="tag">Primary Tag:</label>
	{if $canonical}
		<b><a href="/tags/?tag={if $canonical.prefix}{$canonical.prefix|escape:'url'}:{/if}{$canonical.tag|escape:'url'}&amp;exact=1">{if $canonical.prefix}{$canonical.prefix|escape:'html'}:{/if}{$canonical.tag|escape:'html'}</a></b>
		<input type="hidden" name="tag" value="{if $canonical.prefix}{$canonical.prefix|escape:'html'}:{/if}{$canonical.tag|escape:'html'}" size="20" readonly="readonly" style="border:0;font-size:1.2em"/>
		<input type="hidden" name="tag_id" value="{$canonical.tag_id}"/>


	{else}
			<input type="text" name="tag" value="{if $theprefix}{$theprefix|escape:'html'}:{/if}{$tag|escape:"html"}" size="20" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} {/literal}" onpaste="loadTagSuggestions(this,event);" onmouseup="loadTagSuggestions(this,event);" oninput="loadTagSuggestions(this,event);"/>
			<input type="hidden" name="tag_id"/>


	{/if}
	<div id="tag-message" style="float:right"></div>

	<div class="fieldnotes">This is the definitive - main/primary version of the tag set</div>

	{if $errors.tag}</div>{/if}
</div>

{if $synonyms}

		<label for="canon">Current Synonyms:</label><br/>
		<ul style="margin-left:90px">
		{foreach from=$synonyms item=row}
			{if $tag && $row.tag == $tag && $row.prefix == $theprefix}{assign var="found" value="true"}{/if}
			<li><a href="/tags/?tag={if $row.prefix}{$row.prefix|escape:'url'}:{/if}{$row.tag|escape:'url'}&amp;exact=1">{if $row.prefix}{$row.prefix|escape:'html'}:{/if}{$row.tag|escape:'html'}</a></li>
		{/foreach}
		</ul>

{/if}



<div class="field">
	{if $errors.tag2}<div class="formerror"><p class="error">{$errors.tag2}</p>{/if}

	<label for="tag2">New Synonym:</label>
	<input type="text" name="tag2" value="{if $found}{else}{if $theprefix}{$theprefix|escape:'html'}:{/if}{$tag|escape:"html"}{/if}" size="20" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} {/literal}" onpaste="loadTagSuggestions(this,event);" onmouseup="loadTagSuggestions(this,event);" oninput="loadTagSuggestions(this,event);"/>

	<input type="hidden" name="tag2_id"/>

	<div id="tag2-message" style="float:right"></div>

	<div class="fieldnotes">The new synonym of the above tag{if $synonyms}s{/if}</div>

	{if $errors.tag2}</div>{/if}
</div>


</fieldset>

<p>
<input type="submit" name="submit" value="Submit suggestion..." style="font-size:1.1em" disabled/> (only becomes active when tag(s) has been found)</p>
</form>

<p>Note: your identity is saved with the report, which we may use to contact you if questions.</p>


{literal}
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>

	function loadTagSuggestions(that,event) {

		var unicode=event.keyCode? event.keyCode : event.charCode;
		if (unicode == 13) {
			return;
		}

		param = 'q='+encodeURIComponent(that.value);

		$.getJSON("/tags/tag.json.php?"+param+"&callback=?"+((that.name == 'tag')?'&expand=1':''),

		// on search completion, process the results
		function (data) {
			var div = document.getElementById(that.name+'-message');
			that.form.elements[that.name+'_id'].value = '';

			if (data && data.tag_id) {

				var text = data.tag;
				if (data.prefix) {
					text = data.prefix+':'+text;
				}
				text = text.replace(/<[^>]*>/ig, "");
				text = text.replace(/['"]+/ig, " ");


				str = "Found '<b>"+text+"</b>'";

				if (data.images) {
					str = str + " used by "+data.images+" images";
				}

				if (data.users) {
					str = str + ", by "+data.users+" users";
				}

				if (data.canonical && parseInt(data.canonical,10) > 0) {

					str = str + "<br/>Already a synonym of another tag - can't be used";
					div.innerHTML = str;
					that.form.elements['submit'].disabled = true;
					that.form.elements[that.name+'_id'].value = '';
					return;

				}

				that.form.elements[that.name+'_id'].value = data.tag_id;

			} else if (data.error) {
				str = data.error;
				that.form.elements[that.name+'_id'].value = '';
			} else {
				str = "no tags/images";
				that.form.elements[that.name+'_id'].value = '';

			}
			div.innerHTML = str;

			var v1 = that.form.elements['tag_id'].value;
			var v2 = that.form.elements['tag2_id'].value;
			if (v1 && v2 && v1 != v2) {
				that.form.elements['submit'].disabled = false;
			} else {
				that.form.elements['submit'].disabled = true;
			}
		});
	}
{/literal}

{if !$canonical && $tag}
	loadTagSuggestions(document.theForm.elements['tag'],{literal}{keyCode:0}{/literal});
{/if}
{if !$found && $tag}
	loadTagSuggestions(document.theForm.elements['tag2'],{literal}{keyCode:0}{/literal});
{/if}
</script>
{/dynamic}
{include file="_std_end.tpl"}

