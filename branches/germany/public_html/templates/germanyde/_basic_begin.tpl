<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"{if $rastermap->service == 'Google'} xmlns:v="urn:schemas-microsoft-com:vml"{/if} xml:lang="de" style="margin:0px">
<head>
<title>{$page_title|default:"Geograph"}</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/mapper.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
{if $olayersmap||$rastermap->service == 'OLayers'}
<!-- RasterMap.getScriptTag() -->
    <link rel="stylesheet" href="/ol/theme/default/style.css" type="text/css" />
{if $google_maps_api_key}
    <link rel="stylesheet" href="/ol/theme/default/google.css" type="text/css" />
{/if}
    <!--[if lte IE 6]>
        <link rel="stylesheet" href="/ol/theme/default/ie6-style.css" type="text/css" />
    <![endif]-->
{if $olayersmap}
{literal}
    <style type="text/css">
        .olControlScaleLine {
            bottom: 45px;
        }
        .olControlAttribution {
            bottom: 15px;
            opacity: 0.75;
            background-color: lightblue;
        }
    </style>
{/literal}
{else}
{literal}
    <style type="text/css">
        .olControlZoomPanel {
            top: 14px;
        }
        .olControlAttribution {
            bottom: 0px;
            opacity: 0.75;
            background-color: lightblue;
        }
        .olControlLayerSwitcher {
            font-size:x-small;
            top: 10px;
        }
    </style>
{/literal}
{/if}
{/if}
{if $rastermap->service == 'Google'}
<!-- RasterMap.getScriptTag() -->
{literal}<style type="text/css">
v\:* {
	behavior:url(#default#VML);
}
</style>{/literal}
{/if}
<script type="text/javascript" src="{"/geograph.js"|revision}"></script>
</head>

<body bgcolor="#ffffff" style="background-color:white;margin:0px">
