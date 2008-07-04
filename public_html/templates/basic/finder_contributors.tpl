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

<h2>Contributor Search</h2>

<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<p>
		<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/>
	</p>
</form>


<ol>
{foreach from=$results item=item}
	<li>
	<b><a href="/profile/{$item.user_id}" target="_top">{$item.realname|escape:'html'|default:'unknown'}</a></b>
	{if $item.nickname}<small>Nickname: {$item.realname|escape:'html'}</small>{/if}
	
	{if $item.images}
	<small><small style="color:gray">{$item.images} images submitted</small></small>{/if}
	</li>
{foreachelse}
	{if $q}
		<li><i>There is no content to display at this time.</i></li>
	{/if}
{/foreach}

</ol>

{if $query_info}
	<p>{$query_info}</p>
{/if}
	
{if count($results) eq 15}
	<p>
		<small>&middot; There is no paging of results, to access further results simply add more keywords to refine your search (view <a href="#cheatsheet">Cheatsheet</a>)</small>
	</p>
{/if}


<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>you can enter just the first few letters of a name</li>
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example : <tt>smith -john</tt></li>
		<li>can use OR to match <b>either/or</b> keywords; example: <tt>john OR joan</tt></li>
	</ul>
</div>


{include file="_std_end.tpl"}
