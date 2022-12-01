<?php

if (!empty($_SERVER['CONF_PROFILE'])) {
        require('conf/'.$_SERVER['CONF_PROFILE'].'.conf.php');
}

//enable the new template, just for the main domain
$CONF['template']='resp';


