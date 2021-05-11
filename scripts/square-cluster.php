<?

//these are the arguments we expect
$param=array('execute'=>0,'count'=>0,'square'=>'NS5965', 'limit'=>1000, 'query'=>'', 'debug'=>false,'sleep'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	require '3rdparty/Carrot2.class.php';
	$carrot = Carrot2::createDefault();

if (posix_isatty(STDOUT) && !$param['debug'])
	$param['debug']=1;

############################################

if (!empty($param['count'])) {
	$squares = $db->getAssoc("SELECT grid_reference,gridsquare_id FROM gridsquare WHERE imagecount BETWEEN 5 AND {$param['limit']} AND last_grouped < last_timestamp LIMIT {$param['count']}");

} elseif (!empty($param['square'])) {
	$squares = array(
		$param['square'] => $db->getOne("SELECT gridsquare_id FROM gridsquare WHERE grid_reference = ".$db->Quote($param['square']))
	);
} else {
	die("specify command\n");
}

############################################

		function cmp(&$a, &$b) {
		    return strcmp($a->label,$b->label);
		}

foreach ($squares as $square => $gridsquare_id) {
	if (empty($gridsquare_id))
		die("unknown id for $square\n");

	$recordSet = $db->Execute("SELECT gridimage_id,title,comment FROM gridimage_search WHERE grid_reference = '{$square}' LIMIT {$param['limit']}");
	$lookup = array();
	while (!$recordSet->EOF) {
		$row =& $recordSet->fields;

		$lookup[] = $row['gridimage_id'];
		$carrot->addDocument(
			$row['gridimage_id'],
			utf8_encode(htmlentities($row['title'])),
                        strip_tags(str_replace('<br>',' ',latin1_to_utf8($row['comment'])))

		);
		$recordSet->MoveNext();
	}
	$recordSet->Close();

	$c = $carrot->clusterQuery($param['query'],$param['debug']==='2');
	if (empty($c)) {
		die("no results for $square (dieing without processing any more squares)\n");
	}


	usort($c, "cmp");

	if (!$param['execute']) {
		foreach ($c as $cluster) {
			$count = count($cluster->document_ids);
			print "{$cluster->label}   x{$cluster->score}    ($count docs)\n";
		}
		//print_r($c);
		exit;
	}

	if ($param['debug'])
		print "found ".count($c)." clusters for $square\n";

	######################

	$db->Execute("delete gridimage_group.* from gridimage inner join gridimage_group using (gridimage_id) where gridsquare_id = $gridsquare_id and source='carrot2'");
	if ($param['debug'])
		print "clear1\n";

	foreach ($c as $cluster) {
		if ($param['debug']) {
			$count = count($cluster->document_ids);
			printf("%5d. %s ",$count,$cluster->label);
		}

		$values = array();
		foreach ($cluster->document_ids as $sort_order => $document_id) {
                        $updates = array();

                        $updates['gridimage_id'] = $lookup[$document_id];
                        $updates['label'] = $cluster->label;
                        $updates['score'] = floatval($cluster->score);
                        $updates['sort_order'] = $sort_order;
                        $updates['source'] = 'carrot2';

                        //$db->Execute('INSERT INTO gridimage_group SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			//print ".";
			$updates['label'] = $db->Quote($updates['label']);
			$updates['source'] = $db->Quote($updates['source']);
			$values[] = "(".implode(',',$updates).")";
		}
		$sql = "INSERT INTO gridimage_group (`".implode('`,`',array_keys($updates))."`) VALUES ".implode(',',$values);
		$db->Execute($sql);
		if ($param['debug'])
			print ".. ".$db->Affected_Rows()." affected\n";
	}

	######################

	$db->Execute("delete from gridimage_group_stat where grid_reference = ".$db->Quote($param['square']));
	if ($param['debug'])
		print "clear2";

	//copied almost as is from RebuildGridimageGroupStat.class.php (just changed the where clause!)
        $sql = "
                select null as gridimage_group_stat_id, grid_reference, label
                        , count(*) as images, count(distinct user_id) as users
                        , count(distinct imagetaken) as days, count(distinct year(imagetaken)) as years, count(distinct substring(imagetaken,1,3)) as decades
                        , min(submitted) as created, max(submitted) as updated, gridimage_id
                        , SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(submitted ORDER BY submitted),',',2),',',-1) AS `second`
                        , avg(wgs84_lat) as wgs84_lat, avg(wgs84_long) as wgs84_long
                from gridimage_group inner join gridimage_search using (gridimage_id)
                where label not in ('(other)','Other Topics') and grid_reference = '{$square}'
                group by grid_reference, label having images > 1 order by null";
		//TODO, mariadb, supports LIMIT in group_concat! so GROUP_CONCAT(submitted ORDER BY submitted LIMIT 1,1) AS `second` should work!!

	$db->Execute("INSERT INTO gridimage_group_stat $sql");
	if ($param['debug'])
		print " grouped\n";

	######################

	$db->Execute("UPDATE gridsquare SET last_grouped = NOW() WHERE gridsquare_id = $gridsquare_id");

	######################

	print "done $square.\n";
	if ($param['sleep'])
		sleep($param['sleep']);
	$carrot->clearDocuments();
}
