<?php
//domain specific configuration file
$CONF=array();

//database configuration
$CONF['db_driver']='mysql';
$CONF['db_connect']='localhost';
$CONF['db_user']='geograph';
$CONF['db_pwd']='banjo';
$CONF['db_db']='geograph';

//choose UI template
$CONF['template']='basic';

//turn compile check off on stable site for a small boost
$CONF['smarty_compile_check']=1;

//only enable debugging on development domains
$CONF['smarty_debugging']=1;

//disable caching for everyday development
$CONF['smarty_caching']=0;

//email address to send messages to
$CONF['contact_email']='lordelph@gmail.com,editor@geocachingtoday.com';

//secret string used for registration confirmation hash
$CONF['register_confirmation_secret']='CHANGETHIS';
?>