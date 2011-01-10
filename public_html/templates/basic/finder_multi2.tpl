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

<h2><a href="/finder/">Finder</a> :: Multi Search mk2 <sup style="color:red">Beta</sup></h2>

<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<p>
		<label for="fq">Name</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/> (Enter a placename, grid reference, keyword search, or contributor name)
	</p>
</form>

{if $results}

	{foreach from=$results item=result}
		<h3>{$result.title|escape:'html'}</h3>
		<ul>
			{foreach from=$result.results item=row}
				<li><b><a href="{$row.link|escape:'html'}">{$row.title|escape:'html'}</a></b><small>
				{if $row.type}
						<i>{$row.type|escape:'html'}</i>
				{/if}
				{if $row.images}
						with <b>{$row.images}</b> images
				{/if}
				{if $row.grid_reference}
						in <a href="/gridref/{$row.grid_reference|escape:'url'}">{$row.grid_reference|escape:'html'}</a>
				{/if}
				{if $row.realname}
						by <a href="/profile/{$row.user_id}">{$row.realname|escape:'html'}</a>
				{/if}</small>
				</li>
			{/foreach}
		</ul>
		{if $result.count}
			{if $result.count > 5}
				<b>{$result.count}</b> in total.
				{if $result.link}
						<a href="{$result.link|escape:'html'}">View all results &gt;&gt;&gt;</a>
				{/if}
			{elseif $result.link}
				<a href="{$result.link|escape:'html'}">View in dedicated search &gt;&gt;&gt;</a>
			{/if}
		{elseif $result.link}
			<a href="{$result.link|escape:'html'}">View all results &gt;&gt;&gt;</a>
		{/if}
		<hr/>
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
