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

	$icon="<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - opens in popup window\" src=\"/img/external.png\" width=\"10\" height=\"10\"/>";

	//get params
	$matches=array();
	$gridref4=preg_replace('/^([A-Z]{1,2})\s*(\d{2,5})\s*(\d{2,5})$/i','$1$2$3',$params['gridref']);
	if (preg_match('/^document\./i', $gridref4))
	{
		if (!empty($params['gridref2']))
			$gridref4 .= ",'{$params['gridref2']}'";
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
			$text='OS Get-A-Map';
		return "<a title=\"Ordnance Survey Get-a-Map\" href=\"http://getamap.ordnancesurvey.co.uk/getamap/frames.htm\" onclick=\"popupOSMap('',''); return false;\">$text</a>$icon";
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

function smarty_function_place($params) {

	$place = $params['place'];
	$t = '';
	if ($place['distance'] > 3)
		$t .= ($place['distance']-0.01)." km from ";
	elseif (!$place['isin'])
		$t .= "near to ";

	$t .= "<b>{$place['full_name']}</b><small><i>";
	if ($place['adm1_name'] && $place['adm1_name'] != $place['reference_name'] && $place['adm1_name'] != $place['full_name'])
		$t .= ", {$place['adm1_name']}";
	elseif ($place['hist_county'])
		$t .= ", {$place['hist_county']}";
	$t .= ", {$place['reference_name']}</i></small>";
	if ($params['h3']) {
	 	$t2 = "<h3";
	 	if ($place['hist_county']) {
	 		$t2 .= " title=\"".substr($place['full_name'],0,10).": Historic County - {$place['hist_county']}";
	 		if ($place['hist_county'] == $place['adm1_name'])
	 			$t2 .= ", and modern Administrative Area of the same name";
	 		else
	 			$t2 .= ", modern Administrative Area - {$place['adm1_name']}";
	 		$t2 .= "\"";
	 	}
	 	$t = $t2.">".$t."</h3>";
	}
	return $t;
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
* smarty wrapper to GeographLinks
*/
function smarty_function_geographlinks($input,$thumbs = false) {
	return GeographLinks($input,$thumbs);
}



//replace geograph links
function GeographLinks(&$posterText,$thumbs = false) {
	//look for [[gridref_or_photoid]] and [[[gridref_or_photoid]]]
	if (preg_match_all('/\[\[(\[?)(\w{0,2} ?\d+ ?\d*)(\]?)\]\]/',$posterText,$g_matches)) {
		foreach ($g_matches[2] as $i => $g_id) {
			//photo id?
			if (is_numeric($g_id)) {
				if (!isset($g_image)) {
					$g_image=new GridImage;
				}
				$ok = $g_image->loadFromId($g_id);
				if ($g_image->moderation_status == 'rejected')
					$ok = false;
				if ($ok) {
					$g_title=$g_image->grid_reference.' : '.htmlentities($g_image->title);
					if ($g_matches[1][$i]) {
						if ($thumbs) {
							$g_title.=' by '.htmlentities($g_image->realname);
							$g_img = $g_image->getThumbnail(120,120,false,true);

							$posterText = str_replace("[[[$g_id]]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\" target=\"_blank\" title=\"$g_title\">$g_img</a>",$posterText);

						} else {
							//we don't place thumbnails in non forum links
							$posterText = str_replace("[[[$g_id]]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\">$g_title</a>",$posterText);
						}
					} else {
						$posterText = preg_replace("/(?<!\[)\[\[$g_id\]\]/","<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/$g_id\">$g_title</a>",$posterText);
					}
				}
			} else {
				//link to grid ref
				$posterText = str_replace("[[$g_id]]","<a href=\"http://{$_SERVER['HTTP_HOST']}/gridref/$g_id\">".str_replace(' ','+',$g_id)."</a>",$posterText);
			}
		}
	}

	$posterText = preg_replace('/(?<!=["\'])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;]*)(?<!\.)(?!["\'])/e',"smarty_function_external(array('href'=>\"\$1\",'text'=>'Link','title'=>\"\$1\"))",$posterText);

	$posterText = preg_replace('/(?<![\/])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;]*)(?<!\.)(?!["\'])/e',"smarty_function_external(array('href'=>\"http://\$1\",'text'=>'Link','title'=>\"\$1\"))",$posterText);

	return $posterText;
}


//available as a function, as doesn't come into effect if just re-using a smarty cache
function dieUnderHighLoad($threshold = 2,$template = 'function_unavailable.tpl') {
	global $smarty,$USER;
	if (!isset($_ENV["OS"]) || strpos($_ENV["OS"],'Windows') === FALSE) {
		//lets give registered users a bit more leaway!
		if ($USER->registered) {
			$threshold *= 2;
		}
		//check load average, abort if too high
		$buffer = "0 0 0";
		$f = fopen("/proc/loadavg","r");
		if ($f)
		{
			if (!feof($f)) {
				$buffer = fgets($f, 1024);
			}
			fclose($f);
		}
		$loads = explode(" ",$buffer);
		$load=(float)$loads[0];

		if ($load>$threshold)
		{
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



?>