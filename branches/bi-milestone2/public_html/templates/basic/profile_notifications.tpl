{include file="_std_begin.tpl"}

<h2>Email Preferences</h2>
<p>Use this page to change what emails you receive from the site, using this page you can ensure we will not send you any messages if you desire.</p>

{dynamic}

<form class="simpleform" method="post" action="/profile.php" name="theForm">
<input type="hidden" name="notifications" value="1"/>

{if $errors.general}
<div class="formerror">{$errors.general}</div>
{/if}



<fieldset>
<legend>Change Suggestion Notifications</legend>

<div>
<input type="radio" {$selection.none} name="selection" value="none" id="selection_none" onclick="return change_selection(this)"/> <label for="selection_none">No emails</label>
<input type="radio" {$selection.some} name="selection" value="some" id="selection_some" onclick="return change_selection(this)" /> <label for="selection_some">Custom Selection</label>
<input type="radio" {$selection.all} name="selection" value="all" id="selection_all" onclick="return change_selection(this)" /> <label for="selection_all">All Notifications</label>
</div>
<div id="show113"><br/>
<b>When</b>: <input type="radio" {if $profile->ticket_when eq 'happens'}checked{/if} name="ticket_when" value="happens" id="when_happens" /> <label for="when_happens">As it happens</label>
<input type="radio" {if $profile->ticket_when eq 'digest'}checked{/if} name="ticket_when" value="digest" id="when_digest" /> <label for="when_digest">Daily Summary</label>

<br/><br/>
<div id="hide102">[+] <a href="javascript:void(show_tree(102));">expand <i>custom settings</i>...</a></div> 
<div style="display:none;" id="show102">

	<fieldset>
		<legend><b>Minor Suggestions</b></legend>

		<input type="checkbox" onclick="change_notifications(this)" name="notifications[minor_initial]" id="notifications_minor_initial" {$notifications.minor_initial} {$notifications.all}/>
		<label for="notifications_minor_initial">Initial Suggestion</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[minor_comment]" id="notifications_minor_comment" {$notifications.minor_comment} {$notifications.all}/>
		<label for="notifications_minor_comment">Follow Up Comments</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[minor_changes]" id="notifications_minor_changes" {$notifications.minor_changes} {$notifications.all}/>
		<label for="notifications_minor_changes">Closed with Changes</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[minor_nothing]" id="notifications_minor_nothing" {$notifications.minor_nothing} {$notifications.all}/>
		<label for="notifications_minor_nothing">Closed without Changes</label><br/>
	</fieldset>
	<div class="fieldnotes">Minor suggestions for example only change the spelling and grammar.</div>
	<fieldset>
		<legend><b>Normal Suggestions</b></legend>

		<input type="checkbox" onclick="change_notifications(this)" name="notifications[normal_initial]" id="notifications_normal_initial" {$notifications.normal_initial} {$notifications.all}/>
		<label for="notifications_normal_initial">Initial Suggestion</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[normal_comment]" id="notifications_normal_comment" {$notifications.normal_comment} {$notifications.all}/>
		<label for="notifications_normal_comment">Follow Up Comments</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[normal_changes]" id="notifications_normal_changes" {$notifications.normal_changes} {$notifications.all}/>
		<label for="notifications_normal_changes">Closed with Changes</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[normal_nothing]" id="notifications_normal_nothing" {$notifications.normal_nothing} {$notifications.all}/>
		<label for="notifications_normal_nothing">Closed without Changes</label><br/>
	</fieldset>
	
	<fieldset>
		<legend><b>Moderator Suggestions</b></legend>

		<input type="checkbox" onclick="change_notifications(this)" name="notifications[moderator_initial]" id="notifications_moderator_initial" {$notifications.moderator_initial} {$notifications.all}/>
		<label for="notifications_moderator_initial">Initial Suggestion</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[moderator_comment]" id="notifications_moderator_comment" {$notifications.moderator_comment} {$notifications.all}/>
		<label for="notifications_moderator_comment">Follow Up Comments</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[moderator_changes]" id="notifications_moderator_changes" {$notifications.moderator_changes} {$notifications.all}/>
		<label for="notifications_moderator_changes">Closed with Changes</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[moderator_nothing]" id="notifications_moderator_nothing" {$notifications.moderator_nothing} {$notifications.all}/>
		<label for="notifications_moderator_nothing">Closed without Changes</label><br/>
	</fieldset>
	
	<fieldset>
		<legend><b>Moderator Changes</b></legend>

		<input type="checkbox" onclick="change_notifications(this)" name="notifications[minor_modchanges]" id="notifications_minor_modchanges" {$notifications.minor_modchanges} {$notifications.all}/>
		<label for="notifications_minor_modchanges">Minor Changes Applied Directly</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[normal_modchanges]" id="notifications_normal_modchanges" {$notifications.normal_modchanges} {$notifications.all}/>
		<label for="notifications_normal_modchanges">Normal Changes Applied Directly</label><br/>

	</fieldset>
	<div class="fieldnotes">Moderators can make changes directly, use this box to be notified when these changes are made</div>

	<fieldset>
		<legend><b>Your Suggestions</b></legend>
		<i>including on your own images</i><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[your_comment]" id="notifications_your_comment" {$notifications.your_comment} {$notifications.all}/>
		<label for="notifications_your_comment">Follow Up Comments</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[your_changes]" id="notifications_your_changes" {$notifications.your_changes} {$notifications.all}/>
		<label for="notifications_your_changes">Closed with Changes</label><br/>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[your_nothing]" id="notifications_your_nothing" {$notifications.your_nothing} {$notifications.all}/>
		<label for="notifications_your_nothing">Closed without Changes</label><br/>
	</fieldset>
	
	{if $is_mod}
	<fieldset>
		<legend><b>Moderator Messages</b></legend>
		<input type="checkbox" onclick="change_notifications(this)" name="notifications[mod_comment]" id="notifications_mod_comment" {$notifications.mod_comment} {$notifications.all}/>
		<label for="notifications_mod_comment">Comments on your open suggestions</label><br/>
	</fieldset>
	{/if}
	
	<a href="javascript:void(hide_tree(102));">close</a>
</div>
</div>

</fieldset>



{if $enable_forums}
<fieldset>
<legend>Forum Subscriptions ({$sub_count})</legend>

<div id="hide103">[+] <a href="javascript:void(show_tree(103));">expand <i>subscription listing</i>...</a></div> 
<div style="display:none;" id="show103">
	{foreach from=$subs key=idx item=row}
		<div class="field_tick"> 

		<input type="checkbox" name="topic[{$row.topic_id}]" id="tick_{$row.topic_id}" checked="checked"/>
		<label for="tick_{$row.topic_id}"><b>{$row.topic_title|escape:'html'}</b> (<a href="/discuss/?action=vthread&amp;topic={$row.topic_id}" target="_blank">open</a>)</label>

		</div>
	{/foreach}
	<i>Subscribe to new threads by using the option on the thread itself</i><br/><br/>
	<a href="javascript:void(hide_tree(103));">close</a>
</div>

</fieldset>
{/if}

 
<fieldset>
<legend>General Notifications</legend>

<div class="field_tick">
	 
	<input {$contact_options.show_email} type="checkbox" name="contact_options[]" id="show_email" value="show_email">
	<label for="show_email">Show my email address on profile page </label>

	<br/><br/>

	<input {$contact_options.usermsg} type="checkbox" name="contact_options[]" id="usermsg" value="usermsg">
	<label for="usermsg">Allow visitors to contact me  </label>
	<div class="fieldnotes">people contacting you through the site, will not discover
	your email address unless you reply</div>

</div>

</fieldset>



<hr/>

 	<input type="submit" name="savechanges" value="Save Changes"/>
 	<input type="submit" name="cancel" value="Cancel"/>




 </form>	
{/dynamic}  

<script type="text/javascript">
{literal}

function change_selection(that) {
	if (that.value == 'none') {
		select_all(false);
		hide_tree(102);
		hide_tree(113);
	} else if (that.value == 'all') {
		select_all(true);
		hide_tree(102);
		show_tree(113);
	} else if (that.value == 'some') {
		show_tree(102);
		show_tree(113);
	}  
}

function select_all(result) {
	var ele = document.forms['theForm'].elements;
	for(q=0;q<ele.length;q++) {
		if (ele[q].name.indexOf('notifications[') == 0) {
			ele[q].checked = result;
		}
	}
}

function change_notifications(that) {
	that.form.elements['selection'][1].checked = true;
	show_tree(113);

}

{/literal}
</script>


{include file="_std_end.tpl"}
