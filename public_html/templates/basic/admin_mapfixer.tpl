{assign var="page_title" value="Map Fixer"}
{include file="_std_begin.tpl"}

<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : Map Fixer</h2>

{dynamic}

<form method="get" action="mapfixer.php">
<label for="gridref">Grid Reference</label>
<input type="text" size="6" name="gridref" id="gridref" value="{$gridref|escape:'html'}">
<span class="formerror">{$gridref_error}</span>
<input type="submit" name="show" value="Check">

{if $gridref_ok}
<br/>{getamap gridref=$gridref text="Check OS Map for $gridref"}<br/><br/>

Land percentage for {$gridref} is
<input type="text" size="3" name="percent_land" value="{$percent_land}">
<input type="submit" name="save" value="Save">
<br/>{$status}
{/if}


</form>


<h3>System created squares</h3>    
<p>The following squares were created by the system when someone tried to view or 
submit a square within 2km of an existing one - click one each one to update its
land percentage</p>

{if $unknowns}
<ul>
{foreach from=$unknowns item=unknown}
<li><a href="mapfixer.php?gridref={$unknown.grid_reference}">{$unknown.grid_reference} ({$unknown.imagecount} images)</li>
{/foreach}
</ul>
{else}

<p><i>None found!</i></p>

{/if}

{/dynamic}    
{include file="_std_end.tpl"}
