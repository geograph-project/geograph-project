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

//global routines
require_once('geograph/functions.inc.php');


if (isset($_GET['profile']) && !class_exists('Profiler',false)) {
	require "3rdparty/profiler.php";
	Profiler::enable();

	ProfilerRenderer::setIncludeJquery(true);
	ProfilerRenderer::setJqueryLocation('https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');

	ProfilerRenderer::setPrettifyLocation("/js/code-prettify");
	
	$p = Profiler::start("Global");
}

#################################################

//include domain specific configuration - if your install fails on
//this line, copy and adapt one of the existing configuration
//files in /libs/conf
require('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

@include('conf/revisions.conf.php');

#################################################

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

//optional second database
if (isset($CONF['db_driver2'])) {
	$DSN2 = $CONF['db_driver2'].'://'.
		$CONF['db_user2'].':'.$CONF['db_pwd2'].
		'@'.$CONF['db_connect2'].
		'/'.$CONF['db_db2'].$CONF['db_persist2'];
} else {
	$DSN2 = $DSN;
}

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

	//see if we can use a read only slave connection
	if ($allow_readonly && !empty($GLOBALS['DSN_READ']) && $GLOBALS['DSN'] != $GLOBALS['DSN_READ']) {
	
#		split_timer('db'); //starts the timer
		$db=NewADOConnection($GLOBALS['DSN_READ']);
#		split_timer('db','connect','readonly'); //logs the wall time
		
		if ($db) {
			//if the application dictates it needs currency
			if ($allow_readonly > 1) {
				$row = $db->getRow("SHOW SLAVE STATUS");
				if (!empty($row)) { //its empty if we actully connected to master!
				    if (false && (is_null($row['Seconds_Behind_Master']) || $row['Seconds_Behind_Master'] > 120) && ($row['Seconds_Behind_Master'] < 150) && function_exists('apc_store') && !apc_fetch('lag_warning')) {
				
				
					//email me if we lag, but once gets big no point continuing to notify!
					ob_start();
					print "\n\nHost: ".`hostname`."\n\n";
					print_r($row);
					debug_print_backtrace();
					$con = ob_get_clean();
               				mail('geograph@barryhunter.co.uk','[Geograph LAG] '.$row['Seconds_Behind_Master'],$con);
               				
               				
               				apc_store('lag_warning',1,500);
				    }
				    if (is_null($row['Seconds_Behind_Master']) || $row['Seconds_Behind_Master'] > $allow_readonly) {
					split_timer('db'); //starts the timer
					$db2=NewADOConnection($GLOBALS['DSN']);
					split_timer('db','connect','master-muchlag'); //logs the wall time
					if ($db2) {
						$db2->readonly = false;
						return $db2;
					}
				    }
				}
			}
		
			$db->readonly = true;
			return $db;
		} else {
			//try and fallback and get a master connection
			split_timer('db'); //starts the timer
			$db=NewADOConnection($GLOBALS['DSN']);
			split_timer('db','connect','master-fallback'); //logs the wall time
		}
	} else {
		//otherwise just get a standard connection
		
		//todo - we could add a 'curtail' feature here, to disable any page that needs write access - allowing some pages to still work without master online!
#		split_timer('db'); //starts the timer
		$db=NewADOConnection($GLOBALS['DSN']);
#		split_timer('db','connect','master'); //logs the wall time
	}
	if (!$db && mysql_error() == 'MySQL server has gone away') {
		//one last try! forcing a new connection via nconnect. 
		$db=NewADOConnection($GLOBALS['DSN'].(empty($CONF['db_persist'])?'?':'&')."new");
	}
	if (!$db) {
		split_timer('db','connect','failed'); //just to log the failure!
		//todo - show a 'smart' smarty error here... (probably check for existance of a global $smarty var) 
		header("HTTP/1.0 503 Service Unavailable");

					//email me if we lag, but once gets big no point continuing to notify!
					ob_start();
					print "\n\nHost: ".`hostname`."\n";
					print "Time: ".time()." (".(time()-$_SERVER['REQUEST_TIME'])." seconds)\n\n";
					print_r($_SERVER);
					debug_print_backtrace();
					$con = ob_get_clean();
               				mail('geograph@barryhunter.co.uk','[Geograph Database] Connection failed: '.mysql_error(),$con);
             		
		die("Database connection failed");
	}
	$db->readonly = false;
	return $db;
}

#################################################

if (!empty($CONF['memcache']['app'])) {
	
	if (!function_exists('memcache_pconnect')) {
		die(" Memcache module PECL extension not found!<br>\n");
		return;
	}

	$memcache = new MultiServerMemcache($CONF['memcache']['app']);

	if ($CONF['curtail_level'] > 0) {
		$level = $memcache->get('curtail_level');
		if ($level) {
			$CONF['real_curtail_level'] = $CONF['curtail_level'];
			$CONF['curtail_level'] = $level-1;
		}
	}
} else {
	//need lightweight fake object that does nothing!
	class fakeObject {
		function set($key, &$val, $flag = false, $expire = 0) {return false;}
		function get($key) {return false;}
		function delete($key, $timeout = 0) {return false;}
		function increment($key, $value = 1,$create = false) {return false;}
		function decrement($key, $value = 1,$create = false) {return false;}
		function name_set($namespace, $key, &$val, $flag = false, $expire = 0) {return false;}
		function name_get($namespace, $key) {return false;}
		function name_delete($namespace, $key, $timeout = 0) {return false;}
		function name_increment($namespace, $key, $value = 1,$create = false) {return false;}
		function name_decrement($namespace, $key, $value = 1,$create = false) {return false;}
	}
	
	$memcache = new fakeObject();
	$memcache->valid = false;
}

#################################################

//global security routines
require_once('geograph/security.inc.php');

#################################################

// a 'Hack' so that webarchive.org.uk can come crawling... (but lets do the same for 

$ip = getRemoteIP();
if ($ip == '128.86.236.164' || $ip == '194.66.232.85' || (strpos($_SERVER['HTTP_USER_AGENT'], 'ia_archiver')!==FALSE) || (strpos($_SERVER['HTTP_USER_AGENT'], 'heritrix')!==FALSE) ) {

	if ($CONF['curtail_level'] > 3) {
		  //heritrix doesn't understand 503 errors - so lets cause it to timeout.... (uses a socket timeout of 20000ms)
                        sleep(30);
		
		header("HTTP/1.1 503 Service Unavailable");

		die("server busy, please try later");
	}
	
	$CONF['template']='archive';
	
	$CONF['curtail_level'] = 0; //we dont want any messy proxy urls cached!
}


#################################################

if (!empty($CONF['memcache']['adodb'])) {
	if ($CONF['memcache']['adodb'] != $CONF['memcache']['app']) {
		$ADODB_MEMCACHE_OBJECT = new MultiServerMemcache($CONF['memcache']['adodb']);
	} elseif (isset($memcache)) {
		$ADODB_MEMCACHE_OBJECT =& $memcache;
	}
}

if (!empty($CONF['redis_host'])) {
	require "3rdparty/RedisSessions.php";
	
	redis_session_install();
	
} elseif (!empty($CONF['memcache']['sessions'])) {

	if ($CONF['memcache']['sessions'] != $CONF['memcache']['app']) {
		$memcachesession = new MultiServerMemcache($CONF['memcache']['sessions']);
	} elseif (isset($memcache)) {
		$memcachesession =& $memcache;
	}
	require('geograph/memcachesessions.inc.php');
	
	$memcachesession->period = ini_get("session.gc_maxlifetime");
} elseif (isset($CONF['db_driver2'])) {
	//adodb session configuration - we use second database if possible
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

#################################################


//smarty needed everywhere too
require_once('smarty/libs/Smarty.class.php');

//and our user class
require_once('geograph/user.class.php');

#################################################

//function to replace having to have loads of require_once's
// PHP5 ONLY
function __autoload($class_name) {

split_timer('autoload'); //starts the timer

        if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/../libs/geograph/'.strtolower($class_name).'.class.php')) {
                ob_start();
                debug_print_backtrace();
		print "\n\nHost: ".`hostname`."\n\n";
                print_r($GLOBALS);
		print_r(get_included_files());
                $con = ob_get_clean();
                mail('geograph@barryhunter.co.uk','[Geograph Error] '.date('r'),$con);
		header("HTTP/1.1 505 Server Error");
                die("Fatal Internal Error, the developers have been notified, if possible please <a href='mailto:geograph@barryhunter.co.uk?subject=$class_name'>let us know</a> what you where doing that lead up to this error");
        }

	require_once('geograph/'.strtolower($class_name).'.class.php');
	
split_timer('autoload','include',$class_name); //logs the wall time

}

#################################################

//remember start time of script for logging
if (isset($CONF['log_script_timing']))
{
	list($usec, $sec) = explode(' ',microtime());
	$GLOBALS['STARTTIME'] = ((float)$usec + (float)$sec);
	register_shutdown_function('log_script_timing');
}

#################################################

//global page initialisation
function init_session()
{

	split_timer('app'); //starts the timer

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
	if (isset($_SESSION['user']->about_yourself))
		unset($_SESSION['user']->about_yourself);

	//put user object into global scope
	$GLOBALS['USER'] =& $_SESSION['user'];

	//tell apache our ID, handy for logs
	@apache_note('user_id', $GLOBALS['USER']->user_id);
	
	//HACK for CDN - under heavy traffic this could be uncommented (or enabled via curtail_level) to shift of non logged in traffic to cdn. 
	// could for example only enable for a % of traffic, or based on IP etc etc
	/*
	if (	empty($GLOBALS['USER']->user_id) 
		&& empty($_COOKIE[session_name()])
		&& $_SERVER['HTTP_HOST'] == 'www.geograph.virtual'
		&& empty($_POST['password'])
		&& (stripos($_SERVER['HTTP_USER_AGENT'], 'http')===FALSE)
		&& (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')===FALSE)
	   ) {
		header("HTTP/1.0 307 Temporary Redirect");
		header("Status: 307 Temporary Redirect");
		header("Location: http://www2.geograph.org.uk{$_SERVER['REQUEST_URI']}");
		print "<a href=\"http://www2.geograph.org.uk{$_SERVER['REQUEST_URI']}\">Click to continue</a>";
		exit;
	}
	*/
	
	split_timer('app','init_session',$GLOBALS['USER']->user_id); //logs the wall time

}

#################################################

function smarty_function_pageheader() {
	if(extension_loaded('newrelic')) {
		return newrelic_get_browser_timing_header();
	}
}
function smarty_function_pagefooter() {

	if (isset($_GET['profile']) && class_exists('Profiler',false)) {
		ob_start();
		Profiler::render();
		return ob_get_clean();
	}
	
#return "<style>body {font-family: Helvetica,Arial,sans-serif !important; }</style>";

	if(extension_loaded('newrelic')) {
		return newrelic_get_browser_timing_footer();
	}
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

	split_timer('smarty'); //starts the timer


		//base constructor
		$this->Smarty();

		//set up paths
		$this->template_dir=$_SERVER['DOCUMENT_ROOT'].'/templates/'.$CONF['template'];
		$this->compile_dir=$this->template_dir."/compiled";
		$this->config_dir=$this->template_dir."/configs";
		$this->cache_dir=$this->template_dir."/cache";
		
		if (!empty($CONF['memcache']['smarty'])) {
			$this->compile_dir=$this->template_dir."/compiled-mnt"; ##this seems to fix a bug
		
			global $memcached_res,$memcache;
			if ($CONF['memcache']['smarty'] != $CONF['memcache']['app']) {
				$GLOBALS['memcached_res'] = new MultiServerMemcache($CONF['memcache']['smarty']);
			} elseif (isset($memcache)) {
				$GLOBALS['memcached_res'] =& $memcache;
			}
			
			require_once('3rdparty/memcache_cache_handler.inc.php');
			$this->cache_handler_func = 'memcache_cache_handler';
		} else {
			//subdirs more efficient
			$this->use_sub_dirs=true;
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
		if (!($this->caching = $CONF['smarty_caching'])) {
			//TODO
			//$this->disable_caching = true;
		}

		//register our "dynamic" handler for non-cached sections of templates
		$this->register_block('dynamic', 'smarty_block_dynamic', false,array('cached_user_id'));

		//handy function for linking to getamap
		$this->register_function("getamap", "smarty_function_getamap");

		//external site linker...
		$this->register_function("external", "smarty_function_external");

		//new window linker...
		$this->register_function("newwin", "smarty_function_newwin");

		//gridimage
		$this->register_function("gridimage", "smarty_function_gridimage");

		//gazetteer line
		$this->register_function("place", "smarty_function_place");

		$this->register_function("pageheader", "smarty_function_pageheader");
		$this->register_function("pagefooter", "smarty_function_pagefooter");

		//linktoself
		$this->register_function("linktoself", "smarty_function_linktoself");

		$this->register_modifier("revision", "smarty_modifier_revision");
		$this->register_modifier("geographlinks", "smarty_function_geographlinks");
		$this->register_modifier("ordinal", "smarty_function_ordinal");

		$this->register_modifier("thousends", "smarty_function_thousends");

		
		
		//assign globallly useful stuff
		$this->assign_by_ref('user', $GLOBALS['USER']);
		$this->assign_by_ref('http_host', $_SERVER['HTTP_HOST']);
		$this->assign_by_ref('static_host', $CONF['STATIC_HOST']);
		$this->assign_by_ref('script_name', $_SERVER['PHP_SELF']);
		$this->assign_by_ref('script_uri', $_SERVER['REQUEST_URI']);
		$this->assign_by_ref('searchq', $_SESSION['searchq']);
		$this->assign_by_ref('enable_forums', $CONF['forums']);
		


		$this->assign('session_id', session_id());



		//show more links in template?
		if (isset($GLOBALS['USER']) && $GLOBALS['USER']->user_id > 0) {
			if (function_exists('apc_fetch')) {
				if (apc_fetch($_SERVER['REQUEST_URI']))  {
					$this->caching = 0;
					$this->disable_caching = 1; //just incase app later changes it. 

					$this->assign('extra_meta', '<script src="http://asset.userfly.com/users/14743/userfly.js" type="text/javascript"></script>');
					
					apc_delete($_SERVER['REQUEST_URI']);
				}
/*
				if (($value = apc_fetch('irc.seen')) === FALSE) {
					if (@filemtime($_SERVER['DOCUMENT_ROOT'].'/rss/irc.seen') > time() - 60) {		
						$value = @file_get_contents($_SERVER['DOCUMENT_ROOT'].'/rss/irc.seen');
					} else {
						$value = '?';
					}
					apc_store('irc.seen',$value,30);
				}  
			} else {
				if (@filemtime($_SERVER['DOCUMENT_ROOT'].'/rss/irc.seen') > time() - 60) {		
					$value = @file_get_contents($_SERVER['DOCUMENT_ROOT'].'/rss/irc.seen');
				} else {
					$value = '?';
				}
			}
			$this->assign('irc_seen',$value);
*/
			}


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

	split_timer('smarty','setup'); //logs the wall time

	}

	function is_cached($template, $cache_id = null, $compile_id = null)
	{
		global $USER,$CONF;
		
	split_timer('smarty'); //starts the timer

		if (!empty($this->disable_caching)) {
			$this->caching = 0;
		}
		$filename = str_replace("|","___","{$this->cache_dir}/lock_$template-$cache_id.tmp");
		if (isset($_GET['refresh']) && $USER->hasPerm('admin')) {
			$this->clear_cache($template, $cache_id, $compile_id);
		} elseif (!empty($CONF['memcache']['smarty'])) {
			if ($GLOBALS['memcached_res']->get($filename)) {
				//its recent so lets extend caching to use the current file (IF there is one!)
				$this->cache_lifetime = $this->cache_lifetime+(3600*2); //+2hr
			} 
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
			if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 6 && strpos($_SERVER['PHP_SELF'],'statistics/') !== FALSE ) {
				header("HTTP/1.1 503 Service Unavailable");
				die("server busy, please try later");
			}
		
			if (!empty($CONF['memcache']['smarty'])) {
				$GLOBALS['memcached_res']->set($filename, $template, false, 60*5);
			} else {
				//we don't have a cache so lets write the lock file
				$h = fopen($filename, "w");
				fwrite($h,".");
				fclose($h);
			}
			$this->wroteLock = $filename;
		}

	split_timer('smarty','is_cached',$template.'.'.$cache_id); //logs the wall time

		return $isCached;
	}

	function display($template, $cache_id = null, $compile_id = null)
	{
		global $CONF;
		
	split_timer('smarty'); //starts the timer
	
		if (!empty($this->disable_caching)) {
			$this->caching = 0;
		}
		parent::assign("smarty_template",$template);
		$ret = parent::display($template, $cache_id, $compile_id);

		//we finished so remove the lock file
		if (!empty($this->wroteLock)) {
			if (!empty($CONF['memcache']['smarty'])) {
				$GLOBALS['memcached_res']->delete($this->wroteLock);
			} else {
				unlink($this->wroteLock);
			}
		}
		
	split_timer('smarty','display',$template.'.'.$cache_id); //logs the wall time
	
		return $ret;
	}

	function templateExists($file)
	{
		$basic=$_SERVER['DOCUMENT_ROOT'].'/templates/basic/'.$file;
		return file_exists($this->template_dir.'/'.$file) || file_exists($basic);
	}

	function templateDate($file)
	{
		if (file_exists($this->template_dir.'/'.$file)) {
			return filemtime($this->template_dir.'/'.$file);
		} else {
			return filemtime($_SERVER['DOCUMENT_ROOT'].'/templates/basic/'.$file);
		}
	}

	function reassignPostedDate($which)
	{
		$_POST[$which] = sprintf("%04d-%02d-%02d",$_POST[$which.'Year'],$_POST[$which.'Month'],$_POST[$which.'Day']);
		$this->assign($which, $_POST[$which]);
	}
	
	/**
	 * default template resource loader, used by this class for new templates, it
	 * will load any missing tpl from the basic template folder
	 */
	static function basicTemplateLoader($resource_type, $resource_name, &$template_source, &$template_timestamp,&$smarty_obj)
	{
		split_timer('smarty'); //starts the timer

		if($resource_type == 'file')
		{
			$basic=$_SERVER['DOCUMENT_ROOT'].'/templates/basic/'.$resource_name;
			if (is_readable($basic))
			{
				 $template_source=file_get_contents($basic);
				 $template_timestamp=filemtime($basic);
				 
				 split_timer('smarty','loader',$resource_name); //logs the wall time
				 
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

#################################################

//this is a bit cheeky - if the xhtml validator calls, turn off the automatic
//session id insertion, as it uses & instead of &amp; in urls
//we also turn it off for bots, as session ids can bugger it up
if ( (strpos($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator')!==FALSE) || $CONF['template']=='archive' ||
    (strpos($_SERVER['HTTP_USER_AGENT'], 'bot')>0) )
{
	ini_set ('url_rewriter.tags', '');
}


