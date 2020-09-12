{dynamic}
{assign var="page_title" value="Edit::$title"}

{include file="_std_begin.tpl"}
<script type="text/javascript">{literal}
function unloadMess() {
	var ele = document.forms['theForm'].elements['pledge'];
	var ele2 = document.forms['theForm'].elements['reason'];
	if (ele.value == ele.defaultValue && ele2.value == ele2.defaultValue) {
		return;
	}
	return "**************************\n\nYou have unsaved changes in the pledge/reason box.\n\n**************************\n";
}
//this is unreliable with AttachEvent
window.onbeforeunload=unloadMess;

function cancelMess() {
	window.onbeforeunload=null;
}
function setupSubmitForm() {
	AttachEvent(document.forms['theForm'],'submit',cancelMess,false);
}
AttachEvent(window,'load',setupSubmitForm,false);


{/literal}</script>

{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="{$script_name}" method="post" name="theForm">

<input type="hidden" name="id" value="{$id|escape:"html"}"/>
<input type="hidden" name="pledge_id" value="{$pledge_id|default:'new'}">
<input type="hidden" name="reason_id" value="{$reason_id|default:'new'}">


<fieldset>
<legend>Create/Edit Pledge</legend>

<p>Idea: {$idea.title|escape:'html'}</p>

<p>Add your support by adding a pledge and/or a reason why you think this this idea would be good. Please aim to be short and consise. If you have multiple reasons to support this idea, please submit them seperately. Thank you.</p> 

<div class="field">
	{if $errors.pledge}<div class="formerror"><p class="error">{$errors.pledge}</p>{/if}

	<label for="pledge" style="width:400px">What you would give:</label> (<span id="cnt_pledge">250</span> charactors left)
	<textarea rows="3" cols="80" name="pledge" style="width:58em" onkeyup="update_count(this.form,this.name)">{$pledge|escape:"html"}</textarea>

	<div class="fieldnotes">Explain what you would give here. Be as wacky or as uncoventional as you like. Can offer virtual stuff, as well as physical items. 
		While this offer isnt a legally binding contract, it really is intended you will actully send this, should the idea come to fruition.</div>

	<input type=checkbox name="pledge_anon" {if $pledge_anon}checked{/if}>Make this pledge anonymously (otherwise your name will be shown)<br/>

	{if $reason_id}
	<input type=checkbox name="pledge_delete">Permanently delete this pledge<br/>
	{/if}

	{if $errors.pledge}</div>{/if}
</div>

<br><br><br>

<div class="field">
	{if $errors.reason}<div class="formerror"><p class="error">{$errors.reason}</p>{/if}

	<label for="reason" style="width:400px">Why you support this idea: (optional)</label> (<span id="cnt_reason">250</span> charactors left)
	<textarea rows="3" cols="80" name="reason" style="width:58em" onkeyup="update_count(this.form,this.name)">{$reason|escape:"html"}</textarea>

	<div class="fieldnotes">Short explanation, for why you think this is a good and worthwhile. Be substantive, not just 'because it would be cool', but explain how it would help you, or others</div>

	<input type=checkbox name="reason_anon" {if $reason_anon}checked{/if}>Suggest this reason anonymously (otherwise your name will be shown)<br/>

	{if $reason_id}
	<input type=checkbox name="reason_delete">Permanently delete this reason<br/>
	{/if}


	{if $errors.reason}</div>{/if}
</div>


</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em"/></p>
</form>

{literal}
<script>
function update_count(form,ident) {
	var ele = form.elements[ident];
	document.getElementById('cnt_'+ident).innerHTML = (250-ele.value.length);
}

function set_counts() {
	var form = document.forms['theForm'];
	update_count(form,'pledge');
	update_count(form,'reason');
}
AttachEvent(window,'load',set_counts,false);

</script>
{/literal}


{include file="_std_end.tpl"}
{/dynamic}
