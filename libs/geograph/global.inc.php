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
}

#####################################################################
//database configuration


#####################################################################
// Sphinx/Manticore configuration

function GeographSphinxConnection($type='sphinxql',$new = false) {
	global $CONF;
	return mysql_connect("{$CONF['sphinx_host']}:{$CONF['sphinx_portql']}", '', '', true) or die(mysql_error());
}

#####################################################################
// Redis Host

#####################################################################
// memcache, may be implemented with redis (to avoid needing BOTH redis and memcache backends!

#####################################################################
