<?php

setlocale(LC_ALL,'C'); //to match online servers...

ini_set("display_errors",1);
error_reporting(E_ALL ^ E_NOTICE);

//domain specific configuration file
$CONF=array();

$CONF['curtail_level']=0;

//servers ip BEGIN with (the server that fires cron jobs etc)
$CONF['server_ip'] = '127.0.0.';

//set to X to server from http://s[0-X].$domain/photos/....
$CONF['enable_cluster'] = 2;
$CONF['STATIC_HOST'] = "s0.channel.virtual";

$CONF['CONTENT_HOST'] = "channel.virtual";
$CONF['TILE_HOST'] = "t0.channel.virtual";

//this can be different to your main hostname if want to seperate out the hosting of the Google Earth Superlayer. 
$CONF['KML_HOST'] = $_SERVER['HTTP_HOST'];

##database configuration

$CONF['db_driver']='mysql';
$CONF['db_connect']='localhost';
$CONF['db_user']='channel';
$CONF['db_pwd']='banjo';
$CONF['db_db']='channel';
$CONF['db_persist']=''; //'?persist';

#$CONF['db_read_driver']='mysql';
#$CONF['db_read_connect']='localhost';
#$CONF['db_read_user']='channel_read';
#$CONF['db_read_pwd']='m4pp3r';
#$CONF['db_read_db']='geograph';
#$CONF['db_read_persist']=''; //'?persist';

#this is the database where temporally tables are created, normally left as main database, but in replication need a seperate database. 
#the geograph AND geograph_read user should have full access to this database. whereas the geograph_read only needs SELECT priv on `geograph` db. 
$CONF['db_tempdb']='geograph_tmp';


##optional memcache

$CONF['memcache'] = array(
	'app' => array(
		'host' => '127.0.0.1',
		'port' => 11211,
		#'host2' => 'localhost',
		#'port2' => 11212,
		'p' => 'l'
		),
	);

$CONF['memcache']['adodb'] =& $CONF['memcache']['app'];

#
//not yet implemented:
#$CONF['memcache']['sessions'] =& $CONF['memcache']['app'];

$CONF['memcache']['smarty'] =& $CONF['memcache']['app'];


$CONF['sphinx_host'] = "localhost";
$CONF['sphinx_port'] = 3312;
$CONF['sphinx_cache'] = $_SERVER['DOCUMENT_ROOT'].'/../sphinxcache/';
$CONF['sphinx_prefix'] = "";



//choose UI template  (DONT forget to make sure the Folder ENUM of `smarty_cache_page` has this template)
$CONF['template']='basic';

//enable forums?
$CONF['forums']=false;


##smarty setup

//turn compile check off on stable site for a small boost
$CONF['smarty_compile_check']=1;

//only enable debugging on development domains
$CONF['smarty_debugging']=0;

//disable caching for everyday development
$CONF['smarty_caching']=1;

##admin details

//email address to send site messages to
$CONF['contact_email']='channel@barryhunter.co.uk';

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
$CONF['photo_hashing_secret']='CHANGETHIS';

//secret used for securing map tokens
$CONF['token_secret']='CHANGETHIS';

##imagemagick

//to enable the use of ImageMagick for resize operations, enter path 
//where mogrify etc can be found (highly recommended, faster than the PHP GD based routines)
//set to null or empty string to use php-based routines.
$CONF['imagemagick_path'] = '/usr/bin/';

//font used in map tile generation
$CONF['imagemagick_font'] = '/var/www/channel/public_html/stuff/captcha_fonts/FreeSans.ttf';

//you get minibb admin privilege by using a geograph admin login - these
//settings are no longer used, but you can initialise them "just in case"
$CONF['minibb_admin_user']='admin';
$CONF['minibb_admin_pwd']='CHANGETHIS';
$CONF['minibb_admin_email']='root@wherever';

//during high load can disable thumbs display in the forum pages
$CONF['disable_discuss_thumbs'] = false;


//mapping services to use for the rather maps 
$CONF['raster_service']='Grid,Google';
//valid values (comma seperated list):
// 'vob' - VisionOfBritain Historical Maps - Permission MUST be sought from the visionofbritain.org.uk webmaster before enableing this feature!
// 'OS50k' - OSGB 50k Mapping - Licence Required (see next)
// 'Google' - Use Google Mapping (api key required below)

$CONF['google_maps_api_key'] = '';

$CONF['OS_licence'] = 'XXXXXXXX';

//paths to where map data is stored (should be outside of the web root)
$CONF['os50ktilepath']='/var/www/rastermaps/OS-50k/tiffs/';
$CONF['os50kimgpath']='/var/www/rastermaps/OS-50k/';

$CONF['rastermap'] = array();

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
$CONF['references'] = array(6 => 'Channel Islands');

//including the 'non filted version'
$CONF['references_all'] = array_merge(array(0=>'Channel Islands'),$CONF['references']);

//false origins for the internal grid
$CONF['origins'] = array(6 => array(-520,-5400));

//number of characters in the grid prefix
$CONF['gridpreflen'] = array(1 => 2, 2 => 1, 6=>2);

//name of the grids (shown in page title)
$CONF['gridrefname'] = array(1 => 'OS grid ', 2 => 'OS grid ', 6 => 'Grid ');

// google maps: show meridians n*$CONF['showmeridian'] degrees (0: don't show any meridian)
$CONF['showmeridian'] = 0;

###################################

// picture of the day

$CONF['potd_daysperimage'] = 7;
$CONF['potd_listlen'] = 20;

// picture size

$CONF['pano_upper_limit'] = 0; # 0.5  : try to keep height constant for 2:1 and above
$CONF['pano_lower_limit'] = 0; # 0.25 : keep height*width constant for 4:1 and above
$CONF['img_max_size'] = 640;

// remoderation

$CONF['remod_enable'] = false;
$CONF['remod_count_init'] = 10;
$CONF['remod_count'] = 3;
$CONF['remod_recent_days'] = 10;
$CONF['remod_recent_count'] = 20;
$CONF['remod_days'] = 10;

###################################
# search setup

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
#$CONF['fetch_on_demand'] = 'www.geograph.org.uk';




//script timing logging options (comment out when not required)
//to log to separate file (in docroot/../logs)
//$CONF['log_script_timing'] = 'file';		
//log to apache logfile (use %{php_timing}n in the LogFormat)
//$CONF['log_script_timing'] = 'apache';	

//$CONF['log_script_folder'] = '/var/logs/geograph';	




##limits on numbers of thumbnails per page, and 'single item'
$CONF['global_thumb_limit'] = 300;
$CONF['post_thumb_limit'] = 200;

$CONF['GEOCUBES_API_KEY'] = "Herh2VYnVGJiUBb8nchFPrgWyfnQ18T7e9hWX68rCJPXKqYwK42YlZuc5Zrz";
$CONF['GEOCUBES_API_TOKEN'] = "Pk6Isy103owSxFG2";


// Get a key from http://recaptcha.net/api/getkey
$CONF['recaptcha_publickey'] = "6LfL8QgAAAAAABL2oYWQh25IBjoQlEJ793fWFUr2";
$CONF['recaptcha_privatekey'] = "6LfL8QgAAAAAAKA2CDYtCiDz3H6wLRopinAoDFY3";


?>
