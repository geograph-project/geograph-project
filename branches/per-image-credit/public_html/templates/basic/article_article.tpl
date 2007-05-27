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

</style>{/literal}

<h1>{$title}</h1>

<div style="text-align:right">
{if $licence == 'copyright'}
	text <small>&copy;</small> <a href="/profile.php?u={$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>, {$publish_date|date_format:" %B, %Y"}
{else}
	{if $licence == 'cc-by-sa/2.0'}
		<div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
		alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; Text &copy; Copyright {$publish_date|date_format:" %B, %Y"}, <a href="/profile.php?u={$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>; {if $imageCredits}text and images{/if}
	licensed for reuse under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>
	{else}
		text by <a href="/profile.php?u={$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>, {$publish_date|date_format:" %B, %Y"} {if $licence == 'pd'}(Public Domain){/if}
	{/if}
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
<div class="ccmessage copyright"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
		alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; Images used on this page, &copy; Copyright {$imageCredits};
	licensed for reuse under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>. <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">http://creativecommons.org/licenses/by-sa/2.0/</a><br/><br/></div>
{/if}

{if $copyright}{$copyright}{/if}

{include file="_std_end.tpl"}
