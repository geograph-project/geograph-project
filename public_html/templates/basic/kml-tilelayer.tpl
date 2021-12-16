{assign var="page_title" value=$title|default:"KML Tilelayer"}
{include file="_std_begin.tpl"}


<h2>KML TileLayers for Google Earth for Web</h2>

<p>This page packages up many (not all!) of the layers from the <a href="/mapper/combined.php">Coverage Map v4</a> application and 
makes them viewable in Google Earth for web.</p>

<div class=interestBox>
<form method=get>
	Layer: 
	<select name="layer">
		{dynamic}
		{foreach from=$list key=key item=item}
			<option value={$key}>{$item}</option>
		{/foreach}
		{/dynamic}
	</select>
	<input type=submit value=Download>
</form>
</div>

<p>After downloading the KML file, in Google Earth for Web, goto the Projects menu, and select 'New Project' and '<b>Import KML file from Computer</b>'</p>
... after importing a layer toggle the layer visiblity with the 'eye' symbol that appears when hover over layer name in Projects list. 

<h3>Notes:</h3>
<ul class=spaced>
	<li>The KML file will only work in <b>Google Earth for Web</b>, the version loaded in browser at https://earth.google.com/ - it will NOT work in Google Earth Pro desktop application</li>

	<li>When first load the layer, might zoom out to show entire coverage area, but data will only start showing once zoom in. Each layer only works at certain scales</li>

	<li><b>While you can download multiple KML files, please don't enable too many at once!</b>

	<li>We also host a few layers for other Geograph Projects, which can be found in the above dropdown. Unless otherwise stated, the layers are <b>Geograph Britain and Ireland</b> layers.</li>
</ul>

Layer Types:
<ul class=spaced>
	<li><b>Coverage</b>: Shows coverage by squares,<ul>
		<li>
		Coloured by what Geograph(s) are in the 1km square.
		<span style="opacity:0.6">
		<span style="background-color:#FF0000;padding:3px;">Recent Geographs (last 5 years)</span>
		<span style="background-color:#FF8800;padding:3px;">Only older Geographs</span>
		<span style="background-color:#75FF65;padding:3px;">No Geograph Images</span>
		</span>
		<li>When zoom out, changes to hectad (10km square) grid resolution, and is coloured yellow->red on the number of squares with recent (last 5 years) Geographs
	</ul></li>

	<li><b>Subjects</b>: A blue dot presents one or more photos - dot plotted at photo Subject position (only images with 6fig+ grid-reference plotted!)

	<li><b>Viewpoints</b>: A purple marker - one per photo, showing where the photo was taken from, (optionally) pointing in the approximate direction of view

	<li><b>Opportunities</b>: Lighter (yellow) - more opportunties for points, up to, darker (red) less opportunties, as already lots of photos in square. Experimental coverage layer to see if concept works. Exact specififications of layer subject to change or withdrawl.

	<li><b>PhotoMap</b>: an attempt at rendering actual photos in a layer. As a non-transparent blue background included, would be recommended to use the 'Change Layer Opacity' option in Google Earth to make the whole layer partly transparent to see the underlying terrain.
</ul>

<style>{literal}
ul.spaced li {
	margin-bottom:10px;
}
{/literal}</style>

{include file="_std_end.tpl"}

