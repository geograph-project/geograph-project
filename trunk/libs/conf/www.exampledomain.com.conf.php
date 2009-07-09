<?php

setlocale(LC_ALL,'C'); //to match online servers...

//domain specific configuration file
$CONF=array();

$CONF['curtail_level']=0;

//servers ip BEGIN with (the server that fires cron jobs etc)
$CONF['server_ip'] = '127.0.0.';

//set to X to server from http://s[0-X].$domain/photos/....
$CONF['enable_cluster'] = 2;

//this can be different to your main hostname if want to seperate out the hosting of the Google Earth Superlayer. 
$CONF['KML_HOST'] = $_SERVER['HTTP_HOST'];

##database configuration

$CONF['db_driver']='mysql';
$CONF['db_connect']='localhost';
$CONF['db_user']='geograph';
$CONF['db_pwd']='banjo';
$CONF['db_db']='geograph';
$CONF['db_persist']=''; //'?persist';


##optional memcache

#$CONF['memcache'] = array(
#	'app' => array(
#		'host' => '127.0.0.1',
#		'port' => 11211,
#		#'host2' => 'localhost',
#		#'port2' => 11212,
#		'p' => 'l'
#		),
#	);
#
#$CONF['memcache']['sessions'] =& $CONF['memcache']['app'];
#
//not yet functional (with current adodb)
##$CONF['memcache']['adodb'] =& $CONF['memcache']['app'];
#
//not yet implemented:
##$CONF['memcache']['smarty'] =& $CONF['memcache']['app'];

// forum ids
$CONF['forum_announce']          = 1;
$CONF['forum_generaldiscussion'] = 2;
$CONF['forum_suggestions']       = 3;
$CONF['forum_bugreports']        = 4;
$CONF['forum_gridsquare']        = 5;
$CONF['forum_submittedarticles'] = 6;
$CONF['forum_gallery']           = 7;  #11;
$CONF['forum_moderator']         = -1; #9;
$CONF['forum_privacy']           = -1; #14;
$CONF['forum_teaching']          = -1; #8;
$CONF['forum_devel']             = -1; #12;

// topic ids
$CONF['forum_topic_announce'] =  -1; #5808;
$CONF['forum_topic_numsquare'] = -1; #1235;

// forums which need custom templates
$CONF['forum_to_template'][$CONF['forum_submittedarticles']] = '6';
$CONF['forum_to_template'][$CONF['forum_gallery']] = '11';
$CONF['forum_to_template'][$CONF['forum_bugreports']] = '4';

$CONF['sphinx_host'] = "localhost";
$CONF['sphinx_port'] = 3312;
$CONF['sphinx_cache'] = $_SERVER['DOCUMENT_ROOT'].'/../sphinxcache/';



//choose UI template
$CONF['template']='basic';

//enable forums?
$CONF['forums']=true;


##smarty setup

//turn compile check off on stable site for a small boost
$CONF['smarty_compile_check']=1;

//only enable debugging on development domains
$CONF['smarty_debugging']=1;

//disable caching for everyday development
$CONF['smarty_caching']=1;

##admin details

//email address to send site messages to
$CONF['contact_email']='someone@somewhere.com,other@elsewhere.com';

## adodb setip

//only enable debugging on development domains - this pulls in the
//adodb-errorhandler.inc.php file which causes db errors to output using
//the php error handler
$CONF['adodb_debugging']=1;

//path to adodb cache dir
$CONF['adodb_cache_dir']=$_SERVER['DOCUMENT_ROOT'].'/../adodbcache/';

## folder setup

//path to temp folder for photo uploads - on cluster setups should be a shared folder.
$CONF['photo_upload_dir'] = '/tmp';

## secret tokens

//secret string used for registration confirmation hash
$CONF['register_confirmation_secret']='CHANGETHIS';

//secret string used for hashing photo filenames
$CONF['photo_hashing_secret']='CHANGETHISTOO';

//secret used for securing map tokens
$CONF['token_secret']='CHANGETHIS';

##imagemagick

//to enable the use of ImageMagick for resize operations, enter path 
//where mogrify etc can be found (highly recommended, faster than the PHP GD based routines)
//set to null or empty string to use php-based routines.
$CONF['imagemagick_path'] = '/usr/bin/';

//font used in map tile generation
$CONF['imagemagick_font'] = '/usr/share/fonts/truetype/freefont/FreeSans.ttf';

//you get minibb admin privilege by using a geograph admin login - these
//settings are no longer used, but you can initialise them "just in case"
$CONF['minibb_admin_user']='admin';
$CONF['minibb_admin_pwd']='CHANGETHIS';
$CONF['minibb_admin_email']='root@wherever';

//during high load can disable thumbs display in the forum pages
$CONF['disable_discuss_thumbs'] = false;


//mapping services to use for the rather maps 
$CONF['raster_service']='';
//valid values (comma seperated list):
// 'vob' - VisionOfBritain Historical Maps - Permission MUST be sought from the visionofbritain.org.uk webmaster before enableing this feature!
// 'OS50k' - OSGB 50k Mapping - Licence Required (see next)
// 'Google' - Use Google Mapping (api key required below)
// 'Grid' - Should be used with 'Google'

$CONF['google_maps_api_key'] = 'XXXXXXX';

$CONF['OS_licence'] = 'XXXXXXXX';

//paths to where map data is stored (should be outside of the web root)
$CONF['rastermap'] = array(
	'OS50k' => array(
			'path'=>'c:/home/geograph/rastermaps/OS-50k/',
			'epoch'=>'latest/'
			),
	'OS250k' => array(
			'path'=>'c:/home/geograph/rastermaps/OS-250k/',
			'epoch'=>'latest/'
			)	
);

//Username/Passowrd for the metacarta webservices api
//http://developers.metacarta.com/register/
#$CONF['metacarta_auth'] = 'user@domain.com:password';
$CONF['metacarta_auth'] = '';

//does the map draw the more demanding placenames
$CONF['enable_newmap'] = 1;

//use the smaller towns database for the 'near...' lines rather than placenames
$CONF['use_gazetteer'] = 'towns'; //OS/hist/towns/default
//NOTE: for GB, OS and hist are (c)'ed datasets and are not available under the GPL licence

##country info

//the countries referenced in the reference index 
$CONF['references'] = array(1 => 'Great Britain',2 => 'Ireland');

//including the 'non filted version'
$CONF['references_all'] = array(0=>'British Isles')+$CONF['references'];

//false origins for the internal grid
$CONF['origins'] = array(1 => array(206,0),2 => array(10,149));


## search setup

//the radius for simple searches in km, set high to begin with but set low once number of submissions
$CONF['default_search_distance'] = 10;

//for ri 2 we might want a different number
$CONF['default_search_distance_2'] = 30;

//radius to count number of single image squares
$CONF['search_prompt_radius'] = 4;

//if you have capacity problems true to false, to skip checking count on page 1 of results. 
$CONF['search_count_first_page'] = true; //true/false



//to use the flickr search will need to obtain a flicker api key
//    http://flickr.com/services/api/misc.api_keys.html
$CONF['flickr_api_key'] = '';

//to use the picnik service for upload will need to obtain a api key
//   http://www.picnik.com/keys/request
$CONF['picnik_api_key'] = '';

//method to use for picnik, see 
//http://www.picnik.com/info/api
$CONF['picnik_method'] = 'inabox'; //'inabox'|'redirect'


//domain from which pictures can be pulled on demand
//only for use on development systems to allow 'real' pictures to be
//copied to your local system on demand. Simply give the domain name
//of the target system.
//COMMENT THIS LINE OUT ON LIVE SYSTEMS!
//$CONF['fetch_on_demand'] = 'www.geograph.org.uk';




//script timing logging options (comment out when not required)
//to log to separate file (in docroot/../logs)
//$CONF['log_script_timing'] = 'file';		
//log to apache logfile (use %{php_timing}n in the LogFormat)
//$CONF['log_script_timing'] = 'apache';	

//$CONF['log_script_folder'] = '/var/logs/geograph';	




$CONF['TILE_HOST'] = $_SERVER['HTTP_HOST'];
$CONF['STATIC_HOST'] = $_SERVER['HTTP_HOST'];
$CONF['CONTENT_HOST'] = $_SERVER['HTTP_HOST'];

##limits on numbers of thumbnails per page, and 'single item'
$CONF['global_thumb_limit'] = 300;
$CONF['post_thumb_limit'] = 200;


?>
