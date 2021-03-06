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
  
  function use_in_search(name) {
  	if (window.opener) {
  		window.opener.document.forms['theForm'].elements['user_name'].value = name;
  		window.close();
  	} else {
  		alert("Error: Form no longer available");
  	}
  }
  
  </script>

{/literal}

<h2><a href="/finder/">Finder</a> :: Contributors</h2>

{if !$noSphinx}
<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<p>
		<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/>
	</p>
	{if $popup}
		<input type="hidden" name="popup" value=""/>
	{/if}
</form>

{if count($results) eq 15}
	<p>
		<small>&middot; To refine the results simply add more keywords (view <a href="#cheatsheet">Cheatsheet</a>)</small>
	</p>
{/if}

<ol start="{$offset}">
{foreach from=$results item=item}
	<li>
	<b><a href="/profile/{$item.user_id}" target="_top">{$item.realname|escape:'html'|default:'unknown'}</a></b>
	{if $popup}
	[<a href="javascript:void(use_in_search('{$item.user_id}:{$item.realname|escape:'html'}'));">Use in search</a>]{/if}
	
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

<div style="margin-top:0px"> 
{if $pagesString}
	( Page {$pagesString})
{/if}
</div>	

{if $query_info}
	<p>{$query_info}</p>
{/if}


<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>you can enter just the first few letters of a name</li>
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example : <tt>smith -john</tt></li>
		<li>can use OR (Uppercase only!) to match <b>either/or</b> keywords; example: <tt>john OR joan</tt></li>
	</ul>
</div>
{/if}

{include file="_std_end.tpl"}
