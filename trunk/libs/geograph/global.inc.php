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
require_once('conf/'.$_SERVER['HTTP_HOST'].'.conf.php');

//adodb configuration
require_once('adodb/adodb.inc.php');
if ($CONF['adodb_debugging'])
   require_once('adodb/adodb-errorhandler.inc.php');

//build DSN
$DSN = $CONF['db_driver'].'://'.
	$CONF['db_user'].':'.$CONF['db_pwd'].
	'@'.$CONF['db_connect'].
	'/'.$CONF['db_db'].'?persist';


//adodb session configuration - we use same database
$ADODB_SESSION_DRIVER=$CONF['db_driver'];
$ADODB_SESSION_CONNECT=$CONF['db_connect'];
$ADODB_SESSION_USER =$CONF['db_user'];
$ADODB_SESSION_PWD =$CONF['db_pwd'];
$ADODB_SESSION_DB =$CONF['db_db'];
require_once('adodb/session/adodb-session.php');

//global security routines
require_once('geograph/security.inc.php');

//smarty needed everywhere too
require_once('smarty/libs/Smarty.class.php');

//and our user class
require_once('geograph/user.class.php');



/**
* Smarty block handler 
* Although it doesn't appear to do much, this is registered as a
* non-caching block handler - anything between {dynamic}{/dynamic} will
* not be cached
*/
function smarty_block_dynamic($param, $content, &$smarty) 
{
    return $content;
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
		$this->cache_dir=$this->template_dir."/cache";

		//setup optimisations
		$this->compile_check = $CONF['smarty_compile_check'];
		$this->debugging = $CONF['smarty_debugging'];
		$this->caching = $CONF['smarty_caching'];
		
		//register our "dynamic" handler for non-cached sections of templates
		$this->register_block('dynamic', 'smarty_block_dynamic', false);


		//assign globallly useful stuff
		$this->assign_by_ref('user', $GLOBALS['USER']);
		$this->assign_by_ref('http_host', $_SERVER['HTTP_HOST']);
		$this->assign_by_ref('script_name', $_SERVER['PHP_SELF']);
		$this->assign_by_ref('script_uri', $_SERVER['REQUEST_URI']);
		$this->assign_by_ref('searchq', $_SESSION['searchq']);
		
		
		
		$this->assign('session_id', session_id());
		
		
		
		//show more links in template?
		if ($GLOBALS['USER']->hasPerm('admin'))
		{
			$this->assign('is_admin', true);
		
		
		}
		
	}
	
	function templateExists($file)
	{
		return file_exists($this->template_dir.'/'.$file);
	}
}

//global page initialisation
function init_session()
{
	session_start();
	
	//do we have a user object?
	if (!isset($_SESSION['user']))
	{
		//create new user object - initially anonymous
		$_SESSION['user'] =& new GeographUser;
		
		//give object a chance to auto-login via cookie
		$_SESSION['user']->auto_login();
	}
	
	//put user object into global scope
	$GLOBALS['USER'] =& $_SESSION['user'];
}


?>
