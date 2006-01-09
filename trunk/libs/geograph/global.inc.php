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


//remember start time of script for logging
if (isset($CONF['log_script_timing']))
{
	list($usec, $sec) = explode(' ',microtime());
	$GLOBALS['STARTTIME'] = ((float)$usec + (float)$sec);
	register_shutdown_function('log_script_timing');
}

/**
* Logs execution time of script
* if $CONF['log_script_timing'] isn't set, nothing happens
* if $CONF['log_script_timing'] == 'file' timings are logged in the logs folder
* if $CONF['log_script_timing'] == 'apache' timings are logged via apache
*/
function log_script_timing()
{
	global $STARTTIME,$USER,$CONF;
	
	list($usec, $sec) = explode(' ',microtime());
	$endtime = ((float)$usec + (float)$sec);
	$timetaken = sprintf('%0.4f', $endtime - $STARTTIME);
			
	if ($CONF['log_script_timing']=='file')
	{
		//%03.4f doesn't seem to work so we must add our own padding
		//this makes the output file easily sortable
		if ($timetaken<100)
			$timetaken='0'.$timetaken;
		if ($timetaken<10)
			$timetaken='0'.$timetaken;
		
		$logfile="/home/barry/sitelogs/".date("Ymd-H").".log";
		$h = @fopen($logfile,'a');
		if ($h)
		{
			$time = date("i:s");
			$logline = "$timetaken,$time,{$_SERVER['SCRIPT_URL']},{$_SERVER['REQUEST_METHOD']},\"{$_SERVER['QUERY_STRING']}\",{$_SERVER['REMOTE_ADDR']},{$USER->user_id},\"{$_SERVER['HTTP_REFERER']}\"\n";
			
			fwrite($h,$logline);
			
			fclose($h);   
		}
	}
	elseif($CONF['log_script_timing']=='apache')
	{
		@apache_note('php_timing', $timetaken);
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
  	$matches=array();
  	$gridref4=preg_replace('/^([A-Z]{1,2})\s*(\d{2,5})\s*(\d{2,5})$/i','$1$2$3',$params['gridref']);
  	if (preg_match('/^document\./i', $gridref4))
  	{
			return "<a title=\"Ordnance Survey Get-a-Map\" href=\"javascript:popupOSMap($gridref4)\">{$params['text']}</a>$icon";
  	}
  	else if (preg_match('/^([A-Z]{1,2})(\d{4,10})$/i', $gridref4, $matches))
  	{
			if (!empty($params['text']))
				$text=$params['text'];
			else
				$text=$params['gridref'];
			
			$gridref6="";
			$coords=$matches[2];
			$l=strlen($coords);
			switch ($l)
			{
				case 4: $gridref6=$matches[1].substr($coords,0,2)."5".substr($coords,2,2)."5"; break;
				default: $gridref6=$gridref4;
			}
			
			return "<a title=\"Ordnance Survey Get-a-Map for $gridref4\" href=\"http://getamap.ordnancesurvey.co.uk/getamap/frames.htm?mapAction=gaz&gazName=g&gazString=$gridref6\" onclick=\"popupOSMap('$gridref6'); return false;\">$text</a>$icon";
  	}
  	else if (empty($gridref4)) 
  	{
  		if (!empty($params['text']))
				$text=$params['text'];
			else
				$text='OS Get-A-Map';
  		return "<a title=\"Ordnance Survey Get-a-Map\" href=\"http://getamap.ordnancesurvey.co.uk/getamap/frames.htm\" onclick=\"popupOSMap(''); return false;\">$text</a>$icon";
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
	
	$html='<div class="photoguide">';
	
	$html.='<div style="float:left;width:213px">';
		$html.='<a title="view full size image" href="/photo/'.$image->gridimage_id.'">';
		$html.=$image->getThumbnail(213,160);
		$html.='</a><div class="caption"><a title="view full size image" href="/photo/'.$image->gridimage_id.'">';
		$html.=htmlentities($image->title).'</a></div>';
	$html.='</div>';
	
	if (isset($params['extra']))
		$html.='<div style="float:left;padding-left:20px; width:400px;">'.htmlentities($params['extra']).'</div>';
	
	$html.='<br style="clear:both"/></div>';
	
	return $html;
		  
}

/**
* adds commas to thousendise a number
*/
function smarty_function_thousends($input) {
	return number_format($input);
}

function smarty_function_ordinal($i) {
	$units=$i%10;
	switch($units)
	{
		case 1:$end=($i==11)?'th':'st';break;
		case 2:$end=($i==12)?'th':'nd';break;
		case 3:$end=($i==13)?'th':'rd';break;
		default: $end="th";	
	}
	return $i.$end;
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
		$this->register_modifier("ordinal", "smarty_function_ordinal");

		$this->register_modifier("thousends", "smarty_function_thousends");


		//assign globallly useful stuff
		$this->assign_by_ref('user', $GLOBALS['USER']);
		$this->assign_by_ref('http_host', $_SERVER['HTTP_HOST']);
		$this->assign_by_ref('script_name', $_SERVER['PHP_SELF']);
		$this->assign_by_ref('script_uri', $_SERVER['REQUEST_URI']);
		$this->assign_by_ref('searchq', $_SESSION['searchq']);
		
		
		
		$this->assign('session_id', session_id());
		
		
		
		//show more links in template?
		if (isset($GLOBALS['USER']) && $GLOBALS['USER']->hasPerm('admin'))
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
		$this->assign($which, sprintf("%04d-%02d-%02d",$_POST[$which.'Year'],$_POST[$which.'Month'],$_POST[$which.'Day']));
	}
}

function datetimeToTimestamp($datetime) {
	$p = explode('-',$datetime);
	return mktime(0, 0, 0, $p[1], $p[0], $p[2]);
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

//replace geograph links
function GeographLinks(&$posterText) {
	//look for [[gridref_or_photoid]] and [[[gridref_or_photoid]]]
	if (preg_match_all('/\[\[(\[?)(\w{0,2}\d+)(\]?)\]\]/',$posterText,$g_matches)) {
		foreach ($g_matches[2] as $i => $g_id) {
			//photo id?
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
						$posterText = str_replace("[[[$g_id]]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\">{$g_image->grid_reference} : {$g_image->title}</a>",$posterText);
					} else {
						$posterText = str_replace("[[$g_id]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\">{$g_image->grid_reference} : {$g_image->title}</a>",$posterText);
					}
				}			
			} else {
				//link to grid ref
				$posterText = str_replace("[[$g_id]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/gridref/$g_id\">$g_id</a>",$posterText);
			}
		}
	}
	if (preg_match_all('/(^| |<br\/?>|\n|\r)(http:\/\/[\w\.-]+\.[\w]{2,}\/?[^ <]*)( |<br\/?>|\n|\r|$)/',$posterText,$g_matches)) {
		foreach ($g_matches[2] as $i => $g_url) {
			$posterText = str_replace($g_matches[1][$i].$g_url.$g_matches[3][$i],$g_matches[1][$i].smarty_function_external(array('href'=>$g_url,'text'=>'Link','title'=>$g_url)).$g_matches[3][$i],$posterText);
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
