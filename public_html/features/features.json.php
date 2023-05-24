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

if (isset($_GET['gridimage']) && strlen($_GET['gridimage'])) { //strlen, not empty to allow =0
	if ($_GET['gridimage'] === '4')
		$sql['wheres'][] = "bound_images = 0";
	elseif ($_GET['gridimage'] === '3')
		$sql['wheres'][] = "gridimage_id = 0 AND gridimage_id_user_id > 0";
	elseif ($_GET['gridimage'] === '2')
		$sql['wheres'][] = "gridimage_id > 0 AND gi.gridimage_id IS NOT NULL AND gridimage_id_user_id IS NULL";
	elseif ($_GET['gridimage'])
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
setFilters();

##############################################

        $sql['columns'] = 'feature_item.*,gi.user_id,title,realname,grid_reference'; //for now
      //  $sql['group'] = '';
        $sql['order'] = '`sorter` ASC';
        $sql['limit'] = clamp(0, 1000, intval($_GET['limit'] ?? 20));

if (!empty($_GET['page'])) {
	$sqlpage = ($_GET['page']-1)* $sql['limit'];
	$sql['limit'] = "$sqlpage,".$sql['limit'];
}

if (!empty($_GET['order']) && preg_match('/^\w+ ?(asc|desc)?$/',$_GET['order'])) {
	$sql['order'] = $_GET['order']; //we've checked it safe above
}

##############################################

        $query = sqlBitsToSelect($sql);

require_once('geograph/conversions.class.php');
$conv = new Conversions;


//the dataset can be big, so streaming!

header("Content-Type:application/json");
print "[";

$sep = '';
$recordSet = $db->Execute($query);

if ($count = $recordSet->RecordCount()) {
        while (!$recordSet->EOF)
        {
                $r =& $recordSet->fields;

                $r['name'] = latin1_to_utf8($r['name']);

		if (!empty($r['gridimage_id'])) {
		        $image = new Gridimage();
		        $image->fastInit($r);
		        $image->db = $db;
			$r['thumbnail'] = $image->getThumbnail(120,120,true);
		}

		/*
                if (empty($r['wgs84_lat']) || $r['wgs84_lat'] < 1) {
                        list($r['wgs84_lat'],$r['wgs84_long']) = $conv->national_to_wgs84($r['nateastings'],$r['natnorthings'],$r['reference_index']);
                }*/

                print $sep.json_encode($recordSet->fields,JSON_PARTIAL_OUTPUT_ON_ERROR);
                $sep = ",\n";
                $recordSet->MoveNext();
        }
        $recordSet->Close();
}

print "]";


function clamp($min, $max, $current)
{
	return max($min, min($max, $current));
}
