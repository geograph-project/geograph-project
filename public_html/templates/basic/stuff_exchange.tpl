{include file="_std_begin.tpl"}

<h2>Photo Exchange</h2>
<a href="{$script_name}?replies">View replies</a>
{dynamic}

{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

{if $message}
	<div class="interestBox">{$message}</div>
{/if}

{if $image}

	<h3>{$topic|escape:"html"}</h3>

	The image:

	<div class="photoguide" style="margin-left:auto;margin-right:auto; width:470px">
		<div style="float:left;width:213px">
			<a title="view full size image" href="/photo/{$image->gridimage_id}" target="_blank">
			{$image->getThumbnail(213,160)}
			</a><div class="caption"><a title="view full size image" href="/photo/{$image->gridimage_id}" target="_blank">{$image->title|escape:'html'}</a> for <a href="/gridref/{$image->grid_reference}" target="_blank">{$image->grid_reference}</a></div>
		</div>
		<div style="float:left;padding-left:20px; width:200px;">
			<span style="font-size:0.7em">{$image->comment|escape:'html'|nl2br|geographlinks}</span><br/>
			<br/>
			<small><b>&nbsp; &copy; Copyright <a title="View profile" href="http://{$http_host}{$image->profile_link}" target="_blank">{$image->realname|escape:'html'}</a> and
			licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap" target="_blank">Creative Commons Licence</a></b></small>
		</div>

		<br style="clear:both"/>
	</div>

	<i>suggested by <a href="/profile/{$user_id}">{$realname|escape:"html"}</a>

	<div class="interestBox"><a href="{$script_name}">Go again!</a></div>

{else}

	{if $topics}
		<form class="simpleform" action="{$script_name}" method="post" name="theForm">


		<fieldset>
			<legend>Reply to a Exchange request</legend>

			<p>Pick a topic of interest, find a suitable image and the original requester will be sent your suggestion. You will then see their suggested image.</p>

			<div class="field">
				{if $errors.topic}<div class="formerror"><p class="error">{$errors.topic}</p>{/if}

				<label for="topic">Topic:</label>
				<select name="exchange_id">
				{html_options options=$topics}
				</select>

				{if $errors.topic}</div>{/if}
			</div>


			<div class="field">
				{if $errors.gridimage_id}<div class="formerror"><p class="error">{$errors.gridimage_id}</p>{/if}

				<label for="topic">Image ID:</label>
				<input type="text" name="gridimage_id" value="{$gridimage_id|escape:"html"}" style="font-size:1.1em" maxlength="8" size="15"/>

				<div class="fieldnotes">Paste the ID (or the link to the photo page) for a photo</div>

				{if $errors.gridimage_id}</div>{/if}
			</div>

		<div class="field">
			{if $errors.email}<div class="formerror"><p class="error">{$errors.email}</p>{/if}

			<label for="email">Create New:</label>
			<input type="checkbox" name="create" value="Y" checked/>

			<div class="fieldnotes">Create a new request with this image and topic - so you get another reply</div>

			{if $errors.email}</div>{/if}
		</div>


		<div class="field">
			{if $errors.email}<div class="formerror"><p class="error">{$errors.email}</p>{/if}

			<label for="email">Email me:</label>
			<input type="checkbox" name="email" value="Y"{if $email} checked{/if}/>

			<div class="fieldnotes">Let me know by email, when someone replies to my new request</div>

			{if $errors.email}</div>{/if}
		</div>

		</fieldset>

		<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
		<input type="submit" name="submit" value="Submit" style="font-size:1.1em"/></p>

		</form>

		<p>- or -</p>

	{else}
		<p>No available requests!</p>
	{/if}



	<form class="simpleform" action="{$script_name}" method="post" name="theForm">

	<input type="hidden" name="create" value="true"/>

	<fieldset>
		<legend>Create a Exchange request</legend>

		<p>Pick and image and suitable topic, and make a request for a reply</p>

		<div class="field">
			{if $errors.topic}<div class="formerror"><p class="error">{$errors.topic}</p>{/if}

			<label for="topic">Topic:</label>
			<input type="text" name="topic" value="{$topic|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

			<div class="fieldnotes">Short title for the theme of your request</div>

			{if $errors.topic}</div>{/if}
		</div>


		<div class="field">
			{if $errors.gridimage_id}<div class="formerror"><p class="error">{$errors.gridimage_id}</p>{/if}

			<label for="topic">Image ID:</label>
			<input type="text" name="gridimage_id" value="{$gridimage_id|escape:"html"}" style="font-size:1.1em" maxlength="8" size="15"/>

			<div class="fieldnotes">Paste the ID (or the link to the photo page) for a photo, eg 123456</div>

			{if $errors.gridimage_id}</div>{/if}
		</div>


		<div class="field">
			{if $errors.email}<div class="formerror"><p class="error">{$errors.email}</p>{/if}

			<label for="email">Email me:</label>
			<input type="checkbox" name="email" value="Y"{if $email} checked{/if}/>

			<div class="fieldnotes">Let me know by email, when someone replies to this request</div>

			{if $errors.email}</div>{/if}
		</div>

	</fieldset>

	<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
	<input type="submit" name="submit" value="Submit" style="font-size:1.1em"/></p>

	</form>


	<p><b>Note:</b> Submitting these forms, will reveal your identity to the participants(s) of the exchanges</p>
{/if}

{/dynamic}

{include file="_std_end.tpl"}
