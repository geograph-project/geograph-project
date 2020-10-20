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
        'sleep'=>4,    //sleep time in seconds
);

$HELP = <<<ENDHELP
    --mode=new|retry    : mode=retry, to specically try ones requested
    --sleep=<seconds>   : seconds to sleep between calls (4)
    --number=<number>   : number of items to process in each batch (10)
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//this is not actully the link check bot, but gives something so can contact us!
$ua = 'Mozilla/5.0 (Geograph LinkCheck Bot +http://www.geograph.org.uk/help/bot)';
ini_set('user_agent',$ua);

if (!empty($param['mode']) && $param['mode'] == 'retry') {

	$sql = "SELECT gridimage_link_id,url,first_used FROM gridimage_link
        WHERE archive_url = '' AND archive_requested NOT LIKE '0000%' AND next_check < '9999-00-00'
		AND updated < DATE_SUB(NOW(),interval 2 hour)
		AND archive_checked < DATE_SUB(NOW(),interval 7 day)
        GROUP BY url ORDER BY updated LIMIT {$param['number']}";

} else {

	$sql = "SELECT gridimage_link_id,url,first_used FROM gridimage_link
	WHERE archive_checked LIKE '0000%' AND next_check < '9999-00-00'
	AND url NOT like '%geograph.org.uk/%' AND url NOT like '%geograph.ie/%' AND parent_link_id = 0
        AND url NOT LIKE 'http://web.archive.org/web/%'
        AND url NOT LIKE 'https://web.archive.org/web/%'
	GROUP BY url ORDER BY first_used LIMIT {$param['number']}";
}


$timetravelurl = "http://timetravel.mementoweb.org/api/json/";

if (!empty($CONF['timetravel_url'])) {
	$timetravelurl = $CONF['timetravel_url']."/api/json/"; //in this script we only use the json API, so add the prefix. BUt config file allows for other API use
} // else {
	//die("Local timetravel/memgate not installed, can get away without and use the public one above, but better to use local
//}

//we now have a local installation of https://github.com/oduwsdl/memgator
//test with: GET http://tea-pvt:1208/api/json/20160801/http://www.devizesheritage.org.uk/railway_devizes.html



$recordSet = $db->Execute($sql);
while (!$recordSet->EOF) {
	$bindts = $db->BindTimeStamp(time());
	$row = $recordSet->fields;
	$updates = array();

	if (!empty($timetravelurl)) {
/*
{
	"timegate_uri": "http://timetravel.mementoweb.org/timegate/http://cnn.com",
	"timemap_uri": {
		"json_format": "http://timetravel.mementoweb.org/timemap/json/http://cnn.com",
		"link_format": "http://timetravel.mementoweb.org/timemap/link/http://cnn.com"
	},
	"mementos": {
		"next": {
			"datetime": "2013-01-15T10:38:55Z",
			"uri": [
				"http://web.archive.org/web/20130115103855/http://www.cnn.com/",
				"http://wayback.archive-it.org/all/20130115103855/http://www.cnn.com/"
			]
		},
		"last": {
			"datetime": "2017-01-21T15:22:30Z",
			"uri": [
				"http://web.archive.org/web/20170121152230/http://www.cnn.com/"
			]
		},
		"prev": {
			"datetime": "2013-01-15T09:55:05Z",
			"uri": [
				"http://web.archive.org/web/20130115095505/http://cnn.com/"
			]
		},
		"first": {
			"datetime": "1996-10-13T23:07:10Z",
			"uri": [
				"http://arquivo.pt/wayback/19961013230710/http://www.cnn.com/index.html"
			]
		},
		"closest": {
			"datetime": "2013-01-15T10:09:53Z",
			"uri": [
				"http://web.archive.org/web/20130115100953/http://www.cnn.com/",
				"http://wayback.archive-it.org/all/20130115100953/http://www.cnn.com/"
			]
		}
	},
	"original_uri": "http://cnn.com"
}
		//http://timetravel.mementoweb.org/api/json/20130115102033/http://cnn.com
*/
		if ($row['first_used'] < '2000-00-00')
			$row['first_used'] = '2010'; //just to have somehting?


		$url = $timetravelurl.preg_replace("/[^\d]/",'',$row['first_used'])."/".($row['url']); //the URL SHOULDNT be urlencoded!
		do {
			print str_repeat("#",80)."\n$url\n";

			$sleep = 4;
			do {
				if ($sleep > 4) { //skip the sleep on the very first try.
					if ($sleep > 500)
						die("still failing, lets give up for now!\n");
					print "$status -> sleep($sleep) and try one more time\n";
					sleep($sleep);
				}

				$status = null;
				$location = null;
	        	        $data = file_get_contents($url);
				print_r($http_response_header);
				foreach ($http_response_header as $c => $header) {
                        		if (preg_match('/^HTTP\/\d+.\d+ +(\d+)/i',$header,$m)) {
						$status = $m[1];
					} elseif(preg_match('/^Location:(.*)/i',$header,$m)) {
						$location = $m[1];
						//sometimes get a errorent redirect!
						//   [6] => Location: http://timetravel.mementoweb.org/list/20121008105819/http://www.castlexplorer.co.uk/england/carisbrooke/carisbrooke.php
					}
				}
				$sleep*=2;

			} while ($status == '429' || ($location && $status != '404'));  //over query limit OR the odd redirect
					//(we do also sometimes get 404 too, but lets not retry them, assume it means non available!)


	                $decode = json_decode($data,true);

		//this is tricky, if doesnt end in punct, break; if found a link, break; final clause is just to only conditionally run it
		} while(preg_match('/[^\w]$/', $url) && empty($decode['mementos']['closest']) && ($url = preg_replace('/[^\w]$/', '', $url)) && (sleep(2) == 0) );


		if (!empty($decode['mementos']) && !empty($decode['mementos']['closest'])) {

			//todo, sometimes the reported cloested can be a 404, so check others
			// example on  http://timetravel.mementoweb.org/api/json/20160801/http://www.devizesheritage.org.uk/railway_devizes.html
			// in this example prev is ok, so could check each in turn to see if better one than closest. 

			$a = $decode['mementos']['closest']['uri'];
			$updates['archive_url'] = is_array($a)?array_shift($a):$a; //memgator returns string, whereas mementoweb.org returns array!
			$updates['archive_date'] = $db->BindTimeStamp(strtotime($decode['mementos']['closest']['datetime']));
			$updates['archive_checked'] = $bindts;
		} else { //elseif(!empty($decode) && empty($row['archive_url'])) {
			//memotos returns 404, if the URL has no copies, but may also return 429. need to log these somwhere?
			$updates['archive_checked'] = $bindts;
		}

	} elseif (true) {
		/* https://archive.org/help/wayback_api.php
		The format of the timestamp is 1-14 digits (YYYYMMDDhhmmss) ex:
		//http://archive.org/wayback/available?url=example.com&timestamp=20060101
		{
		    "archived_snapshots": {
		        "closest": {
		            "available": true,
		            "url": "http://web.archive.org/web/20060101064348/http://www.example.com:80/",
		            "timestamp": "20060101064348",
		            "status": "200"
		        }
		    }
		}
		*/



		$url = "http://archive.org/wayback/available?url=".urlencode($row['url']);
		if ($row['first_used'] > '2000-00-00')
			$url .= "&timestamp=".preg_replace("/[^\d]/",'',$row['first_used']);

	if (true) {
		print str_repeat("#",80)."\n";
		print "$url\n";
	}

		$data = file_get_contents($url);
		$decode = json_decode($data,true);

		if (!empty($decode['archived_snapshots']) && !empty($decode['archived_snapshots']['closest'])) {
			$updates['archive_url'] = $decode['archived_snapshots']['closest']['url'];
			$updates['archive_date'] = $db->BindTimeStamp(strtotime($decode['archived_snapshots']['closest']['timestamp']));
			$updates['archive_checked'] = $bindts;
		} elseif(!empty($decode) && empty($row['archive_url'])) {
			$updates['archive_checked'] = $bindts;
		}
	}

	if (empty($updates['archive_url']) && $param['mode'] == 'retry') {
		//retry mode uses updated to avoid rechecking the same url!
		$updates['updated'] = $bindts;
	}

	if (!empty($updates)) {

		//$where = "gridimage_link_id = {$row['gridimage_link_id']}";

		$where = "url = ?";
		$where_value = $row['url']; //need to split this out, if the value contains ? then complains: Input Array does not match ?

		if ($row['first_used'] != '2010')
			$where .= " AND first_used LIKE ".$db->Quote(substr($row['first_used'],0,7).'%'); //add first_used filter, in case it links added at differnt times (and hence have different snapshots, but may as well do the same month...;
                else
                        $where .= " AND archive_url = ''"; //but lets no overwrite specific ones

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
