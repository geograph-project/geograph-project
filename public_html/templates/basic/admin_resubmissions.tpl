{assign var="page_title" value="Verify Resubmission"}
{include file="_std_begin.tpl"}
{dynamic}

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : Verify Resubmission</h2>

{if $message}
	<p>{$message|escape:'html'}</p>
{/if}

<br/>
{if $image}
<form method="post" action="{$script_name}">
	<input type="hidden" name="gridimage_id" value="{$image->gridimage_id}"/>
	<input type="hidden" name="pending_id" value="{$image->pending_id}"/>
	<br/>

	<table cellpadding="10" cellspacing="0" style="background-color:white">
		<tr>
			<th>
				{if $image->showingcurrent}
					Current Larger Image
				{else}
					New Larger Image - Pending Approval
				{/if} (640px preview)
			</th>
			<th>
				Original 640px Image <br>
				Moderation Status: {$image->moderation_status}
			</th>
		</tr>
		<tr>
			<td>
				<div class="img-shadow" id="mainphoto"><img src="{$static_host}{$image->previewUrl}?{$smarty.now}" name="new" style="border:none"></div>

			</td>
			<td>
				<div class="img-shadow" id="mainphoto"><a href="/photo/{$image->gridimage_id}">{$image->getFull()|replace:'alt=':'name="old" style="border:none" alt='}</a></div>
			</td>
		</tr>
		<tr>
			<th>
				{if $image->showingcurrent}
                                        Current Larger Image
                                {else}
                                        New Larger Image (Pending Approval)
                                {/if} (<a href="{$static_host}{$image->pendingUrl}" target="_preview">View full size</a> - {$image->pendingSize|thousends} bytes!)
			</th>
			<th>
				Original 640px Image<br>
<small>If the image fails to load, try <a href="javascript:void(document.images['old'].src=document.images['old'].src.replace(/(_\d+x\d+)?\.jpg/,'_213x160.jpg'))">213 x 160</a>, or <a href="javascript:void(document.images['old'].src=document.images['old'].src.replace(/(_\d+x\d+)?\.jpg/,'_120x120.jpg'))">120 x 120</a>       thumbnail,<br/> or back to <a href="javascript:void(document.images['old'].src=document.images['old'].src.replace(/(_\d+x\d+)?\.jpg/,'.jpg'))">640px</a>.</small>
			</th>
		</tr>
	</table>

	{if $image->status == 'accepted' || $image->status == 'confirmed'}
	<ul>
		<li>Existing Status: {$image->status}</li>
	</ul>
	{elseif $image->previewUrl == "/photos/error.jpg"}
	<ul>
		<li>Unable to load preview. Click the button to notify a developer: <input style="background-color:pink; width:200px" type="submit" name="broken" value="This is broken."/></li>
	</ul>
	{else}
	<p>Please confirm the two images above represent the same base image</p>

	<input style="background-color:pink; width:200px" type="submit" name="diff" value="Different - don't allow!"/>


	<input style="background-color:lightgreen; width:200px" type="submit" name="confirm" value="Identical" onclick="autoDisable(this);" id="identbutton"/>

	<input style="background-color:lightgrey; color:green; width:200px" type="submit" name="similar" value="Close enough" onclick="autoDisable(this);" id="closebutton"/>

	<ul>
	<li>Minor tweaking of contrast, brightness etc is fine - even for "Identical"</li>
	<li>Major tweaking is permissible (such as removing border, overlaid text etc) - but should be marked "Close enough"</li>
	<li>Minor cropping changes is permissible, but must be marked "Close enough"</li>
	<li>Major cropping changes, provided the 'subject focal area' is unchanged, should also be marked "Close Enough"<ul>
		<li>(exception is panoramas that don't have a focal area, but the current image needs to be a crop of - or a frame from - the larger panorama - still marked "Close enough")</li>
		</ul></li>
	<li>Anything else, or when they are not the same image shouldn't be allowed</li>
	</ul>
	{/if}

	<p>Note, for panoramas, we are now encouraging 360 degree photospheres, so these are expressly allowed as the 'Large Upload' on an image, even if the main image is not a panorama. <a href="/discuss/?&action=vthread&topic=31573" target=_blank>see this thread</a>) 

	In a nutshell, if the two preview images above are the <b>same size</b> exactly and <b>look</b> exactly the same, then choose "Identical", otherwise if still confident represent the same image then "Close Enough".
</form>

<script type="text/javascript">
{literal}



function checkImageSizes() {
	var one = document.images['old'];
	var two = document.images['new'];

	var same = true;
	if (one.width != two.width) {
		same = false;
	}
	if (one.height != two.height) {
		same = false;
	}

	if (!same) {
		var button = document.getElementById('identbutton');
		button.style.color = 'lightgrey';
		button.style.backgroundColor = 'white';
		button.onclick = function () {
			return confirm("Please confirm! The two images don't appear to have the same dimensions.");
		};
	}
}

 AttachEvent(window,'load',checkImageSizes,false);


{/literal}

</script>

{else}
	<p>Nothing available currently - please come back later</p>
{/if}


{if $last_id}
	<div class="interestBox"><a href="?review={$last_id|escape:'url'}" target="_blank">Reopen Last page</a> (opens in new window)</div>
{/if}

{/dynamic}
{include file="_std_end.tpl"}
