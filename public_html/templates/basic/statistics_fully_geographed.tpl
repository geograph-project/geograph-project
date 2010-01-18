{assign var="page_title" value="Fully Geographed Squares"}
{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<h2>Fully Geographed Hectads</h2>

    <form method="get" action="{$script_name}">
	<div class="interestBox"><a href="not_geographed.php{if $ri}?ri={$ri}{/if}">Not Geographed</a> | 
	<a href="most_geographed.php{if $ri}?ri={$ri}{/if}">Mostly Geographed</a> | 
	<b>Fully Geographed</b> Hectads -
	<a href="hectads.php{if $ri}?ri={$ri}{/if}">Completion Progress</a>  -
	{if $references}In <select name="ri">
	{html_options options=$references selected=$ri}
	</select>{/if}
	<input type="submit" value="Go"></div>
    </form>

<p>These are the 10km x 10km squares or hectads<a href="/help/squares">?</a> with full land coverage! This page only counts First Geographs.</p>

<p style="font-size:0.8em">Click Mosaic for a large Map. Click a column header to change sort order.</p>


<table class="report sortable" id="table1"> 
<thead><tr><td>Hectad</td><td sorted="desc">Date Completed</td><td>Squares</td><td>Mosaic</td><td>Contributors</td></tr></thead>
<tbody>

{foreach from=$most key=id item=obj}
{cycle values="#f0f0f0,#e9e9e9" assign="bgcolor"}
<tr bgcolor="{$bgcolor}">
<td sortvalue="{$obj.hectad}" style="font-family:monospace" align="right"><a title="View map for {$obj.hectad}" href="/mapbrowse.php?t={$obj.map_token}">{$obj.hectad}</a></td>
<td align="right" sortvalue="{$obj.last_submitted}">{$obj.last_submitted|date_format:"%A, %e %B, %Y"}</td>
<td align="right" title="{$obj.geosquares}/{$obj.landsquares}">{$obj.geosquares}</td>
<td><a title="View Mosaic for {$obj.hectad}" href="/maplarge.php?t={$obj.largemap_token}">Mosaic</a></td>
<td align="right">{$obj.users}</td></tr>
{/foreach}

</tbody>
</table>

</div>




<br style="clear:both"/>
		
{include file="_std_end.tpl"}
