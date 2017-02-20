<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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
	'mode' => 'archive',
        'number'=>10,   //number to do each time
        'sleep'=>4,    //sleep time in seconds
);

$HELP = <<<ENDHELP
    --mode=archive (where found in archive) | =all (all URLs) | =geograph (geograph internal links, we know http status works well here!) 
    --sleep=<seconds>   : seconds to sleep between calls (4)
    --number=<number>   : number of items to process in each batch (10)
ENDHELP;

ini_set("default_socket_timeout",15);
//print ini_get("default_socket_timeout")."\n";exit;

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//this is not actully the link check bot, but gives something so can contact us!
$ua = 'Mozilla/5.0 (Geograph LinkCheck Bot +http://www.geograph.org.uk/help/bot)';
ini_set('user_agent',$ua);

if ($param['mode'] == 'archive') {
$sql = "SELECT gridimage_link_id,gridimage_id,url,first_used,archive_url FROM gridimage_link
	WHERE archive_url != '' AND HTTP_Status > 200 AND url not rlike '[[:alpha:][:digit:]/&#]$'
	AND url NOT like '%geograph.org.uk/%' AND url NOT like '%geograph.ie/%' AND parent_link_id = 0
	AND next_check < '2022' AND updated < DATE_SUB(NOW(),interval 24 hour)
	GROUP BY url ORDER BY HTTP_Status DESC,updated ASC LIMIT {$param['number']}";

} elseif ($param['mode'] == 'geograph') {
$sql = "SELECT gridimage_link_id,gridimage_id,url,first_used,archive_url FROM gridimage_link
	WHERE url not rlike '[[:alpha:][:digit:]/&#]$'
	AND url NOT like '%/of/%'
	AND (url like 'http://www.geograph.org.uk/%' OR url like 'http://www.geograph.ie/%') AND parent_link_id = 0
	AND next_check < '2022' AND updated < DATE_SUB(NOW(),interval 24 hour)
	GROUP BY url ORDER BY HTTP_Status DESC,updated ASC LIMIT {$param['number']}";
}

$user_id = 3;

$recordSet = &$db->Execute($sql);
while (!$recordSet->EOF) {
	$bindts = $db->BindTimeStamp(time());
	$row = $recordSet->fields;
	$updates = array();

	$url=$row['url'];

	print str_repeat("#",80)."\n";
	print_r($row);

	do {
	  	print "$url\n";
		$content = file_get_contents($url);

        } while(preg_match('/[^\w]$/', $url) && empty($content) && ($url = preg_replace('/[^\w]$/', '', $url)) && (sleep(2) == 0) );

	$sqls = array();
	if (strlen($content) && $row['url'] != $url) { //small chance that the link WAS broken, now ok!
		$after = substr($row['url'],strlen($url));

		//this is tricky, sometimes the link is a auto-detected link (from the www.) so comment dosnt actully contain http:// at start
		//eg ... the "fibre-fuel" (www.fibrefuel.co.uk) fired installation ...
		$replace = "REPLACE(comment,".$db->Quote(preg_replace('/^http:\/\//','',$row['url'])).",".
					      $db->Quote(preg_replace('/^http:\/\//','',"$url $after")).")";

                $sqls[] = "INSERT INTO gridimage_ticket SET
                                                                gridimage_id={$row['gridimage_id']},
                                                                suggested=NOW(),
                                                                user_id=$user_id,
                                                                updated=NOW(),
                                                                status='closed',
                                                                notes='Fixing the link. Works without the punctation as part of link.',
                                                                type='minor',
                                                                notify='',
                                                                public='everyone'";

                $sqls[] = "SET @ticket_id := LAST_INSERT_ID()";

                $sqls[] = "INSERT INTO gridimage_ticket_item SELECT
								NULL AS gridimage_ticket_item_id,
                                                                @ticket_id AS gridimage_ticket_id,
                                                                $user_id AS approver_id,
                                                                'comment' AS field,
                                                                comment AS oldvalue,
                                                                $replace AS newvalue,
                                                                'immediate' AS status,
								NOW() AS updated
					FROM gridimage WHERE gridimage_id = {$row['gridimage_id']}";

		$sqls[] = "UPDATE gridimage SET comment = $replace WHERE gridimage_id = {$row['gridimage_id']}";
		$sqls[] = "UPDATE gridimage_search SET comment = $replace WHERE gridimage_id = {$row['gridimage_id']}";

		foreach ($sqls as $sql) {
			print preg_replace("/\s+/",' ',$sql).";\n";
			$db->Execute($sql);
			print "Rows = ".mysql_affected_rows()."\n";
		}
		$updates['next_check'] = '2023-01-01'; //mark the link as deleted!
	} else {
		$updates['updated'] = $bindts; //we order by updated to prevent direct repeats. ideally would have our own column in table??
	}

	if (!empty($updates)) {

		$where = "gridimage_link_id = ?";
		$where_value = $row['gridimage_link_id'];

		$db->Execute($sql = 'UPDATE gridimage_link SET `'.implode('` = ?,`',array_keys($updates))."` = ? WHERE $where",
			array_merge(array_values($updates),array($where_value)) );

		if (true) {
			//print_r($decode);
			print_r($updates);
			print "$sql\n";
			print "Rows = ".mysql_affected_rows()."\n";
//			print_r($db->getAll("SHOW WARNINGS()"));
		}
	}

	if ($param['sleep'])
		sleep($param['sleep']);
        $recordSet->MoveNext();
}
$recordSet->Close();
