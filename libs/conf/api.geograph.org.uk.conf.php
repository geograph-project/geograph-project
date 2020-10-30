<?php

if (!empty($_SERVER['CONF_PROFILE'])) {
        require('conf/'.$_SERVER['CONF_PROFILE'].'.conf.php');
} else {
	require("conf/www.geograph.org.uk.conf.php");
}

$CONF['template']='api';
