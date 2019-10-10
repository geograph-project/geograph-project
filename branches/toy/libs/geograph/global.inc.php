<?

require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

#####################################################################
// smarty configuration

//smarty needed everywhere too
require_once('smarty/libs/Smarty.class.php');


class GeographPage extends Smarty
{
        /**
        * Constructor - sets up smarty appropriately
        */
        function __construct()
        {
                global $CONF;

               //base constructor
                parent::__construct();

                //set up paths
                $this->template_dir=$_SERVER['DOCUMENT_ROOT'].'/templates/'.$CONF['template'];
                $this->compile_dir=$this->template_dir."/compiled";
                $this->cache_dir=$this->template_dir."/cache";

                //setup optimisations
                $this->compile_check = $CONF['smarty_compile_check'];
                $this->debugging = $CONF['smarty_debugging'];
		$this->caching = $CONF['smarty_caching'];

                //register our "dynamic" handler for non-cached sections of templates
                $this->register_block('dynamic', 'smarty_block_dynamic', false,array('cached_user_id'));
	}
}

/**
* Smarty block handler
* Although it doesn't appear to do much, this is registered as a
* non-caching block handler - anything between {dynamic}{/dynamic} will
* not be cached
*/
function smarty_block_dynamic($param, $content, &$smarty)
{
        if (!empty($param) && !empty($param['cached_user_id'])) {
                $smarty->assign('cached_user_id',$param['cached_user_id']);
        }
    return $content;
}



#####################################################################
// folder config

class FileSystem {

	function __construct() {
		global $CONF;

		if (!empty($CONF['awsAccessKey']) && !empty($CONF['awsS3Bucket'])) {
			require_once("3rdparty/S3.php");

			$this->s3 = new S3($CONF['awsAccessKey'], $CONF['awsSecretKey']);
		}
	}

	function put($path, $sourcefile) {
		if (!empty($this->s3)) {
                        global $CONF;

			return $this->s3->putObjectFile($_SERVER['DOCUMENT_ROOT'].'/'.$sourcefile, $CONF['awsS3Bucket'], preg_replace("/^\//",'',$path), S3::ACL_PUBLIC_READ);
		}
	}

	function publicUrl($path) {
		global $CONF;
		//todo, check exists?
		return $CONF['STATIC_HOST']."/".$path;
	}

	//use a static cache, to avoid even memcache calls within same request
	function metadata($path, $fallbacktolocal = true, $usememcache = true) {
		static $cache = array();
		if (!empty($this->s3) && empty($cache[$path])) {
			global $CONF, $memcache;

			$mkey = md5($path); //todo, add bucket?
			if ($usememcache)
			        $value = $memcache->name_get('s3',$mkey);

			if (!empty($value)) {
				$cache[$path] = json_decode($value,true);
			} else {
				// getObjectInfo($bucket, $uri, $returnInfo = true) {

				$cache[$path] = $this->s3->getObjectInfo($CONF['awsS3Bucket'], $path);
				if (is_object($cache[$path]))
					 $cache[$path] = get_object_vars( $cache[$path]);//array is just easier to work with

				$memcache->name_set('s3',$mkey, json_encode($cache[$path]), false, 86400*10);
			}
		} elseif ($fallbacktolocal && empty($cache[$path])) {
			$cache[$path] =  array('file' => $path,
					'size' => filesize($path),
                			'md5sum' => md5_file($path));
		}
		return $cache[$path];
	}

	function exists($path) {
		if (!empty($this->s3) && ($cache = $this->metadata($path)) ) {
			return !empty($cache['size']);
		}
		return file_exists($path);
	}
	function filesize($path) {
		if (!empty($this->s3) && ($cache = $this->metadata($path)) ) {
			$cache['size'];
		}
		return filesize($path);
	}
	function filemtime($path) {
		if (!empty($this->s3) && ($cache = $this->metadata($path)) ) {
                        $cache['time'];
                }
		return filemtime($path);
	}
}

#####################################################################
//database configuration

//adodb configuration
require_once('adodb/adodb.inc.php');
if (!empty($CONF['adodb_debugging']))
   require_once('adodb/adodb-errorhandler.inc.php');


//build DSN
$DSN = $CONF['db_driver'].'://'.
        $CONF['db_user'].':'.$CONF['db_pwd'].
        '@'.$CONF['db_connect'].
        '/'.$CONF['db_db'].$CONF['db_persist'];

//optional slave and read only database
if (isset($CONF['db_read_driver'])) {
        $DSN_READ = $CONF['db_read_driver'].'://'.
                $CONF['db_read_user'].':'.$CONF['db_read_pwd'].
                '@'.$CONF['db_read_connect'].
                '/'.$CONF['db_read_db'].$CONF['db_read_persist'];
} else {
        #$DSN_READ = $DSN;
}

if (empty($CONF['db_tempdb'])) {
       $CONF['db_tempdb']=$CONF['db_db'];
}

function GeographDatabaseConnection($allow_readonly = false) {
        global $ADODB_FETCH_MODE;
        global $CONF;

        //see if we can use a read only slave connection
        if ($allow_readonly && !empty($GLOBALS['DSN_READ']) && $GLOBALS['DSN'] != $GLOBALS['DSN_READ']) {

		$db=NewADOConnection($GLOBALS['DSN_READ']);
	        if ($db) {
			//if the application dictates it needs currency
			if ($allow_readonly > 1) {

				 $prev_fetch_mode = $ADODB_FETCH_MODE;
                	         $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        	                 $row = $db->getRow("SHOW SLAVE STATUS");
				 if (!empty($row)) { //its empty if we actully connected to master!

					 if (is_null($row['Seconds_Behind_Master']) || $row['Seconds_Behind_Master'] > $allow_readonly) {
                     	         	        $db2=NewADOConnection($GLOBALS['DSN']);
	                                        if ($db2) {
	                                                $db2->readonly = false;
        	                                        $ADODB_FETCH_MODE = $prev_fetch_mode;
                	                                return $db2;
                        	                }
					}
                                 }
				$ADODB_FETCH_MODE = $prev_fetch_mode;
			}
			$db->readonly = true;
                        return $db;
		} else {
			//try and fallback and get a master connection
                        $db=NewADOConnection($GLOBALS['DSN']);
		}

	} else {
		//otherwise just get a standard connection
		$db=NewADOConnection($GLOBALS['DSN'].(empty($CONF['db_persist'])?'?':'&')."new");
	}
	if (!$db) {
		header("HTTP/1.0 503 Service Unavailable");
		die("Database connection failed");
	}

        $db->readonly = false;
        return $db;
}



#####################################################################
// Sphinx/Manticore configuration

function GeographSphinxConnection($type='sphinxql',$new = false) {
	global $CONF;

	if ($type=='sphinxql' || $type=='mysql') {

		$sph = NewADOConnection("{$CONF['db_driver']}://{$CONF['sphinx_host']}:{$CONF['sphinx_portql']}/") or die("unable to connect to sphinx.");
		if ($type=='mysql') {
                        return $sph->_connectionID;
                }
                return $sph;
	}
}

#####################################################################
// Redis Host

#####################################################################
// memcache, may be implemented with redis (to avoid needing BOTH redis and memcache backends!

require_once('geograph/multiservermemcache.class.php');

$memcache = new MultiServerMemcache($CONF['memcache']['app']);

#####################################################################

//functions developed on geograph.org.uk

function htmlentities2( $myHTML,$quotes = ENT_COMPAT,$char_set = 'ISO-8859-1')
{
    return preg_replace( "/&amp;([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};|#x[0-9a-fA-F]{2,4};)/", '&$1' ,htmlentities($myHTML,$quotes,$char_set));
}

function latin1_to_utf8($input) {
        //our database has charactors encoded as entities (outside ISO-8859-1) - so need to decode entities.
        //and while we declare ISO-8859-1 as the html charset, we actully using windows-1252, as some browsers are sending us chars not valid in ISO-8859-1.
        //todo detect iconv not installed, and use utf8_encode as a fallback??
        //we dont utf8_encode if can help it, as it only supports ISO-8859-1, NOT windows-1252
        return html_entity_decode(
                iconv("windows-1252", "utf-8", $input),
                ENT_COMPAT, 'UTF-8');
}

