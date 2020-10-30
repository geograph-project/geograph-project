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
        'number'=>1000,   //number to do each time
        'sleep'=>0,    //sleep time in seconds
	'mode'=>'new',
);

$HELP = <<<ENDHELP
    --mode=new|update   : mode (new)
    --sleep=<seconds>   : seconds to sleep between calls (0)
    --number=<number>   : number of items to process in each batch (10)
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$db->Execute("SET SESSION group_concat_max_len = 1000000");

$tables = $db->getAssoc("show table status in geograph_tmp like 'content_with_links'");

if (empty($tables) || strtotime($tables['content_with_links']['Update_time']) < (time() - 60*60*24)) {

	if (!empty($tables['content_with_links'])) {
		$sql = "drop table geograph_tmp.content_with_links";
		print "$sql;\n\n";

		$db->Execute($sql) or die($db->ErrorMsg());
	}

	$sql = "select content_id, c.created, c.updated, foreign_id, source, COALESCE(b.content, g.post_text, a.content, s.comment, t.descr) AS content, 0 as skip
from content c
left join blog b on (source = 'blog' AND b.blog_id = foreign_id AND (b.content LIKE '%http%' OR b.content LIKE '%www.%') )
left join geobb_posts g on (source IN ('gallery','themed') AND g.topic_id = foreign_id AND (g.post_text LIKE '%http%' OR g.post_text LIKE '%www.%') )
left join article a on (source = 'article' AND a.article_id = foreign_id AND (a.content LIKE '%http%' OR a.content LIKE '%www.%') )
left join snippet s on (source = 'snippet' AND s.snippet_id = foreign_id AND (s.comment LIKE '%http%' OR s.comment LIKE '%www.%') )
left join geotrips t on (source = 'trip' AND t.id = foreign_id AND (t.descr LIKE '%http%' OR t.descr LIKE '%www.%') )
where source in ('blog','gallery','themed','article','snippet','trip')
and (b.content IS NOT NULL OR g.post_text IS NOT NULL OR a.content IS NOT NULL OR s.comment IS NOT NULL OR t.descr IS NOT NULL)
group by content_id, g.post_text
order by null
limit {$param['number']}";

	//todo, above with "create table geograph_tmp.content_with_links " (and remove the limit!)

	$sql = "create table geograph_tmp.content_with_links ".str_replace("limit {$param['number']}",'',$sql);
	print "$sql;\n\n";

	$db->Execute($sql) or die($db->ErrorMsg());


	$sql = "alter table geograph_tmp.content_with_links add unique_id int unsigned not null auto_increment primary key, add index(`content_id`)";
	print "$sql;\n\n";

	$db->Execute($sql) or die($db->ErrorMsg());

}


$sql = "select c.* from geograph_tmp.content_with_links c where 1 and source = 'snippet' and skip = 0 group by unique_id limit {$param['number']}";
print "$sql;\n\n";

#####################################################
if ($param['mode'] == 'new') {

	$sql = str_replace('where ','left join gridimage_link l using(content_id) where ', $sql);
	$sql = str_replace('where ','where l.gridimage_link_id IS NULL AND ', $sql);

#####################################################
} elseif ($param['mode'] == 'update') {

	$sql = str_replace('select ',"select max(last_found) as last_link,group_concat(url separator ' ') as urls, ", $sql);
	$sql = str_replace('where ','inner join gridimage_link l using(content_id) where ', $sql);
	$sql = str_replace('where ',"where next_check < '9999' AND parent_link_id = 0 AND ", $sql);
	$sql = str_replace('order ','HAVING updated > last_link order', $sql);

#####################################################
} elseif ($param['mode'] == 'gone') {

	//todo, if using the material view, this will need updating (ie links left join tmp where tmp is null and l.content > 0 )
	$sql = str_replace('select ',"select group_concat(url separator ' ') as urls,", $sql);
	$sql = str_replace('where ','inner join gridimage_link l using(content_id) where ', $sql);
	$sql = str_replace('where ',"where next_check < '9999' AND parent_link_id = 0 AND ", $sql);
	$sql = preg_replace('/AND \((\w+\.\w+) LIKE /',' AND NOT(\1 LIKE ', $sql);

#####################################################
} elseif ($param['mode'] == 'all') {

	//temp mode, just for initial commissioning! (new mode is actully rather inefficent

	$sql = "select c.* from geograph_tmp.content_with_links c"; //no limit!

#####################################################
} else
	die("unknown mode\n");

print "$sql;\n\n";

//if ($param['mode'] != 'all')
//	exit; //currentyl doest have the content_id column!

$done = 0;
$recordSet = $db->Execute($sql);

print "Rows = ".$recordSet->RecordCount()."\n";

$bindts = $db->BindTimeStamp(time());

while (!$recordSet->EOF)
{
	//some people do " also >http://www.ge...", which breaks our 'anti-HTML' extraction
	$recordSet->fields['content'] = preg_replace('/ >http/',' > http',$recordSet->fields['content']);


$all = array();

	if (preg_match_all('/(?<!["\'>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/',$recordSet->fields['content'],$m)) {
		foreach ($m[1] as $url)
			$all[$url]=1;
	}

	if (preg_match_all('/(?<![\/F\.])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/',$recordSet->fields['content'],$m)) {
		foreach ($m[1] as $url)
			$all[$url]=1;
	}

	if (preg_match_all('/\[url=(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)\]/',$recordSet->fields['content'],$m)) {
		foreach ($m[1] as $url)
			$all[$url]=1;
	}

	#print $recordSet->fields['content'];
	#print "<hr><pre>";
	#print_r($m1);
	#print_r($m2);
	#print_r($recordSet->fields['urls']);
	#exit;

if (empty($all)) {
	die("No URLS found.\n".print_r($recordSet->fields,true));


	$db->Execute("UPDATE geograph_tmp.content_with_links SET skip=1 WHERE unique_id = {$recordSet->fields['unique_id']}");
	print "SKIP ";
	$recordSet->MoveNext();
	continue;



}

	$all = array_keys($all);

	$urls = array();
	if (!empty($recordSet->fields['urls'])) {
		foreach (explode(' ',$recordSet->fields['urls']) as $url) {
			$urls[$url] = 1;
		}
	}

print_r($all);

	foreach ($all as $url) {
		if (strpos($url,'http') !== 0) {
			$url = "http://$url";
		}
		$qurl = $db->Quote($url);
		$rows = $db->getAll("SELECT * FROM gridimage_link WHERE url = $qurl ORDER BY content_id = {$recordSet->fields['content_id']} DESC LIMIT 2");

		if (count($rows)) {
			$found_on_image = 0;
			foreach ($rows as $row) {
				if ($row['content_id'] == $recordSet->fields['content_id']) {
					$found_on_image=$row;
				}
			}
			if (!empty($found_on_image)) {
				//existing row on this image, needs updating
				if ($found_on_image['next_check'] > '9999') //the link was previouslly removed
					$db->Execute("UPDATE gridimage_link SET next_check = NOW(),last_found=NOW() WHERE url = $qurl AND content_id = {$recordSet->fields['content_id']}");
				else
					$db->Execute("UPDATE gridimage_link SET last_found=NOW() WHERE url = $qurl AND content_id = {$recordSet->fields['content_id']}");
				print "#";
			} else {
				//existing row on other image, needs duplicating!
				$row = $rows[0];
				unset($row['gridimage_link_id']);
				unset($row['gridimage_id']);
				$row['content_id'] = $recordSet->fields['content_id'];
				$row['created'] = $bindts;
				if ($param['mode'] == 'update') {
					$row['first_used'] = $recordSet->fields['updated'];
				} else
					$row['first_used'] = $recordSet->fields['created']; //hopefully close enough!
				$row['last_found'] = $bindts;

				$db->Execute('INSERT INTO gridimage_link SET `'.implode('` = ?,`',array_keys($row)).'` = ?',array_values($row));
				$done++;
				print ".";
			}
		} else {
			//brand new link, insert it!
			$sql = "INSERT INTO gridimage_link SET
				content_id = {$recordSet->fields['content_id']},
				url = $qurl,
				first_used = NOW(),created = NOW(),last_found=NOW()";
			$db->Execute("$sql");
			$done++;
			print "+";
		}
		if (isset($urls[$url])) {
			unset($urls[$url]);
		}
	}

	if (count($urls)) {
		foreach ($urls as $url => $dummy) {
			print "\nDELETING: $url from {$recordSet->fields['content_id']}\n";
			$qurl = $db->Quote($url);
			$sql = "UPDATE gridimage_link SET next_check = '9999-01-01'
				WHERE content_id = {$recordSet->fields['content_id']}
				AND url = $qurl";
			$db->Execute("$sql");
		}
		$done++;
	}

	print "{$recordSet->fields['content_id']} ";

	$recordSet->MoveNext();
}
$recordSet->Close();

if ($done) {
	print "Links processed so should go again!\n";
}

print "DONE!\n";


