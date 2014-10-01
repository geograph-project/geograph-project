{if $title}
{assign var="page_title" value="$title :: Collections"}
{assign var="meta_description" value="User contributed image collections, `$title`. "}
{else}
{assign var="page_title" value="Collections"}
{assign var="meta_description" value="User contributed image collections. Showcasing a wide range of subject areas, from map symbols to lighthouses!"}
{/if}
{assign var="rss_url" value="/content/feed/recent.rss"}
{include file="_std_begin.tpl"}

<div style="float:right"><a href="./recent.php">Recent</a> <a href="./themes.php?v=1">Common Words</a> {external href="http://maps.google.co.uk/maps?q=http:%2F%2Fwww.geograph.org.uk%2Fcontent%2Ffeed%2Frecent.kml&amp;t=p" text="Map"} <a title="RSS Feed for Geograph Content" href="/content/feed/recent.rss" class="xml-rss">RSS</a></div>


        {if $gridref}
                {include file="_bar_location.tpl"}
                <div class="interestBox">
			<h2>Photo Collections</h2>
                </div>
        {else}
		<h2>Photo Collections</h2>
        {/if}

{dynamic}
{if $pending}
	<p>Your non published articles:
	{foreach from=$pending item=row}
		<a href="/article/{$row.url|escape:'url'}">{$row.title|escape:'html'}</a>,
	{/foreach}
	click a title to view.</p>
{/if}
{/dynamic}


<form action="/content/" method="get" name="theForm">

	<div class="interestBox" style="background-color:#e3e3e3">
		{if $resultCount}
			{$shown} of {$resultCount|thousends}
		{/if}
		<b>{$title|escape:"html"}</b>
	</div>

	<div class="interestBox" style="width:200px;font-size:0.8em;float:right;border:1px solid black">
		Keyword Search:  <input type="submit" value="Find"/> <br/>
		<input type="text" name="q" id="qs" size="20" {if $q} value="{$q|escape:'html'}"{/if}/><br/>
		<input type=checkbox name=in value="title" id="in_title" {if $in_title}checked{/if}/><label for="in_title">Search in title only</label>

		<p>Include: <input type=button value="all" onclick="checkall(this.form.elements['scope[]'],true);" style="font-size:0.7em"> <input type=button value="none" onclick="checkall(this.form.elements['scope[]'],false);" style="font-size:0.7em"><br/>
		<input type=checkbox name=scope[] value="article" onclick="clicked()" {if $scope_article}checked{/if}/><a href="/article/" style="background-color:#{$colours.article}">Articles</a><br/>
		<input type=checkbox name=scope[] value="gallery" onclick="clicked()" {if $scope_gallery}checked{/if}/><a href="/gallery/" style="background-color:#{$colours.gallery}">Galleries</a><br/>
		{if $enable_forums && $user->registered}
			<input type=checkbox name=scope[] value="themed" onclick="clicked()" {if $scope_themed}checked{/if}/><a href="/discuss/index.php?action=vtopic&amp;forum=6" style="background-color:#{$colours.themed}">Themed Topics</a><br/>
		{/if}
		<input type=checkbox name=scope[] value="blog" onclick="clicked()" {if $scope_blog}checked{/if}/><a href="/blog/" style="background-color:#{$colours.blog}">Blog Entries</a><br/>
		<input type=checkbox name=scope[] value="help" onclick="clicked()" {if $scope_help}checked{/if}/><a href="/content/documentation.php" style="background-color:#{$colours.help}">Help Documents</a><br/>
		<input type=checkbox name=scope[] value="snippet" onclick="clicked()" {if $scope_snippet}checked{/if}/><a href="/snippets.php" style="background-color:#{$colours.snippet}">Shared Descriptions</a><br/>
		<input type=checkbox name=scope[] value="trip" onclick="clicked()" {if $scope_trip}checked{/if}/><a href="http://www.geograph.org.uk/geotrips/" title="Trip reports based on Geograph pictures and a GPS track log" style="background-color:#{$colours.trip}">Geo-Trips</a><br/>
		<!--input type=checkbox name=scope[] value="portal" onclick="clicked()" {if $scope_portal}checked{/if}/><span style="background-color:#{$colours.portal}">{external href="http://www.geographs.org/portals/" text="Portals"}</span> <small style="color:red">Experimental</small><br/-->
		<input type=checkbox name=scope[] value="user" onclick="clicked()" {if $scope_user}checked{/if}/><a href="/finder/contributors.php" style="background-color:#{$colours.user}">User Profiles</a><br/>
		<input type=checkbox name=scope[] value="category" onclick="clicked()" {if $scope_category}checked{/if}/><a href="/stuff/canonical.php?final=1" style="background-color:#{$colours.category}">Categories</a><br/>
		<input type=checkbox name=scope[] value="context" onclick="clicked()" {if $scope_context}checked{/if}/><a href="/tags/primary.php" style="background-color:#{$colours.context}">Geographical Context</a><br/>
		<input type=checkbox name=scope[] value="other" onclick="clicked()" {if $scope_other}checked{/if}/><span style="background-color:#{$colours.other}">Other</span>
		<div style="position:relative;float:right;display:none;top:-20px" id="find_button">
			<input type="submit" value="Update" style="font-size:0.8em"/>
		</div>

		<p>Order by:<br/>
		<select name="order" onchange="this.form.submit()">
			{foreach from=$orders item=name key=key}
				<option value="{$key}"{if $order eq $key} selected{/if}>{$name}</option>
			{/foreach}
		</select></p>

		<hr/>
		<p>We also have <a href="/tags/">Tags</a> but they not included on this page.</p>

		<p><small><b>Interested in contributing to Collections?</b> See <a href="/article/Content-on-Geograph">this Article</a> for where to do it</small></p>
	</div>
	{foreach from=$extra key=name item=value}
		<input type="hidden" name="{$name}" value="{$value}"/>
	{/foreach}
</form>

<style>{literal}
ul.content {
	font-family:"Comic Sans MS", Georgia, Verdana, Arial, serif;
}
ul.content b {
	font-family:Georgia, Verdana, Arial, serif;
}

{/literal}</style>


<ul class="content" style="border:0">
{foreach from=$list item=item}

	<li style="border:0; background-color:{cycle values="#e9e9e9,#f0f0f0"}">
	<div style="float:left; width:60px; height:60px; padding-right:10px; position:relative">
		{if $item.image}
		<a title="{$item.image->title|escape:'html'} by {$item.image->realname|escape:'html'} - click to view full size image" href="/photo/{$item.image->gridimage_id}">{$item.image->getSquareThumbnail(60,60)}</a>
		{/if}
	</div>
	{if $item.images > 2 && ($item.source == 'themed' || $item.source == 'gallery' || $item.source == 'snippet' || $item.source == 'article')}
		<div style="position:relative;float:right;margin-right:10px">
			<a href="/browser/#/content_title={$item.title|escape:'url'}/content_id={$item.content_id}" title="View Images"><img src="http://s1.geograph.org.uk/templates/basic/img/cameraicon.gif" border="0"/></a>
		</div>
	{elseif $item.source == 'user' && $item.images > 2}
		<div style="position:relative;float:right;margin-right:10px">
			<a href="/browser/#/realname+%22{$item.title|escape:'url'}%22" title="View Images"><img src="http://s1.geograph.org.uk/templates/basic/img/cameraicon.gif" border="0"/></a>
		</div>
	{/if}
	<b><a href="{$item.url}">{$item.title|escape:'html'}</a></b><br/>
	{assign var="source" value=$item.source}
	<small><small style="background-color:#{$colours.$source}">{$sources.$source}</small><small style="color:gray">{if $item.user_id}{if $item.source == 'themed' || $item.source == 'gallery'} started{/if} by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname|escape:'html'}" style="color:#6699CC">{$item.realname|escape:'html'}</a>{/if}{if $item.posts_count}, with {$item.posts_count} posts{/if}{if $item.words|thousends}, with {$item.words} words{/if}{if $item.images}, {$item.images|thousends} images{/if}{if $item.views} and viewed {$item.views|thousends} times{/if}.
	{if $item.updated}Updated {$item.updated}.{/if}{if $item.created}Created {$item.created}.{/if}</small></small>
	{if $item.extract}
		<div title="{$item.extract|escape:'html'}" style="font-size:0.7em;">{$item.extract|escape:'html'|truncate:90:"... (<u>more</u>)"}</div>
	{/if}
	<div style="clear:left"></div>
	</li>


{foreachelse}
	<li><i>There are no matching Collections to display at this time.</i></li>
	{if $query_info}
		<li style="padding:20px">You have searched in Collections,<br/> you might like to try searching the <a href="/search.php?searchtext={$q|escape:'url'}&amp;do=1"><b>image database</b></a>:<br/><br/>

		<form action="/search.php" class="interestBox" style="width:420px">
			<input type="hidden" name="form" value="content"/>
			<input type=hidden name=do value="1"/>
			Keyword Search <input type=text name="searchtext" value="{$q|escape:'html'}"/>
			<input type=submit value="Search"/>
		</form></li>
	{/if}
{/foreach}

</ul>

<div style="margin-top:0px">
	{if $pagesString}
		( Page {$pagesString})
	{/if}
</div>

<br style="clear:both"/>

{if $query_info}
	<p>{$query_info}</p>
{/if}

<script type="text/javascript">{literal}

function checkall(ele,result) {
	for (q=ele.length-1;q>=0;q--) {
		ele[q].checked = result;
	}
	document.getElementById("find_button").style.display='';
}
function clicked() {
	document.getElementById("find_button").style.display='';
}
{/literal}</script>



{include file="_std_end.tpl"}
