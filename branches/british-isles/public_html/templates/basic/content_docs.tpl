{assign var="page_title" value="Project Information, Guides, Tutorials"}
{assign var="meta_description" value="Listings of various Geograph Information pages, Guides and Tutorials - look here to find out more about the project"}
{include file="_std_begin.tpl"}

<div class="tabHolder">
	<span class="tabSelected">Information</span>
        <a href="/faq3.php?l=0" class="tab">FAQ</a>
        <a href="/news.php" class="tab">News</a>
        <a href="/article/About-Geograph-page" class="tab" title="About Geograph">About Us</a>
        <a href="/team.php" class="tab">The Team</a>
        <a href="/credits/" class="tab">Contributors</a>
        <a href="/help/credits" class="tab">Credits</a>
        <a href="http://hub.geograph.org.uk/downloads.html" class="tab">Downloads</a>
        <a href="/contact.php" class="tab">Contact Us</a>
        <a href="/article/Get-Involved">Get Involved...</a>
</div>

<div class="interestBox">
<h2 style="margin:0">Geograph Project Information, Guides and Tutorials</h2>
</div>

{include file="_doc_search.tpl"}

	<div id="searchresults"></div>


{assign var="lastid" value="0"}
{foreach from=$list item=item}
	{if $lastcat != $item.category_name}
		{if $lastcat}
			</ul>
		{/if}

		{if !$lastid || $item.category_name eq $splitcat}
			{if $lastcat}
				</div>
			{/if}
			<div style="float:left;width:46%;position:relative; padding:5px;">
		{/if}
		<h3>{$item.category_name}</h3>
		<ul class="content">
		{assign var="lastname" value=""}
	{/if}
	<li><b><a title="{$item.extract|default:'View Article'}" href="{$item.url}">{$item.title}</a></b><br/>
	<small id="att{$lastid+1}"><small style="color:lightgrey">by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}"  style="color:#6699CC">{$item.realname}</a>

		</small></small>

	</li>
	{if $lastname == $item.realname && $user->realname != $lastname}
		<script>document.getElementById('att{$lastid}').style.display='none'</script>
	{/if}
	{assign var="lastname" value=$item.realname}
	{assign var="lastcat" value=$item.category_name}
	{assign var="lastid" value=$lastid+1}
{foreachelse}
	<li><i>There are no Articles to display at this time.</i></li>
{/foreach}

</ul>
</div>
<br style="clear:both"/>

	<div class="interestBox" style="font-size:1.3em;margin-bottom:20px">Can't find what you looking for? <a href="/ask.php">Ask us</a>!</div>

	<p align="center">Geograph Project Limited is a company limited by guarantee. Registered in England and Wales, number 7473967.<br> Registered office: Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA. <a href="/article/About-Geograph-page">About Geograph Project</a>.</p>


{include file="_std_end.tpl"}

