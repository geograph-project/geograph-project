{assign var="page_title" value="Broken Links"}
{include file="_std_begin.tpl"}

<script src="{"/sorttable.js"|revision}"></script>


<h2>Broken Links</h2>

<form method="get" action="{$script_name}">
    <p>Severity: 
    <select name="l">
	{html_options options=$levels selected=$l}
    </select>
    {dynamic}
    {if $user->registered}
	<select name="u">
		{if $u && $u != $user->user_id}
			<option value="{$u}">Just for {$profile->realname}</option>
		{/if}
		<option value="{$user->user_id}">Just for {$user->realname}</option>
		<option value="" {if !$u} selected{/if}>For everyone</option>
	</select>
    {else}
	{if $u}
	<select name="u">
		<option value="{$u}" selected>Just for {$profile->realname}</option>
		<option value="">For everyone</option>
	</select>
	{/if}
    {/if}
    {/dynamic}
	[<label><input type=checkbox name=missing {$missing_checked}> only where unable to find archive version]
    <input type="submit" value="Go"/></p></form>
</form>

{if $table}

{if $grouped}
	If Count is above 1, then row show in just an example link/image.
{/if}

<p>Click a column header to reorder</p>

<form action"{$script_name}" method="post">

<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5" style="font-size:0.9em">
<thead>
	<tr>
		<td>Image</td>
		<td>Link &amp; Reported error</td>
		<td>Code</td>
		<td>Checked</td>
		{if $grouped}
			<td>Count</td>
		{else}
			<td>Retry</td>
		{/if}
	</tr>
</thead>
<tbody>
	{foreach from=$table item=item name="i"}
		{assign var="HTTP_Status" value=$item.HTTP_Status}
	{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
	<tr bgcolor="{$bgcolor}">
		<td sortvalue="{$item.gridimage_id}" align="right">
			{if $item.images > 1}
				<a href="/editimage.php?id={$item.gridimage_id}">{$item.ids|replace:',':'<br/>'}</a>
			{else}
				<a href="/editimage.php?id={$item.gridimage_id}">{$item.gridimage_id}</a>
			{/if}
		</td>
		<td sortvalue="{$item.url|escape:'html'}">{external href=$item.url text=$item.url|replace:"http://":""|truncate:90|escape:html|regex_replace:'/^([\w\.-]+)/':'<b>$1</b><small>'}<br/>
		<b>{$item.HTTP_Status}</b> {$codes.$HTTP_Status}</small>
		{if $item.HTTP_Location}
			<small>... has {if $item.HTTP_Status >=400} previouslly{/if} reported a {external href=$item.HTTP_Location text="new location"}
			{if strpos($item.HTTP_Location,'404')}
				[<b>Which <i>appears</i> to be an Error Page</b>]
			{/if}
			</small>
		{/if}
		{if $item.archive_url}
			<br><small>... Found {external href=$item.archive_url text="archived version online"}
			</small>
		{/if}
		
		</td>
		<td class=nowrap>{$item.HTTP_Status}{if $item.HTTP_Status_final && $item.HTTP_Status_final != $item.HTTP_Status}
			-&gt; {$item.HTTP_Status_final}
		{/if}</td>
		<td sortvalue="{$item.last_checked}" style="font-size:0.8em">{$item.last_checked|date_format:"%e %b %Y"}</td>
		{if $item.count}
			<td align=right>{$item.count}</td>
		{else}
		<td style="font-size:0.8em" sortvalue="{$smarty.foreach.i.iteration}">
			{if $item.HTTP_Status != 200 && $item.failure_count < 4} 
				<input type="checkbox" name="retry[]" value="{$item.url|escape:'html'}"/>
			{/if}
		</td>
		{/if}
	</tr>
	{/foreach}
</tbody>
</table>

{if $total == 100}
	Only 100 rows shown here, its possible there are more.
{/if}

<p>
If you have checked some links and believe them to be ok despite the result of our recent automatic check, please tick the relevant links above and click the following button: <input type="submit" name="Submit" value="Schedule a recheck"/><br/>
(Note: even after submitting this form it may take some time for your rescheduled checks to be processed and disappear from this page)</p>

</form>

{else}
	<i>No Matching data to show</i>
{/if}



<h3>HTTP Status Codes</h3>
More information at {external href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes" text="List of HTTP status codes at Wikipedia"}
Summary of most common ones likely to see here:
<ul>
<li><tt>200 - OK</tt> - probably no problem
<li><tt>301 - Moved Permanently</tt> - the page has probably moved to a new URL</li> 
<li><tt>302 - Found</tt> - page can be found at a new URL</li> 
<li><tt>304 - Not Modified</tt> - Page is unchanged since last checked</li>
<li><tt>400 - Bad Request</tt> - the server refusing the connect (it might just be refusing the Checking Bot)</li> 
<li><tt>401 - Unauthorized</tt> - The page requires authentiation, typically means not publically viewable</li> 
<li><tt>403 - Forbidden</tt> - the server refused to return the page (it might just be refusing the Checking Bot)</li> 
<li><tt>404 - Not Found</tt> - the page is not there, might be temporally, but likely gone</li> 
<li><tt>410 - Gone</tt> - the page is gone, typically explicitly configured, and unlikely to be a mistake</li> 
<li><tt>500 - Internal Server Error</tt> - generic error to cover wide range of things</li> 
<li><tt>502 - Bad Gateway</tt> - typically a short term error with the site, might come back</li> 
<li><tt>503 - Service Unavailable</tt> - The server cannot handle the request, typically short term</li> 
<li><tt>522 - Connection timed out</tt> - fucntionlly similar to 502, a short term error</li> 
<li><tt>530 - <i>non standard error</i></tt> - likely "Origin DNS Error", meaning the site has gone offline</li> 
<li><tt>600 - Connection Failure</tt> - unable to connect to server (for a variety of possible reasons) </li> 
</ul>

<p>In practice, only 410 is really conclusive that the page has gone and no longer available, all others could be a short term transient issue, or just that the bot can't access the page (but users can access it fine!) </p>

<p>But would consider 401, 404, 530, and 600 as pretty fatal, and <i>unlikely</i> to be just a short term issue. </p>

<p>The redirecting statuses (301/302), are not nesserally broken, and may be redirecting to the page. But should perhaps be considered at risk as the redirect may stop functioning at some point. Or may NOT actully be redirecting to the original page. Its unfortunately common for redirects to not forward to the correct place! </p>

<p>Finally just because a URL is not reported here (because got 200/304), there is still <i>chance</i> the link is 'broken'. Ie the page functions, but it no longer contains the content the link was meant to be referring to!</p>






    
{include file="_std_end.tpl"}
