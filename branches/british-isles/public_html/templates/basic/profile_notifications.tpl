{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<h2>Notification Preferences - Experimental</h2>

{dynamic}

<div class="interestBox">
	Note: This feature is still being worked on. By enabling notifications below you agree that: 
	<ul>
		<li>We may end up sending emails more regular than specified (but will try to avoid it!)</li>
		<li>We may sometimes fail to send a notification, and that we wont start sending them right away</li>
		<li>For now - changing your preference below, might reset your notifications history, meaning will get duplicate notificiations</li>
	</ul>
</div>

<br/><br/>


<div style="float:right">
Provisional Schedule:
<ul>
	<li>Daily: About 7.30am
	<li>Weekly: Friday 7.30am
	<li>Monthly: 15th of the month
</ul>
</div>

<form method="post">
	Ideal frequency: <select name="freq">
		{html_options values=$freqs output=$freqs selected=$freq}
	</select><br/>

	For:
	<ul>
	{foreach from=$items key=key item=item}
		<li><input type=checkbox name="items[]" value="{$key}"{if $item.checked} checked{/if}/> {$item.title|escape:'html'}</li>
	{/foreach}
	</ul>

	<input type=submit name=savechanges value="Save changes"/>
</form>



{/dynamic}
{include file="_std_end.tpl"}

