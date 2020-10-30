{assign var="page_title" value="Sitemap v2"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.links .info {
	color:green;
}
.links h4.title {
	margin-bottom:0;
	font-size:1.3em;
	width:500px;
}
.links div.filter {
	margin-left:30px;
	padding-left:4px;
	font-size:0.8em;
	background-color:lightgrey;
	width:469px;
	padding-bottom:2px;
}
.links div.filter::before {
  content: ">> ";
  display: inline;
}
.rightbox {
	float:right;
	border-left:2px solid silver;
	margin-left:2px;
	background-color:#fdfdfd;
}
</style>{/literal}

<div align="center" class="tabHolder">
        <a href="/article/About-Geograph-page" class="tab">About Geograph</a>
        <a href="/team.php" class="tab">The Geograph Team</a>
        <a href="/credits/" class="tab">Contributors</a>
        <a href="/help/credits" class="tab">Credits</a>
        <a href="/contact.php" class="tab">Contact Us</a>
        <a href="/article/Get-Involved">Get Involved...</a>
</div>
<div style="position:relative;" class="interestBox">
        <h2 style="margin:0">Geograph Website Sitemap</h2>
</div>


	<div class="rightbox" style="width:250px"> 
		<p>Powered by the {external href="https://www.geograph.org/links/" text="Geograph Links Directory"} - wiki-style editable database of all Geograph-related links.</p>   
		
		{literal}
		<h4 class="title">Search the links...</h4>
		<form action="https://www.geograph.org/links/search.php" method="get"> 
			<input type="hidden" name="inner" value=""/> 
			<input type="text" name="q" value="search keywords..." size="15" onfocus="if (this.value=='search keywords...') {this.value='';}"/><input type="submit" value="Search"/> 
		</form> {/literal}

		{$newlinks}
	</div>

<div class="links">{$content}</div>

<p>&middot; If a link is missing or incorrect, then help us improve by editing the {external href="https://www.geograph.org/links/" text="directory"}. {external href="https://www.geograph.org/links/edit.php?sites[]=`$http_host`" text="Add a Link"}.</p>
    
{include file="_std_end.tpl"}

