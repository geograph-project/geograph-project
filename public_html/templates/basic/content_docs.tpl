{assign var="page_title" value="Project Information, Guides, Tutorials"}
{assign var="meta_description" value="Listings of various Geograph Information pages, Guides and Tutorials - look here to find out more about the project"}
{include file="_std_begin.tpl"}

<style>{literal}
#maincontent *{
	box-sizing:border-box;
}
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

{*---------------------------Search-------------------------*}
{include file="_doc_search.tpl"}
<div id="searchresults"></div>



<div class="twocolsetup">
<div class="twocolumn">
<p>This page provides information about the Geograph Britain and Ireland project. Listed below are the collected guides and tutorials relating to the project where you can find lots of help with using the site. The <a href="/discuss/">Forums</a> are also a good place to get help.</p>

<div style="text-align:center; padding:5px;"><b>Can't find what you are looking for?<br/>Get in touch and <a href="/ask.php">ask us</a>!</b></div>
</div>

{*---------------------------Quick links-------------------------*}
<div class="twocolumn">


<ul class="buttonbar">
<div class="buttonbar-dropdown">
<button>About the project &#9660;</button>
<div class="buttonbar-dropdown-content">
<a href="/article/About-Geograph-page">About Geograph project</a>
<a href="https://en.wikipedia.org/wiki/Geograph_Britain_and_Ireland">View the Wikipedia article on Geograph</a>
<a href="/help/freedom">Freedom - The Geograph Manifesto</a>
</div>
</div>

<div class="buttonbar-dropdown">
<button>Exploring images &#9660;</button>
<div class="buttonbar-dropdown-content">
<a href="/mapper/combined.php">Explore on a map</a>
<a href="/explore/">More exploration methods</a>
</div>
</div>

<div class="buttonbar-dropdown">
<button>Contributing Images &#9660;</button>
<div class="buttonbar-dropdown-content">
<a href="/help/submit_intro">Submission Overview</a>
<a href="/article/Geograph-Introductory-letter">Contributors Introductory letter</a>
</div>
</div>

<div class="buttonbar-dropdown">
<button>Contributing Collections &#9660;</button>
<div class="buttonbar-dropdown-content">
<a href="/article/Content-on-Geograph">Collections on Geograph</a>

</div>
</div>

<div class="buttonbar-dropdown">
<button>More... &#9660;</button>
<div class="buttonbar-dropdown-content">
<a href="/article/Get-Involved">Get Involved page</a>
<a href="/article/Geograph-for-Developers">Info for Developers</a>
<a href="/article/Geograph-Image-APIs">Reusing our content</a>
</div>
</div>

<div class="buttonbar-dropdown">
<button>Other projects &#9660;</button>
<div class="buttonbar-dropdown-content">
<a href="https://www.geograph.org/">Geograph projects worldwide</a>
<a href="http://geo.hlipp.de/">Geograph Germany</a>
<a href="http://www.geograph.org.gg/">Geograph Channel Islands</a>
</div>
</div>

</ul>


</div>

</div>



<br style="clear:both;"/>

  
  
{*---------------------------Articles-------------------------*}

<div class="threecolsetup">
	{assign var="lastid" value="0"}
	{foreach from=$list item=item}
		{if $lastcat != $item.category_name}
			{if $lastcat}
			        </ul>
			</div>
			{/if}

			<div class="threecolumn">
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
	  <ul>
	  <li><i>There are no Articles to display at this time.</i></li>
	  </ul>
	{/foreach}

	{if $lastcat}
			</ul>
		</div>
	{/if}

	<br style="clear:both"/>
</div>

<hr style="color:silver"/>

	<p align="center">Geograph Project Limited is a company limited by guarantee. Registered in England and Wales, number 7473967.<br> Registered office: Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA. <a href="/article/About-Geograph-page">About Geograph Project</a>.</p>


{include file="_std_end.tpl"}

