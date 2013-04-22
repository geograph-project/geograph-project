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

<p>See short <a href="#guide">guide</a> below for reasonable values.</p>

<a name="form"></a>
<form method="post" action="gridbuilder.php">

<label for="shader_image">Greyscale, 1 pixel/km PNG:</label> <input size="50" type="text" id="shader_image" name="shader_image" value="{$shader_image}"/>
<input type="submit" name="uploadfile" value="upload file"> <input type="submit" name="listfiles" value="select file"><br />
<label for="shader_x">Internal X coordinate of bottom left:</label> <input size="3" type="text" id="shader_x" name="shader_x" value="{$shader_x}"/><br />
<label for="shader_y">Internal Y coordinate of bottom left:</label> <input size="3" type="text" id="shader_y" name="shader_y" value="{$shader_y}"/><br />
<label for="reference_index">Grid Reference Index (1=GB 2=Irish):</label> <input size="1" type="text" id="reference_index" name="reference_index" value="{$reference_index}"/><br />

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
<br />
<p>Clipping (bottom left pixel of image: 0,0):</p>
<label for="minx">minimal x coordinate:</label> <input size="6" type="text" id="minx" name="minx" value="{$minx}"/><br />
<label for="maxx">maximal x coordinate:</label> <input size="6" type="text" id="maxx" name="maxx" value="{$maxx}"/><br />
<label for="miny">minimal y coordinate:</label> <input size="6" type="text" id="miny" name="miny" value="{$miny}"/><br />
<label for="maxy">maximal y coordinate:</label> <input size="6" type="text" id="maxy" name="maxy" value="{$maxy}"/><br />
<br />
<input type="submit" name="shader" value="Build Grid">
</form><br />

<a name="guide"></a>
<h2>Short guide</h2>
<h3>Create a png file from osm data</h3>
<ul>
<li>Find out which relations you need to download from OSM.</li>
<li>Download the relations, e.g. using wget (<code>XXX</code> must be replaced with the correct number):<br /><code>wget 'http://api.openstreetmap.org/api/0.6/relation/XXX/full' -O relation_XXX_full</code></li>
<li>Concatenate all relevant relations, e.g. using cat:<br /><code>cat relation_*_full &gt; relation_combined</code></li>
<li>Download the python programs from <a href="http://geo.hlipp.de/code/poly.zip">the code section</a> (and change coord.py and polycomb.py to use your coordinates, i.e. add a replacement for class CoordsGeographD).</li>
<li>Use polycomb.py to convert the relation(s) to a png file and memorize the x any y ranges the program prints:<br />
<code>./polycomb.py relation_combined dest.png  32 200 5200 1000 6200 <small>#(for Germany, UTM zone 32)</small></code><br />
You can also specify a coordinate range to speed up the calculations:<br />
<code>./polycomb.py land_bw__62611_full land_bw__62611.png  32 200 5200 1000 6200  350 5250 650 5550</code>
</li>
</ul>
<h3>Reasonable values</h3>
<ul>
<li>Internal X coordinate, Internal Y coordinate, Grid Reference Index for Germany:
<ul>
<li>Zone 31: -100, 0, 5</li>
<li>Zone 32: 0, 0, 3</li>
<li>Zone 33: 500, 0, 4</li>
</ul>
</li>
<li>Clipping: Enter x and y range printed by polycomb.py or poly.py</li>
<li>When importing several files for the same area, it is enough to check "Redraw maps", and uncheck "Skip Updating GridPrefix Table" for the last file.</li>
<li>Check "Clear existing land squares" if "0% pixels" inside the clipping area really mean "0%" and not just "ignore".</li>
</ul>
<p>Go back to <a href="#form">the form</a>.</p>

{/dynamic}    
{include file="_std_end.tpl"}
