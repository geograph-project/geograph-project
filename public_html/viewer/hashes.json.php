<?

require_once('geograph/global.inc.php');

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//$id = intval($_GET['id']);
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
	$sql['wheres'][] = "gi.user_id = $id";
	$sql['wheres'][] = "$hash != ''";

        $sql['columns'] = 'gridimage_id,substr(source,1,1),phash,grid_reference,imagetaken,title';
	$sql['group'] = $hash;
        $sql['order'] = 'NULL';

##############################################

        $query = sqlBitsToSelect($sql);

//the dataset can be big, so streaming!

customGZipHandlerStart();
customExpiresHeader(3600*6,true);

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
/* $str = '';
for($i=0;$i<strlen($r[$hash]);$i+=2) {
	$str.=hex2bin(substr($r[$hash],$i,2));
}
print "b=".trim(base64_encode($str),'=')."\n"; */

                print $sep.json_encode(array_values($recordSet->fields),JSON_PARTIAL_OUTPUT_ON_ERROR);
                $recordSet->MoveNext();
        }
        $recordSet->Close();
}

print "]";


function clamp($min, $max, $current) {
	return max($min, min($max, $current));
}
