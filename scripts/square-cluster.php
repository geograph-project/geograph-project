<?

//these are the arguments we expect
$param=array('execute'=>0,'square'=>'NS5965', 'limit'=>10000);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	require '3rdparty/Carrot2.class.php';
	$carrot = Carrot2::createDefault();

############################################

	$recordSet = $db->Execute("SELECT gridimage_id,title,comment FROM gridimage_search WHERE grid_reference = '{$param['square']}' LIMIT {$param['limit']}");
	$lookup = array();
	while (!$recordSet->EOF) {
		$row =& $recordSet->fields;

		$lookup[] = $row;
		$carrot->addDocument(
			$row['gridimage_id'],
			utf8_encode(htmlentities($row['title'])),
			utf8_encode(htmlentities($row['comment']))
		);
		$recordSet->MoveNext();
	}
	$recordSet->Close();

	$c = $carrot->clusterQuery();
		function cmp(&$a, &$b) {
		    return strcmp($a->label,$b->label);
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

	$gridsquare_id = $db->getOne("SELECT gridsquare_id FROM gridsquare WHERE grid_reference = '{$param['square']}'");

	$db->Execute("delete gridimage_group.* from gridimage inner join gridimage_group using (gridimage_id) where gridsquare_id = $gridsquare_id and source='carrot2'");
	print "clear.";

	foreach ($c as $cluster) {
		$count = count($cluster->document_ids);
		printf("%5d. %s\n",$count,$cluster->label);

		$l2 = mysql_real_escape_string($cluster->label);
		foreach ($cluster->document_ids as $sort_order => $document_id) {
			$row = $lookup[$document_id];

                        $updates = array();

                        $updates['gridimage_id'] = $row['gridimage_id'];
                        $updates['label'] = $cluster->label;
                        $updates['score'] = floatval($cluster->score);
                        $updates['sort_order'] = $sort_order;
                        $updates['source'] = 'carrot2';

                        $db->Execute('INSERT INTO gridimage_group SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			print ".";
		}
	}

	print "done.\n";

