<div style="position:relative;text-align:right;"><a href="/profile.php?edit=1#prefs" target="_blank">Change your default</a> <sup style="color:red">New!</sup></div>
<div>Please choose the largest image you wish to submit: </div>

<table style="font-weight:bold" cellspacing="0" border="1" bordercolor="#cccccc">
	<tr>
	{if !$hide640}
		<td valign="top"><div class="interestBox"><input type="radio" name="largestsize" checked value="{$stdsize}" id="large{$stdsize}" onclick="selectImage(this.id)"/> {$preview_width} x {$preview_height}</div><br/>
		<label for="large{$stdsize}"><img src="{$preview_url}" width="{$preview_width/$ratio}" height="{$preview_height/$ratio}" name="large{$stdsize}" style="border:2px solid blue"/></label><br/><br/>
		<small>(as shown on<br/> photo page)</small>
		</td>
	{/if}

	{foreach key=idx item=cursize from=$sizes}
		<td valign="top"><div class="interestBox"><input type="radio" name="largestsize" value="{$cursize}" {if $user->upload_size == $cursize} checked{/if} id="large{$cursize}" onclick="selectImage(this.id)"/> {$widths.$idx} x {$heights.$idx}</div><br/>
		<label for="large{$cursize}"><img src="{$preview_url}" width="{$widths.$idx/$ratio}" height="{$heights.$idx/$ratio}" name="large{$cursize}" style="border:2px solid white"/></label>
		</td>
	{/foreach}

	{if $showorig}
		<td valign="top"><div class="interestBox"><input type="radio" name="largestsize" value="65536" {if $user->upload_size > 65530} checked{/if} id="large65536" onclick="selectImage(this.id)"/> {$original_width} x {$original_height}</div><br/>
		<label for="large65536"><img src="{$preview_url}" width="{$original_width/$ratio}" height="{$original_height/$ratio}" name="large65536" style="border:2px solid white"/></label>
		</td>
	{/if}
	</tr>
</table>
<ul>
	<li>Previews shown at <b>{math equation="round(100/r)" r=$ratio}</b>% of actual size - NOT representative of the final quality.</li>
	<li>Even if choose a larger size, we will still make the smaller sizes available too.</li>
	<li>Only choose the maximum size you are willing to release under the Creative Commons Licence.</li>
</ul>

<script type="text/javascript">{literal}
function selectImage(that) {
	for(q=0;q<document.images.length;q++) {
		if (document.images[q].name && document.images[q].name == that) {
			document.images[q].style.border='2px solid blue';
		} else {
			document.images[q].style.border='2px solid white';
		}
	}
	if (document.getElementById("step3")) {
		document.getElementById("step3").style.display = '';
	}
	return true;
}
{/literal}
{if $user->upload_size > $stdsize} 
{literal}
 AttachEvent(window,'load',function () {
		selectImage("large{/literal}{$user->upload_size}{literal}");
	},false);
{/literal}
{/if}
</script>
