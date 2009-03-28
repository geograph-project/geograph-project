{assign var="page_title" value="Gravatars"}
{include file="_std_begin.tpl"}


<h2>Contributors with {external href="http://en.gravatar.com/" text="Gravatars"}</h2>
 
{foreach from=$list item=profile}
	<a href="/profile/{$profile.user_id}" title="{$profile.realname|escape:'html'}"><img src="http://www.gravatar.com/avatar/{$profile.md5_email}?r=G&amp;d=http://{$static_host}/img/blank.gif&amp;s=40" alt="{$profile.realname|escape:'html'}'s Gravatar"/></a>

{/foreach}

{include file="_std_end.tpl"}
