<?php

chdir(__DIR__);
require_once 'inspect.inc.php';

###############################################

$join_on  = ''; //added onto the ON() caluse for index_coverage table!
$where = array();
$where[] = 'c.status IS NULL'; // OR updated < date_sub(now(),interval... ) && status LIKE 'excluded:'
$order = "gridimage_id DESC";
$limit = 10;

$power = 2;  //sleep(round(pow($end-$start, $power)));
$power = 1.1;

$mode = 'new'; // 'ie' || 'gb' || 'sample' || 'new'

foreach ($argv as $key => $value) {
	if (!$key) continue; //skip 'self'
	if (is_numeric($value))
		$limit = $value;
	elseif (preg_match('/^\w+$/',$value))
		$mode = $value;
}

$db = mysqli_connect($_SERVER['CONF_DB_CONNECT'], $_SERVER['CONF_DB_USER'], $_SERVER['CONF_DB_PWD'], 'geograph_live');

###############################################

if ($mode === 'homepage') {
	$domains = array('geograph.org.uk','schools.geograph.org.uk','geograph.ie');
	foreach ($domains as $domain) {
		$urls[] = "http://$domain/";
		$urls[] = "https://$domain/";
		if (strpos($domain,'geograph') === 0) {
			$urls[] = "http://www.$domain/";
			$urls[] = "https://www.$domain/";
		}
		if ($domain == 'geograph.org.uk') {
			$urls[] = "https://m.$domain/";
			$urls[] = "https://m.$domain/?lang=cy";
			$urls[] = "https://www.$domain/?lang=cy";
		}
	}
	print_r($urls);
	foreach ($urls as $url)
		record_result($url);

	exit;

} elseif ($mode === 'sets' || $mode === 'sets2') {

	$where[] = "(serial is not null OR manual is not null)";
	$where[] = "d.gridimage_id > 7500000";

	if ($mode === 'sets2')
		$where[] = "gi.reference_index = 2";

	$where = implode(" AND ",$where);

	 $sql = "select gi.grid_reference, coalesce(manual,serial) as serials, gi.reference_index
         from duplication_stat d inner join gridimage_search gi using (gridimage_id)
	 left join index_coverage c on (url = concat('https://www.geograph.org.uk/photoset/',gi.grid_reference,'/',coalesce(manual,serial)) )
         where $where group by coalesce(manual,serial) order by null
         limit $limit";

	$result = mysqli_query($db,$sql) or die(mysqli_error($db)."\n");
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['reference_index'] == 2) {
			$url = "https://www.geograph.ie/photoset/".urlencode($row['grid_reference']).'/'.urlencode($row['serials']);
		} else {
			$url = "https://www.geograph.org.uk/photoset/".urlencode($row['grid_reference']).'/'.urlencode($row['serials']);
		}
		record_result($url);
	}

	exit;

##########################################################
//all the following are loooking at images only!


} elseif ($mode === 'myriad') {
	$table = "duplication_stat";
	$where[] = "gi.grid_reference LIKE 'SH____'";

	$where[] = "serial is null"; //only NON-set images (for now), should still check them later!

        $order = "gridimage_id ASC";

} elseif ($mode === 'update') {
	//material view of these the hectads!
	$table = "index_coverage_tmp_hectad";
	$where[0] = "c.updated < date_sub(now(),interval 60 day)"; //want to OVERWRITE the status is null caluse!

	//$where[0] = "c.status IS NULL"; //want to OVERWRITE the status is null caluse!


} elseif ($mode === 'hectads') {
	//sitemaps done for, B71, NM98, SV80, SW33, SX97, W67
	$list = array('B7_1_', 'NM9_8_', 'SV8_0_', 'SW3_3_', 'SX9_7_', 'W6_7_');

	$list[] = "H4_7"; // a square with lots of "crawled" (from index_coverage_crawled_stat)

	$one = $list[array_rand($list,1)];

	$table = "duplication_stat";
	$where[] = "gi.grid_reference LIKE '$one'";

//	$where[] = "serial is null"; //only NON-set images (for now), should still check them later!

        $order = "gridimage_id ASC";

} elseif ($mode === 'dups') {
	$table = "duplication_stat";
//	$where[] = "duplication_stat.gridimage_id > 7500000";
//	$where[] = "serial is not null";
	$where[] = "serial LIKE '5f%'"; //to to be a "random" cross-section of old and new!

	$order = "gridimage_id ASC";


} elseif ($mode === 'gb' || $mode === 'ie') {
	if ($mode === 'ie') {
		$url = "https://www.geograph.ie/photo/";
		$where[] = "reference_index = 2";
	} else {
		$url = "https://www.geograph.org.uk/photo/";
		$where[] = "reference_index = 1";
	}
	$table = "index_coverage"; //joins to gridimage_search so will only search image urls
	//$where[] = "$table.url LIKE 'http://%'"; //checking for known non-canonical
	$where[] = "$table.url NOT LIKE '$url%'";

	$join_on = " AND c.url = CONCAT('$url',$table.gridimage_id)"; //specifically checking if the canonical URL is already checked

} elseif ($mode === 'sample') {
	$table = "gridimage_sample";
	$where[] = "v<10";

} elseif ($mode === 'retry') {
	$table = "index_coverage";
	$where[0] = "$table.status = ':'"; //want to OVERWRITE the status is null caluse!

} elseif ($mode === 'recrawl') {
	$table = "index_coverage";
	$where[0] = "$table.status LIKE '%crawled%'"; //want to OVERWRITE the status is null caluse!
	$where[] = "$table.crawled > date_sub($table.updated,interval 2 day)"; //ie if crawled very recently, it COULD still of been in progress at the time.

} else { //$mode = 'new'
	if ($mode === 'ireland')
		$where[] = "reference_index=2";

	$table = "newimages";
	$where[] = "lookup LIKE 'Mozilla/5.0 (compatible; Googlebot/%'";
	$order = "newimages.created DESC";
}

//hectads = B71, SW33,W67,SV80,NM98,SX97
//... but should prime form coverage.

###############################################


$where = implode(" AND ",$where);
$sql = "SELECT $table.gridimage_id,gi.reference_index
 FROM $table
 INNER JOIN gridimage_search gi USING (gridimage_id)
 left join index_coverage    c on (c.gridimage_id = $table.gridimage_id $join_on)
 left join index_performance p on (p.gridimage_id = $table.gridimage_id )
 WHERE $where AND p.url IS NULL ORDER BY $order LIMIT $limit";

$sql = "SELECT $table.gridimage_id,gi.reference_index
 FROM $table
 INNER JOIN gridimage_search gi USING (gridimage_id)
 left join index_coverage    c on (c.gridimage_id = $table.gridimage_id $join_on)
 WHERE $where ORDER BY $order LIMIT $limit";

print "$sql;\n";

$result = mysqli_query($db,$sql) or die(mysqli_error($db)."\n");
while ($row = mysqli_fetch_assoc($result)) {
	$id = $row['gridimage_id'];
	if ($row['reference_index'] == 2)
		$url = "https://www.geograph.ie/photo/$id";
	else
		$url = "https://www.geograph.org.uk/photo/$id";

	record_result($url);
}

#############################################

function record_result($url) {
	global $db,$cachefile,$limit,$power;
	static $failtime = 1;

	print "$url = ";

	$start = microtime(true);
	$r = testUrl($url);
	$json = json_decode($r,true);
	$end = microtime(true);

//if (empty($json['inspectionResult'])) {
	//todo, retry, if was a quota error!
//	die("failed!\n");
//} ...actully for now, still record failure (status = ':' will find them!) and they can be retried

	######################################

	$updates = array();
	$updates['url'] = $url;

	if (file_exists($cachefile))
		$updates['updated'] = date('Y-m-d H:i:s', filemtime($cachefile));
	if (!empty($json['inspectionResult']['indexStatusResult']['lastCrawlTime']))
	        $updates['crawled'] = substr($json['inspectionResult']['indexStatusResult']['lastCrawlTime'],0,10);
        if (preg_match('/photo\/(\d+)/',$updates['url'],$m))
                $updates['gridimage_id'] = $m[1];
        if (preg_match('/gridref\/([A-Z]{1,2}\d{4})$/',$updates['url'],$m))
                $updates['grid_reference'] = $m[1];
        switch($json['inspectionResult']['indexStatusResult']['verdict']) {
                case 'NEUTRAL':
                case 'VERDICT_UNSPECIFIED': $verdict = 'Excluded'; break;
                case 'PASS': $verdict = 'Valid'; break;
                case 'PARTIAL': $verdict = 'Warnings'; break;
                case 'FAIL': $verdict = 'Error'; break;
        }
        $updates['status'] = "$verdict: ".utf8_decode($json['inspectionResult']['indexStatusResult']['coverageState']);
        if (!empty($json['inspectionResult']['indexStatusResult']['googleCanonical'])) // && $json['inspectionResult']['indexStatusResult']['googleCanonical'] != $updates['url'])
                $updates['canonical'] = $json['inspectionResult']['indexStatusResult']['googleCanonical'];
        //$updates[''] = $json['inspectionResult']['indexStatusResult'][''];

        //print_r($updates);
	$sql = updates_to_insertwithdup("index_coverage",$updates);
	//print "$sql;\n";

	mysqli_query($db,$sql);

	######################################

	print $json['inspectionResult']['indexStatusResult']['coverageState']."\n";

	if ($limit === '1')
		die("\n"); //avoids the long delay until exits!

	if (empty($json['inspectionResult'])) {
		print " (sleeping for ".$failtime.")\n";
		sleep($failtime);
		$failtime *= 2; //next wait longer!
		if ($failtime>3600)
			$failtime=3600;
	} else {
	        print " (sleeping for ".round(pow($end-$start,$power))." seconds)\r";
        	sleep(round(pow($end-$start, $power)));
	        //sleep(round($end-$start));
		$failtime = 1; //reset it!
	}
}


#############################################


function updates_to_a(&$updates) {
        global $db;
        $a = array();
        foreach ($updates as $key => $value) {
                //NULL
                if (is_null($value)) {
                        $a[] = "`$key`=NULL";
                } else {
                        //converts uk dates to mysql format (mostly) - better than strtotime as it might not deal with uk dates
                        if (preg_match('/^(\d{2})[ \/\.-]{1}(\d{2})[ \/\.-]{1}(\d{4})$/',$value,$m)) {
                                $value = "{$m[3]}-{$m[2]}-{$m[1]}";
                        }
                        //numbers and functions, eg NOW()
                        if (is_numeric($value) || preg_match('/^\w+\(\)$/',$value)) {
                                $a[] = "`$key`=$value";
                        } else {
                                $a[] = "`$key`='".mysqli_real_escape_string($db,$value)."'";
                        }
                }
        }
        return $a;
}

function updates_to_insert($table,$updates) {
        $a = updates_to_a($updates);
        return "INSERT INTO $table SET ".join(',',$a);
}

function updates_to_replace($table,$updates) {
        $a = updates_to_a($updates);
        return "REPLACE INTO $table SET ".join(',',$a);
}

function updates_to_update($table,$updates,$primarykey,$primaryvalue) {
        global $db;
        $a = updates_to_a($updates);
        if (!is_numeric($primaryvalue)) {
                $primaryvalue = "'".mysqli_real_escape_string($db,$value)."'";
        }
        return "UPDATE $table SET ".join(',',$a)." WHERE `$primarykey` = $primaryvalue";
}

function updates_to_insertwithdup($table,$updates) {
        $a = updates_to_a($updates);
        return "INSERT INTO $table SET ".join(',',$a).",`created` = NOW() ON DUPLICATE KEY UPDATE ".join(',',$a);
}


