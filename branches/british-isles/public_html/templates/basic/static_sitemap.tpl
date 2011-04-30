{assign var="page_title" value="Sitemap v2"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
.links {
	background-color:#eeeeee;
}
.links .feature {
	color: green;
}
.links h4.title {
	margin-bottom:0;
	background-color:#eeeeee;
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

<div class="interestBox" style="float:right">This is a new experimental dynamic sitemap, <a href="/help/sitemap_orig">see the original version</a>.</div>

 <h2>Geograph Sitemap</h2>
  
 <p>Powered by the {external href="http://www.geographs.org/links/" text="Geograph Links Directory"} - wiki-style editable database of all Geograph-related links.</p>   

	<div class="rightbox"> 
		<ul> 
			<li><a href="http://www.geographs.org/links/"><b>Links Directory</b></a></li> 
		</ul> {literal}
		<form action="http://www.geographs.org/links/search.php" method="get"> 
			<input type="hidden" name="inner" value=""/> 
			<input type="text" name="q" value="search keywords..." size="15" onfocus="if (this.value=='search keywords...') {this.value='';}"/><input type="submit" value="Search"/> 
		</form> {/literal}
	</div>

<div class="links">{$content}</div>

<p>&middot; If a link is missing or incorrect, then help us improve by editing the {external href="http://www.geographs.org/links/" text="directory"}. {external href="http://www.geographs.org/links/edit.php?sites[]=`$http_host`" text="Add a Link"}.</p>
    
{include file="_std_end.tpl"}

