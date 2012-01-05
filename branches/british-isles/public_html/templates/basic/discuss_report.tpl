{include file="_std_begin.tpl"}
{dynamic}
<form class="simpleform" action="{$script_name}" method="post" name="theForm">


<fieldset style="width:800px">
<legend>Report Form</legend>


{if $message}
	<p>{$message}</p>

	<a href="/discuss/">back to discussions</a>
{else}


	<div class="field">
		{if $errors.thread}<div class="formerror"><p class="error">{$errors.thread}</p>{/if}

		<label for="thread">Thread:</label>
		<input type="text"  value="{$thread|escape:"html"}" size="60" readonly disabled/>
		<input type="hidden" name="topic_id" value="{$topic_id}"/>

		{if $errors.thread}</div>{/if}
	</div>

	{if $post}
		<div class="field">
			{if $errors.post}<div class="formerror"><p class="error">{$errors.post}</p>{/if}

			<label for="thread">Post:</label>
			<input type="text"  value="{$post|escape:"html"}" size="60" readonly disabled/>
			<input type="hidden" name="post_id" value="{$post_id}"/>

			{if $errors.thread}</div>{/if}
		</div>

		<div class="field">
			{if $errors.type}<div class="formerror"><p class="error">{$errors.type}</p>{/if}

			<label for="type">Scope:</label>
			<select name="type">
			{html_options options=$types selected=$type}
			</select>

			{if $errors.type}</div>{/if}
		</div>
	{/if}

	<div class="field">
		{if $errors.comment}<div class="formerror"><p class="error">{$errors.comment}</p>{/if}

		<label for="comment">Comments:</label>
			<textarea name="comment" rows="4" cols="80">{$comment|escape:"html"}</textarea>

		<div class="fieldnotes">Optional, please describe the issue</div>

		{if $errors.tag2}</div>{/if}
	</div>

	</fieldset>

	<p><a href="/discuss/">back to discussions</a>
	<input type="submit" value="Submit report..." style="font-size:1.1em"/></p>
	</form>

	<p>Note: your identity is saved with the report, you wont get an individual reply. We may or may not act on this information. Thank you for taking the time to send us feedback.</p>

{/if}


{/dynamic}


{include file="_std_end.tpl"}

