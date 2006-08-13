{assign var="page_title" value=$title}

{include file="_std_begin.tpl"}

<table><tr><td>
<h2 style="display:inline">{$title}</h2>
</td></tr><tr><td>
<div style="text-align:right">
{if $licence == 'copyright'}
<small>&copy; {$create_time|date_format:" %B, %Y"}</small>
{else}
by
{/if}
<a href="/profile.php?u={$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>
</div> 
</td></tr></table>
<hr>
{$content|articletext}

{include file="_std_end.tpl"}
