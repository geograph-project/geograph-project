{assign var="page_title" value="Project Information, Guides, Tutorials"}
{assign var="meta_description" value="Listings of various Geograph Information pages, Guides and Tutorials - look here to find out more about the project"}
{include file="_std_begin.tpl"}

<style>{literal}

div.picks {
	float:right;
	max-width: 250px;
	background-color:#eee;
	margin-left:10;
}
div.pickbox {
}
div.pickbox h4 {
	margin-top:10px;
	margin-bottom:0;
	padding:5px;
}
div.pickbox ul {
	margin:0;
	padding:0;
	padding-left:20px;
}
div.pickbox ul li {
	margin-bottom:4px;
}

br.final {
}

ul.infos li {
	margin-bottom:3px;
}
ul.infos li>small {
	display:none;
}
ul.infos li:hover>small {
	display:inline;
}
{/literal}</style>

<div class="tabHolder">
	<span class="tabSelected">Information</span>
        <a href="/faq3.php?l=0" class="tab">FAQ</a>
        <a href="/news.php" class="tab">News</a>
        <a href="/article/About-Geograph-page" class="tab" title="About Geograph">About Us</a>
        <a href="/team.php" class="tab">The Team</a>
        <a href="/credits/" class="tab">Contributors</a>
        <a href="/help/credits" class="tab">Credits</a>
        <a href="/contact.php" class="tab">Contact Us</a>
        <a href="/article/Get-Involved">Get Involved...</a>
</div>

<div class="interestBox">
	<h2 style="margin:0">Geograph Project Information, Guides and Tutorials</h2>
</div>



{include file="_doc_search.tpl"}


<div class="picks">
<h3>Quick Links</h3>
<div class="pickbox">
	<h4>About the project</h4>
	<ul>
		<li><a href="/article/About-Geograph-page">About Geograph project</a>
		<li>{external href="https://en.wikipedia.org/wiki/Geograph_Britain_and_Ireland" text="more on Wikipedia"}</li>
		<li><a href="/help/freedom">Freedom - The Geograph Manifesto</a>
	</ul>
</div>
<div class="pickbox">
	<h4>Exploring images</h4>
	<ul>
		<li>Enter a search term in search box top right
		<li>... or <a href="/mapper/combined.php">explore on map</a> 
		<li><a href="/explore/">More exploration methods</a>
	</ul>
</div>
<div class="pickbox">
	<h4>Contributing Images</h4>
	<ul>
		<li><a href="/help/submit_intro">Submission Overview</a>
		<li><a href="/article/Geograph-Introductory-letter">Contributors Introductory letter</a>
	</ul>
</div>
<div class="pickbox">	
	<h4>Contributing Collections</h4>
	<ul>
		<li><a href="/article/Content-on-Geograph">Collections on Geograph</a>
	</ul>
</div>
<div class="pickbox">
	<h4>More...</h4>
	<ul>
		<li><a href="/article/Get-Involved">Get Involved page</a>
		<li><a href="/article/Geograph-for-Developers">for Developers</a> and <a href="/article/Geograph-Image-APIs">Reusing our content</a>
	</li>	
</div>
<div class="pickbox">
	<div style="padding:10px;">
		See also <a href="https://www.geograph.org/">www.geograph.org</a> for information about other Geograph Projects (for Germany and the Channel Islands) 
	</div>
</div>

<br class="final">
</div>




	<div id="searchresults"></div>


{assign var="lastid" value="0"}
{foreach from=$list item=item}
	{if $lastcat != $item.category_name}
		{if $lastcat}
			</ul>
		{/if}

		<h3>{$item.category_name}</h3>
		<ul class="infos">
		{assign var="lastname" value=""}
	{/if}
	<li><a title="{$item.extract|default:'View Article'}" href="{$item.url}">{$item.title}</a>
		<small id="att{$lastid+1}"><small style="color:lightgrey">{if $item.user_id}by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}"  style="color:#6699CC">{$item.realname}</a>{/if}
		</small></small>
	</li>
	{assign var="lastname" value=$item.realname}
	{assign var="lastcat" value=$item.category_name}
	{assign var="lastid" value=$lastid+1}
{foreachelse}
	<li><i>There are no Articles to display at this time.</i></li>
{/foreach}

</ul>

<br style="clear:both"/>

	<div class="interestBox" style="font-size:1.3em;margin-bottom:20px">Can't find what you looking for? <a href="/ask.php">Ask us</a>!</div>

	<p align="center">Geograph Project Limited is a company limited by guarantee. Registered in England and Wales, number 7473967.<br> Registered office: Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA. <a href="/article/About-Geograph-page">About Geograph Project</a>.</p>


{include file="_std_end.tpl"}

