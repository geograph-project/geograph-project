{assign var="page_title" value=$title}

{assign var="content_articletext" value=$content|articletext}

{include file="_std_begin.tpl"}

<table><tr><td>
<h2 style="display:inline">{$title}</h2>
</td></tr><tr><td>
<div style="text-align:right">
{if $licence == 'copyright'}
	<small>&copy;</small> <a href="/profile.php?u={$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>, {$publish_date|date_format:" %B, %Y"}
{else}
	{if $licence == 'cc-by-sa/2.0'}
		<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
		alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; Text &copy; Copyright {$publish_date|date_format:" %B, %Y"}, <a href="/profile.php?u={$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>; {if $imageCredits}, text and images{/if}
	licensed for reuse under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
	{else}
		by <a href="/profile.php?u={$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>, {$publish_date|date_format:" %B, %Y"}
	{/if}
{/if}

</div> 
</td></tr></table>
{if $copyright}{$copyright}{/if}
<hr>
{$content_articletext}

{if $imageCredits}
<hr/>
<div class="ccmessage copyright"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
		alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; Images used on this page, &copy; Copyright {$imageCredits};
	licensed for reuse under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>. <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">http://creativecommons.org/licenses/by-sa/2.0/</a><br/><br/></div>
{/if}

{if $copyright}{$copyright}{/if}

{include file="_std_end.tpl"}
