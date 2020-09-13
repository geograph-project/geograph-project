<tr class=pass>
       <td>Smarty Templating</td>
       <td class=result>pass</td>
       <td>Cached Time: {$smarty.now} (when this template was cached) Version: {$smarty_version}</td>
</tr>

{assign var="cached_now" value=$smarty.now}

{dynamic cached_user_id=$cached_now}
	<tr class=pass>
	       <td>Smarty Dynamic Block</td>
	       <td class=result>pass</td>
	       <td>Time Now: {$smarty.now} (should be the live time)</td>
	</tr>

	{assign var="noncached_now" value=$smarty.now}

	{if $cached_user_id eq $noncached_now}
		<tr class=error>
		       <td>Smarty Caching Test</td>
		       <td class=result>error</td>
		       <td>Two times above are same, caching appears non functional</td>
		</tr>
	{else}
		<tr class=pass>
		       <td>Smarty Caching Test</td>
		       <td class=result>pass</td>
		       <td>Caching Appears functional, times above differ</td>
		</tr>
	{/if}
{/dynamic}
