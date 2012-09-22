{assign var="page_title" value="Article History"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>
{literal}<style type="text/css">
#maincontent h1 { padding: 5px; margin:0px; background-color: black; color:white}
#maincontent h2 { padding: 5px; background-color: lightgrey}

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
	<div class="ccmessage" style="color:gray; font-size:0.9em; text-align:right">Images also under a similar <a href="#imlicence">Creative Commons licence</a>.</div>
{/if}

{else}
	 <div class="ccmessage">{if $licence == 'pd'}<a rel="license" href="http://creativecommons.org/licenses/publicdomain/">
	<img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/publicdomain/88x31.png" /></a> {/if} Text by <a href="/profile/{$user_id}" title="View Geograph Profile for {$realname}">{$realname}</a>, {$publish_date|date_format:" %B %Y"}
	</a>{if $licence == 'pd'}; This work is dedicated to the 
	<a rel="license" href="http://creativecommons.org/licenses/publicdomain/">Public Domain</a>.{/if}</div>
{/if}

</div>
<br/>
<h2>Edit History <small><sub>Click a column header to reorder</sub></small></h2>



<form method="get" action="/article/diff.php">
<input type="hidden" name="page" value="{$url}"/>
<input type="submit" value="Compare Selected Revisions"/>
<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5" style="font-size:0.9em">
<thead>
	<tr>
		<td>1</td>
		<td>2</td>
		<td>Title</td>
		<td>Category</td>
		<td>Length</td>
		<td>Licence</td>
		<td>Updated</td>
		<td>Modifier</td>
	</tr>
</thead>
<tbody>
	{foreach from=$list item=item}
	<tr>
		<td><input type="radio" name="1" value="{$item.article_revision_id}" {if ($item.approved < 1 || $item.licence == 'none') && !$isadmin && ($item.user_id != $user->user_id)} disabled="disabled"{/if}/></td>
		
		<td><input type="radio" name="2" value="{$item.article_revision_id}" {if ($item.approved < 1 || $item.licence == 'none') && !$isadmin && ($item.user_id != $user->user_id)} disabled="disabled"{/if}/></td>
		
		<td sortvalue="{$item.title}"><b>{if $item.approved < 1 || $item.licence == 'none'}<s>{/if}<a title="{$item.extract|default:'View Article'}" href="/article/{$item.url}">{$item.title}</a></b>{if $item.approved < 1 || $item.licence == 'none'}</s> ({if $item.approved == -1}<i>Archived <small>and not available for publication</small></i>{else}hidden{/if}){/if}</td>
		
		<td><small>{$item.category_name|truncate:30}</small></td>
		<td align="right">{$item.content_length}</td>
		<td><small>{$item.licence}</small></td>
		
		<td sortvalue="{$item.update_time}" style="font-size:0.8em">{if $item.update_time|date_format:"%a, %e %b %Y" eq $smarty.now|date_format:"%a, %e %b %Y"}Today {$item.update_time|date_format:"%H:%M"}{else}{$item.update_time|date_format:"%a, %e %b %Y"}{/if}</td>
		
		<td style="font-size:0.9em"><a href="/profile/{$item.modifier}" title="View Geograph Profile for {$item.modifier_realname}">{$item.modifier_realname}</a></td>
		
	</tr>
	{/foreach}
</tbody>
</table>

<input type="submit" value="Compare Selected Revisions"/>
<p>Select two revisions in the list and click the above button to review the individual changes</p>
</form>


<br style="clear:both"/>



{include file="_std_end.tpl"}
