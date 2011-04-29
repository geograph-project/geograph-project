{assign var="page_title" value="Category Search"}
{if $inner}
{include file="_basic_begin.tpl"}
{else}
{include file="_std_begin.tpl"}
{/if}
{literal}
<style type="text/css">
	ul.explore tt {
		border:1px solid gray;
		padding:5px;
	}
</style>

  <script type="text/javascript">

  function focusBox() {
  	if (el = document.getElementById('fq')) {
  		el.focus();
  	}
  }
  AttachEvent(window,'load',focusBox,false);

  </script>

{/literal}

{if !$inner}
	<h2><a href="/finder/">Finder</a> :: Categories</h2>

	<form action="{$script_name}" method="get" onsubmit="focusBox()">
		<p>
			<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			<input type="submit" value="Search"/>
		</p>
	</form>

	{if count($results) eq 60}
		<p>
			<small>&middot; To refine the results simply add more keywords (view <a href="#cheatsheet">Cheatsheet</a>)</small>
		</p>
	{/if}
{/if}

		<div style="float:right;width:200px">
			Geographical Context suggestion
		</div>

<ol start="{$offset}" style="margin:-8px">
{foreach from=$results item=item}
	<li>
	{if $item.top}
		<div style="float:right;width:200px">
			top:{$item.top|escape:'html'}
		</div>
	{/if}


	<b><a href="/search.php?imageclass={$item.imageclass|escape:'url'}&amp;do=1" target="_top">{$item.imageclass|escape:'html'|default:'unknown'}</a></b>

	{if $item.images}
	<small><small style="color:gray">{$item.images} images</small></small>{/if}
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

{if $inner}
</body>
</html>
{else}
<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>you can enter just the first few letters of a name</li>
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example : <tt>smith -john</tt></li>
		<li>can use OR (Uppercase only!) to match <b>either/or</b> keywords; example: <tt>john OR joan</tt></li>
	</ul>
</div>


{include file="_std_end.tpl"}
{/if}