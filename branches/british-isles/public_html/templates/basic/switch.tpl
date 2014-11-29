{assign var="page_title" value="Submission Method"}
{include file="_std_begin.tpl"}
<div class="tabHolder" style="text-align:right">
        <a href="/profile.php" class="tab">Back to Profile</a>
        <a href="/profile.php?edit=1" class="tab">General Settings</a>
        <a href="/profile.php?notifications=1" class="tab">Email Notifications</a>
        <a href="/choose-search.php" class="tab">Site Search Engine</a>
        <a href="/choose-preview.php" class="tab">Preview Method</a>
        <a href="/switch_tagger.php" class="tab">Tagging Box</a>
        <span class="tabSelected">Submission Method</span>
</div>
<div style="position:relative;" class="interestBox">
	<h2>Submission Method Update</h2>
</div>


<p>We have recently introduced tags (along with Geographical Context) as a complete replacement for the old category system.</p>

<p>Can read more about it in this article: <a href="http://www.geograph.org.uk/article/Transitioning-Categories-to-Tags">Transitioning Categories to Tags</a></p>


{dynamic}
{if $new}
	<div class="interestBox" style="padding-left:20px;border:3px solid lightgreen">
	<h4>You are currently opted into use the Tags only based submission, thank you!</h4>

	<p>For a short while you can revert to Categories, by <a href="?new=0">clicking here</a> (However please <a href="/contact.php">tell us why</a> you are switching back)
{else}
	<div class="interestBox" style="padding-left:20px; border:3px solid pink">
	<h4>You still have the category dropdown enabled on your account.</h4>

	<p>Please consider disabling it, do so by <a href="?new=1">clicking here</a>. This will allow us to move forward with the transition.</p>

	<ul>
		<li>You can see what the new tab based submission method looks like <a href="/stuff/category.php?type=top&v=3" target="_blank">on this mockup</a>.<br/><br/></li>
		<li>It's almost identical to the current submission, but the dropwdown to select the category from a mamoth 9000+ list, you just choose from a short compact list. Please try it!</li>
	</ul>

	(NOTE: This only applies, to the original submission method, not the new V2) 
{/if}
</div>

<p>Continue to the submission processes:</p>
<ul>
	<li><a href="/submit.php?new={$new}&amp;redir=false">Original Submission method</a></li>
</ul>


{/dynamic}


{include file="_std_end.tpl"}
