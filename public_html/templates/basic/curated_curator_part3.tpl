{assign var="page_title" value="Curation Part 3"}
{include file="_std_begin.tpl"}

<h2>Verify Tags for Image</h2>

{dynamic}

<a href="/photo/{$image->gridimage_id}" target=_blank>{$image->getFull()}</a>
<p>{$image->title|escape:'html'}</p>

<form method=post>
	<input type=hidden name="gridimage_id" value="{$image->gridimage_id}">

	<div style="padding:3px; background-color:#eee">
		<p><label for="top"><b>Geographical Context</b></label> <small style="font-size:0.7em">(tick as many as required, hover over name for a description, <a href="/tags/primary.php" text="More examples" class="about" target="_blank" style="font-size:0.85em">more</a>)</small></p>

			{foreach from=$tops key=key item=item}
				<div class="plist">
					<div style="color:black">{$key}</div>
					{foreach from=$item item=row}{assign var="tagtop" value=$row.top}
						<label for="c-{$row.top|escape:'url'}" title="{$row.description|escape:'html'}" id="l-{$row.top|escape:'url'}">
							<input type="checkbox" name="tags[]" value="top:{$row.top|escape:'html'}" id="c-{$row.top|escape:'url'}" onclick="rehighlight(this,true)" {if $tagarray.$tagtop} checked{/if} disabled/>
							{$row.top|escape:'html'}
						</label>
					{/foreach}
					<br/>
				</div>
			{/foreach}
			<br style="clear:both"/>
	</div>

{literal}
<script type="text/javascript">
//todo, the actual highlighting could now be done with native css :checked
function rehighlight(that,check) {
	if (check) {
		var name=that.name;
		var ele = that.form.elements[name];
		count=-1; //the current one will already be checked
		for(q=0;q<ele.length;q++)
			if (ele[q].checked)
				count++;
		if (count > 5) {
			if (!confirm("Are you sure you wish to enable '"+that.value.replace(/top:/,'')+"'?\n\n You already have "+count+" ticked items, which is probably plenty!")) {
				that.checked = false;
			}
		}

	}
	var id = that.id.replace(/c-/,'l-');
	document.getElementById(id).style.fontWeight=that.checked?'bold':'normal';
	document.getElementById(id).style.color=that.checked?'blue':'black';
	document.getElementById(id).style.backgroundColor=that.checked?'white':'';

}
{/literal}

{if $tagarray}
	{foreach from=$tagarray key=key item=row}
		if (document.getElementById("c-{$key|escape:'url'}"))
			rehighlight(document.getElementById("c-{$key|escape:'url'}"));
	{/foreach}
{/if}

</script>

{/dynamic}

    <div class="buttons-section">
	 <button type=submit class="skip" name=action value=skip>Skip</button>
	 <button type=submit class="save-progress" name=action value=looksok>Looks Ok</button>
	 <button type=submit class="save-mark" name=action value=errors>Errors</button>
    </div>
</form>

<p>Skip just moves to next image <i>without</i> saving anything; 'Looks Ok' will mark the image as verified, and 'Errors' will flag it for review.</p>
<p><small>Note: When reviewing, please consider there is a lot of subjectivity in selecting the tags, so only select 'Errors' if they seem wrong (missing something major, and/or having an inappropriate tag), rather than just not exactly what you would select.</small></p>

{include file="_std_end.tpl"}
