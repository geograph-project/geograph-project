{assign var="content_articletext" value=$content|articletext}
{if $pagetitle}{assign var="page_title" value="$pagetitle :: $title"}{else}{assign var="page_title" value=$title}{/if}
{if $inner}
{include file="_basic_begin.tpl"}
<div id="maincontent">
{else}
{include file="_std_begin.tpl"}
{/if}
{if $include_sorttable}
<script src="{"/sorttable.js"|revision}"></script>
{/if}<a name="top"></a>
{literal}<style type="text/css">
#maincontent h1 { padding: 5px; margin-top:0px; background-color: black; color:white}
#maincontent h2 { padding: 5px; background-color: lightgrey}
#maincontent h3 { padding: 5px; margin-top:20px; border: 1px solid lightgrey; background-color: #eeeeee}
#maincontent h4 { padding: 5px; margin-top:20px; border: 1px dashed lightgrey; background-color: #eeeeee}
#maincontent tt { padding: 1px; background-color: #f9f9f9}

#maincontent h1.pageheading { margin-bottom:0}
#maincontent h2.pageheading { margin-top:0; background-color:gray; font-size:0.8em; color:white}

#contents_table {  border: 1px solid lightgrey; background-color: #eeeeee; padding: 10px }
#contents_table .title { font-weight:bolder;  padding:3px; border-bottom:1px solid black; margin-bottom:5px; }
#contents_table ul { margin-top:0;padding:0; border-bottom:1px solid black; padding-bottom: 8px; margin-bottom:5px; }
#contents_table .h2 { margin-left:0; font-weight:bold; list-style-type:none }
#contents_table .h3 { margin-left:16px;}
#contents_table .h4 { margin-left:26px; font-size:0.7em; }
#contents_table .h2 small, #contents_table .h3 small, #contents_table .h4 small { font-size:0.6em; color:gray }
#contents_table li { color:silver }
#contents_table a { text-decoration:none; }
#contents_table a:hover { text-decoration:underline; }

@media print {
	.no_print {
		display: none;
	}
	img {
		border: 0px;
	}
}

ul.content {padding:0 0 0 0; border-bottom: 1px solid gray}
ul.content li {	padding:3px; border-top: 1px solid gray}
div.breadcrumb div {
	display:inline;
	font-size:0.8em;
}
div.breadcrumb a {
	text-decoration: none;
}
</style>{/literal}
{dynamic}
	{if !$inner}
	{if $user->registered && $approved == 2 && $edit_prompt}
		<div class="no_print" style="padding-bottom:20px">
			<div style="position:relative;float:right">[[<a href="/article/edit.php?page={$url|escape:'url'}">edit this article</a>]] [[<a href="/article/history.php?page={$url|escape:'url'}">article history</a>]]</div>
			<b>This article is open for collaborative editing</b>. {$edit_prompt}
		</div>
	{elseif $user->user_id == $user_id || $ismod}
		<p style="text-align:center" class="no_print">[[<a href="/article/edit.php?page={$url|escape:'url'}">edit this article</a>]] [[<a href="/article/history.php?page={$url|escape:'url'}">article history</a>]] [[<a href="/article/">article listing</a>]]</p>
		{if $user_warning}
                        <p style="text-align:center;color:red;padding:7px;border:2px solid red" class="no_print">{$user_warning}</p>
		{/if}
	{/if}
	{/if}
{/dynamic}
{if $parent_url && $parent_title}
<div class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
<div itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><meta itemprop="position" content="1" />
  <a href="/article/" itemprop="item"><span itemprop="name">Articles</span></a> &gt;
</div>
<div itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><meta itemprop="position" content="2" />
  <a href="/article/{$parent_url|escape:'url'}" itemprop="item"><span itemprop="name">{$parent_title|escape:'html'}</span></a> &gt;
</div>
<div itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><meta itemprop="position" content="3" />
  <span itemprop="name"><b>{$title|escape:'html'}</b></span>
</div>
</div>
{/if}
{if $pagetitle}
	{if $page!=$pagetitle}
		<h1 class="pageheading"><small>{$page}.</small> {$pagetitle|escape:'html'}</h1>
	{else}
		<h1 class="pageheading">Page {$page}</h1>
	{/if}
	<h2 class="pageheading">{$title|escape:'html'}</h2>
{else}
	<h1>{$title|escape:'html'}</h1>
{/if}
{if $pagesString}
	<div style="margin-top:0px;font-size:0.8em;">
		( Page {$pagesString})
	</div>
{/if}

<div style="text-align:right">
{if $licence == 'copyright'}
	Text <small>&copy;</small> Copyright <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname|escape:'html'}">{$realname|escape:'html'}</a>, {$publish_date|date_format:" %B %Y"}
{elseif $licence == 'cc-by-sa/2.0'}
	<!-- Creative Commons Licence -->
		<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img
		alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" /></a> &nbsp; Text &copy; Copyright {$publish_date|date_format:" %B %Y"}, <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname|escape:'html'}">{$realname|escape:'html'}</a>;
		licensed for re-use under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
	<!-- /Creative Commons Licence -->

<!--

<rdf:RDF xmlns="http://web.resource.org/cc/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:georss="http://www.georss.org/georss/">
<Work rdf:about="">
     <dc:title>{if $grid_reference}{$grid_reference} : {/if}{$title|escape:'html'}</dc:title>
     <dc:creator><Agent>
        <dc:title>{$realname}</dc:title>
     </Agent></dc:creator>
     <dc:rights><Agent>
        <dc:title>{$realname}</dc:title>
     </Agent></dc:rights>
     <dc:format>text/html</dc:format>
     <dc:date>{$publish_date}</dc:date>
     <dc:publisher><Agent>
        <dc:title>{$http_host}</dc:title>
     </Agent></dc:publisher>
{if $lat && $long}
     <georss:point>{$lat|string_format:"%.5f"} {$long|string_format:"%.5f"}</georss:point>
{/if}
     <license rdf:resource="http://creativecommons.org/licenses/by-sa/2.0/" />
</Work>

<License rdf:about="http://creativecommons.org/licenses/by-sa/2.0/">
   <permits rdf:resource="http://web.resource.org/cc/Reproduction" />
   <permits rdf:resource="http://web.resource.org/cc/Distribution" />
   <requires rdf:resource="http://web.resource.org/cc/Notice" />
   <requires rdf:resource="http://web.resource.org/cc/Attribution" />
   <permits rdf:resource="http://web.resource.org/cc/DerivativeWorks" />
   <requires rdf:resource="http://web.resource.org/cc/ShareAlike" />
</License>

</rdf:RDF>

-->

{if $moreCredits}
	<div class="ccmessage" style="color:gray; font-size:0.8em; text-align:right">With contributions by {$moreCredits}. (<a href="/article/history.php?page={$url|escape:'url'}">details</a>)</div>
{/if}
{if $imageCredits}
	<div class="ccmessage" style="color:gray; font-size:0.9em; text-align:right">Images also under a similar <a href="#imlicence">Creative Commons Licence</a>.</div>
{/if}

{elseif $licence == 'geograph'}

{else}
	<div class="ccmessage">{if $licence == 'pd'}<a rel="license" href="http://creativecommons.org/licenses/publicdomain/">
	<img alt="Creative Commons License" style="border-width:0" src="{$static_host}/img/pd-88x31.png" /></a> {/if} Text by <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname|escape:'html'}">{$realname|escape:'html'}</a>, {$publish_date|date_format:" %B %Y"}
	</a>{if $licence == 'pd'}; This work is dedicated to the
	<a rel="license" href="http://creativecommons.org/licenses/publicdomain/">Public Domain</a>.{/if}</div>
{/if}

{if $imageCredits && $licence != 'cc-by-sa/2.0'}
	<div class="ccmessage" style="color:gray; font-size:0.9em; text-align:right">Images are under a separate <a href="#imlicence">Creative Commons Licence</a>.</div>
{/if}

</div>

{if $grid_reference && !$inner}
	<div class="no_print" style="float:left; font-size:0.8em">
	<img src="{$static_host}/img/geotag_16.png" width="10" height="10" align="absmiddle" alt="geotagged!"/> <a href="/gridref/{$grid_reference}/links">Links for {$grid_reference}</a>&nbsp;
	</div>
{/if}

{if $copyright}{$copyright}{/if}

<hr/><br/>
{if $tableContents}
	<div style="float:right; width:250px; position:relative;" id="contents_table">
	<div class="title">Contents</div>
	<ul>
		{$tableContents}
	</ul>
	</div>
{/if}
{$content_articletext}
{if $nextString}
	<div style="text-align:right;"><i>{$nextString}</i></div>
{/if}

{if $imageCredits}
	<hr/>
	{if !$inner}
	<div style="float:right;position:relative"><a title="View these images in Google Earth" href="/search.php?article_id={$article_id}&amp;orderby=seq_id&amp;kml" class="xml-kml no_print">KML</a></div>
	{/if}
	<div class="ccmessage copyright"><a rel="license" name="imlicence" href="http://creativecommons.org/licenses/by-sa/2.0/"><img
		alt="Creative Commons Licence [Some Rights Reserved]" src="{$static_host}/img/somerights20.gif" /></a> &nbsp; <b><a href="/search.php?article_id={$article_id}&amp;orderby=seq_id">Images used on this page</a></b>, &copy; Copyright {$imageCredits};
		licensed for re-use under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>. <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">http://creativecommons.org/licenses/by-sa/2.0/</a><br/><br/></div>
{/if}

{if $copyright}{$copyright}{/if}

{if $grid_reference && !$inner}
	<div class="no_print">
	<img src="{$static_host}/img/geotag_16.png" width="16" height="16" align="absmiddle" alt="geotagged!"/> <a href="/gridref/{$grid_reference}/links">Further links for {$grid_reference}</a>
	</div>
{/if}

{if $pagesString}
	<hr/>	( Page {$pagesString})
{/if}
{if $inner}
</div>
</body>
</html>
{else}
{include file="_std_end.tpl"}
{/if}
