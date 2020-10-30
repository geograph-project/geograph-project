<?php

//reads the configuation from the envoiroment, particully suitable for use in kubernetes/docker etc. means the difference between staging/production is outside application code
$CONF=array();

########################################################################

$CONF['PROTOCOL'] = "http://";
if (!empty($_SERVER['HTTPS']) || ( !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
        $CONF['PROTOCOL'] = "https://";

foreach ($_SERVER as $key => $value) {
	if (preg_match('/^CONF_(\w+)/',$key,$m)) {
		if (preg_match('/^[\[\{].*[\}\]]$/',$value))
			$value = json_decode($value,true);
		if (preg_match('/_HOST$/',$key) && strpos($value,'http')===0) //these have special handling, and are explicitly upper case
			$CONF[$m[1]] = preg_replace('/^https?:\/\//',$CONF['PROTOCOL'],$value);
		else
			$CONF[strtolower($m[1])] = $value;
	}
}

$CONF['SELF_HOST'] = $CONF['PROTOCOL'].$_SERVER['HTTP_HOST'];

########################################################################
// configure the optional slave, do this, so dont need to duplicate everything in config. 

if (!empty($CONF['db_read_connect'])) {
	if (empty($CONF['db_read_driver']))
		$CONF['db_read_driver'] = $CONF['db_driver'];
	if (empty($CONF['db_read_user']))
		$CONF['db_read_user']=$CONF['db_user']; //the replica has --read-only, so wont be able to write anyway.
	if (empty($CONF['db_read_pwd']))
		$CONF['db_read_pwd']=$CONF['db_pwd'];
	if (empty($CONF['db_read_db']))
		$CONF['db_read_db']=$CONF['db_db'];
	if (empty($CONF['db_read_persist']))
		$CONF['db_read_persist']=$CONF['db_persist'];
}

########################################################################
// todo this really should be reworked to be tidier.

$CONF['rastermap'] = array(
	'OS50k' => array(
			'path'=>$_SERVER['BASE_DIR'].'/rastermaps/OS-50k/',
			'epoch'=>'latest/'
			),
	'OS250k' => array(
			'path'=>$_SERVER['BASE_DIR'].'/rastermaps/OS-250k/',
			'epoch'=>'latest/'
			)
);

$CONF['os50ktilepath']=$_SERVER['BASE_DIR'].'/rastermaps/OS-50k/latest/tiffs/';
$CONF['os50kimgpath']=$_SERVER['BASE_DIR'].'/rastermaps/OS-50k/';
$CONF['os50kepoch']='latest/';

$CONF['imagemagick_font'] = $_SERVER['BASE_DIR'].'/libs/fonts/FreeSans.ttf';

########################################################################
//these are not really config as such, the config file is jsut a good place to put constants. You can't really change these

$CONF['references'] = array(1 => 'Great Britain',2 => 'Ireland');

$CONF['references_all'] = array_merge(array(0=>'British Isles'),$CONF['references']);

$CONF['origins'] = array(1 => array(206,0), 2 => array(10,149));

$CONF['content_sources'] = array('portal'=>'Portal', 'article'=>'Article', 'blog'=>'Blog Entry', 'trip'=>'Geo-trip', 'gallery'=>'Gallery', 'themed'=>'Themed Topic', 'help'=>'Help Article', 'gsd'=>'Grid Square Discussion', 'snippet'=>'Shared Description', 'user'=>'Contributor', 'category'=>'Category', 'context'=>'Geographical Context', 'other'=>'Other', 'faq'=>'FAQ Answer', 'link' => 'Website Page','cluster' => 'Photo Cluster');

