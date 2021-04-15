<?php

//reads the configuation from the envoiroment, particully suitable for use in kubernetes/docker etc. means the difference between staging/production is outside application code
$CONF=array();

########################################################################

$CONF['PROTOCOL'] = "http://";
if (!empty($_SERVER['HTTPS']) || ( !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
        $CONF['PROTOCOL'] = "https://";

foreach ($_SERVER as $key => $value) {
	if (preg_match('/^CONF_(\w+)/',$key,$m)) {
		if (preg_match('/env\.([A-Z]\w+)/',$value,$m2))
			$CONF[strtolower($m[1])] = $_SERVER[$m2[1]];
		elseif (preg_match('/_HOST$/',$key) && strpos($value,'http')===0) //these have special handling, and are explicitly upper case
			$CONF[$m[1]] = preg_replace('/^https?:\/\//',$CONF['PROTOCOL'],$value);
		else {
			if (preg_match('/^[\[\{].*[\}\]]$/',$value))
				$value = json_decode($value,true);

			$CONF[strtolower($m[1])] = $value;
		}
	}
}

$CONF['SELF_HOST'] = $CONF['PROTOCOL'].$_SERVER['HTTP_HOST'];

########################################################################

$CONF['curtail_level']=0; //deprecated - dont use!

//uncomment one appropriate line below as needed. edting the time as needed.
$CONF['submission_message'] = 'There will be a brief interuption of service sometime between 2-4pm (BST) - expected about 20 minutes.';

//and if enabling submission_message - set this to be about 20minutes before (and uncomment it!).
$CONF['critical'] = '1345'; //note: a string, not a number, format HHmm. Eg if closing at 2pm, set '1340'

//uncomment this if want the site to also be marked reasonly. Needs to be used with 'submission_message'

#$CONF['readonly'] = true; // note only affects the submission, as in filesystem readoly, not database readonly!

#~~~~~~~~~~~~~~~~
# shoudnt need to edit below here often.

if (!empty($CONF['submission_message'])) {
	//this, just prettifies the message if active.

	$CONF['submission_message'] .= ' It is not recommended to have any submissions in progress during that time, as the site may disappear at any time. Submission will be closed between 2-4pm';
	$CONF['submission_message'] .= ' Sorry for the inconvenience.';

	if (date('Gi') > $CONF['critical']) {
		$CONF['submission_message'] .= ' When this message disappears it will be safe to continue.';
		//$CONF['submission_message'] .= ' Please aim to have all in progress submissions completed <b>ASAP</b> to avoid loosing information.';

		$CONF['submission_message'] = '<div class="interestBox" style="background-color:yellow;border:10px solid red;padding:20px;margin:10px;margin-bottom:200px;">'.$CONF['submission_message'].'</div>';
	} else {
		$CONF['submission_message'] = '<div class="interestBox" style="border:1px solid red;padding:20px;margin:10px;">'.$CONF['submission_message'].'</div>';
	}
	$CONF['moderation_message'] = str_replace('submission','action',$CONF['submission_message']);
}


########################################################################
// configure the optional slave, do this, so dont need to duplicate everything in config

if (!empty($CONF['db_read_connect'])) {
	if (empty($CONF['db_read_driver']))
		$CONF['db_read_driver'] = $CONF['db_driver'];
	if (empty($CONF['db_read_user']))
		$CONF['db_read_user']=$CONF['db_user']; //the replica has --read-only, so wont be able to write anyway
	if (empty($CONF['db_read_pwd']))
		$CONF['db_read_pwd']=$CONF['db_pwd'];
	if (empty($CONF['db_read_db']))
		$CONF['db_read_db']=$CONF['db_db'];
	if (empty($CONF['db_read_persist']))
		$CONF['db_read_persist']=$CONF['db_persist'];
}

########################################################################
// todo this really should be reworked to be tidier

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



########################################################################
// Hack, just for now. Mutate the config a bit for testing, to save having to have whole seperate configmap for dev

if ($_SERVER['HOSTNAME'] == 'development-0') {
	$keys = array('CONTENT_HOST'); //at the moment, we dont have other domains setup
	foreach ($keys as $key)
		$CONF[$key] = str_replace('staging','development', $CONF[$key]);
}



