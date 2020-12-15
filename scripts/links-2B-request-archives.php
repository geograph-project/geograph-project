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
	'mode'=>'new',
        'number'=>10,   //number to do each time
        'sleep'=>0,    //sleep time in seconds
);

$HELP = <<<ENDHELP
    --mode=new|retry    : retry ones been requested (new)
    --sleep=<seconds>   : seconds to sleep between calls (0)
    --number=<number>   : number of items to process in each batch (10)
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!$db->getOne("SELECT GET_LOCK('".basename($argv[0])."',3600)")) {
        die("unable to get a lock;\n");
}

############################################


//this is not actully the link check bot, but gives something so can contact us!
$ua = 'Mozilla/5.0 (Geograph LinkCheck Bot +http://www.geograph.org.uk/help/bot)';
ini_set('user_agent',$ua);


$sql = "SELECT gridimage_link_id,url,first_used,archive_url FROM gridimage_link
	WHERE archive_url = '' AND archive_checked NOT LIKE '0000%'
	AND HTTP_Status_final IN (200,304)
	AND archive_requested < date_sub(NOW(), INTERVAL 90 DAY)
	AND url NOT LIKE 'http://web.archive.org/web/%'
	AND url NOT LIKE 'https://web.archive.org/web/%'
	GROUP BY url ORDER BY archive_requested ASC,RAND() LIMIT {$param['number']}";
//for now only try archiving 200 OK. Might be ok archiving 301/302 too?



$recordSet = $db->Execute($sql);
while (!$recordSet->EOF) {
	$bindts = $db->BindTimeStamp(time());
	$row = $recordSet->fields;
	$updates = array();

	if (true) {
		//http://web.archive.org/save/http://www.geocities.com/TheTropics/6727/howlssa.htm,gfg

		$url = 'http://web.archive.org/save/'.($row['url']);

        print "URL: ".$url."\n";

                $content = file_get_contents($url);

        print "LEN: ".strlen($content)."\n";
        print "\n";
        print_r($http_response_header);
        print "\n";

		$updates['archive_requested'] = $bindts;
	}

	if (!empty($updates)) {

		$where = "url = ?";
		$where_value = $row['url'];

		$db->Execute($sql = 'UPDATE gridimage_link SET `'.implode('` = ?,`',array_keys($updates))."` = ? WHERE $where",
			array_merge(array_values($updates),array($where_value)) );

		if (true) {
			//print_r($decode);
			print_r($updates);
			print "$sql\n";
			print "Rows = ".$db->Affected_Rows()."\n";
//			print_r($db->getAll("SHOW WARNINGS()"));
		}
	}

	if ($param['sleep'])
		sleep($param['sleep']);
        $recordSet->MoveNext();
}
$recordSet->Close();
