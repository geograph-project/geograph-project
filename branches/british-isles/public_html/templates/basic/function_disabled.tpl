{include file="_std_begin.tpl"}

{dynamic}
{if $temp}
<h2>Sorry, this feature is temporarily disabled</h2>
<p>For a short period we are disabling 'heavy' searches, to gauge the impact of a more permanent removal of legacy text queries.</p>
{else}
<h2>Sorry, this feature is disabled</h2>
{/if}
{/dynamic}

<p>If you used to enjoy this feature, and would be intererested a similar feature, please <a title="Contact Us" href="/contact.php">contact us</a>{if $enable_forums}, or let us know via the <a href="/discuss/">Discussion Forum</a>{/if}, or simply with the 'Give Feedback' form below.

		<div id="showfeed2" class="interestBox"><form method="post" action="/stuff/feedback.php">
		<label for="feedback_comments"><b>Tell us what you enjoyed most about this previous feature</b>:</label><br/>
		<input type="text" name="comments" size="80" id="feedback_comments"/><input type="submit" name="submit" value="send"/>
		{dynamic}{if $user->registered}<br/>
		<small>(<input type="checkbox" name="nonanon" checked/> <i>Tick here to include your name with this comment, so we can then reply</i>)</small>
		{else}<br/>
		<i><small>If you want a reply please use the <a href="/contact.php">Contact Us</a> page. We are <b>unable</b> to reply to comments left here.</small></i>
		{/if}{/dynamic}
		<input type="hidden" name="template" value="{$smarty_template}"/>
		<input type="hidden" name="referring_page" value="{$smarty.server.HTTP_REFERER}"/>
		    <div style="display:none">
		    <br /><br />
		    <label for="name">Leave Blank!</label><br/>   
			<input size="40" id="name" name="name" value=""/>
		    </div>
		</form></div>
		<br/>


<ul>
	<li style="padding:10px">Mostly these features are removed due to resource constraints - generating these pages cause strain on the server</li>
	
	<li style="padding:10px">Therefore, to continue to offer this feature we need to work on an alternative implementation</li>

	<li style="padding:10px">...But we need to know if it's worth the investment, so get in contact if you like this feature. <b>If you don't it probably won't return.</b></li>
</ul>

{include file="_std_end.tpl"}
