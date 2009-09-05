{assign var="page_title" value="Contributor Search"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
	ul.explore tt {
		border:1px solid gray;
		padding:5px;
	}
</style>

  <script type="text/javascript">
  
  function focusBox() {
  	el = document.getElementById('fq');
  	el.focus();
  }
  AttachEvent(window,'load',focusBox,false);
  
  </script>

{/literal}

<h2><a href="/finder/">Finder</a> :: Contributors</h2>

<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<p>
		<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/>
	</p>
</form>

{if count($results) eq 15}
	<p>
		<small>&middot; To refine the results simply add more keywords (view <a href="#cheatsheet">Cheatsheet</a>)</small>
	</p>
{/if}


{if $gaz_results}
	{box colour="333" style="width:160px;float:left;"}

	<ol start="{$offset}">
	{foreach from=$gaz_results item=item}
		<li>
		{$item.gr} <b><a href="/search.php?placename={$item.id}&amp;do=1" target="_top">{$item.name|escape:'html'|default:'unknown'}</a></b>
		{if $item.localities}<small>{$item.localities|escape:'html'}</small>{/if}

		</li>
	{foreachelse}
		{if $q}
			<li><i>There is no content to display at this time.</i></li>
		{/if}
	{/foreach}

	</ol>

	{/box}
{/if}



{if $user_results}
	{box colour="333" style="width:160px;float:left;"}

	<ol start="{$offset}">
	{foreach from=$user_results item=item}
		<li>
		<b><a href="/profile/{$item.user_id}" target="_top">{$item.realname|escape:'html'|default:'unknown'}</a></b>
		{if $item.nickname}<small>Nickname: {$item.nickname|escape:'html'}</small>{/if}

		{if $item.images}
		<small><small style="color:gray">{$item.images} images submitted</small></small>{/if}
		</li>
	{foreachelse}
		{if $q}
			<li><i>There is no content to display at this time.</i></li>
		{/if}
	{/foreach}

	</ol>

	{/box}
{/if}


{if $query_info}
	<p>{$query_info}</p>
{/if}


<br style="clear:both"/>

{include file="_std_end.tpl"}
