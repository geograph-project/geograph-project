{include file="_std_begin.tpl"}
{dynamic}
<h2>EXIF data for {$image->title|escape:'html'}</h2>

<p align="right"><a href="/photo/{$image->gridimage_id}">back to image</a></p>

{if $exif}
<dl>
{show_exif exif=$exif}
</dl>

{if kml_available}
	<div class="interestBox">
	<h3 style="margin-top:0">View Cone - in development</h3>
	<a href="?id={$image->gridimage_id}&amp;kml" class="xml-kml" type="application/vnd.google-earth.kml+xml">KML</a>
	<a href="?id={$image->gridimage_id}&amp;kml">Get View Cone in KML format</a> (opens in Google Earth)<br/>
	or view in {external href="http://maps.google.co.uk/maps?q=http://`$http_host`/show_exif.php%3Fid%3D`$image->gridimage_id`%26kml&amp;z=13" text="Google Maps"}
	</div>
{/if}

{else}
<p>unable to load exif data for this image</p>
{/if}

{/dynamic}

{include file="_std_end.tpl"}
