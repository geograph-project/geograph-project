<?php

if (!empty($_SERVER['CONF_PROFILE'])) {
        require('conf/'.$_SERVER['CONF_PROFILE'].'.conf.php');
} else {
	require("conf/staging.geograph.org.uk.conf.php");
}

$CONF['template']='charcoal';

$CONF['forums']=false;

$CONF['google_maps_api_key'] = 'ABQIAAAAw3BrxANqPQrDF3i-BIABYxR9xdLlvyJBQXqWfTCI05PTpf76ihT4nw9GyCRpPUsxH6P9bmPkmUcv4Q';


