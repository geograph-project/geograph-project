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
        function GeographPage()
        {
                global $CONF;

               //base constructor
                $this->Smarty();

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
	function publicUrl($path) {
		global $CONF;
		//todo, check exists?
		return $CONF['STATIC_HOST']."/".$path;
	}

	//this is a toy implemenation, that only works if a local POSIX fileystem.
	// in reality it may need to implement via S3 API or whatever!
	function exists($path) {
		return file_exists($path);
	}
	function filesize($path) {
		return filesize($path);
	}
	function filemtime($path) {
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
