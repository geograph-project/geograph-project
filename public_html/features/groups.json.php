<?

require_once('geograph/global.inc.php');

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$id = intval($_GET['id']);
//$row = $db->getRow("SELECT * FROM feature_type WHERE feature_type_id = $id AND status > 0");

$sql = array();
        $sql['tables'] = array();
        $sql['tables'][] = 'feature_item';
	$sql['tables'][] = 'left join gridimage_search gi using (gridimage_id)';

$sql['wheres'] = array();
$sql['wheres'][] = "feature_type_id = $id";
$sql['wheres'][] = "status > 0";

if (isset($_GET['gridimage']) && strlen($_GET['gridimage'])) {
	if ($_GET['gridimage'])
		$sql['wheres'][] = "gridimage_id > 0 AND gi.gridimage_id IS NOT NULL";
	else
		$sql['wheres'][] = "gridimage_id = 0";
}

//its a function, because may need to call it repretely (with different skips!)
function setFilters($skip = '') {
	global $sql,$db;
	foreach (array('name','label','category','subcategory','county','country','region') as $col) {
		if ($col != $skip && isset($_GET[$col]) && $_GET[$col] != '.any.') { //use a fake value for any, so can filter on blank string!!
			if ($_GET[$col] == '.nonblank.')
				$sql['wheres'][$col] = "`$col` != ''"; //this still works if a single space, collations 'rtrim'
			elseif ($col == 'name')
				$sql['wheres'][$col] = "`$col` LIKE ".$db->Quote($_GET[$col]."%");
			else
				$sql['wheres'][$col] = "`$col` = ".$db->Quote($_GET[$col]);

		} elseif (isset($sql['wheres'][$col]))
			unset($sql['wheres'][$col]);
	}
}

##############################################

$sql['columns'] = 'count(*)';
setFilters();

$query = sqlBitsToSelect($sql);
$count = $db->getOne($query);

$sql['limit'] = 100;

//note, that we CANT skip further processing if no $count, as there might still be results when skip each filter!

##############################################

header("Content-Type:application/json");
print "{";

print "\"count\":$count\n"; $sep = ",";

foreach (array('name','category','subcategory','county','country','region') as $col) {
	//todo, check the col is 'active' on this dataset!

	if ($col == 'name') {
		$sql['columns'] = "UPPER(SUBSTRING($col,1,1)) as $col,count(*) as count";
		$sql['group'] = "SUBSTRING($col,1,1)";
	        $sql['order'] = "$col ASC";
	} else {
		$sql['columns'] = "$col,count(*) as count";
		$sql['group'] = $col;
	        $sql['order'] = '`count` DESC';
	}
	//set the filters repeatedly, because we need to IGNORE the filter on the current column
	setFilters($col);

        $query = sqlBitsToSelect($sql);

	if ($data = $db->getAll($query)) {
		print "$sep".json_encode($col).": ".json_encode($data)."\n";
		$sep = ",";
	}
}

print "}";


