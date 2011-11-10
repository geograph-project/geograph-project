{dynamic}
{assign var="page_title" value="Edit Tag Description"}

{include file="_std_begin.tpl"}
<script type="text/javascript">{literal}
function unloadMess() {
	var ele = document.forms['theForm'].elements['description'];
	if (ele.value == ele.defaultValue) {
		return;
	}
	return "**************************\n\nYou have unsaved changes in the description box.\n\n**************************\n";
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


        <div class="tabHolder">
                <a href="/tags/primary.php" class="tab">Geographical Context</a>
                <a href="/article/Image-Buckets" class="tab">Image Buckets</a>
		{if $thetag || $theprefix || $prefixes}
                <a href="/tags/" class="tabSelected">Tags</a>
		{else}
                <span class="tabSelected">Tags</span>
		{/if}
        </div>
        <div style="position:relative;padding-bottom:3px" class="interestBox">
		<h2 style="margin:0">Public Tags <sup><a href="/article/Tags" class="about" style="font-size:0.7em">About tags on Geograph</a></sup></h2>
        </div>

{if $error}
	<div><span class="formerror">{$error}</span></div>
{/if}

<form class="simpleform" action="{$script_name}?tag={if isset($theprefix)}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}" method="post" name="theForm">

<input type="hidden" name="tag_id" value="{$tag_id|escape:"html"}"/>

<p><a href="/tags/?tag={if isset($theprefix)}{$theprefix|escape:'url'}:{/if}{$thetag|escape:'url'}">&lt;&lt; Return to Tag Page</a></p>


<fieldset>
<legend>Edit Description for '{if isset($theprefix)}{$theprefix|escape:'html'}:{/if}{$thetag|escape:'html'}'</legend>




<div class="field">
	{if $errors.description}<div class="formerror"><p class="error">{$errors.description}</p>{/if}

	<label for="description">Description:</label>
	<textarea rows="10" cols="80" name="description" style="width:58em">{$description|escape:"html"}</textarea></p>

	<div class="fieldnotes">Note: Tag descriptions are like a wiki - anybody may edit/refine them.</div>

	{if $errors.description}</div>{/if}
</div>



</fieldset>

<input type="reset" name="reset" value="Reset" onclick="return confirm('Are you sure? Changes will be lost!');"/>
<input type="submit" name="submit" value="Save Changes..." style="font-size:1.1em"/> </p>
</form>



<div class="interestBox" style="background-color:pink; font-size:0.7em; border-top:2px solid gray"><i>For clarification, you are submitting this description to Geograph Project directly. Geograph Project then grants any contributor the right to re-use any tag description.</i></div>

{include file="_std_end.tpl"}
{/dynamic}
