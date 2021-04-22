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

function split_timer($profile,$key='',$id='') {

	if (isset($_GET['php_profile'])) {
		static $starts = array();
		
		if (empty($key)) {
			$starts[$profile] = microtime(true);
			return;
		} elseif (empty($starts[$profile])) {
			//wtf?
			return;
		}
		$p = Profiler::start("$profile-$key");
		$p->started = $starts[$profile]; //hack alert!
		$p->end();
		
		//restarts the clock for the next 'segment' (if any)
		$starts[$profile] = microtime(true);
	}
	
	return false;
	
	#####################
	
	static $filehandle;
	static $request;
	static $unique;
	static $starts;
	
	list($usec, $sec) = explode(' ',microtime());
	$microtime = ((float)$usec + (float)$sec);
	
	if (empty($key)) {
		if (empty($starts))
			$starts = array();
		$starts[$profile] = $microtime;
		return;
	} elseif (empty($starts[$profile])) {
		//wtf?
		return;
	}
	
	if (empty($filehandle)) {
		$logfile='/tmp/split.'.date('Ymd-H').'.log';
		$filehandle = @fopen($logfile,'a');
		
		$request = $_SERVER['REQUEST_URI'];
		$unique = uniqid();
	}

	if ($filehandle)
	fputcsv($filehandle,array(
		$_SERVER['REQUEST_TIME'], //timestamp of request start
		$unique, //helps idenity unique reqests
		$request, //example "/search.php"
		$microtime, //curent timestamp
		$profile, //example 'sphinx'
		$key, //example 'lookupids'
		$id, //example 'query_id=123456'
		sprintf("%.6f",$microtime - $starts[$profile]), //the WALL time.
	));


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

/**
* Smarty Getamap linker
*
* Makes linking to OS maps easy {getamap gridref='TL0000' text='get a map'}
*/
function smarty_function_getamap($params)
{
	global $CONF;
	$icon=empty($params['icon'])?"<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - opens in popup window\" src=\"{$CONF['STATIC_HOST']}/img/external.png\" width=\"10\" height=\"10\"/>":'';

	//get params
	$matches=array();
	$gridref4=preg_replace('/^([A-Z]{1,3})\s*(\d{2,5})\s*(\d{2,5})$/i','$1$2$3',$params['gridref']);
	if (preg_match('/^document\./i', $gridref4))
	{
		if (!empty($params['gridref2']))
			$gridref4 .= ",'{$params['gridref2']}'";
		$params['text'] =  str_ireplace('Get-a-map','Map', $params['text']);

		return "<a title=\"1:25,000 OS Maps\" href=\"javascript:popupOSMap($gridref4)\">{$params['text']}</a>$icon";
	}
	else if (preg_match('/^([A-Z]{1,3})(\d{4,10})$/i', $gridref4, $matches))
	{
		if (!empty($params['text']))
			$text=$params['text'];
		else
			$text=$params['gridref'];

		$text = str_ireplace('Get-a-map','Map',$text);

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
			$title="1:25,000 OS Maps for $gridref4";
#return "<a href=\"/gridref/$gridref6/links?getamap\" target=\"gam\">$text</a>";
#return "<a href=\"http://www.getamap.ordnancesurveyleisure.co.uk/\" target=\"gam\">$text</a>";
		return "<a title=\"$title\" href=\"/showmap.php?gridref=$gridref4\" onclick=\"popupOSMap('$gridref4',''); return false;\">$text</a>$icon";
	}
	else if (empty($gridref4))
	{
		if (!empty($params['text']))
			$text=$params['text'];
		else
			$text='OS Get-a-Map';
return "<a href=\"http://www.getamap.ordnancesurveyleisure.co.uk/\" target=\"gam\">$text</a>";
		return "<a title=\"Ordnance Survey Get-a-Map\" href=\"http://getamap.ordnancesurvey.co.uk/getamap/frames.htm\" onclick=\"popupOSMap('',''); return false;\">$text</a>$icon";
	}
	else
	{
		//error
		return htmlentities($gridref4);
	}
}


/**
* Smarty new window linker
*/
function smarty_function_newwin($params)
{
	//external finction now supports new window via a paramater
	$params['target'] = '_blank';
	return smarty_function_external($params);
}

/**
* Provides centralised formatting of external links
* href, title and text are the params here...
*/
function smarty_function_external($params)
{
	global $CONF;
  	//get params and use intelligent defaults...
  	$href=str_replace(' ','+',$params['href']);
  	if (!preg_match('/^https?:\/\//',$href) && strpos($href,'/') !== 0)
  		$href ="http://$href";

  	if (isset($params['text']))
  		$text=$params['text'];
  	else
  		$text=$href;

	if ($text == 'Link' && preg_match('/\/\/[\w\.]*archive[\w\.]+.*\/\d{14}\/http/',$href))
		$text='Archive Link';

  	if (isset($params['title']))
		$title=$params['title'];
	else
		$title=strip_tags($text);

	if (isset($params['onclick']))
		$title .= "\" onclick=\"".$params['onclick'];

	$prompt = "External link - shift click to open in new window";
	$icon = "/img/external.png";
	if (isset($params['target']) && $params['target'] == '_blank') {
		$title .= "\" target=\"_blank";
		$prompt = "External link - opens in a new window";
		$icon = "/img/newwin.png";

	} elseif (preg_match("/^https?:\/\/{$_SERVER['HTTP_HOST']}\//",$href)) {
		//for 'internal' links, can skip rest, dont need to show 'external' icon, nor should be adding the nofollow!
		return "<a title=\"$title\" href=\"$href\">$text</a>";
	}

	$rel = array();
	if (isset($params['nofollow']))
		$rel[] = "nofollow ugc";
	if (!strpos("//{$_SERVER['HTTP_HOST']}/",$href))
		$rel[] = "noopener";
	if (!empty($rel))
		$title .= "\" rel=\"".implode(" ",$rel);

	return "<span class=\"nowrap\"><a title=\"$title\" href=\"$href\">$text</a>".
		"<img style=\"padding-left:2px;\" alt=\"External link\" title=\"$prompt\" src=\"{$CONF['STATIC_HOST']}$icon\" width=\"10\" height=\"10\"/></span>";
}

/**
* Smarty gridimage thumbnail link
*
* given image id makes a nice thumbnail link
*/
function smarty_function_gridimage($params)
{
	global $imageCredits;

	$image=new GridImage($params['id']);

	if (!$image->isValid() || $image->moderation_status == 'rejected') {
		return '<i style=color:gray>[[unable to display image '.htmlentities($params['id']).']]</i>';
	}

	if (!empty($image->ext_server)) {
		$host = "http://".$image->ext_server;
		$image->gridimage_id = $image->ext_gridimage_id; //hack so the links below work. loadfromServer does NOT set this, as might be confusing
	} else
		$host = ""; //will remain relative links!


	if (isset($imageCredits[$image->realname])) {
		$imageCredits[$image->realname]++;
	} else {
		$imageCredits[$image->realname]=1;
	}

	$html='<div class="photoguide">';

	$html.='<div style="float:left;width:213px">';

		$title=$image->grid_reference.' : '.htmlentities2($image->title).' by '.htmlentities2($image->realname);

		$html.='<a title="'.$title.' - click to view full size image" href="'.$host.'/photo/'.$image->gridimage_id.'">';
		$html.=$image->getThumbnail(213,160);
		$html.='</a><div class="caption"><a href="'.$host.'/gridref/'.$image->grid_reference.'">'.$image->grid_reference.'</a> : <a title="view full size image" href="'.$host.'/photo/'.$image->gridimage_id.'">';
		$html.=htmlentities2($image->title).'</a> by <a href="'.$image->profile_link.'">'.htmlentities2($image->realname).'</a></div>';
	$html.='</div>';

	if (isset($params['extra'])) {
		if ($params['extra'] == '{description}') {
			if (!empty($image->comment)) {
				$comment2 = preg_replace("/[\n\r]+/",' ',nl2br(htmlentities2($image->comment)));
				$desc = GeographLinks($comment2).'<div style="text-align:right;font-size:0.8em">by '.htmlentities2($image->realname).'</a></div>';

				$desc = preg_replace('/\b(more sizes)\b/i',"<a href=\"$host/more.php?id=".$image->gridimage_id."\">\$1</a>",$desc);
			} else {
				$desc = '';
			}

			$s = $image->loadSnippets();
			if (!empty($image->snippet_count)) {
				if (!function_exists('smarty_modifier_truncate')) {
					require_once("smarty/libs/plugins/modifier.truncate.php");
				}

				$desc .= "<div style=\"text-align:left\"><i>Shared Description".($image->snippet_count>1?'s':'')."</i>".($image->snippets_as_ref?'<ol':'<ul')." style=\"margin:0\">";
				foreach ($image->snippets as $snippet) {
					$desc .= "<li><a href=\"$host/snippet/{$snippet['snippet_id']}\" title=\"".htmlentities2(smarty_modifier_truncate($snippet['comment'],90,"... more"))."\">". ($snippet['title']?htmlentities2($snippet['title']):'untitled')."</a></li>";
				}
				$desc .= ($image->snippets_as_ref?'</ol>':'</ul>')."</div>";
			}
		} else {
			$desc = htmlentities2($params['extra']);
		}
		if (!empty($desc)) {
			$html.='<div style="float:left;padding-left:20px; width:400px;">'.$desc.'</div>';
		}
	}

	$html.='<br style="clear:both"/></div>';

	return $html;
}

function recaps($in) {
	$out = preg_replace_callback('/(^|[ \/-])([^ \/-]{3,})/',
		function($m) { return $m[1].ucfirst($m[2]); },
		strtolower($in));
	return stripslashes(preg_replace_callback('/(^|\/)([^ \/-])/',
		function($m) { return $m[1].strtoupper($m[2]); },
		$out));
}

function smarty_function_place($params) {

	$place = $params['place'];
	$t = '';
	if (!empty($params['takenago']))
		$t .= "<span title=\"{$params['taken']}\">taken <b>{$params['takenago']}</b></span>, ";
	if (!empty($place['distance']) && $place['distance'] > 3)
		$t .= ($place['distance']-0.01)." km from ";
	elseif (empty($place['isin']))
		$t .= "<span title=\"about ".($place['distance']-0.01)." km from\">near</span> to ";

	$t .= "<span itemprop=\"contentLocation\" itemscope itemtype=\"http://schema.org/Place\"><span itemprop=\"name\">";
	if (!ctype_lower($place['full_name'])) {
		$t .= "<b>".recaps($place['full_name'])."</b><small><i>";
	} else {
		$t .= "<b>{$place['full_name']}</b><small><i>";
	}
	$t = str_replace(' And ','</b> and <b>',$t);
	if (!empty($place['adm1_name']) && $place['adm1_name'] != $place['reference_name'] && $place['adm1_name'] != $place['full_name'] && !preg_match('/\(general\)$/',$place['adm1_name'])) {
		$parts = explode('/',$place['adm1_name']);
		if (!ctype_lower($parts[0])) {
			if (isset($parts[1]) && $parts[0] == $parts[1]) {
				unset($parts[1]);
			}
			$t .= ", ".recaps(implode('/',$parts));
		} else {
			$t .= ", {$place['adm1_name']}";
		}
	} elseif (!empty($place['hist_county']))
		$t .= ", {$place['hist_county']}";
	$t .= ", {$place['reference_name']}</i></small></span>";

	if (!empty($params['lat'])) { //we only check latitude, as technically longitude can be exactly 0 in GB!
	  $t .= sprintf('<span itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
	    <meta itemprop="latitude" content="%.5f" />
	    <meta itemprop="longitude" content="%.5f" />
	  </span>',$params['lat'],$params['long']);
	}

	$t .= "</span>";

	$tag = (isset($params['h3']))?'h3':'span';
	$t2 = "<$tag";
	if (!empty($params['h3']) && strlen($params['h3']) > 1)
		$t2 .= $params['h3'];
	if (!empty($place['hist_county'])) {
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

function smarty_function_linktoself($params) {
	$a = array();
	$b = explode('?',$_SERVER['REQUEST_URI']);
	if (isset($b[1])) 
		parse_str($b[1],$a);
	if (!empty($params['name'])) {
		if ($params['value'] == 'null') {
			if (isset($a[$params['name']]))
				unset($a[$params['name']]);
		} else {
			$a[$params['name']] = $params['value'];
		}
	}
	if (!empty($params['delete'])) {
		unset($a[$params['delete']]);
	}
	return htmlentities($_SERVER['SCRIPT_NAME'].count($a)?("?".http_build_query($a,'','&')):'');
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

function to_title_case($i) {

	return ucfirst(preg_replace_callback('/([^\W_]+[^\s-]*) */', function($m) {
		//todo, /js/to-title-case.js prevents the last word from firing too. (we ue the outer ucfirst, to always cap the first word!)
		if ($m[1] == 'i' || preg_match('/^i{2,}/',$m[1])) {
			return str_replace('i','I',$m[0]);
		} elseif (preg_match("/^(a|an|and|as|at|but|by|en|for|if|in|of|on|or|the|to|vs?\.?|via|with)$/i",$m[1])) {
			return $m[0];
		} else {
			return ucfirst($m[0]);
		}
	},preg_replace('/ s\b/','s',$i)));
}

function smarty_function_capitalizetag($i) {
	$bits = explode(":",$i,2);
	if (count($bits) == 2) {
		return strtolower($bits[0]).':'.to_title_case($bits[1]);
	} else {
		return to_title_case($i);
	}
}

/**
* smarty function to get revision number
*/
function smarty_modifier_revision($filename) {
	global $REVISIONS,$CONF;
	if (isset($REVISIONS[$filename])) {
		return $CONF['STATIC_HOST'].preg_replace('/\.(js|css)$/',".v{$REVISIONS[$filename]}.$1",$filename);
	} else {
		#return $CONF['STATIC_HOST'].preg_replace('/\.(js|css)$/',".v".time().".$1",$filename); //should use filemtime(), rather tham time()
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
		preg_match('/^([A-Z]{1,3})([\d_]*)([NS]*)([EW]*)$/',strtoupper($gr),$m);
		$s = $m[1];
		if ($level > 2) {
			$numbers = $m[2];
			$numlen = strlen($m[2]);
			$c = $numlen/2;

			$n = sprintf("%d%d",intval($numbers{0}/2)*2,intval($numbers{$c}/2)*2);
		}
	}

	if ($level == 5) {
		//if level 5 quantize to subhectad/mosaic (and define gr to be in SH43NW format)

		//SH4(0)35  -> SH435(W)
		$gr = preg_replace('/^(.+)[5-9](\d)(\d)$/','$1$2${3}E',$gr);
		$gr = preg_replace('/^(.+)[0-4](\d)(\d)$/','$1$2${3}W',$gr);
		//SH43(5)E  -> SH43(N)E
		$gr = preg_replace('/^(.+)[5-9]([EW])$/','${1}N$2',$gr);
		$gr = preg_replace('/^(.+)[0-4]([EW])$/','${1}S$2',$gr);
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
function GeographLinks($posterText,$thumbs = false,$char_set = 'ISO-8859-1') {
	global $imageCredits,$CONF,$global_thumb_count,$ADODB_FETCH_MODE;
	//look for [[gridref_or_photoid]] and [[[gridref_or_photoid]]]
	if (preg_match_all('/\[\[(\[?)([a-z]+:)?(\w{0,3} ?\d+ ?\d*)(\]?)\]\]/',$posterText,$g_matches)) {
		$thumb_count = 0;

		split_timer('app'); //starts the timer

		$g_image=new GridImage;
		$ids = array();
		foreach ($g_matches[3] as $g_i => $g_id) {
			if (empty($g_matches[2][$g_i]) && is_numeric($g_id)) {
				$ids[] = $g_id;
			}
		}
		if (count($ids) > 0) {
			$db = $g_image->_getDB(true);
			$prev_fetch_mode = $ADODB_FETCH_MODE;
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$data = $db->CacheGetAssoc(3600,"SELECT gridimage_id,moderation_status,title,grid_reference,user_id,realname,credit_realname FROM gridimage_search WHERE gridimage_id IN (".implode(',',$ids).") LIMIT {$CONF['post_thumb_limit']}");
			$ADODB_FETCH_MODE = $prev_fetch_mode;
		}

		$g_image=new GridImage;
		foreach ($g_matches[3] as $g_i => $g_id) {
			$server = $_SERVER['HTTP_HOST'];
			$prefix = $g_matches[2][$g_i];

			//photo id?
			if (is_numeric($g_id)) {
				if ($global_thumb_count > $CONF['global_thumb_limit'] || $thumb_count > $CONF['post_thumb_limit']) {
					$posterText = preg_replace("/\[?\[\[$prefix$g_id\]\]\]?/","[[<a href=\"http://{$server}/photo/$g_id\">$prefix$g_id</a>]]",$posterText);
				} else {
					if ($prefix) {
						$ok = $g_image->loadFromServer($prefix, $g_id);
						if ($ok) {
							$server = $g_image->ext_server;
						}
					} elseif (isset($data[$g_id])) {
						$data[$g_id]['gridimage_id'] = $g_id;
						$g_image->fastInit($data[$g_id]);
						$ok = 1;
					} else {
						$ok = $g_image->loadFromId($g_id);
					}
					if ($g_image->moderation_status == 'rejected' && !empty($db)) {
						if ($to = $db->getOne("SELECT destination FROM gridimage_redirect WHERE gridimage_id = ".intval($g_id))) {
							$ok = $g_image->loadFromId($to);
						}
					}

					if ($g_image->moderation_status == 'rejected') {
						if ($thumbs) {
							$posterText = str_replace("[[[$prefix$g_id]]]",'<img src="'.$CONF['STATIC_HOST'].'/photos/error120.jpg" width="120" height="90" alt="image no longer available"/>',$posterText);
						}
						$posterText = preg_replace("/\[{2,3}$prefix$g_id\]{2,3}/",'[image no longer available]',$posterText);
					} elseif ($ok) {
						if ($char_set == 'UTF-8')
							$g_image->title = latin1_to_utf8($g_image->title);
						$g_title=$g_image->grid_reference.' : '.htmlentities2($g_image->title,ENT_COMPAT,$char_set);
						if ($g_matches[1][$g_i]) {
							if ($thumbs) {
								if ($char_set == 'UTF-8')
									$g_image->realname = latin1_to_utf8($g_image->realname);
								$g_title.=' by '.htmlentities2($g_image->realname,ENT_COMPAT,$char_set);
								$g_img = $g_image->getThumbnail(120,120,false,true);

								$posterText = str_replace("[[[$prefix$g_id]]]","<a href=\"http://{$server}/photo/$g_id\" target=\"_blank\" title=\"$g_title\">$g_img</a>",$posterText);
								if (isset($imageCredits[$g_image->realname])) {
									$imageCredits[$g_image->realname]++;
								} else {
									$imageCredits[$g_image->realname]=1;
								}
								$global_thumb_count++;
							} else {
								//we don't place thumbnails in non forum links
								$posterText = str_replace("[[[$prefix$g_id]]]","<a href=\"http://{$server}/photo/$g_id\">$g_title</a>",$posterText);
							}
						} else {
							$posterText = preg_replace("/(?<!\[)\[\[$prefix$g_id\]\]/","<a href=\"http://{$server}/photo/$g_id\">$g_title</a>",$posterText);
						}
					}
				}
				$thumb_count++;
			} else {
				//link to grid ref
				$posterText = str_replace("[[$prefix$g_id]]","<a href=\"http://{$server}/gridref/".str_replace(' ','+',$g_id)."\">$g_id</a>",$posterText);
			}
		}

		split_timer('app','GeographLinks'.$thumb_count,strlen($posterText)); //logs the wall time
	}
	if ($CONF['CONTENT_HOST'] != $CONF['SELF_HOST']) {
		$posterText = str_replace($CONF['CONTENT_HOST'],$CONF['SELF_HOST'],$posterText);
	}

	# TODO we probably should introduce something like [[:url:href|text]] and [[:url:href]] which would become <a href="href">text</a> or <a href="href">Link</a>
	#      would make parsing easier, no assumptions about probable urls needed... could easily introduce [[:whatever:...]] using the same code...
	$posterText = preg_replace_callback('/(?<!["\'>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/', function($m) {
		return smarty_function_external(array('href'=>$m[1],'text'=>'Link','nofollow'=>1,'title'=>$m[1]));
	}, $posterText);

	$posterText = preg_replace_callback('/(?<![>\/F\.])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/', function($m) {
		return smarty_function_external(array('href'=>"http://".$m[1],'text'=>'Link','nofollow'=>1,'title'=>$m[1]));
	}, $posterText);

	//temp bodge, both CONTENT_HOST and SELF_HOST use $CONF['PROTOCOL'], so current protocol, doesnt actully fix https links!
	//we now WE can support https:// and consider it canonical, even if http:// urls still work!
	$posterText = str_replace("http://".$_SERVER['HTTP_HOST'],"https://".$_SERVER['HTTP_HOST'],$posterText);

	//... although geotrip urls are still http:// canoncial! ?!
	$posterText = str_replace("https://".$_SERVER['HTTP_HOST']."/geotrip","http://".$_SERVER['HTTP_HOST']."/geotrip",$posterText);

	return $posterText;
}

function replace_tags($text) {
        static $db;
        if (empty($db)) {
                $db = GeographDatabaseConnection(true);
        }
        $tag = $text;
        $where = array();
        $where['prefix'] = "prefix = ''";
        if (strpos($tag,':') !== FALSE) {
                list($prefix,$tag) = explode(':',$tag,2);
                $where['prefix'] = "prefix = ".$db->Quote($prefix);
        }
        $where['tag'] = "tag = ".$db->Quote($tag);
        $row= $db->getRow("SELECT tag_id,prefix,tag,description,canonical,COUNT(*) AS images FROM tag_public WHERE ".implode(' AND ',$where)." GROUP BY tag_id ORDER BY NULL");
        if (!empty($row)) {
                $row['tag'] = urlencode($row['tag']);
                if (!empty($row['prefix']))
                        $row['tag'] = urlencode($row['prefix']).":".$row['tag'];
                $row['description'] = htmlentities2($row['description']);
                $text = "[<a href=\"/tags/?tag={$row['tag']}\" title=\"{$row['description']}\" target=\"_blank\">$text</a>] <i style=\"color:gray\">({$row['images']} images)</i>";
        } else {
                $text2= urlencode($text);
                $text = "[<a href=\"/search.php?text=$text2\">$text</a>]";
        }
        return $text;
}


function pageMustBeHTTPS($status = 301) {
	global $CONF;

	//only do GETs for now
	if ($_SERVER['REQUEST_METHOD'] != 'GET')
		return;

	//TODO/TOFIX this doesnt work, because right now $CONF['PROTOCOL'] matches current accesss.
	// ... dont have a way to disable this function right now
	//if (empty($CONF['PROTOCOL']) || $CONF['PROTOCOL'] != 'https://')
	//	return; //site is NOT enabled for https anyway

	if (!empty($_SERVER['HTTPS']))
		return; //page is already HTTPS!

	if (!empty($CONF['server_ip']) && strpos($_SERVER['REMOTE_ADDR'],$CONF['server_ip']) === 0 //checks that we the request is from local proxy
		&& !empty($_SERVER['HTTP_X_FORWARDED_FOR']) //checks its a forwarded request!
		&& !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') //checks it really is a https request
		return; //page is already HTTPS via proxy!

	//TODO/TOFIX, should perhaps be using SELF_HOST but that might not be https yet.
	header("Location: https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", true, $status);
	exit;
}

//temp function as some pages DONT work on https :(
function pageMustBeHTTP($status = 302) {
	global $CONF;

	//only do GETs for now
	if ($_SERVER['REQUEST_METHOD'] != 'GET' && empty($_POST['login'])) //actully allow a redirect if a 'login' POST. (got this far, means now logged in!)
		return;

	//take shortcut and use $CONF['PROTOCOL'], which right now is automatically set..
	if ($CONF['PROTOCOL'] == "http://")
		return;

	header("Location: http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", true, $status);
	exit;
}


function dieIfReadOnly($template = 'function_readonly.tpl') {
	global $smarty,$CONF;

	$filesystem = GeographFileSystem();

	if (!$filesystem->hasAuth() && !is_writable($_SERVER['DOCUMENT_ROOT'].'/geophotos/'))
		$CONF['readonly'] = true; //TODO - hopefully temporally function, but if ELB thinks ALL targets are down, it continues to send traffic. So need to disable writing (as s3fs may be offline)

	if (!empty($CONF['readonly'])) {
		if (empty($smarty))
			$smarty = new GeographPage;

		dieUnderHighLoad(0,$template);
	}
}

/**
* get 1 minute load average
*/
function get_loadavg()
{
	if (!empty($_SERVER['CONF_PROFILE']) && is_readable("/sys/fs/cgroup/cpu/tasks")) {
		$count = trim(`cat /sys/fs/cgroup/cpu/tasks | wc -l`);
		return pow($count/10,1.3); //fake load from processes!
	}

        if (!function_exists('posix_uname')) {
                return -1;
        }

        //if available, this seems the most reliable way
        if (function_exists('sys_getloadavg'))
                return @array_shift(sys_getloadavg()); //array_shift accepts by reference, which emits notice when used like this

	if (is_readable("/proc/loadavg")) {
	        $buffer = "0 0 0";
        	$f = fopen("/proc/loadavg","r");
	        if (!feof($f)) {
        	        $buffer = fgets($f, 1024);
	        }
        	fclose($f);
	        $load = explode(" ",$buffer);
        	return (float)$load[0];
	}
}


//available as a function, as doesn't come into effect if just re-using a smarty cache
function dieUnderHighLoad($threshold = 2,$template = 'function_unavailable.tpl') {
	global $smarty,$USER,$CONF;
	if ($threshold == 0) {
		if ($CONF['template']=='archive') {
			//heritrix doesn't understand 503 errors - so lets cause it to timeout.... (uses a socket timeout of 20000ms)
			sleep(30);
		}
		header("HTTP/1.1 503 Service Unavailable");
		if (!empty($_GET['q']))
			$smarty->assign('searchq',stripslashes($_GET['q']));
		$smarty->display($template);
		exit;
	} elseif (!isset($_ENV["OS"]) || strpos($_ENV["OS"],'Windows') === FALSE) {

		//fudge it a bit - our servers are generally busier
		$threshold *= 2; 
		$threshold += 2;

		//lets give registered users a bit more leaway!
		if ($USER->registered) {
			$threshold *= 2;
		}

		$load = get_loadavg();

		if ($load>$threshold)
		{
			if ($CONF['template']=='archive') {
				//heritrix doesn't understand 503 errors - so lets cause it to timeout.... (uses a socket timeout of 20000ms)
				sleep(30);
			}
			header("HTTP/1.1 503 Service Unavailable");
			if (!empty($_GET['q']))
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
			if (strlen($input) > 10) 
				$t=strtotime($input);
			else
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

	//ugly, but out server doesnt have a welsh locale installed.
        if (strlen($date)>4 && !empty($_GET['lang']) && $_GET['lang'] == 'cy') {
                $translate = array("January"=>"Ionawr","July"=>"Gorffennaf","February"=>"Chwefror","August"=>"Awst","March"=>"Mawrth","September"=>"Medi",
                "April"=>"Ebrill","October"=>"Hydref","May"=>"Mai","November"=>"Tachwedd","June"=>"Mehefin","December"=>"Rhagfyr",
                "Monday"=>"Dydd Llun","Friday"=>"Dydd Gwener","Tuesday"=>"Dydd Mawrth","Saturday"=>"Dydd Sadwrn",
                "Wednesday"=>"Dydd Mercher","Sunday"=>"Dydd Sul","Thursday"=>"Dydd Iau");
                $date = str_replace(array_keys($translate),array_values($translate),$date);
        }


	return $date;
}

//credit: http://www.php.net/fsockopen
function connectToURL($addr, $port, $path, $userpass="", $timeout="30") {
	$urlHandle = @fsockopen($addr, $port, $errno, $errstr, $timeout);
	if ($urlHandle)	{
		socket_set_timeout($urlHandle, $timeout);
		if ($path) {
			$urlString = "GET $path HTTP/1.0\r\nHost: $addr\r\nUser-Agent: {$_SERVER['HTTP_HOST']}\r\n";
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

function get_no_content($url) {
	$parts=parse_url($url);

	$fp = fsockopen($parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 1);

	if ($fp!=0)
		return "Couldn't open a socket to ".$url." (".$errstr.")";

	$out = "GET ".$parts['path']." HTTP/1.1\r\n";
	$out.= "Host: ".$parts['host']."\r\n";
	$out.= "Connection: Close\r\n\r\n";

	fwrite($fp, $out);
	fclose($fp); //don't wait for reply!
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

function customExpiresHeader($diff,$public = false,$overwrite = false) {
	$private = ($public)?'':', private';
	if ($diff > 0) {
		//if (strpos($_SERVER['HTTP_USER_AGENT'], 'bingbot')!==FALSE)
		//	return;
		$expires=gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+$diff);
		header("Expires: $expires");
		header("Cache-Control: max-age=$diff$private",$overwrite);
		if ($overwrite) {
			header("Pragma:"); //sessions by default set this
		}
	} else {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past 
		header("Cache-Control: max-age=0$private",$overwrite);
	}
	//via http://redbot.org - 
	// Therefore, SSL-protected or HTTP-authenticated (NOT cookie-authenticated) resources may have use for public to improve cacheability, if used judiciously.
	// However, other responses do not need to contain public ; it does not make the response "more cacheable", and only makes the headers larger.
	//if ($public)
	//	header("Cache-Control: public",false);
}

function getEncoding() {
	global $encoding;
	if (false && !empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
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
		if (defined('VARY_COOKIE')) {
			header ('Vary: Cookie,Accept-Encoding');
		} else {
			header ('Vary: Accept-Encoding');
		}
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
	return str_replace('&#38;amp;','&#38;', preg_replace_callback('/[^!-%\x27-;=?-~ ]/',
	       function($m) { return '&#'.ord($m[0]).';'; },
	       htmlspecialchars($myXML)));
}

function xmlentities($string, $charset = 'UTF-8') {
        return htmlspecialchars($string,ENT_QUOTES,$charset,false);
}


function translit_to_ascii($in, $charset = 'ISO-8859-15') {

        $currentLocal = setlocale(LC_CTYPE, 0);
        //see comments on http://php.net/manual/en/function.iconv.php  //TRANSLIT may only work if set a UTF8 locale, even though NOT even using unicode (ie not set to charset, always utf8)
        setlocale(LC_CTYPE, "en_US.UTF-8");

        $new = iconv($charset, 'ASCII//TRANSLIT', $in);

        setlocale(LC_CTYPE, $currentLocal);

        return $new;
}


function manticore_to_utf8($input) {
	//no longer need to convert to utf8 explicilty, as manticore has data in utf8 already
	// ie "windows-1252" converted to "utf-8" during indexing, but need to decode entiteis, as utf8 was still left as entities
	return html_entity_decode($input, ENT_COMPAT, 'UTF-8');
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

function utf8_to_latin1($input) {
	//dedect chars OUTSIDE of cp1252 and convert to HTML-Entities, just like browsers do on submitting forms!
	// http://www.intertwingly.net/blog/2004/04/15/Character-Encoding-and-HTML-Forms
        // see code at https://stackoverflow.com/questions/3231819/convert-utf8-to-latin1-in-php-all-characters-above-255-convert-to-char-referenc
        $convmap= array(0x0100, 0xFFFF, 0, 0xFFFF);
        $input= mb_encode_numericentity($input, $convmap, 'UTF-8');
	return iconv("utf-8", "windows-1252", $input);
}

function urlencode2($input) {
	return str_replace(array('%2F','%3A','%20'),array('/',':','+'),urlencode($input));
}

function pagesString($currentPage,$numberOfPages,$prefix,$postfix = '',$extrahtml = '',$showLastPage = false) {
	static $r;
	if (!empty($r))
		return($r);

	if (!empty($prefix)) {
		//this is tricky, callers typically pass PHP_SELF, which needs escaping. But can't call htmlentities on whole prefix, as aldready using &amp; etc in paths
		$bits = explode('?',$prefix,2);
		if (count($bits) == 2)
			$prefix = htmlentities($bits[0])."?".$bits[1];
	}

	if ($currentPage > 1)
		$r .= "<a href=\"$prefix".($currentPage-1)."$postfix\"$extrahtml class=\"pageNav\" rel=\"prev\">&lt; &lt; prev</a> ";
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
		$r .= "<a href=\"$prefix".($currentPage+1)."$postfix\"$extrahtml class=\"pageNav\" rel=\"next\">next &gt;&gt;</a> ";

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

function sqlBitsToCount(&$sql) {
	if (isset($sql['group'])) {
		if (isset($sql['having'])) {
			$query = "SELECT COUNT(DISTINCT IF({$sql['having']},{$sql['group']},NULL))";
		} else {
			$query = "SELECT COUNT(DISTINCT {$sql['group']})";
		}
	} else {
		$query = "SELECT COUNT(*)";
	}
	if (isset($sql['tables']) && count($sql['tables'])) {
		$query .= " FROM ".join(' ',$sql['tables']);
	}
	if (isset($sql['wheres']) && count($sql['wheres'])) {
		$query .= " WHERE ".join(' AND ',$sql['wheres']);
	}
	return $query;
}

function sqlBitsToSelect($sql) {
	$query = "SELECT {$sql['columns']}";
	if (!empty($sql['tables'])) {
		$query .= " FROM ".join(' ',$sql['tables']);
	}
	if (!empty($sql['wheres'])) {
		$query .= " WHERE ".join(' AND ',$sql['wheres']);
	}
	if (isset($sql['group'])) {
		$query .= " GROUP BY {$sql['group']}";
	}
	if (isset($sql['having'])) {
		$query .= " HAVING {$sql['having']}";
	}
	if (isset($sql['order'])) {
		$query .= " ORDER BY {$sql['order']}";
	}
	if (isset($sql['limit'])) {
		$query .= " LIMIT {$sql['limit']}";
	}
	if (!empty($sql['option'])) {
		if (is_array($sql['option']))
			$sql['option'] = implode(', ',$sql['option']);
		$query .= " OPTION {$sql['option']}";
	}
	return $query;
}

function outputJSON(&$data) {
        if (!empty($_GET['callback'])) {
		header("Content-Type:text/javascript");
                $callback = preg_replace('/[^\w\.\$]+/','',$_GET['callback']);
                echo "/**/{$callback}(";
        } else {
		header("Content-Type:application/json");
	}

	if (!function_exists('json_encode')) {
	        require_once '3rdparty/JSON.php';
	}

        print json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR );

        if (!empty($_GET['callback'])) {
                echo ");";
        }
}



//if update this function, remember there is a javascript version too!
function cleanTag($text, $fix_apos = true) {
        //basic HTML injection protection
	$text = preg_replace('/[<>]+/',' ',preg_replace('/<[^>]*>/','',preg_replace('/\\\\/','',$text)));

	$prefix = null;
	if (strpos($text,':') !== FALSE) {
                list($prefix,$text) = explode(':',$text,2);
		//prefixes have particully restricted charactor set.
		$prefix = trim(preg_replace('/[ _]+/',' ',preg_replace('/[^\w]+/',' ',strtolower($prefix))));
	}

	if ($prefix != 'top') {
	        //this is just a short list of charactors we KNOW not support, plenty more.
	        //Todo.. change to whitelist, rather than blacklist.
	        $text = preg_replace('/[?|;,]+/',' ',$text);
	        // in general the whitelist is [A-Za-z0-9/\.& ()\*!-]
	        //NOTE: do still have legacy with '?' and ',' can be allowed in top: tags only!
	}

	//quotes not supported, clean whitespace.
	$text = trim(preg_replace('/[ _\t\n\r]+/',' ',preg_replace('/[\'"]+/','',$text)));

	//this is a well known and common issue to fix, our house style doesnt have dot after st.
	$text = preg_replace('/\b(st)\.+\s*/i','$1 ',$text);

	//just to catch odd cases were tag ends up actully blank!
	$text = preg_replace('/^\s*$/','blank',$text);

	//old tags might still have a space before s, in partiucular where apostrophy was replaced by space.
	if ($fix_apos)
		$text = preg_replace('/ s\b/','s',$text);

	if ($prefix)
                $text = $prefix.':'.$text;

	return $text;
}


function mail_wrapper($email, $subject, $body, $headers = '', $param = '', $debug = false) {
	global $CONF;

	if (!empty($CONF['smtp_host'])) {
		require_once "3rdparty/class.phpmailer.php";
		require_once "3rdparty/class.smtp.php";

		$mail = new PHPMailer;

		#########################
		if ($debug)
			$mail->SMTPDebug = 3;                               // Enable verbose debug output

		$mail->XMailer = 'x'; //used to SKIP the header

		$mail->isSMTP();
		$mail->Host = $CONF['smtp_host'];
		if (!empty($CONF['smtp_user'])) {
			$mail->SMTPAuth = true;
			$mail->Username = $CONF['smtp_user'];
			$mail->Password = $CONF['smtp_pass'];
		}
		if ($CONF['smtp_port']> 25)
			$mail->SMTPSecure = 'tls';                    // Enable TLS encryption, `ssl` also accepted
		$mail->Port = $CONF['smtp_port'];                     // TCP port to connect to

		#########################

		$mail->setFrom($CONF['smtp_from'],'',true);//set sender too

		#########################
		// parse the general headers from request

		if ($headers) {
			if (is_array($headers))
				$headers = implode("\n", $headers);

			//basic header parser

			if (preg_match('/Received:(.*)/',$headers, $m)) {
                                $mail->addCustomHeader('Received',trim($m[1]));
                        }

			if (preg_match('/From:(.*)<(.*)>/',$headers, $m)) { //no DOTALL, so shouldnt match mutliline!
				//$mail->setFrom('from@example.com', 'Mailer');
		        	$mail->setFrom(trim($m[2]), trim($m[1]));
			} elseif (preg_match('/From:(.*)/',$headers, $m)) {
                                $mail->setFrom(trim($m[1]));
                        }

			if (preg_match('/Reply-To:(.*)<(.*)>/',$headers, $m)) {
                	        $mail->addReplyTo(trim($m[2]), trim($m[1]));
			} elseif (preg_match('/Reply-To:(.*)/',$headers, $m)) {
                                $mail->addReplyTo(trim($m[1]));
                        }

			if (preg_match('/Sender:(.*)/',$headers, $m)) {
                                $mail->Sender = trim($m[1]);
                        }
                }

		if (preg_match('/(.*)<(.*)>/',$email, $m)) {
			$mail->addAddress(trim($m[2]), trim($m[1]));
		} else
			$mail->addAddress($email);

		$mail->Subject = $subject;
		$mail->Body = $body; //if using isHTML will be the HTML verson, AltBody, will be plain text!

		return $mail->send();
	} else {
		return mail($email, $subject, $body, $headers, $param);
	}
}

function debug_message($subject,$body) {
	global $CONF;

	$body = "Host: ".`hostname`."\n".
		" [HTTP_HOST] => ".$_SERVER['HTTP_HOST']."\n".
		" [REQUEST_URI] => ".$_SERVER['REQUEST_URI']."\n".
		" [QUERY_STRING] => ".$_SERVER['QUERY_STRING']."\n".
		" [HTTP_USER_AGENT] => ".$_SERVER['HTTP_USER_AGENT']."\n".
		" [HTTP_X_FORWARDED_FOR] => ".$_SERVER['HTTP_X_FORWARDED_FOR']."\n".
		" [REQUEST_TIME] => ".$_SERVER['REQUEST_TIME']."\n".
		" [HTTP_X_AMZN_TRACE_ID] => ".$_SERVER['HTTP_X_AMZN_TRACE_ID']."\n\n".
		$body;

	ob_start();
	debug_print_backtrace();
        $con = ob_get_clean();

	mail_wrapper($CONF['contact_email'],$subject,$body."\n\n".$con);
}

