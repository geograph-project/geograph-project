{assign var="page_title" value="Multi Search"}
{include file="_std_begin.tpl"}
{literal}

  <script type="text/javascript">
  
  function focusBox() {
  	el = document.getElementById('fq');
  	el.focus();
  }
  AttachEvent(window,'load',focusBox,false);
   
  </script>

{/literal}

<h2><a href="/finder/">Finder</a> :: Multi Search <sup style="color:red">Beta</sup></h2>

<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<p>
		<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/> (Enter a placename, grid reference, keyword search, or contributor name) 
	</p>
</form>

{if $inners}
	{foreach from=$inners item=item}
		<div style="position:relative;float:left">
			<div class="interestBox" style="margin-left:2px">{$item.title|escape:'html'|default:'Search'}</div>
			<iframe src="{$item.url}" width="{if count($inners) eq 1}800{elseif count($inners) eq 2}440{else}282{/if}" height="600"></iframe>
		</div>
	{/foreach}
	<br style="clear:both"/>
{else}
	<p>NOTE: Only enter one type of term, eg just a Grid Reference, or just a placename. In the future we plan to support multiple types of terms in one query. </p>
	
	
	
	{if $q}
		<p><i>No Results</i></p>
	{/if}
{/if}


{if $others}
	<h3>More possible searches for [ {$q|escape:'html'} ]</h3>
	<ul>
	{foreach from=$others item=item}
		<li>
		<b><a href="{$item.url}" target="_top">{$item.title|escape:'html'|default:'unknown'}</a></b>
		</li>
	{/foreach}
	</ul>
{/if}


{if $query_info}
	<p>{$query_info}</p>
{/if}

{include file="_std_end.tpl"}
