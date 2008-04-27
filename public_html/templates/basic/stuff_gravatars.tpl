{assign var="page_title" value="Gravatars"}
{include file="_std_begin.tpl"}


<h2>Contributors with {external href="http://en.gravatar.com/" text="Gravatars"}</h2>
 
{foreach from=$list item=profile}
	<a href="/profile/{$profile.user_id}" title="{$profile.realname|escape:'html'}"><img src="http://www.gravatar.com/avatar.php?gravatar_id={$profile.md5_email}&amp;rating=G&amp;default=http://{$static_host}/img/blank.gif&amp;size=40" alt="{$profile.realname|escape:'html'}'s Gravatar"/></a>

{/foreach}

{include file="_std_end.tpl"}
