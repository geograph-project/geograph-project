{include file="_std_begin.tpl"}
{dynamic}

<h2>Grid Building</h2>
<p>This page links to tools for initialising the database of
grid squares - this chiefly consists of two elements - shading the squares
to indicate whether they are land or sea, and assigning real-world
coordinates to such squares.</p>


<h3>Automatic Grid Shader</h3>
<p>This tool initialises grid squares from a greyscale 1pixel-per-km
image. This tool is safe to re-run - all it does is create a grid square
if it doesn't already exist, and assign a land percentage to that square.</p>

<form method="post" action="gridbuilder.php">

<label for"shader_image">Greyscale, 1 pixel/km PNG:</label> <input type="text" id="shader_image" name="shader_image" value="{$shader_image}"/><br />
<label for"shader_x">Internal X coordinate of bottom left:</label> <input size="3" type="text" id="shader_x" name="shader_x" value="{$shader_x}"/><br />
<label for"shader_y">Internal Y coordinate of bottom left:</label> <input size="3" type="text" id="shader_y" name="shader_y" value="{$shader_y}"/><br />
<label for"reference_index">Grid Reference Index (1=GB 2=Irish):</label> <input size="1" type="text" id="reference_index" name="reference_index" value="{$reference_index}"/><br />

<input type="checkbox" name="clearexisting" id="clearexisting" value="1" {if $clearexisting}checked{/if}>
<label for="clearexisting">Clear existing land squares if marked as sea in this image</label><br />

<input type="checkbox" name="skipupdategridprefix" id="skipupdategridprefix" value="1" {if $skipupdategridprefix}checked{/if}>
<label for="updategridprefix">Skip Updating GridPrefix Table</label><br />

<input type="checkbox" name="redrawmaps" id="redrawmaps" value="1" {if $redrawmaps}checked{/if}>
<label for="redrawmaps">Redraw maps (base and detail)</label><br />

<input type="checkbox" name="ignore100" id="ignore100" value="1" {if $ignore100}checked{/if}>
<label for="ignore100">Ignore 100% squares, useful for makeing lakes where the map doesnt include sea</label><br />

<input type="checkbox" name="dryrun" id="dryrun" value="1" {if $dryrun}checked{/if}>
<label for="dryrun">Dry run: Don't touch the database</label><br />

<input type="submit" name="shader" value="Build Grid">


</form>

{/dynamic}    
{include file="_std_end.tpl"}
