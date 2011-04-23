{assign var="page_title" value="Submission Method"}
{include file="_std_begin.tpl"}

<h2>Submission Method Update</h2>

<p>We will shortly be introducing Tags as a complete replecement for categories.</p>

<p>Can read more about it in this article: <a href="http://www.geograph.org.uk/article/Transitioning-Categories-to-Tags">Transitioning Categories to Tags</a></p>


{dynamic}
<div class="interestBox" style="padding-left:20px">
{if $new}
	<h4>You are currently opted into use the new Tags based submission, thank you!</h4>

	<p>For a short while you can revert to Categories, by <a href="?new=0">clicking here</a> (Please tell us why you are switching back -via the forum, or the feedback form below)
{else}
	<h4>You have <u>not</u> yet opted to use the new Submission method.</h4>

	<p>Please consider switching, do so by <a href="?new=1">clicking here</a>.</p>

	<ul>
		<li>You can see what the new tab based submission method looks like <a href="/stuff/category.php?type=top&v=3" target="_blank">on this mockup</a>.<br/><br/></li>
		<li>Note: That the new submission method is new and might still have issues, so you can still opt back out of using it by returning to this page.</li>
	</ul>

{/if}
</div>

<p>Continue to the submission processes:</p>
<ul>
	<li><a href="/submit.php?new={$new}&amp;redir=false">Original Submission method</a></li>
	<li><a href="/submit2.php?new={$new}">Submission v2</a></li>
	<li><a href="/submit2.php?display=tabs&amp;new={$new}">Submission v2 (tabs)</a></li>
	<li><a href="/submit-multi.php?new={$new}">Multi Submission</a></li>
</ul>


{/dynamic}


{include file="_std_end.tpl"}
