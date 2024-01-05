{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<div class="tabHolder" style="text-align:right">
        <a href="/profile.php" class="tab">Back to Profile</a>
        <a href="/profile.php?edit=1" class="tab">General Settings</a>
        <span class="tabSelected">Email Notifications</span>
        <a href="/choose-search.php" class="tab">Site Search Engine</a>
        <a href="/choose-preview.php" class="tab">Preview Method</a>
        <a href="/switch_tagger.php" class="tab">Tagging Box</a>
</div>
<div style="position:relative;" class="interestBox">
	<h2>Email Notifications: Preferences (Experimental)</h2>
</div>



{dynamic}


<div>
<p>This page configures automated email notifications, which will let you know how your contributed images are being used around the site.</p>


<form method="post">
	<h4>Email frequency</h4>
  <select name="freq" style="margin-left:20px;">{html_options values=$freqs output=$freqs selected=$freq}</select>

	<h4>Notifications</h4>
	<ul>
	{foreach from=$items key=key item=item}
		<li><input type=checkbox name="items[]" value="{$key}"{if $item.checked} checked{/if}/> {$item.title|escape:'html'}</li>
	{/foreach}
	</ul>

	<input type=submit name=savechanges value="Save changes"/>
</form>
</div>


<div>
<h4>Typical email schedule</h4>
<ul>
	<li>Daily: About 7.30am
	<li>Weekly: Friday 7.30am
	<li>Monthly: 15th of the month
</ul>
</div>

{/dynamic}
{include file="_std_end.tpl"}

