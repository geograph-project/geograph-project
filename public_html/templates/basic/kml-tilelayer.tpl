{assign var="page_title" value=$title|default:"KML Tilelayer"}
{include file="_std_begin.tpl"}


<h2>KML TileLayers for Google Earth for Web</h2>

<p>Note this KML file will only work in <b>Google Earth For Web</b>, the version loaded in browser at https://earth.google.com/
- it will NOT work in Google Earth Pro desktop application</p>

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

While you can download multiple KML files, please don't enable too many at once!

{include file="_std_end.tpl"}

