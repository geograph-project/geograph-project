<?

$CONF = array();

#####################################################################

//URL served by Apache
$CONF['CONTENT_HOST'] = "https://toy.geograph.org.uk";

//FILE servering host
$CONF['STATIC_HOST'] = "https://toy-static.geograph.org.uk"; //eg might be a external CDN

#####################################################################
// smarty configuration

//choose UI template
$CONF['template']='basic';

//turn compile check off on stable site for a small boost
$CONF['smarty_compile_check']=1;

//only enable debugging on development domains
$CONF['smarty_debugging']=0;

//disable caching for everyday development
$CONF['smarty_caching']=1;

#####################################################################
// folder config

$CONF['photo_upload_dir'] = '/mnt/upload/upload_tmp_dir';


#####################################################################
//database configuration

$CONF['db_driver']='mysql';
$CONF['db_connect']='192.168.1.50';
$CONF['db_user']='geograph';
$CONF['db_pwd']='changethis';
$CONF['db_db']='geograph_you';
$CONF['db_persist']=''; //'?persist';


//$CONF['db_read_driver']='mysql'; //comment out this line to disable to the slave
$CONF['db_read_connect']='192.168.1.51';
$CONF['db_read_user']=$CONF['db_user'];
$CONF['db_read_pwd']=$CONF['db_pwd'];
$CONF['db_read_db']=$CONF['db_db'];
$CONF['db_read_persist']=''; //'?persist';


$CONF['db_tempdb']='geograph_tmp';
//optional second database, that can be excluded from replication
//replicate-wild-ignore-table=geograph_tmp.%


#####################################################################
// Sphinx/Manticore configuration

$CONF['sphinx_host'] = '192.168.1.90';
$CONF['sphinx_port'] = 3312;
$CONF['sphinx_portql'] = 9306;

#####################################################################
// Redis Host

$CONF['redis_host'] = '192.168.1.48';
$CONF['redis_port'] = 6379;
$CONF['redis_db'] = 7;

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

$CONF['carrot2_dcs_url'] = "http://localhost:8081/dcs/rest";

#####################################################################

$CONF['timetravel_url'] = "http://localhost:1208/api/json/";

#####################################################################
// example email... (during testing can send an email here!)

$CONF['contact_email']='barry@geograph.org.uk';


