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

$ADODB_CACHE_DIR =& $CONF['adodb_cache_dir'];



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
* Smarty Getamap linker
* 
* Makes linking to OS maps easy {getamap gridref='TL0000' text='get a map'}
*/
function smarty_function_getamap($params)
{
  	
  	$icon="<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - opens in popup window\" src=\"/img/external.png\" width=\"10\" height=\"10\"/>";
  	
  	//get params
  	$gridref4=$params['gridref'];
  	if (preg_match('/^document\./i', $gridref4))
  	{
		return "<a title=\"Ordnance Survey Get-a-Map\" href=\"javascript:popupOSMap($gridref4)\">{$params['text']}</a>$icon";
  	}
  	else if (preg_match('/([A-Z]{1,2})(\d\d)(\d\d)/i', $gridref4))
  	{
		if (!empty($params['text']))
			$text=$params['text'];
		else
			$text=$gridref4;

		//we take a 4 figure reference, need to turn this into a 
		//6 figure one centred on the desired square
		$gridref6=preg_replace('/([A-Z]{1,2})(\d\d)(\d\d)/i', 
			'${1}${2}5${3}5', $gridref4);

		return "<a title=\"Ordnance Survey Get-a-Map for $gridref4\" href=\"javascript:popupOSMap('$gridref6')\">$text</a>$icon";
  	}
  	else if (empty($gridref4)) 
  	{
  		if (!empty($params['text']))
			$text=$params['text'];
		else
			$text='OS Get-A-Map';
  		return "<a title=\"Ordnance Survey Get-a-Map\" href=\"javascript:popupOSMap('')\">$text</a>$icon";
  	} 
  	else
  	{
  		//error
  		return $gridref4;
  	}
}


/**
* Smarty external site linker
* 
* Provides centralised formatting of external links
* href, title and text are the params here...
*/
function smarty_function_external($params)
{
  	//get params and use intelligent defaults...
  	$href=str_replace(' ','+',$params['href']);
  	
  	if (isset($params['text']))
  		$text=$params['text'];
  	else
  		$text=$href;
  	
  	if (isset($params['title']))
		$title=$params['title'];
	else
		$title=$text;
  	
  	if ($params['target'] == '_blank') {
  		return "<span class=\"nowrap\"><a title=\"$title\" href=\"$href\" target=\"_blank\">$text</a>".
  			"<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - opens in a new window\" src=\"http://{$_SERVER['HTTP_HOST']}/img/external.png\" width=\"10\" height=\"10\"/></span>";
  	} else {
  		return "<span class=\"nowrap\"><a title=\"$title\" href=\"$href\">$text</a>".
  			"<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - shift click to open in new window\" src=\"http://{$_SERVER['HTTP_HOST']}/img/external.png\" width=\"10\" height=\"10\"/></span>";
  	}  	
}

/**
* Smarty gridimage thumbnail link
* 
* given image id makes a nice thumbnail link
*/
function smarty_function_gridimage($params)
{
	require_once("geograph/gridsquare.class.php");
	require_once("geograph/gridimage.class.php");
	$image=new GridImage;
	$image->loadFromId($params['id']);
	
	$html='<div style="float:left;" class="photo33">';
	$html.='<a title="view full size image" href="/photo/'.$image->gridimage_id.'">';
	$html.=$image->getThumbnail(213,160);
	$html.='</a><div class="caption"><a title="view full size image" href="/photo/'.$image->gridimage_id.'">';
	$html.=htmlentities($image->title).'</a>';
	
	if (isset($params['extra']))
		$html.='<div>'.htmlentities($params['extra']).'</div>';
	
	$html.='</div></div>';
	
	return $html;
		  
}

/**
* adds commas to thousendise a number
*/
function smarty_function_thousends($input) {
	return number_format($input);
}

/**
* wrapper to GeographLinks
*/
function smarty_function_geographlinks($input) {
	return GeographLinks($input);
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
		
		//handy function for linking to getamap
		$this->register_function("getamap", "smarty_function_getamap");
		
		//external site linker...
		$this->register_function("external", "smarty_function_external");

		//gridimage
		$this->register_function("gridimage", "smarty_function_gridimage");


		$this->register_modifier("geographlinks", "smarty_function_geographlinks");

		$this->register_modifier("thousends", "smarty_function_thousends");


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
	
	function reassignPostedDate($which)
	{
		//single digit month to get round a (bug?) inconsistencny in smarty (its a one line fix to get to work as expected)
		$this->assign($which, sprintf("%04d-%d-%02d",$_POST[$which.'Year'],$_POST[$which.'Month'],$_POST[$which.'Day']));
	}
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
}

//replace geograph links
function GeographLinks(&$posterText) {
	if (preg_match_all('/\[\[(\[?)(\w{0,2}\d+)(\]?)\]\]/',$posterText,$g_matches)) {
		foreach ($g_matches[2] as $i => $g_id) {
			if (is_numeric($g_id)) {
				if (!isset($g_image)) {
					require_once('geograph/gridimage.class.php');
					require_once('geograph/gridsquare.class.php');
					$g_image=new GridImage;
				}
				$ok = $g_image->loadFromId($g_id);
				if ($ok) {
					if ($g_matches[1][$i]) {
						//we don't place thumbnails in non forum links
						$posterText = str_replace("[[[$g_id]]]","{<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\">{$g_image->grid_reference} : {$g_image->title}</a>}",$posterText);
					} else {
						$posterText = str_replace("[[$g_id]]","{<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\">{$g_image->grid_reference} : {$g_image->title}</a>}",$posterText);
					}
				}			
			} else {
				$posterText = str_replace("[[$g_id]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/gridref/$g_id\">$g_id</a>",$posterText);
			}
		}
	}
	if (preg_match_all('/(^| |<br\/?>)(http:\/\/[\w\.]+\.[\w]{2,}\/[^ <]*)( |<br\/?>|$)/',$posterText,$g_matches)) {
		foreach ($g_matches[2] as $i => $g_url) {
			
			$posterText = str_replace("$g_url",smarty_function_external(array('href'=>$g_url,'text'=>'{link}','title'=>$g_url)),$posterText);
		}
	}
	return $posterText;
}


//this is a bit cheeky - if the xhtml validator calls, turn off the automatic
//session id insertion, as it uses & instead of &amp; in urls
//we also turn it off for bots, as session ids can bugger it up
if (($_SERVER['HTTP_USER_AGENT']=='W3C_Validator/1.305.2.148 libwww-perl/5.803')||
    (strpos($_SERVER['HTTP_USER_AGENT'], 'bot')>0))
{
	ini_set ('url_rewriter.tags', '');
}


?>
