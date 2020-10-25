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

set_time_limit(3600*24);

###############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!$db->getOne("SELECT GET_LOCK('".basename($argv[0])."',3600)")) {
        die("unable to get a lock;\n");
}

###############################################



/* we can run this, as we KNOW nither Google Maps nor Bing support KML URLs any more! (although should check first that they ARE all gmaps or bing links!
update gridimage_link SET HTTP_Status_final = 404,HTTP_Status=IF(HTTP_Status>0,HTTP_Status,404),last_checked=NOW(),next_check = '2018-01-01'
where parent_link_id = 0   AND  url like '%maps%.kml%' AND next_check < '9999-00-00';
*/

$offset = isset($param['offset'])?intval($param['offset']).",":'';

	## NOte uses=2 is just to make sure will process duplicates ok
$sql = "
SELECT
        gridimage_link_id,gridimage_id,url,2 as uses
FROM
        gridimage_link l
WHERE
        next_check < now() AND parent_link_id = 0
        AND (url like 'http://www.geograph.org.uk/%' OR url like 'http://www.geograph.ie/%')
GROUP BY url
LIMIT {$offset}{$param['number']}";


$done = 0;
$recordSet = $db->Execute("$sql");

print "c = ".$recordSet->RecordCount()."\n";

$ua = 'Mozilla/5.0 (Geograph LinkCheck Bot +http://www.geograph.org.uk/help/bot)';
ini_set('user_agent',$ua);
while (!$recordSet->EOF)
{
	$bindts = $db->BindTimeStamp(time());
	$bindts10 = $db->BindTimeStamp(time()+3600*24*10);
	$bindts90 = $db->BindTimeStamp(time()+3600*24*90);

	$rs = $recordSet->fields;
	$url = $rs['url'];

	print str_repeat('#',80)."\n";
	print "URL: $url\n";

	// Images!
	if (preg_match('/\.(uk|ie)\/(photo\/|p\/|)(\d+)/',$url,$m)) {
		$gridimage_id = intval($m[3]);
		if ($db->getOne("SELECT gridimage_id FROM gridimage_search WHERE gridimage_id = $gridimage_id")) {
			$updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 200;
		} elseif ($db->getOne("SELECT gridimage_id FROM gridimage WHERE gridimage_id = $gridimage_id")) {
			//rejected are 410s
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 410;
                } else {
			//unknown!
			$updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 404;
		}

	// Collections!
	} elseif(preg_match('/\.(uk|ie)(\/blog\/\d+)/',$url,$m)
		|| preg_match('/\.(uk|ie)(\/geotrips\/\d+)/',$url,$m)
		|| preg_match('/\.(uk|ie)(\/snippet\/\d+)/',$url,$m)
		|| preg_match('/\.(uk|ie)(\/profile\/\d+)/',$url,$m)
		|| preg_match('/\.(uk|ie)(\/article\/[\w-]+)/',$url,$m) //todo we COULD check the fragment /article/A-History-of-Port-Glasgow#gourock-ropeworks TOO!
		//|| preg_match('/\.(uk|ie)(\/gallery\/[\w-]+)/',$url,$m) //do this with ID directly below instead... 
		) {
		$lookup = $db->quote($m[2]);
		if ($db->getOne("SELECT content_id FROM content WHERE url = $lookup")) {
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 200;
                } else {
                        //unknown!
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 404;
                }

	//Galleries
	} elseif(preg_match('/\.(uk|ie)\/discuss\/.*topic=(\d+)/',$url,$m)
		|| preg_match('/\.(uk|ie)\/gallery\/[\w-]+?_(\d+)\b/',$url,$m) //we do gallery directly because the nameslug is UNimportant and could change
		) {
		 $lookup = intval($m[2]);
		$forum_id = $db->getOne("SELECT forum_id FROM geobb_topics WHERE topic_id = $lookup");
                if ($forum_id==11) { //need to SPECIFICALLY check for galleries!
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 200;
                } elseif ($forum_id) {
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 403;
                } else {
                        //unknown!
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 404;
                }

	} elseif(preg_match('/\.(uk|ie)\/discuss\/.*gridref=(\w{1,2}\d+)/',$url,$m)) {
		 $lookup = $db->Quote($m[2]);
		$forum_id = $db->getOne("SELECT forum_id FROM geobb_topics WHERE topic_title = $lookup");
                if ($forum_id==11) { //need to SPECIFICALLY check for galleries!
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 200;
                } elseif ($forum_id) {
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 403;
                } else {
                        //unknown!
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 404;
                }

	//search results
	} elseif(preg_match('/\.(uk|ie)\/search\.php\?.*\bi=(\d+)/',$url,$m)) {
		 $lookup = intval($m[2]);
                if ($db->getOne("SELECT id FROM queries WHERE id = $lookup")) {
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 200;
                } elseif ($db->getOne("SELECT id FROM queries_archive WHERE id = $lookup")) {
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 200;
                } else {
                        //unknown!
                        $updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 404;
                }

	//odd links
	} elseif(strpos($url,'http://www.geograph.org.uk/submit.php')===0 || strpos($url,'http://www.geograph.org.uk/submissions.php')===0) {
		//dont know why these exist, but dont make sence!
		$updates['HTTP_Status'] =  $updates['HTTP_Status_final'] = 403;

	//others
	} else {

/*todo
http://www.geograph.org.uk/gridref/B7919
http://www.geograph.org.uk/browse.php?p=330460
http://www.geograph.org.uk/search.php?i=10090652
http://www.geograph.org.uk/stuff/fade.php?1=153188&2=4883206#0
http://www.geograph.org.uk/tagged/Arches+at+Bowling#photo=4143159

The idea of this file is to do the bulk of links effientiylu (particully the image and collection links!) 
*/
	        $recordSet->MoveNext();
		continue; //skip for now! could be checked with standard HTTP based check...
	}

	if ($rs['uses'] == 1) {
		$where = "gridimage_link_id = ?";
		$where_value = $rs['gridimage_link_id'];
	} else {
		$where = "url = ?";
		$where_value = $url;
	}

	if (!empty($updates)) {
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

	$recordSet->MoveNext();

        if ($param['sleep'])
                sleep($param['sleep']);
}
$recordSet->Close();

if($done == 0)
	print "Now no urls to do, could use 'scripts/links-3-check-links.php --mode=geograph' to do the straggers!\n";

print "#DONE\n\n";

