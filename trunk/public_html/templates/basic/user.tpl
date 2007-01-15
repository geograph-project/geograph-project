{assign var="page_title" value="Contributing Users"}
{include file="_std_begin.tpl"}

<h2>Contributing Users</h2>

<ul style="font-size:0.7em"><li>The bigger the text the more images contributed</li>

<li>You can also click a nickname to see the users profile.</li>
<li>Switch to <a href="/statistics.php?by=user">list version</a>.</li></ul>

<h3>Contributing Users</h3>
<p class="wordnet" align="justify"> 
{foreach from=$users key=nick item=obj}
<a style="font-size:{$obj.size}px;" title="{$obj.realname},{$obj.images} images" href="/user/{$nick|escape:url}/">{$nick}</a>
{/foreach}
</p>
 		
{include file="_std_end.tpl"}
