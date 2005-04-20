{include file="_std_begin.tpl"}
{dynamic}

<h2>Recreate Thumbnails</h2>
<p>This is an advanced administrative tool for recreating the thumbnails 
using a new unsharp mask
</p>


<form method="post" action="recreatethumbs.php">

Date From {html_select_date prefix="datefrom" time=$imagetaken start_year="2005" field_order="DMY"} <br/>

Date To {html_select_date prefix="dateto" time=$imagetaken start_year="+10"  field_order="DMY"} <br/>

amount: <input type="text" name="amount" value="{$amount}" size="5"/><br />

radius: <input type="text" name="radius" value="{$radius}" size="5"/><br />

threshold: <input type="text" name="threshold" value="{$threshold}" size="5"/><br />

function: <select name="function">
<option value="getThumbnail">getThumbnail - used for normal viewin thumbnails (120x80 & 213x160)</option>
<option value="getSquareThumb">getSquareThumb - for the mapbrowser (40x40)</option>
<option value="getSquareThumbnail">getSquareThumbnail - for the old map (was 40x40)</option>
</select><br/>

w/h: <input type="text" name="w" value="{$w}" size="3"/>/<input type="text" name="h" value="{$h}" size="3"/><br />


<input type="submit" name="go" value="Create Thumbs">

</form>
{/dynamic}    
{include file="_std_end.tpl"}
