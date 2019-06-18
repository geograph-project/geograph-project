<?

require "conf/example.conf";

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

	}

}

#####################################################################
// folder config

class FileSystem {
	function exists($path) {
		return file_exists($path);
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
