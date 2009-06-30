{include file="_std_begin.tpl"}
{dynamic}
<h2>EXIF data for {$image->title|escape:'html'}</h2>

<p align="right"><a href="/photo/{$image->gridimage_id}">back to image</a></p>

<dl>
{show_exif exif=$exif}
</dl>
{/dynamic}
{include file="_std_end.tpl"}
