{include file="_std_begin.tpl"}



<h2>{$page_title|escape:'html'|default:'Untitled'} <small>:: Demo page</small></h2>



<ul>
	<li>By <a href="/profile/{$user_id}">{$realname|escape:'html'}</a></liv>
	<li>Grid Reference: <a href="/gridref/{$grid_reference|escape:'url'}">{$grid_reference|escape:'html'}</a></li>
	<li><a href="/photo/{$gridimage_id}">back to photo page</a></li>
</li>




<br/>


{include file="_std_end.tpl"}