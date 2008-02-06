{assign var="page_title" value=$title}

{assign var="content_articletext" value=$content|articletext}

{include file="_std_begin.tpl"}

{literal}<style type="text/css">
#maincontent h1 { padding: 5px; margin-top:0px; background-color: black; color:white}
#maincontent h2 { padding: 5px; background-color: lightgrey}
#maincontent h3 { padding: 5px; margin-top:20px; border: 1px solid lightgrey; background-color: #eeeeee}
#maincontent h4 { padding: 5px; margin-top:20px; border: 1px dashed lightgrey; background-color: #eeeeee}

#contents_table {  border: 1px solid lightgrey; background-color: #eeeeee; padding: 10px } 
#contents_table .title { font-weight:bolder;  padding:3px; border-bottom:1px solid black; margin-bottom:5px; }
#contents_table ul { margin-top:0;padding:0 0 0 1em; border-bottom:1px solid black; padding-bottom: 8px; margin-bottom:5px; }
#contents_table .h2 { font-weight:bold; }
#contents_table .h3 { padding-left: 3px; }
#contents_table .h4 { padding-left: 10px; font-size: 0.7em}
#contents_table .h2 small, #contents_table .h3 small, #contents_table .h4 small { font-size:0.6em; color:gray }
@media print {
	.no_print {
		display: none;
	}
}
</style>{/literal}
{dynamic}{if $user->user_id == $user_id}<p style="text-align:center" class="no_print">[[<a href="/article/edit.php?page={$url}">edit this article</a>]]</p>{/if}{/dynamic}

<h1>{$title|escape:'html'}</h1>

<div style="text-align:right">
{if $licence == 'copyright'}
	Text <small>&copy;</small> Copyright <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>, {$publish_date|date_format:" %B %Y"}
{elseif $licence == 'cc-by-sa/2.0'}
	<!-- Creative Commons Licence -->
		<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
		alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; Text &copy; Copyright {$publish_date|date_format:" %B %Y"}, <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>; 
		licensed for reuse under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
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

{if $imageCredits}
	<div class="ccmessage" style="color:gray; font-size:0.9em; text-align:right">Images also under a similar <a href="#imlicence">Creative Commons licence</a>.</div>
{/if}

{else}
	 <div class="ccmessage">{if $licence == 'pd'}<a rel="license" href="http://creativecommons.org/licenses/publicdomain/">
	<img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/publicdomain/88x31.png" /></a> {/if} Text by <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>, {$publish_date|date_format:" %B %Y"}
	</a>{if $licence == 'pd'}; This work is dedicated to the 
	<a rel="license" href="http://creativecommons.org/licenses/publicdomain/">Public Domain</a>.{/if}</div>
{/if}

{if $imageCredits && $licence != 'cc-by-sa/2.0'}
	<div class="ccmessage" style="color:gray; font-size:0.9em; text-align:right">Images are under a seperate <a href="#imlicence">Creative Commons licence</a>.</div>
{/if}

</div> 

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

{if $imageCredits}
	<hr/>
	<div class="ccmessage copyright"><a rel="license" name="imlicence" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
		alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; <b>Images used on this page</b>, &copy; Copyright {$imageCredits};
		licensed for reuse under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>. <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">http://creativecommons.org/licenses/by-sa/2.0/</a><br/><br/></div>
{/if}

{if $copyright}{$copyright}{/if}

{include file="_std_end.tpl"}
