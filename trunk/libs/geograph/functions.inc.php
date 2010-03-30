<?php

/**
 * $Project: GeoGraph $
 * $Id: functions.inc.php 2911 2007-01-11 17:37:55Z barry $
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
* @version $Revision: 2911 $
*/

$global_thumb_count =0;


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

		$logfile=$CONF['log_script_folder'].'/'.date('Ymd-H').'.log';
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
	global $CONF;
	$icon=empty($params['icon'])?"<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - opens in popup window\" src=\"http://{$CONF['STATIC_HOST']}/img/external.png\" width=\"10\" height=\"10\"/>":'';

	//get params
	$matches=array();
	$gridref4=preg_replace('/^([A-Z]{1,3})\s*(\d{2,5})\s*(\d{2,5})$/i','$1$2$3',$params['gridref']);
	if (preg_match('/^document\./i', $gridref4))
	{
		if (!empty($params['gridref2']))
			$gridref4 .= ",'{$params['gridref2']}'";
		return "<a title=\"Ordnance Survey Get-a-Map\" href=\"javascript:popupOSMap($gridref4)\">{$params['text']}</a>$icon";
	}
	else if (preg_match('/^([A-Z]{1,3})(\d{4,10})$/i', $gridref4, $matches))
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

		if (isset($params['title']))
			$title=$params['title'];
		else
			$title="Ordnance Survey Get-a-Map for $gridref4";

		return "<a title=\"$title\" href=\"http://getamap.ordnancesurvey.co.uk/getamap/frames.htm?mapAction=gaz&amp;gazName=g&amp;gazString=$gridref6\" onclick=\"popupOSMap('$gridref6',''); return false;\">$text</a>$icon";
	}
	else if (empty($gridref4))
	{
		if (!empty($params['text']))
			$text=$params['text'];
		else
			$text='OS Get-a-Map';
		return "<a title=\"Ordnance Survey Get-a-Map\" href=\"http://getamap.ordnancesurvey.co.uk/getamap/frames.htm\" onclick=\"popupOSMap('',''); return false;\">$text</a>$icon";
	}
	else
	{
		//error
		return $gridref4;
	}
}


/**
* Smarty new window linker
*
* Provides centralised formatting of external links
* href, title and text are the params here...
*/
function smarty_function_newwin($params)
{
	global $CONF;
  	//get params and use intelligent defaults...
  	$href=str_replace(' ','+',trim($params['href']));
  	
  	if (isset($params['text']))
  		$text=$params['text'];
  	else
  		$text=$href;

  	if (isset($params['title']))
		$title=$params['title'];
	else
		$title=$text;
	
	if (isset($params['nofollow']))
		$title .= "\" rel=\"nofollow"; 	
	
	if (isset($params['onclick']))
		$title .= "\" onclick=\"".$params['onclick']; 	
	
	return "<span class=\"nowrap\"><a title=\"$title\" href=\"$href\" target=\"_blank\">$text</a>".
		"<img style=\"padding-left:2px;\" alt=\"New Window\" title=\"opens in a new window\" src=\"http://{$CONF['STATIC_HOST']}/img/newwin.png\" width=\"10\" height=\"10\"/></span>"; 
}

/**
* Smarty new window linker
*
* Provides centralised formatting of external links
* href, title and text are the params here...
*/
function smarty_function_external($params)
{
	global $CONF;
  	//get params and use intelligent defaults...
  	$href=str_replace(' ','+',$params['href']);
  	if (strpos($href,'http://') !== 0)
  		$href ="http://$href";

  	if (isset($params['text']))
  		$text=$params['text'];
  	else
  		$text=$href;

  	if (isset($params['title']))
		$title=$params['title'];
	else
		$title=$text;
	
	if (isset($params['nofollow']))
		$title .= "\" rel=\"nofollow"; 	

  	if ($params['target'] == '_blank') {
  		return "<span class=\"nowrap\"><a title=\"$title\" href=\"$href\" target=\"_blank\">$text</a>".
  			"<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - opens in a new window\" src=\"http://{$CONF['STATIC_HOST']}/img/external.png\" width=\"10\" height=\"10\"/></span>";
  	} else {
  		return "<span class=\"nowrap\"><a title=\"$title\" href=\"$href\">$text</a>".
  			"<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - shift click to open in new window\" src=\"http://{$CONF['STATIC_HOST']}/img/external.png\" width=\"10\" height=\"10\"/></span>";
  	}
}

/**
* Smarty gridimage thumbnail link
*
* given image id makes a nice thumbnail link
*/
function smarty_function_gridimage($params)
{
	global $imageCredits;

	$image=new GridImage;
	$image->loadFromId($params['id']);

	if (isset($imageCredits[$image->realname])) {
		$imageCredits[$image->realname]++;
	} else {
		$imageCredits[$image->realname]=1;
	}

	$html='<div class="photoguide">';

	$html.='<div style="float:left;width:213px">';
		$html.='<a title="view full size image" href="/photo/'.$image->gridimage_id.'">';
		$html.=$image->getThumbnail(213,160);
		$html.='</a><div class="caption"><a title="view full size image" href="/photo/'.$image->gridimage_id.'">';
		$html.=htmlentities2($image->title).'</a></div>';
	$html.='</div>';

	if (isset($params['extra']))
		$html.='<div style="float:left;padding-left:20px; width:400px;">'.htmlentities2($params['extra']).'</div>';

	$html.='<br style="clear:both"/></div>';

	return $html;

}

if (!function_exists('mb_ucfirst') && function_exists('mb_substr')) {
	function mb_ucfirst($string) {
		return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
	}
}

function recaps($in) {
	$out = preg_replace('/(^|[ \/-])([^ \/-]{3,})/e','"$1".mb_ucfirst("$2")',mb_strtolower($in));
	return stripslashes(preg_replace('/(^|\/)([^ \/-])/e','"$1".mb_strtoupper("$2")',$out));
}

function smarty_function_place($params) {

	$place = $params['place'];
	$t = '';
	if ($place['distance'] > 3)
		$t .= ($place['distance']-0.01)." km from ";
	elseif (!$place['isin'])
		$t .= "<span title=\"about ".($place['distance']-0.01)." km from\">near</span> to ";

	$place['full_name'] = _utf8_decode($place['full_name']);

	if (!ctype_lower($place['full_name'])) {
		$t .= "<b>".recaps($place['full_name'])."</b><small><i>";
	} else {
		$t .= "<b>{$place['full_name']}</b><small><i>";
	}
	$t = str_replace(' And ','</b> and <b>',$t);
	if ($place['adm1_name'] && $place['adm1_name'] != $place['reference_name'] && $place['adm1_name'] != $place['full_name'] && !preg_match('/\(general\)$/',$place['adm1_name'])) {
		$parts = explode('/',$place['adm1_name']);
		if (!ctype_lower($parts[0])) {
			if (isset($parts[1]) && $parts[0] == $parts[1]) {
				unset($parts[1]);
			}
			$t .= ", ".recaps(implode('/',$parts));
		} else {
			$t .= ", {$place['adm1_name']}";
		}
	} elseif ($place['hist_county'])
		$t .= ", {$place['hist_county']}";
	$t .= ", {$place['reference_name']}</i></small>";
	
	$tag = (isset($params['h3']))?'h3':'span';
	$t2 = "<$tag";
	if (!empty($params['h3']) && strlen($params['h3']) > 1)
		$t2 .= $params['h3'];
	if ($place['hist_county']) {
		$t2 .= " title=\"".substr($place['full_name'],0,12).": Historic County - {$place['hist_county']}";
		if ($place['hist_county'] == $place['adm1_name'])
			$t2 .= ", and modern Administrative Area of the same name";
		else
			$t2 .= ", modern Administrative Area - {$place['adm1_name']}";
		$t2 .= "\"";
	}
	$t = $t2.">".$t."</$tag>";

	return $t;
}

function _utf8_decode($string)
{
  $tmp = $string;
  $count = 0;
  while (mb_detect_encoding($tmp)=="UTF-8")
  {
    $tmp = utf8_decode($tmp);
    $count++;
  }
  
  for ($i = 0; $i < $count-1 ; $i++)
  {
    $string = utf8_decode($string);
    
  }
  return $string;
  
}

function smarty_function_linktoself($params) {
	$a = array();
	$b = explode('?',$_SERVER['REQUEST_URI']);
	if (isset($b[1])) 
		parse_str($b[1],$a);
	if ($params['value'] == 'null') {
		if (isset($a[$params['name']]))
			unset($a[$params['name']]);
	} else {
		$a[$params['name']] = $params['value'];
	}
	return htmlentities(count($a)?("?".http_build_query($a,'', '&')):'');
}

/**
* adds commas to thousendise a number
*/
function smarty_function_thousends($input,$decimals=0) {
	return number_format($input,$decimals);
}

function smarty_function_ordinal($i) {
	$units=$i%10;
	$tens=$i%100;
	switch($units)
	{
		case 1:$end=($tens==11)?'th':'st';break;
		case 2:$end=($tens==12)?'th':'nd';break;
		case 3:$end=($tens==13)?'th':'rd';break;
		default: $end="th";
	}
	return $i.$end;
}

/**
* smarty function to get revision number
*/
function smarty_modifier_revision($filename) {
	global $REVISIONS,$CONF;
	if (isset($REVISIONS[$filename])) {
		$url = "http://{$CONF['STATIC_HOST']}".preg_replace('/\.(js|css)$/',".v{$REVISIONS[$filename]}.$1",$filename);
		
		if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 4 && strpos($filename,'css') === FALSE && empty($GLOBALS['USER']->user_id)) {
			$url = cachize_url($url);
		}
		return $url;
	} else {
#return "http://{$CONF['STATIC_HOST']}".preg_replace('/\.(js|css)$/',".v".time().".$1",$filename);
        	return $filename;
	}
}


function getSitemapFilepath($level,$square = null,$gr='',$i = 0) {
	#$i = 270727;
	if (is_object($square)) {
		$s = $square->gridsquare;
		if ($level > 2) {
			$n = sprintf("%d%d",intval($square->eastings/20)*2,intval($square->northings/20)*2);
		}
		if (empty($gr)) {
			$gr = $square->grid_reference;
		}
	} elseif (!empty($gr)) {
		preg_match('/^([A-Z]{1,3})([\d_]*)$/',strtoupper($gr),$m);
		$s = $m[1];
		if ($level > 2) {
			$numbers = $m[2];
			$numlen = strlen($m[2]);
			$c = $numlen/2;
			
			$n = sprintf("%d%d",intval($numbers{0}/2)*2,intval($numbers{$c}/2)*2);
		}
	}
	
	$extension = 'html';
	$prefix = "/sitemap";
	
	if ($i) {
		$prefix .= "/$i";
	} 

	
	if ($level == 3) {
		return "$prefix/$s/$n.$extension";
	} elseif ($level == 2) {
		return "$prefix/$s.$extension";
	} elseif ($level == 1) {
		return "$prefix/geograph.$extension";
	} else {
		return "$prefix/$s/$n/$level/$gr.$extension";
	}

}

/**
* smarty wrapper to GeographLinks
*/
function smarty_function_geographlinks($input,$thumbs = false) {
	return GeographLinks($input,$thumbs);
}



//replace geograph links
function GeographLinks(&$posterText,$thumbs = false) {
	global $imageCredits,$CONF,$global_thumb_count;
	//look for [[gridref_or_photoid]] and [[[gridref_or_photoid]]]
	if (preg_match_all('/\[\[(\[?)(\w{0,3} ?\d+ ?\d*)(\]?)\]\]/',$posterText,$g_matches)) {
		$thumb_count = 0;
		foreach ($g_matches[2] as $i => $g_id) {
			//photo id?
			if (is_numeric($g_id)) {
				if ($global_thumb_count > $CONF['global_thumb_limit'] || $thumb_count > $CONF['post_thumb_limit']) {
					$posterText = preg_replace("/\[?\[\[$g_id\]\]\]?/","[[<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\">$g_id</a>]]",$posterText);
				} else {
					if (!isset($g_image)) {
						$g_image=new GridImage;
					}
					$ok = $g_image->loadFromId($g_id);
					if ($g_image->moderation_status == 'rejected') {
						$posterText = str_replace("[[[$g_id]]]",'<img src="/photos/error120.jpg" width="120" height="90" alt="image no longer available"/>',$posterText);
					} elseif ($ok) {
						$g_title=$g_image->grid_reference.' : '.htmlentities2($g_image->title);
						if ($g_matches[1][$i]) {
							if ($thumbs) {
								$g_title.=' by '.htmlentities($g_image->realname);
								$g_img = $g_image->getThumbnail(120,120,false,true);

								$posterText = str_replace("[[[$g_id]]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\" target=\"_blank\" title=\"$g_title\">$g_img</a>",$posterText);
								if (isset($imageCredits[$g_image->realname])) {
									$imageCredits[$g_image->realname]++;
								} else {
									$imageCredits[$g_image->realname]=1;
								}
							} else {
								//we don't place thumbnails in non forum links
								$posterText = str_replace("[[[$g_id]]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\">$g_title</a>",$posterText);
							}
						} else {
							$posterText = preg_replace("/(?<!\[)\[\[$g_id\]\]/","<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\">$g_title</a>",$posterText);
						}
					}
					$global_thumb_count++;
				}
				$thumb_count++;
			} else {
				//link to grid ref
				$posterText = str_replace("[[$g_id]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/gridref/$g_id\">".str_replace(' ','+',$g_id)."</a>",$posterText);
			}
		}
	}
	if ($CONF['CONTENT_HOST'] != $_SERVER['HTTP_HOST']) {
		$posterText = str_replace($CONF['CONTENT_HOST'],$_SERVER['HTTP_HOST'],$posterText);
	}
	
	$posterText = preg_replace('/(?<!["\'>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)(?<!\.)(?!["\'])/e',"smarty_function_external(array('href'=>\"\$1\",'text'=>'Link','nofollow'=>1,'title'=>\"\$1\"))",$posterText);

	$posterText = preg_replace('/(?<![\/F\.])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)(?<!\.)(?!["\'])/e',"smarty_function_external(array('href'=>\"http://\$1\",'text'=>'Link','nofollow'=>1,'title'=>\"\$1\"))",$posterText);

	return $posterText;
}


//available as a function, as doesn't come into effect if just re-using a smarty cache
function dieUnderHighLoad($threshold = 2,$template = 'function_unavailable.tpl') {
	global $smarty,$USER;
	if ($threshold == 0) {
		header("HTTP/1.1 503 Service Unavailable");
		$smarty->assign('searchq',stripslashes($_GET['q']));
		$smarty->display($template);
		exit;
	} elseif (!isset($_ENV["OS"]) || strpos($_ENV["OS"],'Windows') === FALSE) {
		$threshold *= 1.5;
		//lets give registered users a bit more leaway!
		if ($USER->registered) {
			$threshold *= 2;
		}
		//check load average, abort if too high
		$buffer = "0 0 0";
		if (is_readable("/proc/loadavg")) {
			$f = fopen("/proc/loadavg","r");
			if ($f)
			{
				if (!feof($f)) {
					$buffer = fgets($f, 1024);
				}
				fclose($f);
			}
		}
		$loads = explode(" ",$buffer);
		$load=(float)$loads[0];

		if ($load>$threshold)
		{
			header("HTTP/1.1 503 Service Unavailable");
			$smarty->assign('searchq',stripslashes($_GET['q']));
			$smarty->display($template);
			exit;
		}
	}
}


function datetimeToTimestamp($datetime) {
	$p = explode('-',$datetime);
	return mktime(0, 0, 0, intval($p[1]), intval($p[2]), intval($p[0]));
}

function getFormattedDate($input) {
	list($y,$m,$d)=explode('-', $input);
	$date="";
	if ($d>0) {
		if ($y>1970) {
			//we can use strftime
			$t=strtotime($input." 0:0:0");//stop a warning
			$date=strftime("%A, %e %B, %Y", $t);   //%e doesnt work on WINDOWS!  (could use %d)
		} else {
			//oh my!
			$t=strtotime("2000-$m-$d");
			$date=strftime("%e %B", $t)." $y";
		}
	} elseif ($m>0) {
		//well, it saves having an array of months...
		$t=strtotime("2000-$m-01");
		if ($y > 0) {
			$date=strftime("%B", $t)." $y";
		} else {
			$date=strftime("%B", $t);
		}
	} elseif ($y>0) {
		$date=$y;
	}
	return $date;
}

//credit: http://www.php.net/fsockopen
function connectToURL($addr, $port, $path, $userpass="", $timeout="30") {
	$urlHandle = @fsockopen($addr, $port, $errno, $errstr, $timeout);
	if ($urlHandle)	{
		socket_set_timeout($urlHandle, $timeout);
		if ($path) {
			$urlString = "GET $path HTTP/1.1\r\nHost: $addr\r\nConnection: keep-alive\r\nUser-Agent: www.geograph.org.uk\r\n";
			if ($userpass)
				$urlString .= "Authorization: Basic ".base64_encode("$userpass")."\r\n";
			$urlString .= "\r\n";
			fputs($urlHandle, $urlString);
			$response = fgets($urlHandle);
			if (substr_count($response, "200 OK") > 0) {	// Check the status of the link
				$endHeader = false;			// Strip initial header information
				while ($urlHandle && !$endHeader && !feof($urlHandle)) {
					if (fgets($urlHandle) == "\r\n")
						$endHeader = true;
				}
				return $urlHandle;			// All OK, return the file handle
			} else if (strlen($response) < 15) {		// Cope with wierd non standard responses
				fclose($urlHandle);
				return -1;
			} else {					// Cope with a standard error response
				fclose($urlHandle);
				return substr($response,9,3);
			}
		}
		return $urlHandle;
	} else
		return 0;
}

function customCacheControl($mtime,$uniqstr,$useifmod = true,$gmdate_mod = 0) {
	global $encoding;
	if (isset($encoding) && $encoding != 'none' && $encoding != '') {
		$uniqstr .= $encoding;
	}
	
	$hash = "\"".md5($mtime.'-'.$uniqstr)."\"";

	
	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) { // check ETag
		if($_SERVER['HTTP_IF_NONE_MATCH'] == $hash ) {
			header("HTTP/1.0 304 Not Modified");
			header ("Etag: $hash"); 
			header('Content-Length: 0'); 
			exit;
		}
		
		//also check legacy Etag
		$hash2 = "\"".$mtime.'-'.md5($uniqstr)."\"";
		
		if($_SERVER['HTTP_IF_NONE_MATCH'] == $hash2 ) {
			header("HTTP/1.0 304 Not Modified");
			header ("Etag: $hash2"); 
			header('Content-Length: 0'); 
			exit;
		}
	}	

	header ("Etag: $hash"); 

	if (!$gmdate_mod)
		$gmdate_mod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

	if ($useifmod && !empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
		$if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);

		if ($if_modified_since == $gmdate_mod) {
			header("HTTP/1.0 304 Not Modified");
			header('Content-Length: 0'); 
			exit;
		}
	}

	header("Last-Modified: $gmdate_mod");
}

function customNoCacheHeader($type = 'nocache',$disable_auto = false) {
	//none/nocache/private/private_no_expire/public
	if ($type == 'nocache') {
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1 
		header("Cache-Control: post-check=0, pre-check=0", false); 
		header("Pragma: no-cache"); 
		customExpiresHeader(-1);
	} 	
	if ($disable_auto) {
		//call to disable the auto session one, could then call another here if needbe
		session_cache_limiter('none');
	}
}

function customExpiresHeader($diff,$public = false) {
	if ($diff > 0) {
		$expires=gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+$diff);
		header("Expires: $expires");
		header("Cache-Control: max-age=$diff",false);
	} else {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past 
		header("Cache-Control: max-age=0",false);
	}
	if ($public)
		header("Cache-Control: Public",false);
}

function getEncoding() {
	global $encoding;
	if (!empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
		$gzip = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');
		$deflate = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate');

		$encoding = $gzip ? 'gzip' : ($deflate ? 'deflate' : '');

		if (!strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') && 
				preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches)) {
			$version = floatval($matches[1]);

			if ($version < 6)
				$encoding = '';

			if ($version == 6 && !strstr($_SERVER['HTTP_USER_AGENT'], 'EV1')) 
				$encoding = '';
		}
	} else {
		$encoding = '';
	}
	return $encoding;
}

function customGZipHandlerStart() {
	global $encoding;
	if ($encoding = getEncoding()) {
		ob_start();
		register_shutdown_function('customGZipHandlerEnd');
	}
}

function customGZipHandlerEnd() {
	global $encoding;
	
	$contents =& ob_get_clean();

	if (isset($encoding) && $encoding) {
		// Send compressed contents
		$contents = gzencode($contents, 9,  ($encoding == 'gzip') ? FORCE_GZIP : FORCE_DEFLATE);
		header ('Content-Encoding: '.$encoding);
		header ('Vary: Accept-Encoding');
	}
	//else ... we could still send Vary: but because a browser that doesnt will accept non gzip in all cases, doesnt matter if the cache caches the non compressed version (the otherway doesnt hold true, hence the Vary: above)
	header('Content-Length: '.strlen($contents));
	echo $contents;
}
 
function htmlspecialchars2( $myHTML,$quotes = ENT_COMPAT,$char_set = 'ISO-8859-1')
{
    return preg_replace( "/&amp;([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};|#x[0-9a-fA-F]{2,4};)/", '&$1' ,htmlspecialchars($myHTML,$quotes,$char_set));
} 
 
function htmlentities2( $myHTML,$quotes = ENT_COMPAT,$char_set = 'ISO-8859-1')
{
    return preg_replace( "/&amp;([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};|#x[0-9a-fA-F]{2,4};)/", '&$1' ,htmlentities($myHTML,$quotes,$char_set));
} 
  
function htmlnumericentities($myXML){
  return preg_replace('/[^!-%\x27-;=?-~ ]/e', '"&#".ord("$0").chr(59)', htmlspecialchars($myXML));
}


function pagesString($currentPage,$numberOfPages,$prefix,$postfix = '',$extrahtml = '',$showLastPage = false) {
	static $r;
	if (!empty($r))
		return($r);
	if ($currentPage > 1) 
		$r .= "<a href=\"$prefix".($currentPage-1)."$postfix\"$extrahtml class=\"pageNav\">&lt; &lt; prev</a> ";
	$start = max(1,$currentPage-5);
	$endr = min($numberOfPages+1,$currentPage+8);

	if ($start > 1)
		$r .= "<a href=\"$prefix$postfix\"$extrahtml class=\"pageNav\">1</a> ... ";

	for($index = $start;$index<$endr;$index++) {
		if ($index == $currentPage) 
			$r .= "<b class=\"pageNav\">$index</b> ";
		else
			$r .= "<a href=\"$prefix$index$postfix\"$extrahtml class=\"pageNav\">$index</a> ";
	}
	if ($endr < $numberOfPages+1)
		$r .= "... ";

	if ($numberOfPages > $currentPage) 
		$r .= "<a href=\"$prefix".($currentPage+1)."$postfix\"$extrahtml class=\"pageNav\">next &gt;&gt;</a> ";

	if ($showLastPage) 
		$r .= "<a href=\"$prefix".($numberOfPages)."$postfix\"$extrahtml class=\"pageNav\">last</a> ";

	return $r;
}

/**
 * returns a standard textual representation of a number
 */
function heading_string($deg) {
	$dirs = array('north','east','south','west');
	$rounded = round($deg / 22.5) % 16;
	if ($rounded < 0)
		$rounded += 16;
	if (($rounded % 4) == 0) {
		$s = $dirs[$rounded/4];
	} else {
		$s = $dirs[2 * intval(((intval($rounded / 4) + 1) % 4) / 2)];
		$s .= $dirs[1 + 2 * intval($rounded / 8)];
		if ($rounded % 2 == 1) {
			$s = $dirs[round($rounded/4) % 4] . '-' . $s;
		}
	}
	return $s;
}


/**
 * combine texts of different languages
 */
function combineTexts($lang1, $lang2)
{
	if ($lang1 == $lang2 || $lang2 == '')
		return $lang1;
	elseif ($lang1 == '')
		return $lang2;
	else
		return $lang1 . ' (' . $lang2 . ')';
}

?>
