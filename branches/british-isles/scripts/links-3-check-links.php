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
        'number'=>10,   //number to do each time
        'sleep'=>0,    //sleep time in seconds
);

$HELP = <<<ENDHELP
    --sleep=<seconds>   : seconds to sleep between calls (0)
    --number=<number>   : number of items to process in each batch (10)
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

set_time_limit(3600*24);


#####################

$offset = isset($param['number'])?intval($param['offset']).",":'';

if (!empty($param['full'])) {
$sql = "
SELECT
	gridimage_link_id,gridimage_id,url,HTTP_Last_Modified,count(*) as uses
FROM
	gridimage_link l
WHERE
	next_check < now()
	AND url NOT like '%geograph.org.uk/%' AND url NOT like '%geograph.ie/%'
GROUP BY
	url
ORDER BY
	PASSWORD(url)
LIMIT {$offset}{$param['number']}";
} else {
	//quick nibble version!
	## NOte uses=2 is just to make sure will process duplicates ok
$sql = "
SELECT
        gridimage_link_id,gridimage_id,url,HTTP_Last_Modified,2 as uses
FROM
        gridimage_link l
WHERE
        next_check < now() AND parent_link_id = 0
        AND url NOT like '%geograph.org.uk/%' AND url NOT like '%geograph.ie/%'
ORDER BY RAND()
LIMIT {$offset}{$param['number']}";
}

$done = 0;
$recordSet = &$db->Execute("$sql");

$ua = 'Mozilla/5.0 (Geograph LinkCheck Bot +http://www.geograph.org.uk/help/bot)';
ini_set('user_agent',$ua);
$bindts = $db->BindTimeStamp(time());
$bindts10 = $db->BindTimeStamp(time()+3600*24*10);
$bindts90 = $db->BindTimeStamp(time()+3600*24*90);
$done_urls = array();
while (!$recordSet->EOF)
{
	$rs = $recordSet->fields;
	$url = $rs['url'];
	if (isset($done_urls[$url])) {
		$recordSet->MoveNext();
		continue;
	}

	print str_repeat('#',80)."\n";
	print "URL: $url\n";

	$user_agent = "$ua\r\nReferer: http://{$_SERVER['HTTP_HOST']}/photo/{$rs['gridimage_id']}";
	if ($rs['HTTP_Last_Modified']) {
		$user_agent .= "\r\nIf-Modified-Since: ".$rs['HTTP_Last_Modified'];
	}
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
	print_r($http_response_header);

	if ($http_response_header) {
		$updates['HTTP_Status'] = 601;
		$heads = array(); $i=-1;
		foreach ($http_response_header as $c => $header) {
			if (preg_match('/^HTTP\/\d+.\d+ +(\d+)/i',$header,$m)) {
				$i++;
				$heads[$i] = array();
				$heads[$i]['HTTP_Status'] = $m[1];
				$updates['HTTP_Status_final'] = $m[1]; //also save the last one in the 'chain'
			} elseif(preg_match('/^Location:(.*)/i',$header,$m)) {
				if (strpos(trim($m[1]),'http') ===0) {
					$heads[$i]['HTTP_Location'] = trim($m[1]);
				} else {
					$heads[$i]['HTTP_Location'] = InternetCombineUrl($url, str_replace(" ",'+',trim($m[1])));
				}
			} elseif(preg_match('/^Last-Modified:(.*)/i',$header,$m)) {
				$heads[$i]['HTTP_Last_Modified'] = trim($m[1]);
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
						$parent_link_id = mysql_insert_id();
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
		$updates['HTTP_Status'] = 600;
	}

	if ($rs['uses'] == 1) {
		$where = "gridimage_link_id = ?";
		$where_value = $rs['gridimage_link_id'];
	} else {
		$where = "url = ?";
		$where_value = $url;
	}

	if ($rs['HTTP_Last_Modified'] && $updates['HTTP_Status'] == 304) {
		$db->Execute($sql = "UPDATE gridimage_link SET last_checked = NOW(),next_check=date_add(NOW(),interval 90 day) WHERE $where",array($where_value));
	} else {
		$updates['last_checked'] = $bindts;
		if ($updates['HTTP_Status'] == 200 || $updates['HTTP_Status'] == 301 || $updates['HTTP_Status'] == 302) {
			$updates['next_check'] = $bindts90;
			$extra = ",failure_count = 0";
		} else {
			//todo, if(failure_count > 2) next=90 maybe?
			$updates['next_check'] = $bindts10;
			$extra = ",failure_count = failure_count + 1";
		}

		$db->Execute($sql = 'UPDATE gridimage_link SET `'.implode('` = ?,`',array_keys($updates))."` = ? $extra WHERE $where",
		array_merge(array_values($updates),array($where_value)));
	}

	//print "".$sql."\n\n";
	print "".print_r($updates,1)."\n\n";

	$done++;
	$done_urls[$url]=1;

	$recordSet->MoveNext();

        if ($param['sleep'])
                sleep($param['sleep']);
}
$recordSet->Close();

print "#DONE\n\n";



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


