<div style="position:relative;text-align:right;"><a href="/profile.php?edit=1#prefs" target="_blank">Change your default</a></div>
<div>Please choose the largest image you wish to submit: </div>

{if $user->upload_size <= 1024 && ($original_width >=1024 || $original_height >=1024)}
	<div class="interestBox">
		<span style="color:red">&middot; Note </span>: We've recently changed the <i>default size</i> from 640px to 1024px, please double check this image is sized such that you happy to release under Creative Commons for others to use. (You can still choose to only release smaller images)
	</div>
	<br><br>
{/if}

{if $original_width > 4000}
	{math equation="o/320" o=$original_width assign="ratio"}
{else}
	{math equation="o/180" o=$original_width assign="ratio"}
{/if}
{assign var="last_width" value=0}

<table style="font-weight:bold" cellspacing="0" border="1" bordercolor="#cccccc">
	<tr>

	{if !$hide640}
		<td valign="top"><div class="interestBox"><input type="radio" name="largestsize" checked value="640" id="large640" onclick="selectImage(this.id)"/> {$preview_width} x {$preview_height}</div><br/>
		<label for="large640"><img src="{$preview_url}" width="{$preview_width/$ratio}" height="{$preview_height/$ratio}" name="large640" style="border:2px solid blue"/></label><br/><br/>
		{assign var="last_width" value=$preview_width}
		{assign var="last_height" value=$preview_height}
		</td>
	{/if}

	{if $original_width > 800 || $original_height > 800}

		{if $original_width>$original_height}
			{assign var="resized_width" value=800}
			{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
		{else}
			{assign var="resized_height" value=800}
			{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
		{/if}

		<td valign="top"><div class="interestBox"><input type="radio" name="largestsize" value="800" {if $user->upload_size >= 800} checked{/if} id="large800" onclick="selectImage(this.id)"/> {$resized_width} x {$resized_height}</div><br/>
		<label for="large800"><img src="{$preview_url}" width="{$resized_width/$ratio}" height="{$resized_height/$ratio}" name="large800" style="border:2px solid white"/></label>
		{assign var="last_width" value=$resized_width}
		{assign var="last_height" value=$resized_height}
		</td>
	{/if}

	{if $original_width > 1024 || $original_height > 1024}

		{if $original_width>$original_height}
			{assign var="resized_width" value=1024}
			{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
		{else}
			{assign var="resized_height" value=1024}
			{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
		{/if}

		<td valign="top"><div class="interestBox"><input type="radio" name="largestsize" value="1024" {if $user->upload_size >= 1024} checked{/if} id="large1024" onclick="selectImage(this.id)"/> {$resized_width} x {$resized_height}</div><br/>
		<label for="large1024"><img src="{$preview_url}" width="{$resized_width/$ratio}" height="{$resized_height/$ratio}" name="large1024" style="border:2px solid white"/></label>
		{assign var="last_width" value=$resized_width}
		{assign var="last_height" value=$resized_height}
		</td>
	{/if}

	{if $original_width > 1600 || $original_height > 1600}

		{if $original_width>$original_height}
			{assign var="resized_width" value=1600}
			{math assign="resized_height" equation="round(dw*sh/sw)" dw=$resized_width sh=$original_height sw=$original_width}
		{else}
			{assign var="resized_height" value=1600}
			{math assign="resized_width" equation="round(dh*sw/sh)" dh=$resized_height sh=$original_height sw=$original_width}
		{/if}

		<td valign="top"><div class="interestBox"><input type="radio" name="largestsize" value="1600" {if $user->upload_size >= 1600} checked{/if} id="large1600" onclick="selectImage(this.id)"/> {$resized_width} x {$resized_height}</div><br/>
		<label for="large1600"><img src="{$preview_url}" width="{$resized_width/$ratio}" height="{$resized_height/$ratio}" name="large1600" style="border:2px solid white"/></label>
		{assign var="last_width" value=$resized_width}
		{assign var="last_height" value=$resized_height}
		</td>
	{/if}

	{if $original_width > $last_width || $original_height > $last_height || $user->upload_size > 65530}
		{if $original_width>$original_height}
			{assign var="largest_dimension" value=$original_width}
		{else}
			{assign var="largest_dimension" value=$original_height}
		{/if}

		<td valign="top"><div class="interestBox"><input type="radio" name="largestsize" value="65536" {if $user->upload_size > 65530 || $user->upload_size >= $largest_dimension} checked{/if} id="large65536" onclick="selectImage(this.id)"/> {$original_width} x {$original_height}</div><br/>
		<label for="large65536"><img src="{$preview_url}" width="{$original_width/$ratio}" height="{$original_height/$ratio}" name="large65536" style="border:2px solid white"/></label>
		</td>
	{/if}
	</tr>
</table>
<ul>
	<li>An image upto 1024px may be shown on the main photo page.</li>
	<li>Previews are shown at <b>{math equation="round(100/r)" r=$ratio}</b>% of actual size - NOT representative of the final quality.</li>
	<li>Even if you choose a larger size, we will still make the smaller sizes available too.</li>
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
{if $user->upload_size > 640}
{literal}
 AttachEvent(window,'load',function () {
		selectImage("large{/literal}{$user->upload_size}{literal}");
	},false);
{/literal}
{/if}
</script>

