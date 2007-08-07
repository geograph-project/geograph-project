<?php
/**
 * $Project: GeoGraph $
 * $Id$
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
* This file is included into every requested script
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/


//include domain specific configuration - if your install fails on
//this line, copy and adapt one of the existing configuration
//files in /libs/conf
require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

//adodb configuration
require_once('adodb/adodb.inc.php');
if ($CONF['adodb_debugging'])
   require_once('adodb/adodb-errorhandler.inc.php');

$ADODB_CACHE_DIR =& $CONF['adodb_cache_dir'];



//build DSN
$DSN = $CONF['db_driver'].'://'.
	$CONF['db_user'].':'.$CONF['db_pwd'].
	'@'.$CONF['db_connect'].
	'/'.$CONF['db_db'].$CONF['db_persist'];

if (isset($CONF['db_driver2'])) {
	$DSN2 = $CONF['db_driver2'].'://'.
		$CONF['db_user2'].':'.$CONF['db_pwd2'].
		'@'.$CONF['db_connect2'].
		'/'.$CONF['db_db2'].$CONF['db_persist2'];
} else {
	$DSN2 = $DSN;
}

if (!empty($CONF['memcache']['app'])) {
	
	if (!function_exists('memcache_pconnect')) {
		die(" Memcache module PECL extension not found!<br>\n");
		return;
	}

	$memcache = new MultiServerMemcache($CONF['memcache']['app']);
} else {
	//need lightweight fake object that does nothing!
	class fakeObject {
		function set($key, &$val, $flag = false, $expire = 0) {return false;}
		function get($key) {return false;}
		function delete($namespace, $key, $timeout = 0) {return false;}
		function name_set($namespace, $key, &$val, $flag = false, $expire = 0) {return false;}
		function name_get($namespace, $key) {return false;}
		function name_delete($namespace, $key, $timeout = 0) {return false;}
	}
	
	$memcache = new fakeObject();
	$memcache->valid = false;
}

//global security routines
require_once('geograph/security.inc.php');


if (!empty($CONF['memcache']['adodb'])) {
	if ($CONF['memcache']['adodb'] != $CONF['memcache']['app']) {
		$ADODB_MEMCACHE_OBJECT = new MultiServerMemcache($CONF['memcache']['adodb']);
	} elseif (isset($memcache)) {
		$ADODB_MEMCACHE_OBJECT =& $memcache;
	}
}


if (!empty($CONF['memcache']['sessions'])) {

	if ($CONF['memcache']['sessions'] != $CONF['memcache']['app']) {
		$memcachesession = new MultiServerMemcache($CONF['memcache']['sessions']);
	} elseif (isset($memcache)) {
		$memcachesession =& $memcache;
	}
	require('geograph/memcachesessions.inc.php');
	
	$memcachesession->period = ini_get("session.gc_maxlifetime");
} elseif (isset($CONF['db_driver2'])) {
	//adodb session configuration - we use same database
	$ADODB_SESSION_DRIVER=$CONF['db_driver2'];
	$ADODB_SESSION_CONNECT=$CONF['db_connect2'];
	$ADODB_SESSION_USER =$CONF['db_user2'];
	$ADODB_SESSION_PWD =$CONF['db_pwd2'];
	$ADODB_SESSION_DB =$CONF['db_db2'];
	require_once('adodb/session/adodb-session.php');
	if (empty($CONF['db_persist2'])) 
		adodb_sess_open(false,false,false);
} else {
	//adodb session configuration - we use same database
	$ADODB_SESSION_DRIVER=$CONF['db_driver'];
	$ADODB_SESSION_CONNECT=$CONF['db_connect'];
	$ADODB_SESSION_USER =$CONF['db_user'];
	$ADODB_SESSION_PWD =$CONF['db_pwd'];
	$ADODB_SESSION_DB =$CONF['db_db'];
	require_once('adodb/session/adodb-session.php');
	if (empty($CONF['db_persist'])) 
		adodb_sess_open(false,false,false);
}



//global routines
require_once('geograph/functions.inc.php');

//smarty needed everywhere too
require_once('smarty/libs/Smarty.class.php');

//and our user class
require_once('geograph/user.class.php');


//function to replace having to have loads of require_once's
// PHP5 ONLY
function __autoload($class_name) {
	require_once('geograph/'.strtolower($class_name).'.class.php');
}

//remember start time of script for logging
if (isset($CONF['log_script_timing']))
{
	list($usec, $sec) = explode(' ',microtime());
	$GLOBALS['STARTTIME'] = ((float)$usec + (float)$sec);
	register_shutdown_function('log_script_timing');
}


//global page initialisation
function init_session()
{
	session_start();

	//do we have a user object?
	if (!isset($_SESSION['user']))
	{
		//this is a new session - as a safeguard against session
		//fixation, we regenerate the session id
		//not sure if wanted: if ($_REQUEST['PHPSESSID'])
			session_regenerate_id();

		//create new user object - initially anonymous
		$_SESSION['user'] =& new GeographUser;

		//give object a chance to auto-login via cookie
		$_SESSION['user']->autoLogin();
	}

	//put user object into global scope
	$GLOBALS['USER'] =& $_SESSION['user'];

	//tell apache our ID, handy for logs
	@apache_note('user_id', $GLOBALS['USER']->user_id);
}



/**
* Smarty derivation for Geograph
*
* This is a subclass of smarty which does all the setting up
* common to geograph templates
*
* @package Geograph
*/
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
		$this->config_dir=$this->template_dir."/configs";

		//subdirs more efficient
		$this->use_sub_dirs=true;

		if (!empty($CONF['memcache']['smarty'])) {
			if ($CONF['memcache']['smarty'] != $CONF['memcache']['app']) {
				$memcached_res = new MultiServerMemcache($CONF['memcache']['smarty']);
			} elseif (isset($memcache)) {
				$memcached_res =& $memcache;
			}
			
			require('geograph/memcache_cache_handler.inc.php');
			$this->cache_handler_func = 'memcache_cache_handler';
		} else {
			$this->cache_dir=$this->template_dir."/cache";
		}

		//if we're not using the basic template,install this default template
		//loader which aids template development by loading missing tpl files
		//from basic
		// set the default handler
		if ($CONF['template']!='basic')
			$this->default_template_handler_func = array('GeographPage', 'basicTemplateLoader');
		

		//setup optimisations
		$this->compile_check = $CONF['smarty_compile_check'];
		$this->debugging = $CONF['smarty_debugging'];
		$this->caching = $CONF['smarty_caching'];

		//register our "dynamic" handler for non-cached sections of templates
		$this->register_block('dynamic', 'smarty_block_dynamic', false);

		//handy function for linking to getamap
		$this->register_function("getamap", "smarty_function_getamap");

		//external site linker...
		$this->register_function("external", "smarty_function_external");

		//gridimage
		$this->register_function("gridimage", "smarty_function_gridimage");

		//gridimage
		$this->register_function("place", "smarty_function_place");


		$this->register_modifier("geographlinks", "smarty_function_geographlinks");
		$this->register_modifier("ordinal", "smarty_function_ordinal");

		$this->register_modifier("thousends", "smarty_function_thousends");


		//assign globallly useful stuff
		$this->assign_by_ref('user', $GLOBALS['USER']);
		$this->assign_by_ref('http_host', $_SERVER['HTTP_HOST']);
		$this->assign_by_ref('script_name', $_SERVER['PHP_SELF']);
		$this->assign_by_ref('script_uri', $_SERVER['REQUEST_URI']);
		$this->assign_by_ref('searchq', $_SESSION['searchq']);
		$this->assign_by_ref('javascript_version', $CONF['javascript_version']);
		$this->assign_by_ref('enable_forums', $CONF['forums']);
		


		$this->assign('session_id', session_id());



		//show more links in template?
		if (isset($GLOBALS['USER'])) {
			if ($GLOBALS['USER']->hasPerm('admin'))
			{
				$this->assign('is_admin', true);
			}
			if ($GLOBALS['USER']->hasPerm('moderator'))
			{
				$this->assign('is_mod', true);
			}
			if ($GLOBALS['USER']->hasPerm('ticketmod'))
			{
				$this->assign('is_tickmod', true);
			}
		}

	}

	function is_cached($template, $cache_id = null, $compile_id = null)
	{
		global $USER;
		$filename = str_replace("|","___","{$this->cache_dir}/lock_$template-$cache_id.tmp");
		if (isset($_GET['refresh']) && $USER->hasPerm('admin')) {
			$this->clear_cache($template, $cache_id, $compile_id);
		} else {
			//check if there is a generation already in progress
			if (file_exists($filename)) {
				//we have a lock file
				if (filemtime($filename) > (time() - 60*5)) {
					//its recent so lets extend caching to use the current file (IF there is one!)
					$this->cache_lifetime = $this->cache_lifetime+(3600*2); //+2hr
				} else {
					//the lock is now old, so lets remove it and try again
					unlink($filename);
				}
			}
		}

		$isCached = parent::is_cached($template, $cache_id, $compile_id);
		if (!$isCached) {
			//we don't have a cache so lets write the lock file
			$h = fopen($filename, "w");
			fwrite($h,".");
			fclose($h);
			$this->wroteLock = $filename;
		}
		return $isCached;
	}

	function display($template, $cache_id = null, $compile_id = null)
	{
		$ret = parent::display($template, $cache_id, $compile_id);

		//we finished so remove the lock file
		if (!empty($this->wroteLock))
			unlink($this->wroteLock);

		return $ret;
	}

	function templateExists($file)
	{
		$basic=$_SERVER['DOCUMENT_ROOT'].'/templates/basic/'.$file;
		return file_exists($this->template_dir.'/'.$file) || file_exists($basic);
	}

	function reassignPostedDate($which)
	{
		$this->assign($which, sprintf("%04d-%02d-%02d",$_POST[$which.'Year'],$_POST[$which.'Month'],$_POST[$which.'Day']));
	}
	
	/**
	 * default template resource loader, used by this class for new templates, it
	 * will load any missing tpl from the basic template folder
	 */
	static function basicTemplateLoader($resource_type, $resource_name, &$template_source, &$template_timestamp,&$smarty_obj)
	{
		if($resource_type == 'file')
		{
			$basic=$_SERVER['DOCUMENT_ROOT'].'/templates/basic/'.$resource_name;
			if (is_readable($basic))
			{
				 $template_source=file_get_contents($basic);
				 $template_timestamp=filemtime($basic);
				 return true;
			}
			else
			{
				//no such template
				return false;
			}
		} 
		else
		{
			// not a file
			return false;
		}
	}

}



//this is a bit cheeky - if the xhtml validator calls, turn off the automatic
//session id insertion, as it uses & instead of &amp; in urls
//we also turn it off for bots, as session ids can bugger it up
if ( (strpos($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator')!==FALSE) ||
    (strpos($_SERVER['HTTP_USER_AGENT'], 'bot')>0) )
{
	ini_set ('url_rewriter.tags', '');
}


?>
