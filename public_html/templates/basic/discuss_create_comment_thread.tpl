{include file="_std_begin.tpl"}
{dynamic}
<form class="simpleform" action="{$script_name}" method="post" name="theForm">


<fieldset style="width:800px">
<legend>Create Thread</legend>

{if $message}
	<p>{$message}</p>

	<a href="/discuss/">back to discussions</a>
{else}

	<div class="field">
		{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

		<label for="title">Title:</label>
		<input type="text" id=title name=title value="{$title|escape:"html"}" size="60" maxlength=64 />
		 (optional)

		{if $errors.title}</div>{/if}
	</div>

	{if $for_topic_id}
		<div class="field">
			{if $errors.for_topic_id}<div class="formerror"><p class="error">{$errors.for_topic_id}</p>{/if}

			<label for="topic_title">Reference Thread:</label>
			<input type="text" id=topic_title value="{$topic_title|escape:"html"}" size="60" readonly style="background-color:silver;border:0;padding:2px" />
			<input type="hidden" name="for_topic_id" value="{$for_topic_id}"/>
	
			{if $errors.for_topic_id}</div>{/if}
		</div>
	{/if}

	{if $for_post_id} 
		<div class="field">
			{if $errors.for_post_id}<div class="formerror"><p class="error">{$errors.for_post_id}</p>{/if}

			<label for="for_post_id">Reference Post:</label>
			<input type="text" id=for_post_id value="{$for_post_id|escape:"html"}" size="10" readonly  style="background-color:silver;border:0;padding:2px" />

			{if $errors.for_post_id}</div>{/if}
		</div>
	{/if}

	{if $for_user_id}
	        <div class="field">
		        {if $errors.for_user_id}<div class="formerror"><p class="error">{$errors.for_user_id}</p>{/if}

			<label for="realname">User:</label>
	                <input type="text" id=realname value="{$realname|escape:"html"}" size="60" readonly style="background-color:silver;border:0;padding:2px" />
		        <input type="hidden" name="for_user_id" value="{$for_user_id}"/>

	                {if $errors.for_user_id}</div>{/if}
		</div>
	{/if}

        <div class="field">
                {if $errors.for_right}<div class="formerror"><p class="error">{$errors.for_right}</p>{/if}

                <label for="for_right">Scope:</label>
		<select name="for_right">
			{if $has_right.forum}<option value="forum">Forum Moderators</option>{/if}
			{if $has_right.moderator}<option value="moderator">Image Moderators</option>{/if}
			{if $has_right.director}<option value="director">Directors</option>{/if}
			<option value="basic">Any Registered User</option>
			<option value="all">Public</option>
		</select>

                {if $errors.for_right}</div>{/if}
        </div>

	<div class="field">
		{if $errors.comment}<div class="formerror"><p class="error">{$errors.comment}</p>{/if}

		<label for="comment">First Comment:</label>
			<textarea name="comment" rows="6" cols="80" required>{$comment|escape:"html"}</textarea>
			{if $for_user_id}
			<br>(the selected user will receive this comment by email)
			{else}
			<br>Currently nobody will be notified of this comment
			{/if}
		{if $errors.comment}</div>{/if}
	</div>

	<div class="field" style="display:none">
		{if $errors.message}<div class="formerror"><p class="error">{$errors.message}</p>{/if}

		<label for="message">Blank:</label>
			<textarea name="message" rows="4" cols="80">{$message|escape:"html"}</textarea>

		<div class="fieldnotes">LEAVE THIS BOX BLANK PLEASE</div>

		{if $errors.message}</div>{/if}
	</div>

	{if $has_right.forum}

        <div class="field">
                {if $errors.anon}<div class="formerror"><p class="error">{$errors.anon}</p>{/if}

                <label for="anon">From:</label>
			<input type=checkbox name=anon value="forum"> Sign message from 'Geograph Forum Moderators' rather than you specifically

                {if $errors.anon}</div>{/if}
        </div>

	{/if}

	</fieldset>

	<p><a href="/discuss/">back to discussions</a>
	<input type="submit" value="Create Comment Thread" style="font-size:1.1em"/></p>
	</form>

{/if}


{/dynamic}


{include file="_std_end.tpl"}

