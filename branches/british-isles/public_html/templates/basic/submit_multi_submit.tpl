{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<div style="float:right;position:relative"><a href="/submit.php?redir=false">v1</a> / <a href="/submit2.php">v2</a> / <b>multi</b> / <a href="/help/submit">more...</a></div>

	<h2>Multiple Image Submission</h2>

<div style="position:relative;">
	<div class="tabHolder">
		<a class="tab nowrap" id="tab1" href="{$script_name}">A) Add/Upload Images</a>&nbsp;
		<a class="tabSelected nowrap" id="tab2">B) Submit Images (v1)</a>
		<a class="tab nowrap" id="tab3" href="/submit2.php?multi=true">B) Submit Images (v2)</a>
                <a class="tab nowrap" id="tab4" href="/submit2.php?multi=true&amp;display=tabs">(Tabs)</a>
	</div>

	<div class="interestBox">

			<fieldset>
				<legend>Set Location</legend>

				<form action="#" onsubmit="return copytoall(this)">
					Subject Grid Reference: <input type="text" name="grid_reference" size="6"/>
					<input type="submit" value="Copy to all"/>
				</form>
			</fieldset>
		<br style="clear:both"/>

		<table id="upload" class="report sortable">
			<thead>
			<tr>
				<td>Preview</td>
				<td>Continue</td>
				<td>Uploaded</td>
				<td>Taken</td>
				<td>Done</td>
			</tr>
			</thead>
			<tbody>
			{dynamic}
			{foreach from=$data item=item}

				<tr>
					<td><a href="/submit.php?preview={$item.transfer_id}" target="_blank"><img src="/submit.php?preview={$item.transfer_id}" width="160"/></a></td>
					<td><form action="/submit.php" method="post" target="_blank" style="margin:0; background-color:lightgrey; padding:5px">
						Subject GR: <input type="text" name="grid_reference" size="10" value="{$item.grid_reference}"/> {if $item.grid_reference}<small>{$item.grid_reference} from EXIF</small>{/if}<br/>
						{if $item.photographer_gridref}Photographer: <input type="text" name="photographer_gridref" size="10" value="{$item.photographer_gridref}"/><br/> <small style="font-size:0.7em">{$item.photographer_gridref} from EXIF</small><br/>{/if}

						<br/><input type="hidden" name="gridsquare" value="1">

						<input type="hidden" name="transfer_id" value="{$item.transfer_id}">

						<input type="submit" value="continue &gt;">

					</form></td>
					<td sortvalue="{$item.uploaded}">{$item.uploaded|date_format:"%a, %e %b %Y at %H:%M"}</td>
					<td sortvalue="{$item.imagetaken}">{if $item.imagetaken}{$item.imagetaken|date_format:"%a, %e %b %Y at %H:%M"}{/if}</td>
					<td><input type="checkbox"/><br/><br/><a href="{$script_name}?tab=submit&amp;delete={$item.transfer_id}" onclick="return confirm('Are you sure?');" style="color:red">Delete</a></td>

				</tr>
			{foreachelse}
				<tr><td colspan="4">click "Add/Upload Images" above, and send us some images!</td></tr>
			{/foreach}
			{/dynamic}
			</tbody>
		</table>


	</div>
</div>
{literal}
<script type="text/javascript">
        function copytoall(that) {
                f = document.forms;
                for(q=0;q<f.length;q++) {
                        if (f[q] != that && f[q].grid_reference) {
                                f[q].grid_reference.value = that.grid_reference.value;
                        }
                }
                return false;
        }
</script>{/literal}

{include file="_std_end.tpl"}
