{assign var="page_title" value="Article Differences"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
#maincontent h1 { padding: 5px; margin:0px; background-color: black; color:white}
#maincontent h2 { padding: 5px; background-color: lightgrey}

#output {
	border:2px solid black; 
	padding:3px; 
	background-color:#eeeeee;
	color:gray;
}

#output td {
	text-align:right;
	border-bottom:1px solid #dddddd;
	border-right:1px solid #dddddd;
	font-size:0.9em;
}

#output td.code {
	text-align:left;
	font-family:monospace;
	color:black;
	border-bottom:1px solid #dddddd;
}

#output .new {
	background-color:cyan;
	font-size:1.1em;
}
#output .old {
	background-color:pink;
	color:gray;
	font-size:1.1em;
}
#output .blank {
	font-size:0.5em;
}
</style>{/literal}
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

{if $imageCredits}
	<div class="ccmessage" style="color:gray; font-size:0.9em; text-align:right">Images also under a similar <a href="#imlicence">Creative Commons Licence</a>.</div>
{/if}

{else}
	 <div class="ccmessage">{if $licence == 'pd'}<a rel="license" href="http://creativecommons.org/licenses/publicdomain/">
	<img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/publicdomain/88x31.png" /></a> {/if} Text by <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>, {$publish_date|date_format:" %B %Y"}
	</a>{if $licence == 'pd'}; This work is dedicated to the 
	<a rel="license" href="http://creativecommons.org/licenses/publicdomain/">Public Domain</a>.{/if}</div>
{/if}

</div>
<br/>
<h2>Difference View</h2>

{if $output}
	<table cellspacing="0" cellpadding="1" id="output">
		{$output}
	</table>
{else}
	<p>please select two revisions to review</p>
{/if}
<br style="clear:both"/>



{include file="_std_end.tpl"}
