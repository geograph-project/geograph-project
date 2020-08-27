<?

$CONF = array();

#####################################################################

//URL served by Apache
$CONF['CONTENT_HOST'] = $_SERVER['CONTENT_HOST'];

//FILE servering host
$CONF['STATIC_HOST'] = $_SERVER['STATIC_HOST']; //eg might be a external CDN

#####################################################################
// smarty configuration

//choose UI template
$CONF['template']='basic';

//turn compile check off on stable site for a small boost
$CONF['smarty_compile_check']=0;

//only enable debugging on development domains
$CONF['smarty_debugging']=0;

//disable caching for everyday development
$CONF['smarty_caching']=1;

#####################################################################
// folder config

$CONF['photo_upload_dir'] = '/mnt/upload';

#####################################################################
// AWS (for s3 access)

if (isset($_SERVER['AWS_ACCESS_KEY_ID'])) {
	$CONF['awsAccessKey'] = $_SERVER['AWS_ACCESS_KEY_ID'];
	$CONF['awsSecretKey'] = $_SERVER['AWS_SECRET_ACCESS_KEY'];
	$CONF['awsS3Bucket'] = $_SERVER['S3_BUCKET_NAME'];
}

#####################################################################
//database configuration

$CONF['db_driver']='mysqli';

$CONF['db_connect']=$_SERVER['MYSQL_HOST'];
$CONF['db_user']=$_SERVER['MYSQL_USER'];
$CONF['db_pwd']=$_SERVER['MYSQL_PASSWORD'];
$CONF['db_db']=$_SERVER['MYSQL_DATABASE'];
$CONF['db_persist']=''; //'?persist';

if (isset($_SERVER['MYSQL_READ_HOST'])) {
	$CONF['db_read_driver']='mysqli';
	$CONF['db_read_connect']=$_SERVER['MYSQL_READ_HOST'];
	$CONF['db_read_user']=$_SERVER['MYSQL_READ_USER'] ?? $CONF['db_user'];
	$CONF['db_read_pwd']=$_SERVER['MYSQL_READ_PASSWORD'] ?? $CONF['db_pwd'];
	$CONF['db_read_db']=$_SERVER['MYSQL_READ_DATABASE'] ?? $CONF['db_db'];
	$CONF['db_read_persist']=''; //'?persist';
}

$CONF['db_tempdb']=$_SERVER['MYSQL_TEMP_DATABASE'] ?? $CONF['db_db'];
//optional second database, that can be excluded from replication
//replicate-wild-ignore-table=geograph_tmp.%


#####################################################################
// Sphinx/Manticore configuration

$CONF['sphinx_host'] = $_SERVER['SPHINX_HOST'];
$CONF['sphinx_port'] = (int)($_SERVER['SPHINX_API_PORT'] ?? 9312);
$CONF['sphinx_portql'] = (int)($_SERVER['SPHINX_QL_PORT'] ?? 9306);
//Note, SphinxQL connection will reuse $CONF['db_driver'] above!


#####################################################################
// Redis Host

$CONF['redis_host'] = $_SERVER['REDIS_HOST'];
$CONF['redis_port'] = (int)($_SERVER['REDIS_PORT'] ?? 6379);
$CONF['redis_db'] = (int)($_SERVER['REDIS_DATABASE'] ?? 0);

#####################################################################
// memcache Setup

$CONF['memcache'] = array(
        'app' => 'redis' //impleented via Redis above!
);

/*
Note, can use memcache if prefer:
	'app' => array(
                'host1' => '192.168.1.80', 'port1' => 11211,
                'host2' => '192.168.1.81', 'port2' => 11211,
                'p' => 'L'
                ),
... although redis is still required, so only use memcache, if want to use it shared on multiple servers (or want a bigger cache than single redis instance!)
*/

#####################################################################

$CONF['carrot2_dcs_url'] = $_SERVER['CARROT2_DCS_URL'];

#####################################################################

$CONF['timetravel_url'] = $_SERVER['TIMETRAVEL_URL'];

#####################################################################
// example email... (during testing can send an email here!)

$CONF['contact_email']='barry@geograph.org.uk';


