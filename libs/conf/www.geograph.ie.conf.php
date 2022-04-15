<?php

if (!empty($_SERVER['CONF_PROFILE'])) {
        require('conf/'.$_SERVER['CONF_PROFILE'].'.conf.php');
} else {
	require("conf/www.geograph.org.uk.conf.php");
}

$CONF['template']='ireland';

$CONF['google_maps_api_key'] = 'ABQIAAAAw3BrxANqPQrDF3i-BIABYxTmuuy-uNtb-hPxfvT1_u5ELsEdTxSMBQk-NOk1ARewPPP_76QqjP3omw';

$CONF['raster_service']='Leaflet,Google,Grid';

