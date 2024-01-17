<?

//these are the arguments we expect
$param=array('count'=>100, 'square'=>'NS5965',  'debug'=>false, 'sleep'=>0, 'repair'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$dbprimary = GeographDatabaseConnection(false);
if (!empty($CONF['db_read_connect'])) {
	$dbreplica = GeographDatabaseConnection(true);

	if (empty($dbreplica->readonly))
		die("no replica\n");
} else {
	 $dbreplica = $dbprimary;
}

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (posix_isatty(STDOUT) && !$param['debug'])
	$param['debug']=1;

############################################

if (!empty($param['repair'])) {
	$squares = $dbprimary->getAssoc($sql = "SELECT grid_reference,gridsquare_id FROM gridsquare
	inner join gridimage_search using (grid_reference) inner join duplication_stat using (gridimage_id)
	WHERE imagecount > 1 AND serial IS NULL
	AND gridimage_id > {$param['repair']} AND same_serial > 1
	GROUP BY gridsquare_id ORDER BY NULL
	LIMIT {$param['count']}");


} elseif (!empty($param['count'])) {
	$squares = $dbprimary->getAssoc($sql = "SELECT grid_reference,gridsquare_id FROM gridsquare
	WHERE imagecount > 1 AND last_stat < last_timestamp
	ORDER BY last_timestamp DESC
	LIMIT {$param['count']}");

} elseif (!empty($param['square'])) {
	$squares = array(
		$param['square'] => $dbreplica->getOne("SELECT gridsquare_id FROM gridsquare WHERE grid_reference = ".$dbreplica->Quote($param['square']))
	);
} else {
	die("specify command\n");
}

print "rows = ".count($squares)."\n";
print "$sql;\n";

############################################

		function cmp(&$a, &$b) {
		    return strcmp($a->label,$b->label);
		}

$values = array();
$done = array();
foreach ($squares as $square => $gridsquare_id) {
	if (empty($gridsquare_id))
		die("unknown id for $square\n");

		//same_user_id not particilly useful (it duplicates user_gridsquare, but important user_id is part of serial!
	$rows = $dbreplica->getAssoc("SELECT gridimage_id,title,md5(comment) as comment,length(comment) as comment_len,tags,imageclass,imagetaken,gi.user_id
		, GROUP_CONCAT(snippet_id ORDER BY snippet_id) as snippets,manual
		FROM gridimage_search gi LEFT JOIN gridimage_snippet USING (gridimage_id)
			LEFT JOIN duplication_stat USING (gridimage_id)
		WHERE grid_reference = '{$square}'
		GROUP BY gridimage_id ORDER BY NULL");

	$matrix = array();
	foreach ($rows as &$row) {
		$row2 = $row;
		unset($row2['manual']);
			//if add centi/naten etc, then would remove from $row2 too! (not part of serial)
		$row['serial'] = md5(serialize($row2)); //excludes gridimage_id! (+manual)
		if (empty($row['tags']) && !empty($row['imageclass']))
			$row['tags'] = $row['imageclass'];
		foreach ($row as $key => $value) {
			if ($key == 'comment_len')
				continue;
			if (empty($value) || ($key == 'comment' && !$row['comment_len']) || $value == '0000-00-00')
				continue;
			@$matrix[$key][$row[$key]]++;
		}
	}

	foreach ($rows as $gridimage_id => &$row) { //this doesnt 'need' the &, as not modifying the row, but without it, $row seems to always refer to one row?!

		//print "$gridimage_id\t: ".implode(", ",$row)."\n";

		$updates= array();
		$updates['gridimage_id'] = $gridimage_id;
		//make sure the updates arrays have all columns for all rows!
		foreach ($row as $key => $value) {
                        if ($key == 'comment_len')
                                continue;
			$updates['same_'.$key] = $matrix[$key][$row[$key]]??'NULL';
		}

		//should always find itself, so will update something. //todo, is to strip postfixes (like numbers)
		foreach ($matrix['title'] as $title => $count) {
			if (strpos($title, $row['title']) === 0) //todo, stripos?
				@$updates['same_title_prefix']+=$count;
		}
		//print "Prefix Count: {$updates['same_title_prefix']}\n";

		if ($updates['same_serial'] > 1)
			$updates['serial']=$dbprimary->Quote($row['serial']);
		else
			$updates['serial']='NULL';

		//todo, this does not do escaping. only ok for numeric updates! (and 'NULL'!)
		$values[] = "(".implode(',',$updates).")";
	}

	if (count($values) > 1000) {
		$sql = "INSERT INTO duplication_stat (`".implode('`,`',array_keys($updates))."`) VALUES ".implode(',',$values);
		$sql .= " ON DUPLICATE KEY UPDATE "; $sep = '';
		foreach ($updates as $key => $value) {
			if ($key == 'gridimage_id')
				continue;
			$sql .= $sep." $key = VALUES($key)";
			$sep = ',';
		}
        	if ($param['debug'])
			print " Saving ".count($values)." ... ";

        	$dbprimary->Execute($sql);
	        if ($param['debug'])
        	        print "# ".$dbprimary->Affected_Rows()." affected\n";

		if (!empty($done))
			$dbprimary->Execute("UPDATE gridsquare SET last_stat = NOW(),last_timestamp=last_timestamp WHERE gridsquare_id IN (".implode(',',$done).")");

		$values = array();
		$done = array();
	}

	######################

	$done[] = $gridsquare_id;

	if ($param['debug'])
		print "$square. ";

	######################

	if (!empty($_SERVER['BASE_DIR']) && file_exists($_SERVER['BASE_DIR'].'/shutdown-sential')) {
        	break; //break, not exit, so the insert still happens!
	}

	if ($param['sleep']) {
		if ($param['sleep'] < 1)
			usleep(intval($param['sleep']*1000000));
		else
			sleep($param['sleep']);
	}
}

if (!empty($values)) {
	//REPLACE INTO is really slow on Innodb, useing DUPLICATE KEY UPDATE is much quicker

	$sql = "INSERT INTO duplication_stat (`".implode('`,`',array_keys($updates))."`) VALUES ".implode(',',$values);
	$sql .= " ON DUPLICATE KEY UPDATE "; $sep = '';
	foreach ($updates as $key => $value) {
		if ($key == 'gridimage_id')
			continue;
		$sql .= $sep." $key = VALUES($key)";
		$sep = ',';
	}

	if ($param['debug'])
		print " Saving ".count($values)." ... ";
		//print "\n\n$sql; ";
	$dbprimary->Execute($sql);
	if ($param['debug'])
        	print "# ".$dbprimary->Affected_Rows()." affected\n";

	if (!empty($done)) {
		$dbprimary->Execute("UPDATE gridsquare SET last_stat = NOW(),last_timestamp=last_timestamp WHERE gridsquare_id IN (".implode(',',$done).")");
		if ($param['debug'])
        		print "# ".$dbprimary->Affected_Rows()." squares affected (on last loop only)\n";
	}
}
