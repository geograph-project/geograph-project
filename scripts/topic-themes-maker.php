<?

//these are the arguments we expect
$param=array('execute'=>0,'topic'=>30260,'limit'=>100,'second'=>false , 'algo' => 'lingo');

//algos from "GET http://cake-pvt:8081/dcs/components | grep algo"  (the id=...!) 

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	require '3rdparty/Carrot2.class.php';
	$carrot = Carrot2::createDefault();

############################################

	$recordSet = $db->Execute("SELECT gridimage_id,realname,title,comment FROM gridimage_search INNER JOIN gridimage_post USING (gridimage_id)
			WHERE topic_id = {$param['topic']} LIMIT {$param['limit']}");
	$lookup = array();
	while (!$recordSet->EOF) {
		$row =& $recordSet->fields;

		$lookup[] = $row;

		$carrot->addDocument(
			(string)$row['gridimage_id'],
			(string)utf8_encode(htmlentities($row['title'])),
			(string)utf8_encode(htmlentities($row['comment']))
		);

		$recordSet->MoveNext();
	}
	$recordSet->Close();

	$c = $carrot->clusterQuery($param['query'],false, $param['algo']);

		function cmp(&$a, &$b) {
		    return strcmp($a->label,$b->label);
		}
		usort($c, "cmp");

	if (!$param['execute']) {
		foreach ($c as $cluster) {
			$count = count($cluster->document_ids);
			if (isset($reverse[strtolower($cluster->label)]))
				$cluster->label = $reverse[strtolower($cluster->label)];
			print "{$cluster->label}   x{$cluster->score}    ($count docs)\n";
		}
		//print_r($c);
		exit;
	}


	foreach ($c as $cluster) {
		$count = count($cluster->document_ids);
		printf("%5d. %s\n",$count,$cluster->label);

		foreach ($cluster->document_ids as $sort_order => $document_id) {
			$row = $lookup[$document_id];
			print $row['id'].": ".$row['title']." by ".$row['realname']."\n";
		}
		print "\n";
	}


	print "done.\n";

