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
        'sleep'=>1,    //sleep time in seconds
);

$HELP = <<<ENDHELP
    --mode=exteral|geograph
    --sleep=<seconds>   : seconds to sleep between calls (0)
    --number=<number>   : number of items to process in each batch (10)
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

set_time_limit(3600*24);


#####################

$sql = "SELECT gridimage_link_id,gridimage_id,content_id,url,first_used,archive_url FROM gridimage_link
        WHERE archive_url != '' AND HTTP_Status > 200 AND url not rlike '[[:alpha:][:digit:]/&#]$'
        AND url NOT like '%geograph.org.uk/%' AND url NOT like '%geograph.ie/%' AND parent_link_id = 0
        AND next_check < '9999-00-00' AND fix_attempted LIKE '0000%'
        GROUP BY url ORDER BY HTTP_Status DESC,updated ASC LIMIT {$param['number']}";

$done = 0;
$recordSet = &$db->Execute("$sql");

$ua = 'Mozilla/5.0 (Geograph LinkCheck Bot +http://www.geograph.org.uk/help/bot)';
ini_set('user_agent',$ua);

while (!$recordSet->EOF)
{
	$bindts = $db->BindTimeStamp(time());
	$bindts10 = $db->BindTimeStamp(time()+3600*24*10);
	$bindts90 = $db->BindTimeStamp(time()+3600*24*90);

	$rs = $recordSet->fields;
	$url = $rs['url'];
	$updates = array();

	print str_repeat('#',80)."\n";
	print "URL: $url\n";

        if ($rs['gridimage_id']) {
                $user_agent = "$ua\r\nReferer: http://{$_SERVER['HTTP_HOST']}/photo/{$rs['gridimage_id']}";
        } elseif ($rs['content_id']) {
                $user_agent = "$ua\r\nReferer: http://{$_SERVER['HTTP_HOST']}".$db->getOne("SELECT url FROM content WHERE content_id = {$rs['content_id']}");
        }
	if ($rs['HTTP_Last_Modified']) {
		$user_agent .= "\r\nIf-Modified-Since: ".$rs['HTTP_Last_Modified'];
	}
	ini_set('user_agent',$user_agent);



	if (!empty($updates)) {
		$where = "url = ?";
		$where_value = $url;


		$db->Execute($sql = 'UPDATE gridimage_link SET `'.implode('` = ?,`',array_keys($updates))."` = ? $extra WHERE $where",
		array_merge(array_values($updates),array($where_value)));

		//print "".$sql."\n\n";
		print "".print_r($updates,1)."\n\n";
	}

	$done++;

	$recordSet->MoveNext();

        if ($param['sleep'])
                sleep($param['sleep']);
}
$recordSet->Close();

print "#DONE\n\n";

