{include file="_std_begin.tpl"}

{dynamic}
{if $temp}
<h2>Sorry, this feature is temporarily disabled</h2>
<p>For a short period we are disabling 'heavy' searches, to gauge what the impact of a more permanent removal of legacy text queries would be.</p>
{else}
<h2>Sorry, this feature is disabled</h2>
{/if}
{/dynamic}

<p>If you enjoyed this feature please <a title="Contact Us" href="/contact.php">Contact Us</a>{if $enable_forums}, or let us know via the <a href="/discuss/">Discussion Forum</a>{/if}, <span style="color:red">as it will help us work out best how to work on alternatives</span>.</p>

{include file="_std_end.tpl"}
