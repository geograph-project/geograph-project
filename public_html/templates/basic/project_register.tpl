{dynamic}
{assign var="page_title" value="Register::$title"}

{include file="_std_begin.tpl"}


{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="{$script_name}" method="post" name="theForm">

<input type="hidden" name="id" value="{$id|escape:"html"}"/>



<fieldset>
<legend>Register Interest :: {$title|escape:'html'}</legend>


<div class="field">

	{if $errors.supporter}<div class="formerror"><p class="error">{$errors.supporter}</p>{/if}

	<label for="supporter">Support:</label>
	<input type="checkbox" id="supporter" name="supporter" {if $register.supporter} checked{/if} /> (optional)

	<div class="fieldnotes">Show you as a supporter of this project - even if you can't help directly. Basically you are interested in its outcome. (The list of supporters is available publically)</div>

	{if $errors.supporter}</div>{/if}
</div>

<div class="field">

	{if $errors.helper}<div class="formerror"><p class="error">{$errors.helper}</p>{/if}

	<label for="helper">Helper:</label>
	<input type="checkbox" id="helper" name="helper" {if $register.helper} checked{/if} /> (optional)

	<div class="fieldnotes">Show you as a helper on this project. (The list of helpers is available publically)</div>

	{if $errors.helper}</div>{/if}
</div>

<div class="field">
	{if $errors.role}<div class="formerror"><p class="error">{$errors.role}</p>{/if}

	<label for="role">Role:</label> (optional)
	<textarea rows="2" cols="80" name="role" style="width:58em">{$register.role|escape:"html"}</textarea>

	<div class="fieldnotes">If registering as a helper, please description what you would like to do (or have done) within the projects. Your area of expertise or interest in the project - to help collaborators deside on their own roles.</div>

	{if $errors.role}</div>{/if}
</div>

<div class="field">

	{if $errors.subscriber}<div class="formerror"><p class="error">{$errors.subscriber}</p>{/if}

	<label for="subscriber">Subscribe:</label>
	<input type="checkbox" id="subscriber" name="subscriber"  {if $register.subscriber} checked{/if}/> (optional)

	<div class="fieldnotes">Register to receive updates on the progress of the project. (NOTE THIS IS NOT CURRENTLY FUNCTIONAL - no updates are sent yet)</div>

	{if $errors.subscriber}</div>{/if}
</div>


<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em"/></p>
</form>



{include file="_std_end.tpl"}
{/dynamic}
