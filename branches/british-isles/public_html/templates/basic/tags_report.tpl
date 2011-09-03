{include file="_std_begin.tpl"}
{dynamic}
<form class="simpleform" action="{$script_name}" method="post" name="theForm">

<input type="hidden" name="id" value="{$id|escape:"html"}"/>



<fieldset style="width:800px">
<legend>Tag Report Form</legend>


{if $message}
	<p>{$message}</p>
{/if}


<div class="field">
	{if $errors.tag}<div class="formerror"><p class="error">{$errors.tag}</p>{/if}

	<label for="tag">Tag:</label>
	<input type="text" name="tag" value="{$tag|escape:"html"}" size="20" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} {/literal}" onpaste="loadTagSuggestions(this,event);" onmouseup="loadTagSuggestions(this,event);" oninput="loadTagSuggestions(this,event);"/>
	<input type="hidden" name="tag_id"/>

	<div id="tag-message" style="float:right"></div>

	<div class="fieldnotes">The tag you are reporting. If has a prefix, enter "prefix:tag"</div>

	{if $errors.tag}</div>{/if}
</div>


<div class="field">
	{if $errors.tag2}<div class="formerror"><p class="error">{$errors.tag2}</p>{/if}

	<label for="tag2">New:</label>
	<input type="text" name="tag2" value="{$tag2|escape:"html"}" size="20" onkeyup="{literal}if (this.value.length > 2) {loadTagSuggestions(this,event);} {/literal}" onpaste="loadTagSuggestions(this,event);" onmouseup="loadTagSuggestions(this,event);" oninput="loadTagSuggestions(this,event);"/>

	<input type="hidden" name="tag2_id"/>

	<div id="tag2-message" style="float:right"></div>

	<div class="fieldnotes">Optional, what this tag should/could be changed to.</div>

	{if $errors.tag2}</div>{/if}
</div>


<div class="field">
	{if $errors.type}<div class="formerror"><p class="error">{$errors.type}</p>{/if}

	<label for="type">Type:</label>
	<select name="type">
	<option value=""></option>
	{html_options options=$types selected=$type}
	</select>

	<div class="fieldnotes">what type of report is this</div>

	{if $errors.type}</div>{/if}
</div>

</fieldset>

<p>
<input type="submit" name="submit" value="Submit report..." style="font-size:1.1em" disabled/> (only becomes active when tag has been found)</p>
</form>

<p>Note: your identity is saved with the report, which we may use to contact you if questions.</p>
.
{if $reports}
	<p>We have reports for the following tags already: (no need to resubmit)</p>
	<ul>
	{foreach from=$reports item=row}
		<li>{$row.tag|escape:'html'}</li>
	{/foreach}
	</ul>
{/if}

{if $recent}
	<p>And these have recently been dealt with:</p>
	<ul>
	{foreach from=$recent item=row}
		<li>{$row.tag|escape:'html'}</li>
	{/foreach}
	</ul>
{/if}


{/dynamic}
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

				that.form.elements[that.name+'_id'].value = data.tag_id;

				if (that.name == 'tag') {
					that.form.elements['submit'].disabled = false;
				}
			} else if (data.error) {
				if (that.name == 'tag') {
					str = data.error;
					that.form.elements['submit'].disabled = true;
				} else {
					str = 'no tags/images';
				}
			} else {
				if (that.name == 'tag') {
					str = "tag not found!";
					that.form.elements['submit'].disabled = true;
				} else {
					str = "no tags/images";
				}
			}
			div.innerHTML = str;
		});
	}

</script>
{/literal}

{include file="_std_end.tpl"}

