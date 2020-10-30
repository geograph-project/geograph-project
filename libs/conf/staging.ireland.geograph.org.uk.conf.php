<?php

if (!empty($_SERVER['CONF_PROFILE'])) {
        require('conf/'.$_SERVER['CONF_PROFILE'].'.conf.php');
} else {
	require("conf/staging.geograph.org.uk.conf.php");
}

$CONF['template']='ireland';



