{include file="_std_begin.tpl"}

{dynamic}
{if $temp}
<h2>Sorry, this feature is temporarily disabled</h2>
<p>For a short period we are disabling 'heavy' searches, to gauge what the impact of a more permanent removal of legacy text queries would be.</p>
{else}
<h2>Sorry, this feature is disabled</h2>
{/if}
{/dynamic}

<p>If you enjoyed this feature please <a title="Contact Us" href="/contact.php">Contact Us</a>{if $enable_forums}, or let us know via the <a href="/discuss/">Discussion Forum</a>{/if}, or simply with the 'Give Feedback' form below.

<ul>
	<li style="padding:20px">Most of the time these features are removed due to resource constraints - in that generating these pages cause strain on the server</li>
	
	<li style="padding:20px">Therefore to continue to offer this feature we need to work on an alternative implementation</li>

	<li style="padding:20px">But we need to know if its worth the investment, so get in contact if you like this feature. If you don't it probably wont return.</li>
</ul>

{include file="_std_end.tpl"}
