<?

//these are the arguments we expect
$param=array('execute'=>0,'query'=>'bridge','limit'=>100,'second'=>false , 'algo' => 'lingo');

//algos from "GET http://cake-pvt:8081/dcs/components | grep algo"  (the id=...!) 

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

   $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());

	require '3rdparty/Carrot2.class.php';
	$carrot = Carrot2::createDefault();

############################################

	if ($param['query'] == 'sea arch')
		$param['query'] = "(Sea Arch) | (Arch Coastal) | (Arch @landcover (sediment | Unknown | Saltwater))";


	$limit = intval($param['limit']);
        $query = $sph->Quote($param['query']);

	if ($limit > 1000)
		$limit .= " OPTION max_matches=".($limit+100);

	$cols = 'tags';
	$cols = 'county,decade,format,grid_reference,hectad,imageclass,imageclass,myriad,place,snippets,subjects,tags,takenday,takenmonth,takenyear,terms,types,scenti';
	#contexts,country
	$recordSet = $sph->Execute("SELECT id,title,realname,$cols FROM sample8A WHERE MATCH($query) ORDER BY sequence ASC LIMIT $limit");
	$lookup = array();
	$reverse = array();
	while (!$recordSet->EOF) {
		$row =& $recordSet->fields;
		$lookup[] = $row;
		$tags = array();
		foreach($row as $key => $value) {
			if ($key == 'id' || $key == 'title' || $key == 'realname') {
				continue;
			} elseif (strpos($value,'_SEP_') !== FALSE) {
				foreach (explode('_SEP_',$value) as $word) {
					$tags[trim($word)]=1;
				}
			} elseif (!empty($value) && is_numeric($value)) {
				$hash = hash('md5',$value);
				$reverse[$hash] = $value;
				$tags[$hash]=1;
			} else {
				$tags[trim($value)]=1;
			}
		}

		$carrot->addDocument(
			(string)$row['id'],
			(string)utf8_encode(htmlentities($row['title'])),
			(string)utf8_encode(htmlentities(implode('. ',array_keys($tags))))
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

	if ($param['second']) { //count($c) > 50) {

ini_set('display_errors',1);

		print str_repeat('#',78)."\n\n";
		//cluster the labels!
		$labels = array();
		foreach ($c as $cluster)
			$labels[] = $cluster->label;
print "C=".count($labels)."\n";
		$carrot->clearDocuments();
		foreach ($labels as $idx => $label)
	                $carrot->addDocument(
        	                (string)$idx.$label,
                	        (string)utf8_encode(htmlentities($label)),
	                        (string)utf8_encode(htmlentities($label))
        	        );
	        $c = $carrot->clusterQuery('Castle',true);
                usort($c, "cmp");

print "C=".count($c)."\n";

	        foreach ($c as $cluster) {
	                $count = count($cluster->document_ids);
        	        printf("%5d. %s\n",$count,$cluster->label);

	                foreach ($cluster->document_ids as $sort_order => $document_id) {
                	        $label = $labels[$document_id];
        	                print "$label, ";
	                }
			print "\n\n";
		}
	}


	print "done.\n";

