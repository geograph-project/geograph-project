<?

require_once('geograph/global.inc.php');

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$token=new Token;
if ($token->parse(@$_GET['t'])) {
	$id = intval($token->getValue("id"));
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	exit;
}
$hash = 'phash';

##############################################

$sql = array();

        $sql['tables'] = array();
        $sql['tables'][] = 'gridimage_hash';
	$sql['tables'][] = 'inner join gridimage_search gi using (gridimage_id)';

	$sql['wheres'] = array();
	$sql['wheres'][] = "gi.user_id = $id"; //currently only returning live images - todo, would be to include the temp area images (plus pending?)
	$sql['wheres'][] = "$hash != ''";

        $sql['columns'] = 'gridimage_id,substr(source,1,1),phash,grid_reference,imagetaken,title';
	$sql['group'] = $hash; //removes the duplicate hashes - particully multi size for same image
        $sql['order'] = 'NULL';

##############################################

        $query = sqlBitsToSelect($sql);

//the dataset can be big, so streaming!

customGZipHandlerStart();
customExpiresHeader(3600*6,true);

header('Access-Control-Allow-Origin: https://www.geograph.org.uk');
header("Content-Type:application/json");
print "[";

$sep = '';
$recordSet = $db->Execute($query);

if ($count = $recordSet->RecordCount()) {
        $r =& $recordSet->fields;
        print $sep.json_encode(array_keys($recordSet->fields),JSON_PARTIAL_OUTPUT_ON_ERROR);
        $sep = ",\n";

        while (!$recordSet->EOF) {
                $r =& $recordSet->fields;
		$r['gridimage_id'] = intval($r['gridimage_id']);

                print $sep.json_encode(array_values($recordSet->fields),JSON_PARTIAL_OUTPUT_ON_ERROR);
                $recordSet->MoveNext();
        }
        $recordSet->Close();
}

print "]";


function clamp($min, $max, $current) {
	return max($min, min($max, $current));
}
