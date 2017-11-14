<?xml version="1.0" encoding="iso-8859-1"?>
<gpx xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" version="1.0"
creator="{$http_host}"
xsi:schemaLocation="http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd" xmlns="http://www.topografix.com/GPX/1/0">
<desc>{$searchdesc|escape:"html"}</desc>
<author>{$http_host}</author>
<email>support@{$http_host}</email>
<url>http://{$http_host}/</url>
<urlname>Geograph British Isles</urlname>
<time>{$smarty.now|date_format:'%Y-%m-%dT%H:%M:%S'}</time>
<keywords>geograph, photo, photograph</keywords>

{foreach from=$data item=row}
	<wpt lat="{$row.lat}" lon="{$row.long}">
		<name>{$row.grid_reference}</name>
		<desc>{$row.grid_reference} :: {$row.imagecount} Images</desc>
		<url>http://{$http_host}/gridref/{$row.grid_reference}</url>
		<urlname>{$row.grid_reference}</urlname>
		<sym>Geograph</sym>
	</wpt>
{/foreach}
</gpx>
