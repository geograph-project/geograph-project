{assign var="page_title" value="Search Engine to Use"}
{include file="_std_begin.tpl"}


<div class="tabHolder" style="text-align:right">
        <a href="/profile.php" class="tab">Back to Profile</a>
        <a href="/profile.php?edit=1" class="tab">General Settings</a>
        <a href="/profile.php?notifications=1" class="tab">Email Notifications</a>
        <a href="/choose-search.php" class="tab">Site Search Engine</a>
        <span class="tabSelected">Preview Method</span>
        <a href="/switch_tagger.php" class="tab">Tagging Box</a>
        <a href="/switch.php" class="tab">Submission Method</a>
</div>
<div style="position:relative;" class="interestBox">
	<h2>Preview Method - HIGHLY EXPERIMENTAL</h2>
</div>

<p>Use this page to configure which 'preview' method to use.<br/><br/>

<div class="interestBox">
Currently, this ONLY affects the following pages...
<ul>
	<li><a href="/finder/recent.php">Recently Submitted Images</a></li>
	<li>New <a href="/of/basin">Images Of</a>/<a href="/near/Oxford/SP5106">Images Near</a> Interface</a></li>
	<li>First page of the <a href="/discuss/index.php?&action=vthread&forum=12&topic=26442">Discussion Thread</a></li>
	<li><a href="/search.php?i=49292411&displayclass=thumbsmore">thumbnails + more</a> search results pages</li>
</ul>(being convenient pages to test out this feature, if this trail is successful, will add to more pages)
</div>

<p>
<i>NOTE: Changing the option on this page, can take about an hour to fully apply. Please do not use F5 to try to hurry it along.

To see what method you currently have enabled can try pointing at this link: <a href="/photo/12345">link to example photo</a></i></p>

{dynamic}
{if $optset}
	<div class="interestBox" style="margin:20px"><b>Option Saved</b></div>
{/if}
{/dynamic}


<form method="get">
<table>
<tr>
	<td>
		<h3>None</h3>
	</td>
	<td>
		No preview popup is provided.

		{dynamic}
		{if $option == 'none'}
			<b><i>This is your current selection</i></b>
		{else}
			<input type="submit" name="submit[none]" value="Use this"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<tr>
	<td>
		<h3>Preview Bar</h3>
	</td>
	<td>
		A bar along the bottom (or top) of the window, providing a bigger thumbnail, big title, description, and map extract.

		{dynamic}
		{if $option == 'preview'}
			<b><i>This is your current selection</i></b>
		{else}
			<input type="submit" name="submit[preview]" value="Use this"/>
		{/if}
		{/dynamic}
	</td>
</tr>
<tr>
	<td>
		<h3>Preview Popup</h3>
	</td>
	<td>
		Pops up a smaller window, just containing a bigger thumbnail, and title/description. 

		{dynamic}
		{if $option == 'preview2'}
			<b><i>This is your current selection</i></b>
		{else}
	                <input type="submit" name="submit[preview2]" value="Use this"/>
		{/if}
		{/dynamic}
	</td>
</tr>

</table>
</form>

{dynamic}
<script src="/preview.js.php?{$option}"></script>
{/dynamic}

{include file="_std_end.tpl"}
