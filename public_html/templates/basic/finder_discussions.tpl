{assign var="page_title" value="Discussion Search"}
{include file="_std_begin.tpl"}
{literal}
<style type="text/css">
	ul.explore tt {
		border:1px solid gray;
		padding:5px;
	}
	ol.results li {
		padding-bottom:4px;
		border-bottom:1px solid lightgrey;
		margin-bottom:6px;
	}
	ol.results ol.inner {
		border-top:1px solid lightgrey;
		margin-left:-30px;
	}
	.hidediv {
		margin-left:20px; 
		margin-bottom: 20px;
		border:2px solid lightgrey; 
		padding:2px;
		font-weight:bold
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

<h2><a href="/finder/">Finder</a> :: Discussions</h2>

<form action="{$script_name}" method="get" onsubmit="focusBox()">
	<p>
		<label for="fq">Keywords</label>: <input type="text" name="q" id="fq" size="40"{dynamic}{if $q} value="{$q|escape:'html'}"{/if}{/dynamic}/>
		<input type="submit" value="Search"/><br/>
		<label for="grouped">One result per Thread?</label> <input type="checkbox" name="t" id="grouped" {if $grouped}checked{/if}/>
	</p>
</form>

{if $gridref}
	<b>Did you mean?</b>
	<ul>
		<li><a href="/discuss/search.php?q={$gridref}">discussions near <b>{$gridref}</b></a>?</li>
	</ul>
{/if}

{if count($results) eq 15}
	<p>
		<small>&middot; To refine the results simply add more keywords (view <a href="#cheatsheet">Cheatsheet</a>)</small>
	</p>
{/if}
{assign var="last" value=-1}
<ol start="{$offset}" class="results">
{foreach from=$results item=item key=key}
	{if $item.era != $last}
		{if $item.era}
			<div class="interestBox" style="margin-left:-30px;text-align:right"><b>Within the last {$item.era}</b></div>
		{else}
			<div class="interestBox" style="margin-left:-30px;text-align:right"><b>More than three months ago</b></div>
		{/if}
	{/if}
	<li>
	<b><a href="/discuss/?action=vpost&forum={$item.forum_id}&amp;topic={$item.topic_id}&amp;post={$item.id}" target="_top">{$item.topic_title|escape:'html'|default:'unknown'}</a></b> <small>[thread by <a href="/profile/{$item.topic_poster}">{$item.topic_poster_name|escape:'html'}</a>]</small><br/>
	<div style="float:right">{$item.post_time|date_format:"%a, %e %b %Y"}</div>
	<small style="color:gray">{$item.excerpt|replace:'<br>':' '} <span class="nowrap">[post by <a href="/profile/{$item.poster_id}">{$item.poster_name|escape:'html'}</a>]</span></small>
	{if count($item.results)}
		<div id="hide{$key}" class="hidediv">... and <a href="javascript:void(show_tree({$key}));">{$item.result_count} more results from this thread.</a></div>
		<div id="show{$key}" style="display:none">
			<ol type="i" class="inner">
			{foreach from=$item.results item=item2 key=key2}
				<li><div style="float:right">{$item2.post_time|date_format:"%a, %e %b %Y"}</div><small style="color:gray">[<a href="/discuss/?action=vpost&forum={$item2.forum_id}&amp;topic={$item2.topic_id}&amp;post={$item2.id}">view post</a>] {$item2.excerpt|replace:'<br>':' '} <span class="nowrap">[post by <a href="/profile/{$item2.poster_id}">{$item2.poster_name|escape:'html'}</a>]</span></small></li>
			{/foreach}
			</ol>
		</div>
	{/if}
	</li>
	{assign var="last" value=$item.era}
{foreachelse}
	{if $q}
		<li><i>There are no results to display at this time.</i></li>
	{/if}
{/foreach}

</ol>
{if $results}<hr/>{/if}

<div style="margin-top:0px"> 
{if $pagesString}
	<small>( Page {$pagesString})</small>
{/if}
</div>	

{if $query_info}
	<p>{$query_info}</p>
{/if}


<div class="interestBox" style="margin-top:60px;">
	<big><a name="cheatsheet"></a>Cheatsheet</big>:
	<ul class="explore">
		<li>search for posts in specific threads : <tt>railway title:track</tt> (where track is keyword required in the thread title)</li>
		<li>or just the post content : <tt>railway text:track</tt></li>
		<li>can find only posts by a user with by:&lt;nickname&gt; : <tt>railway by:fred</tt> or exclude <tt>railway -by:fred</tt></li>
		<li>find discussions by date: <tt>day:20061225</tt>, <tt>month:200612</tt>  or <tt>year:2006</tt>  </li>
		<li>prefix a keyword with - to <b>exclude</b> that word from the match; example : <tt>railway -track</tt> <tt>railway -title:track</tt></li>
		<li>prefix a keyword with = to match <b>exactly</b>; otherwise we match similar words at the same time (stemming)</li>
		<li>can use OR (case sensitive) to match <b>either/or</b> keywords; example: <tt>train OR railway</tt></li>
		<li>Grid Square Discussions by location: <tt>hectad:TQ74</tt> or <tt>railway myriad:NT</tt> (<a href="/discuss/search.php">see also</a>)</li>
	</ul>
</div>


{include file="_std_end.tpl"}
