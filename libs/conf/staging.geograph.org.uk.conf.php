<?php
//domain specific configuration file
$CONF=array();

$CONF['curtail_level']=0;

//main server (the server that fires cron jobs etc)
$CONF['server_ip'] = '10.72.'; //todo this isnt secure or right on cloud. need to revamp all checks using this

########################################################################

$CONF['enable_cluster'] = 4;

$CONF['PROTOCOL'] = "http://";
if (!empty($_SERVER['HTTPS']) || ( !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
        $CONF['PROTOCOL'] = "https://";

$CONF['STATIC_HOST'] = $CONF['PROTOCOL']."staging.s0.geograph.org.uk";
$CONF['CONTENT_HOST'] = $CONF['PROTOCOL']."staging.geograph.org.uk";
$CONF['TILE_HOST'] = $CONF['PROTOCOL']."staging.t0.geograph.org.uk";
$CONF['KML_HOST'] = "http://staging.kml.geograph.org.uk";
$CONF['SELF_HOST'] = $CONF['PROTOCOL'].$_SERVER['HTTP_HOST'];

########################################################################
//email

/* we can now do email directly via smpt, but UNUSED, because for now pet has postfix.
$CONF['smtp_from'] = "noreply@cloud.geograph.org.uk";
$CONF['smtp_host'] = "email-smtp.eu-west-1.amazonaws.com";
$CONF['smtp_port'] = 587; //port 25 doesnt work?
$CONF['smtp_user'] = "...";
$CONF['smtp_pass'] = "...";
*/

########################################################################
//database configuration
$CONF['db_driver']='mysqli';

$CONF['db_connect']=$_SERVER['MYSQL_HOST'];
$CONF['db_user']='geograph';
$CONF['db_pwd']=$_SERVER['MYSQL_PASSWORD'];
$CONF['db_db']=$_SERVER['MYSQL_DATABASE'];
$CONF['db_persist']='';//'?persist';

	//optaionl slave
	$CONF['db_read_driver']=$CONF['db_driver']; //comment to disable!
	$CONF['db_read_connect']=$_SERVER['MYSQL_SLAVE_HOST'];
	$CONF['db_read_user']=$CONF['db_user']; //the replica has --read-only, so wont be able to write anyway.
	$CONF['db_read_pwd']=$CONF['db_pwd'];
	$CONF['db_read_db']=$CONF['db_db'];
	$CONF['db_read_persist']=$CONF['db_persist'];


#this is the database where temporally tables are created, normally left as main database, but in replication need a seperate database.
#the geograph AND geograph_read user should have full access to this database. whereas the geograph_read only needs SELECT priv on `geograph` db.
$CONF['db_tempdb']='geograph_tmp';

$CONF['use_insertionqueue'] = true;

###############

//only enable debugging on development domains - this pulls in the
//adodb-errorhandler.inc.php file which causes db errors to output using
//the php error handler
$CONF['adodb_debugging']=1;

//path to adodb cache dir
$CONF['adodb_cache_dir']='/var/www/geograph/adodbcache/';


################


#//optional session database (omit to use same db)
#$CONF['db_driver2']='mysql';
#$CONF['db_connect2']='';
#$CONF['db_user2']='';
#$CONF['db_pwd2']='';
#$CONF['db_db2']='';
#$CONF['db_persist2']='';//'?persist';


########################################################################

/*
$CONF['memcache'] = array(
	'app' => array(
                'host' => '192.168.77.81',  'port' => 11211,
                'host1' => '192.168.77.80',  'port1' => 11211,
                'p' => 'S'
		),
	);

$CONF['memcache']['adodb'] =& $CONF['memcache']['app'];
$CONF['memcache']['smarty'] =& $CONF['memcache']['app'];
#$CONF['memcache']['sessions'] =& $CONF['memcache']['app'];

#unset($CONF['memcache']);
*/

$CONF['memcache'] = array(
	'app' => array('redis'=>0), //redirect to redis below
	'adodb' => array('redis'=>1),
//	'sessions' => array('redis'=>2),  //if redis_host is defined, this is ignored. uses redis directly, rather tha memcache emulation.
	'smarty' => array('redis'=>3),
);


$CONF['redis_host'] = $_SERVER['REDIS_HOST'];
$CONF['redis_port'] = 6379;
$CONF['redis_session_db'] = 2;
$CONF['redis_api_db'] = 4;



########################################################################


$CONF['sphinx_host'] = $_SERVER['SPHINX_HOST'];
$CONF['sphinx_port'] = 9312;
$CONF['sphinx_portql'] = 9306;
$CONF['sphinx_prefix'] = '';


$CONF['sphinx_prefix'] = ''; //we can use the live indexes, using the 'safe' bodge in search.php!

########################################################################


$CONF['carrot2_dcs_url'] = $_SERVER['CARROT2_DCS_URL']; //the specific rest API endpoint

$CONF['timetravel_url'] = $_SERVER['TIMETRAVEL_URL']; #our proxy uses hostname, ther than specific port! //just the hostname (and optional port) without trailing slash. We add the full API path


########################################################################


$CONF['photo_upload_dir'] = '/var/www/geograph/upload_tmp_dir';


//to enable the use of ImageMagick for resize operations, enter path 
//where mogrify etc can be found (highly recommended, faster than the PHP GD based routines)
//set to null or empty string to use php-based routines.
$CONF['imagemagick_path'] = '/usr/bin/';


$CONF['rastermap'] = array(
	'OS50k' => array(
			'path'=>'/var/www/geograph/rastermaps/OS-50k/',
			'epoch'=>'latest/'
			),
	'OS250k' => array(
			'path'=>'/var/www/geograph/rastermaps/OS-250k/',
			'epoch'=>'latest/'
			)
);

$CONF['os50ktilepath']='/var/www/geograph/rastermaps/OS-50k/latest/tiffs/';
$CONF['os50kimgpath']='/var/www/geograph/rastermaps/OS-50k/';
$CONF['os50kepoch']='latest/';


$CONF['imagemagick_font'] = '/var/www/geograph/FreeSans.ttf';


########################################################################


//choose UI template
$CONF['template']='basic';

$CONF['forums'] = true;

//turn compile check off on stable site for a small boost
$CONF['smarty_compile_check']=1;

//only enable debugging on development domains
$CONF['smarty_debugging']=0;

//disable caching for everyday development
$CONF['smarty_caching']=1;

//email address to send messages to
$CONF['contact_email']='barry@barryhunter.co.uk';

//secret string used for registration confirmation hash
$CONF['register_confirmation_secret']=$_SERVER['REGISTER_SECRET'];

//secret string used for hashing photo filenames
$CONF['photo_hashing_secret']=$_SERVER['PHOTO_HASHING_SECRET'];


//admin user for miniBB -ToDo: make this db driven
$CONF['minibb_admin_user']='Geograph Website';
$CONF['minibb_admin_pwd']='thisisnotused';
$CONF['minibb_admin_email']='noreply@geograph.co.uk';

//secret used for securing map tokens
$CONF['token_secret']=$_SERVER['TOKEN_SECRET'];

//mapping service to use for the rather maps
$CONF['raster_service']='OSOS,OSOSPro,OS50k,Google,Grid';


$CONF['OS_OpenSpace_Licence'] = 'A493C3EB96133019E0405F0ACA6056E3';


$CONF['google_maps_api_key'] = 'ABQIAAAAw3BrxANqPQrDF3i-BIABYxR7sTJmTkFOba0AcTw5f8vPszpr5hTGbCEfS2eI3B_hmSTU76_7dfkhWg';


//the countries referenced in the reference index 
$CONF['references'] = array(1 => 'Great Britain',2 => 'Ireland');

$CONF['references_all'] = array_merge(array(0=>'British Isles'),$CONF['references']);


$CONF['default_search_distance'] = 10;

$CONF['default_search_distance_2'] = 20;

$CONF['search_prompt_radius'] = 4;

$CONF['origins'] = array(1 => array(206,0), 2 => array(10,149));

//match what intergrated mapping uses!
$CONF['intergrated_layers'] = array(0 => 'FTT000000000B000FT', 1 => 'FTFB000000000000FT', 2 => 'FFT000000000000BFT');
$CONF['intergrated_zoom'] = array(0 => 13, 1 => 6, 2 => 13);
$CONF['intergrated_zoom_centi'] = array(0 => 15, 1 => 8, 2 => 15);


//to use the flickr search will need to obtain a flicker api key
//    http://flickr.com/services/api/misc.api_keys.html
$CONF['flickr_api_key'] = '';

//does the map draw the more demanding placenames
$CONF['enable_newmap'] = 1;

//use the smaller towns database
$CONF['use_gazetteer'] = 'OS250'; //OS/OS250/towns/default


$CONF['OS_licence'] = '100045616';

$CONF['search_count_first_page'] = false; //true/false

//domain from which pictures can be pulled on demand
//only for use on development systems to allow 'real' pictures to be
//copied to your local system on demand. Simply give the domain name
//of the target system.
//COMMENT THIS LINE OUT ON LIVE SYSTEMS!
$CONF['fetch_on_demand'] = 'www.geograph.org.uk';


$CONF['disable_discuss_thumbs'] = false;

$CONF['picnik_api_key'] = '2489f7615d82c66a28afeb957c789a6b';
$CONF['picnik_method'] = 'inabox'; //inabox|redirect

$CONF['juppy_minimum_images'] = 5;

$CONF['global_thumb_limit'] = 300;
$CONF['post_thumb_limit'] = 200;

$CONF['disable_spelling']= 1;

// Get a key from http://recaptcha.net/api/getkey
$CONF['recaptcha_publickey'] = $_SERVER['RECAPTCHA_PUB'];
$CONF['recaptcha_privatekey'] = $_SERVER['RECAPTCHA_PRI'];


###################################


$CONF['content_sources'] = array('portal'=>'Portal', 'article'=>'Article', 'blog'=>'Blog Entry', 'trip'=>'Geo-trip', 'gallery'=>'Gallery', 'themed'=>'Themed Topic', 'help'=>'Help Article', 'gsd'=>'Grid Square Discussion', 'snippet'=>'Shared Description', 'user'=>'Contributor', 'category'=>'Category', 'context'=>'Geographical Context', 'other'=>'Other', 'faq'=>'FAQ Answer', 'link' => 'Website Page','cluster' => 'Photo Cluster');

