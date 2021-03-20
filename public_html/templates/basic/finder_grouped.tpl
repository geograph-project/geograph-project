{assign var="page_title" value="Grouped Search"}
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

{if !$inner}
	<h2><a href="/finder/">Finder</a> :: Grouped Search PROTOTYPE!</h2>

	<form action="{$script_name}" method="get" onsubmit="focusBox()">
		<p>
			<label for="fq">keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
			at most <select name="number">
				{html_options options=$numbers selected=$number}
			</select> from each <select name="group">
                                {html_options options=$groups selected=$group}
                        </select>
			<input type="submit" value="Search"/>
		</p>
		<p>Note: This is only a demo - to test the 'at most X from each Y' functionality we just got. In particular ordering of images is rather unpredictable.</p>
	</form>

	{if count($results) eq 30}
		<p>
			<small>&middot; To refine the results simply add more keywords</small>
		</p>
	{/if}
{/if}

{assign var="last" value="-1"}
{foreach from=$results item=image}
	{if $last != $image->group}
		{if $last != -1}
			</div>
		{/if}
		<div class="interestBox" style="float:left;margin:5px">
		{assign var="last" value=$image->group}
        {/if}

	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="{$image->grid_reference} : {$image->title|escape:'html'} by {$image->realname} {$image->dist_string} - click to view full size image" href="/photo/{$image->gridimage_id}" target="_parent">{$image->getThumbnail(120,120,false,true)}</a></div>
	  </div>

{foreachelse}
	{if $q}
		<p><i>There is no content to display at this time.</i></p>
	{/if}
{/foreach}
		{if $last != -1}
			</div>
		{/if}



<div style="margin-top:0px;clear:both"> 
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
{include file="_std_end.tpl"}
{/if}
