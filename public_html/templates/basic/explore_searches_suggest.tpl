{assign var="page_title" value="Explore Featured Searches"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

{dynamic}

{if $saved}
	{if $ok}
		<p>Thank you for suggesting <a href="/search.php?i={$i}">this search</a>!</p>
	{else}
		<p style="color:#990000;font-weight:bold;">Unknown error, suggestion NOT saved. Please contact us.</p>
	{/if}
{elseif $query.created}
	<h3>Thank you for the suggestion</h3>
	<p>This search has already been suggested.</p>
{elseif $errormsg}
	<p style="color:#990000;font-weight:bold;">{$errormsg}</p>
{else}

<form class="simpleform" action="{$script_name}" method="post">

<input type="hidden" name="i" value="{$i|escape:"html"}"/>

 
<fieldset>
<legend>Suggest a Featured Search</legend>

<div class="field">
	 
	<label for="title">Search:</label>
	<tt>images{$query.searchdesc|escape:'html'}</tt>
	
</div>

<div class="field">
	{if $errors.comment}<div class="formerror"><p class="error">{$errors.comment}</p>{/if}
	 
	<label for="url">Reason:</label>
	<input type="text" name="comment" value="{$query.comment|escape:"html"}" maxlength="100" size="60"/></span>

	<div class="fieldnotes">Please explain why you think this search is worthy of being featured, in under 100 charactors.</div>
	
	{if $errors.comment}</div>{/if}
</div>

</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Submit" style="font-size:1.1em"/>
</form>
{if !$is_mod}
	<br/><br/>(Searches will only show on the site once they have been approved by a site moderator)</p>
{/if}

{/if}

{/dynamic}
{include file="_std_end.tpl"}
