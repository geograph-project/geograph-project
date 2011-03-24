{assign var="page_title" value="Geographical Context Categories"}
{include file="_std_begin.tpl"}


<h2><a href="?">Geographical Context Category Mapping</a> :: Statistics</h2>

<h3>Categories</h3>

<blockquote><p><b>{$normal|thousends}</b> Categories, of which <b>{$cats|thousends}</b> have had suggestions made.</p></blockquote>

<h3>Suggestions</h3>

<blockquote><p><b>{$suggestions|thousends}</b> suggestions, made by <b>{$users}</b> users, using <b>{$tops|thousends}</b> different Geographical Context categories.</p></blockquote>

<h3>Geographical Context Category</h3>

<blockquote><p><b>{$final|thousends}</b> categories have confirmed Geographical Context category, using <b>{$tops_final|thousends}</b> different Geographical Context categories.</p></blockquote>

<hr/>

<p>Special thanks to the following users for the suggestions made to make this this mapping possible: 
{foreach from=$userlist item=item}
	<a href="/profile/{$item.user_id}">{$item.realname|escape:'html'}</a>, 
{/foreach}
<b>Thank you!</b>
</p>

<br/><br/>

<a href="?">Go Back</a>

<br/><br/>

{include file="_std_end.tpl"}
