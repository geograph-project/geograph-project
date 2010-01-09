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
	<br/>
	
	<table border="1" cellpadding="4" cellspacing="0">
		<tr>
			<th>
				New Image (640px preview)
			</th>
			<th>
				Current Image
			</th>
		</tr>
		<tr>
			<td>
				<div class="img-shadow" id="mainphoto"><img src="{$image->previewUrl}"></div>
				
			</td>
			<td>
				<div class="img-shadow" id="mainphoto"><a href="/photo/{$image->gridimage_id}">{$image->getFull()}</a></div>
			</td>
		</tr>
		<tr>
			<th>
				New Image (<a href="{$image->pendingUrl}">View full size</a> - {$image->pendingSize|thousends} bytes!)
			</th>
			<th>
				Current Image
			</th>
		</tr>
	</table>	

<p>Please confirm the two images above are the same</p>

	<input style="background-color:pink; width:200px" type="submit" name="diff" value="Different - don't allow!"/>
	
	
	<input style="background-color:lightgreen; width:200px" type="submit" name="confirm" value="The Same" onclick="autoDisable(this);"/> 

	<ul>
	<li>Note however that minor tweaking of contrast and brightness is fine, as is removing borders and overlaid text</li>	
	</ul>
	
</form>
{else}
	<p>Nothing available currently - please come back later</p>
{/if}

{/dynamic}    
{include file="_std_end.tpl"}
