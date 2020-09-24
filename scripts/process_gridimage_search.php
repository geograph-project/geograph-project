<?

//these are the arguments we expect
$param=array('limit' => 1000);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
$limit = $param['limit'];

print "max = $max, limit = $limit\n";

$tim = time();

for($start = 1; $start <= $max; $start += $limit) {
	$end = $start + $limit -1;

	$where = "gridimage_id BETWEEN $start AND $end";

	print "$where; ";


        $recordSet = $db->Execute("SELECT gi.*
		 FROM gridimage gi LEFT JOIN gridimage_search gv USING (gridimage_id)
		 WHERE (gv.gridimage_id IS NULL OR gi.moderation_status != gv.moderation_status) AND gi.moderation_status in ('accepted','geograph')
		 AND gi.$where");

	$count = $recordSet->recordCount();
	printf("got %d rows at %d seconds; ",$count,time()-$tim);
	if (!$count) {
		$recordSet->Close();
		print "\n";
		continue;
	}

	$count=0;
        while (!$recordSet->EOF) {
		$image = new GridImage();
		$image->_setDB($db);
		$image->_initFromArray($recordSet->fields);

		$image->updateCachedTables();
		print "{$recordSet->fields['gridimage_id']} ";

                $recordSet->MoveNext();
		$count++;
	}
        $recordSet->Close();

	printf("done %d in %d seconds\n",$count,time()-$tim);

}
