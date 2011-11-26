<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" style="margin:0px">
<head>
<title>{$page_title|default:"Geograph"}</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/mapper.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
{if $rastermap->service == 'OLayers'}
<!-- RasterMap.getScriptTag() -->
{literal}<link rel="stylesheet" href="/ol/theme/default/style.css" type="text/css" />
    <link rel="stylesheet" href="/ol/theme/default/google.css" type="text/css" />
    <!--[if lte IE 6]>
        <link rel="stylesheet" href="/ol/theme/default/ie6-style.css" type="text/css" />
    <![endif]-->
    <!--link rel="stylesheet" href="style.css" type="text/css" /-->
    <style type="text/css">
        .olImageLoadError {
            background-color: transparent;
            /*background-color: pink;
	    opacity: 0.5;
	    filter: alpha(opacity=50);*/ /* IE */
	}
	.olControlZoomPanel {
	    top: 14px;
	}

        .olControlAttribution {
            bottom: 0px;
        }
        /*#map {
            height: 512px;
        }*/
    </style>{/literal}
{/if}
<script type="text/javascript" src="{"/geograph.js"|revision}"></script>
</head>

<body bgcolor="#ffffff" style="background-color:white;margin:0px">
