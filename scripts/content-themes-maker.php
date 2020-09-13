<?

//these are the arguments we expect
$param=array('execute'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


	require '3rdparty/Carrot2.class.php';
	$carrot = Carrot2::createDefault();

	$limit = 100000;
	$crit = " source NOT IN ('gsd','themed') AND `type` = 'info'";
	$crit = " source IN ('article','gallery') AND `type` = 'info'";
//	$crit = " source IN ('snippet')";

	$recordSet = $db->Execute("SELECT content_id,title,extract,url FROM content WHERE $crit LIMIT $limit") or die(mysql_error()."\n");
	$lookup = array();
	while (!$recordSet->EOF) {
		$row =& $recordSet->fields;
		$lookup[] = $row['content_id'];
		$carrot->addDocument(
			(string)$row['url'],
			(string)utf8_encode(htmlentities($row['title'])),
			str_replace(str_replace('...','',$row['title']),'',strip_tags(str_replace('<br>',' ',utf8_encode(htmlentities($row['extract'])))))
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

	$sql = "delete from content_group where source in ('carrot2','sphinx')";
	//todo this might need updateing to be only delete non overlapping
	$db->Execute($sql);


	foreach ($c as $cluster) {
		$count = count($cluster->document_ids);
		printf("%5d. %s\n",$count,$cluster->label);

		$l2 = mysql_real_escape_string($cluster->label);
		foreach ($cluster->document_ids as $sort_order => $document_id) {
			$content_id = $lookup[$document_id];
			$sql = "INSERT INTO content_group SET content_id = $content_id, label = '$l2',score = {$cluster->score},sort_order=$sort_order,source='carrot2'";
			//print "$sql;\n";
			$db->Execute($sql);
		}

		$ids = getSphinxIds("@(title,extract) ".$cluster->label." @source -themed @type info",15);
		if (count($ids)) {
			foreach ($ids as $sort_order => $content_id) {
				$sql = "INSERT INTO content_group SET content_id = $content_id, label = '$l2',score = {$cluster->score},sort_order=$sort_order,source='sphinx'";
				//print "$sql;\n";
				$db->Execute($sql);
			}
		}
	}

	print "done.\n";

function getSphinxIds($q,$limit = 15,$index = 'content_stemmed') {
	global $CONF;
	static $sph;
	if (empty($sph)) {
		$sph = GeographSphinxConnection('sphinxql',false);
	}
	$query = $sph->Quote($q);
	$sql = "SELECT id FROM $index WHERE MATCH($query) LIMIT $limit";
	return $sph->getCol($sql);
}


