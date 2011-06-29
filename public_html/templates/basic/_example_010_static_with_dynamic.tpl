{include file="_std_begin.tpl"}



<h2>Static Page with Dynamic section</h2>

<p>Date when generated {$static_now} (Using a variable in is_cached)</p>

{dynamic}
	<p>And a dynamic bit: {$user->realname|escape:'html'|default:'Unregistered Guest'} (This is different for everyone who views the page)
	<p>Date now {$now} (changes on every page load)</p>
{/dynamic}

<br/>


{include file="_std_end.tpl"}