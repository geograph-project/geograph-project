{include file="_std_begin.tpl"}


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

Greyscale, 1 pixel/km PNG: <input type="text" name="shader_image" value="{$shader_image}"/><br />
Internal X coordinate of bottom left: <input size="3" type="text" name="shader_x" value="{$shader_x}"/><br />
Internal Y coordinate of bottom left: <input size="3" type="text" name="shader_y" value="{$shader_y}"/><br />

<input type="submit" name="shader" value="Build Grid">


</form>
    
{include file="_std_end.tpl"}
