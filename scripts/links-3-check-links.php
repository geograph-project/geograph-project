<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array(
	'mode'=>'external',
        'number'=>10,   //number to do each time
        'offset'=>0,   //offset - useful for many workers
        'sleep'=>0,    //sleep time in seconds
	'id' => 0,
	'force' => 0,
	'dedup'=>1,
	'prefix'=>'',
);

$HELP = <<<ENDHELP
    --mode=exteral|geograph
    --sleep=<seconds>   : seconds to sleep between calls (0)
    --number=<number>   : number of items to process in each batch (10)
    --offset=<number>   : useful for many workers
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

set_time_limit(3600*24);

$lockkey = basename($argv[0]).md5(serialize($param));

if (!$db->getOne("SELECT GET_LOCK('$lockkey',3600)")) {
        die("unable to get a lock;\n");
}

############################################

/* we can run this, as we KNOW nither Google Maps nor Bing support KML URLs any more!
update gridimage_link SET HTTP_Status_final = 404,HTTP_Status=IF(HTTP_Status>0,HTTP_Status,404),last_checked=NOW(),next_check = '2018-01-01'
where parent_link_id = 0   AND  url like '%maps%.kml%' AND next_check < '9999-00-00';
*/

$offset = (!empty($param['offset']))?intval($param['offset']).",":'';

$domains = array("http://www.geograph.org.uk/","https://www.geograph.org.uk/","http://www.geograph.ie/","https://www.geograph.ie/");

$where = array();
$where[] = 'parent_link_id = 0';
if (!empty($param['force']))
	$where[] = "next_check < '9999-00-00'";
else
	$where[] = "next_check < now()";


if ($param['mode'] == 'geograph') {
	//we dont normally do geograph links,as links-3B-check-geograph.php is more efficient, but we CAN do them to catch stragglers
	$where[] = "(url like '".implode("%' OR url like '",$domains)."%')";
} else {
	//temporally bodge, to not bother checking these, they DONT work!
	$domains[] = "http://list.english-heritage.org.uk";

	$where[] = "url NOT like '".implode("%' AND url NOT like '",$domains)."%'";
}

if (!empty($param['id']))
	$where[] = "gridimage_id = ".intval($param['id']);

if (!empty($param['prefix']))
	$where[] = "url LIKE ".$db->Quote($param['prefix']."%")." ORDER BY last_checked";

#####################

$where= implode(' AND ',$where);
$sql = "
SELECT
        gridimage_link_id,gridimage_id,content_id,url,HTTP_Last_Modified,failure_count
FROM
        gridimage_link l
WHERE
        $where
LIMIT {$offset}{$param['number']}";

$done = 0;
$recordSet = $db->Execute("$sql");

#####################

$ua = 'Mozilla/5.0 (Geograph LinkCheck Bot +http://www.geograph.org.uk/help/bot)';
ini_set('user_agent',$ua);
$done_urls = array();
$last = null;
while (!$recordSet->EOF)
{
	$rs = $recordSet->fields;
	$url = $rs['url'];

	$bits = explode("/",$url);

	//skip the HTTPS redirect!
	if (preg_match('/\b(wikipedia\.org|wikimedia\.org|bench-marks\.org\.uk)$/', $bits[2])) {
		//wikimedia does: 	Strict-Transport-Security: max-age=106384710; includeSubDomains; preload
		// todo, extend to other domains!
		$url = preg_replace('/^http:/','https:',$url);
	}

	if (isset($done_urls[$url])) {
		$recordSet->MoveNext();
		continue;
	}
	if ($param['dedup']) {
		if (!empty($hosts[$bits[2]])) {
			print "SKIP: $url   [/{$hosts[$bits[2]]}]\n";
			$hosts[$bits[2]]--;
			if ($hosts[$bits[2]] < 1)
				unset($hosts[$bits[2]]);

	                $recordSet->MoveNext();
	                continue;
		}
		if ($last == $bits[2] && $param['sleep'])
			sleep($param['sleep']);
		$last = $bits[2];
	}

	print str_repeat('#',80)."\n";
	print "URL: $url\n";

	$bindts = $db->BindTimeStamp(time());
	$bindts10 = $db->BindTimeStamp(time()+3600*24*10);
	$bindts90 = $db->BindTimeStamp(time()+3600*24*90);

	if ($rs['gridimage_id']) {
		$user_agent = "$ua\r\nReferer: http://{$_SERVER['HTTP_HOST']}/photo/{$rs['gridimage_id']}";
	} elseif ($rs['content_id']) {
		$user_agent = "$ua\r\nReferer: http://{$_SERVER['HTTP_HOST']}".$db->getOne("SELECT url FROM content WHERE content_id = {$rs['content_id']}");
	}
	if ($rs['HTTP_Last_Modified']) {
		$user_agent .= "\r\nIf-Modified-Since: ".$rs['HTTP_Last_Modified'];
	}
//print "UA:$user_agent\n";
	ini_set('user_agent',$user_agent);

	$content = '';
	$updates = array();
	$http_response_header = '';
	if ($handle = @fopen($url, "rb")) { ##php throws an warning on non 200
		while ($handle && !feof($handle)) {
			$content .= fread($handle, 8192);
		}
		fclose($handle);
	}

	print "LEN: ".strlen($content)."\n";
	print "\n";
//	print_r($http_response_header);

	if ($http_response_header) {
		$updates['HTTP_Status'] = $updates['HTTP_Status_final'] = 601;
		$heads = array(); $i=-1;
		foreach ($http_response_header as $c => $header) {
			if (preg_match('/^HTTP\/\d+.\d+ +(\d+)/i',$header,$m)) {
				$i++;
				$heads[$i] = array();
				$heads[$i]['HTTP_Status'] = $m[1];
				$heads[$i]['HTTP_HSTS'] = 0; //set to no, will be changed later (used to make sure overwrite -1)
				$updates['HTTP_Status_final'] = $m[1]; //also save the last one in the 'chain'
			} elseif(preg_match('/^Location:(.*)/i',$header,$m)) {
				if (strpos(trim($m[1]),'http') ===0) {
					$heads[$i]['HTTP_Location'] = trim($m[1]);
				} else {
					$heads[$i]['HTTP_Location'] = InternetCombineUrl($url, str_replace(" ",'+',trim($m[1])));
				}
			} elseif(preg_match('/^Last-Modified:(.*)/i',$header,$m)) {
				$heads[$i]['HTTP_Last_Modified'] = trim($m[1]);
			} elseif(preg_match('/^Strict-Transport-Security:(.*)/i',$header,$m)) {
				//  Strict-Transport-Security: max-age=106384710; includeSubDomains; preload
				$heads[$i]['HTTP_HSTS'] = (strpos($header,'includeSubDomains')!==FALSE)?2:1;
			}
		}

		if (count($heads) == 0) {
		} else {
			if (count($heads) > 1) {
				//need to create additional links...

				$parent_link_id = $rs['gridimage_link_id'];
				for($i =1;$i<count($heads);$i++) {
					$url2 = $heads[$i-1]['HTTP_Location'];

					if (!isset($done_urls[$url2])) {
						$row = array();
						$row['gridimage_id'] = $rs['gridimage_id'];
						$row['created'] = $bindts;
						$row['last_checked'] = $bindts;
						$row['next_check'] = $bindts90;
						$row['url'] = str_replace(" ","%20",$url2);
						$row['parent_link_id'] = $parent_link_id;
						foreach ($heads[$i] as $key => $value) {
							$row[$key] = $value;
						}
						print "CREATED<pre>".print_r($row,1)."</pre>";
						$db->Execute('INSERT INTO gridimage_link SET `'.implode('` = ?,`',array_keys($row)).'` = ? ON DUPLICATE KEY UPDATE gridimage_link_id = LAST_INSERT_ID(gridimage_link_id), last_checked = ? ',array_merge(array_values($row),array($row['last_checked'])) );
						$parent_link_id = $db->Insert_ID();
						$done_urls[$url2] = 1;
					}
				}
			}
			foreach ($heads[0] as $key => $value) {
				$updates[$key] = $value;
			}
		}
		if ($content && preg_match('/<title>(.*?)<\/title>/is',$content,$m) && $updates['HTTP_Status'] == 200) {
			$updates['page_title'] = $m[1];
		}
	} else {
		$updates['HTTP_Status'] = $updates['HTTP_Status_final'] = 600;
	}

	if ($updates['HTTP_Status'] == 304) {
		//$where = "gridimage_link_id = ?";
                //$where_value = array($rs['gridimage_link_id']);
		$where = "url = ? AND HTTP_Last_Modified = ?";
		$where_value = array($rs['url'],$rs['HTTP_Last_Modified']);
	} else {
		$where = "url = ?";
		$where_value = array($rs['url']);
	}

	if (!empty($updates)) {
		$updates['last_checked'] = $bindts;
		if ($updates['HTTP_Status'] == 200 || $updates['HTTP_Status'] == 301 || $updates['HTTP_Status'] == 302 || $updates['HTTP_Status'] == 304) {
			$updates['next_check'] = $bindts90;
			$extra = ",failure_count = 0";
		} else {
			//todo, if(failure_count > 2) next=90 maybe?
			if ($rs['failure_count'] > 2)
				$updates['next_check'] = $bindts90;
			else
				$updates['next_check'] = $bindts10;
			$extra = ",failure_count = failure_count + 1";
		}

		$db->Execute($sql = 'UPDATE gridimage_link SET `'.implode('` = ?,`',array_keys($updates))."` = ? $extra WHERE $where",
		array_merge(array_values($updates),$where_value));
	}

	//print "".$sql."\n\n";
	print "".print_r($updates,1)."\n\n";

	$done++;
	$done_urls[$url]=1;

	if ($param['dedup']) {
		if (!empty($hosts)) {
			foreach ($hosts as $host => $count) {
				$hosts[$host]--;
				if (empty($hosts[$host]))
					unset($hosts[$host]);
			}
		}
		$bits = explode("/",$url);
		@$hosts[$bits[2]]+=10;
	}

	$recordSet->MoveNext();

        if ($param['sleep'])
                sleep($param['sleep']);
}
$recordSet->Close();

print "#DONE $done\n\n";



function InternetCombineUrl($absolute, $relative) {
	if (preg_match('/=nolink$/',$relative)) {
		return 'javascript:void(0);';
	}
	extract(parse_url($absolute));
	if($relative{0} == '/') {
		$cparts = array_filter(explode("/", $relative));
	}
	else {
		$aparts = array_filter(explode("/", $path));
		$rparts = array_filter(explode("/", $relative));
		$cparts = array_merge($aparts, $rparts);
		foreach($cparts as $i => $part) {
			if($part == '.') {
				$cparts[$i] = null;
			}
			if($part == '..') {
				$cparts[$i - 1] = null;
				$cparts[$i] = null;
			}
		}
		$cparts = array_filter($cparts);
	}
	$path = implode("/", $cparts);
	$url = "";
	if($scheme) {
		$url = "$scheme://";
	}
	if($user) {
		$url .= "$user";
		if($pass) {
			$url .= ":$pass";
		}
		$url .= "@";
	}
	if($host) {
		$url .= "$host/";
	}
	$url .= $path;
	return $url;
}


