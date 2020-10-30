<?php

if (!empty($_SERVER['CONF_PROFILE'])) {
        require('conf/'.$_SERVER['CONF_PROFILE'].'.conf.php');
} else {
	require("conf/www.geograph.org.uk.conf.php");
}

$CONF['curtail_level'] = 0;

$CONF['template']='archive';

$CONF['google_maps_api_key'] = 'ABQIAAAAw3BrxANqPQrDF3i-BIABYxQmRrJZEy0b9Xmb78P8sl6wHVVn3xQU9J50LbwdcKZPRjXuLoc1y8KBXQ';

$CONF['smarty_caching']=0;

