<?

//these are the arguments we expect
$param=array(
	'full'=>0, //show actual documents
	'sort'=>1, //sort by label
	'mysql'=>"select gridimage_id, title, comment from gridimage_search where comment != '' order by gridimage_id desc",
	'sphinx'=>"",//"select id, title, '' from sample8",
	'limit'=>100,
	'query'=>'',
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (count($argv) == 1) //empty!
        print "php {$argv[0]} --mysql=".escapeshellarg($param['mysql'])."\n";



$start = microtime(true);

if (!empty($param['sphinx'])) {
  	$sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());
	$ADODB_FETCH_MODE = ADODB_FETCH_NUM;

	//dont bother with limit, as sphinx implicit has LIMIT 20 anyway!

	$lookup = $sph->getAll($param['sphinx']);
} else {
	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_NUM;

	if (!preg_match('/limit \d+/i',$param['mysql']))
		$param['mysql'] .= " LIMIT ".$param['limit'];

	$lookup = $db->getAll($param['mysql']);
}

$end = microtime(true);

if (empty($lookup)) {
	print "no rows\n";
	print mysql_error()."\n";
	exit;
}


printf("Got %d rows in %.3f seconds\n",count($lookup),$end-$start);

############################################

	require '3rdparty/Carrot2.class.php';
	$carrot = Carrot2::createDefault();

############################################

$start = microtime(true);

	foreach ($lookup as $row) {
		$carrot->addDocument(
			$row[0],
			utf8_encode(htmlentities($row[1])),
			utf8_encode(htmlentities($row[2]))
		);
	}

	$c = $carrot->clusterQuery($param['query']);

$end = microtime(true);

printf("Got %d clusters in %.3f seconds\n",count($c),$end-$start);

############################################

		function cmp(&$a, &$b) {
		    return strcmp($a->label,$b->label);
		}

	if ($param['sort']) {
		usort($c, "cmp");
	}

############################################

	if (!$param['full']) {
		$max = 0;
		foreach ($c as $cluster) {
			$l = strlen($cluster->label);
			if ($l>$max) $max=$l;
		}

		foreach ($c as $cluster) {
			$count = count($cluster->document_ids);
			printf("%5d. %-{$max}s   %7.1f\n",$count,$cluster->label,$cluster->score);

			//print "{$cluster->label}   x{$cluster->score}    ($count docs)\n";
		}
		//print_r($c);
		exit;
	}

############################################

	foreach ($c as $cluster) {
		$count = count($cluster->document_ids);
		printf("%5d. %s\n",$count,$cluster->label);

		$l2 = mysql_real_escape_string($cluster->label);
		foreach ($cluster->document_ids as $sort_order => $document_id) {
			$row = $lookup[$document_id];
			print "\t\t{$row[0]}\t{$row[1]}\n";
		}
	}

############################################

	print "done.\n";

