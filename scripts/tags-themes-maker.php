<?

//these are the arguments we expect
$param=array('execute'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

   $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());

	require '3rdparty/Carrot2.class.php';
	$carrot = Carrot2::createDefault();

############################################

	$limit = 100;

	$recordSet = $db->Execute("SELECT tag_id,tagtext from tag_stat WHERE tagtext LIKE '%cycle%path%' group by final_id");
	$lookup = array();
	while (!$recordSet->EOF) {
		$row =& $recordSet->fields;

		$lookup[] = $row;
		$carrot->addDocument(
			'',
			utf8_encode(htmlentities($row['tagtext'])),
			''
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

	foreach ($c as $cluster) {
		$count = count($cluster->document_ids);
		printf("%5d. %s\n",$count,$cluster->label);

		$l2 = mysql_real_escape_string($cluster->label);
		foreach ($cluster->document_ids as $sort_order => $document_id) {
			$row = $lookup[$document_id];
			print "\t\t{$row['tagtext']}\n";
		}
	}

	print "done.\n";

