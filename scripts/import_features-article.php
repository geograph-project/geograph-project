<?

$d = getcwd();

//this whare is intended as a example really
$param = array('execute'=>0, 'article'=>'Castles-Ireland', 'dest'=>10, 'country'=>'Ireland');

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

function myquote($in) {
	if (is_numeric($in))
		return $in;
	global $db;
	return $db->Quote($in);
}

#####################################################

$article_id = $db->getOne("SELECT article_id FROM article WHERE url = ".$db->Quote($param['article']));
$feature_type_id = $param['dest'];

#####################################################
// first loop though all revisions and find when image was first added!
$images = array();

$sql = "SELECT content,modifier,update_time FROM article_revisions WHERE article_id = $article_id ORDER BY article_revision_id";
$recordSet = $db->Execute($sql);
print "Revisions: ".$recordSet->RecordCount()."\n";
while (!$recordSet->EOF) {
        $r =& $recordSet->fields;

	$content = preg_replace_callback('/\[image id=(\d+)/', function($m) use($r) {
		global $images;
		if (!isset($images[$m[1]]))
                        $images[$m[1]] = array('user_id' => $r['modifier'], 'updated' => $r['update_time']);
        }, $r['content']);

        $content = preg_replace_callback('/\[\[\[?(\d+)\]?\]\]/', function($m) use ($r) {
		global $images;
		if (!isset($images[$m[1]]))
                        $images[$m[1]] = array('user_id' => $r['modifier'], 'updated' => $r['update_time']);
        }, $r['content']);

        $recordSet->MoveNext();
}
$recordSet->Close();

#####################################
// then grab and parse the current content!

$row = $db->getRow("SELECT * FROM article WHERE article_id = $article_id");

$lines = explode("\n",$row['content']);

//$out = $db->getAssoc("DESCRIBE feature_item");
foreach ($lines as $line) {
	print "$line\n";

	if (!preg_match('/^(\*?)\|/',$line,$m))
		continue;

	$bits = preg_split('/\s*\|\s*/',$line);

	if ($m[1]) {//headline!
		$head = $bits;
		print_r($bits);
		continue;
	}

	$updates = array();
	$id = null;

	//todo, calculate a 'table_id' - using crc?
	//$updates['name'] = ..
	//label,category,subcategory,county,gridref,user_id,gridimage_id_user_id etc


	// *| Photo | Region | Castle | Grid Square |

	foreach ($bits as $idx=> $value) {
		if ($head[$idx] == 'Photo') {
			if (preg_match('/\[\[\[?(\d+)\]?\]\]/',$value,$m)) {
				$id = $m[1];
				$updates['gridimage_id'] = $m[1];
				$updates['gridimage_id_user_id'] = @$images[$id]['user_id'];
			}
		} elseif ($head[$idx] == 'Region') {
			if ($value != '-')
				$updates['region'] = $value;
		} elseif ($head[$idx] == 'Castle') {
			$updates['name'] = $value;
		} elseif ($head[$idx] == 'Grid Square' || $head[$idx] == 'Grid square') {
			if ($value != '-')
				$updates['gridref'] = trim($value,'[]');
		}
	}


	if (empty($updates))
		continue;

	$updates['feature_type_id'] = $feature_type_id;
	if (!empty($param['country']))
		$updates['country'] = $param['country'];

	if (empty($updates['name']))
		die("no name?\n");

	if (empty($param['execute'])) {
		print_r($updates);
		continue;
	}

	if (true) {
		$sql = 'INSERT INTO feature_item SET `'.implode('` = ?,`',array_keys($updates)).'` = ?';
        } else {
                $sql = 'UPDATE feature_item SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE feature_item_id = '.$db->Quote($_REQUEST['id']);
        }
	$db->Execute($sql, array_values($updates));
	$item_id = $db->Insert_ID();

		if (!empty($id) && !empty($images[$id])) {

                        foreach ($updates as $key => $value) {
                                if ($key == 'gridimage_id') {
                                        $inserts = array();
                                        $inserts['feature_item_id'] = $item_id;

                //store the type_id and table_id - just in case a future import does a 'replace into'!!
                $inserts['feature_type_id'] = $feature_type_id;
                $inserts['table_id'] = @$updates['table_id'];

                                        $inserts['user_id'] = $images[$id]['user_id'];
                                        $inserts['field'] = $key;
                                        $inserts['oldvalue'] = '';
                                        $inserts['newvalue'] = $value;
					$inserts['updated'] = $images[$id]['updated'];

                                        $db->Execute('INSERT INTO feature_item_log SET `'.implode('` = ?,`',array_keys($inserts)).'` = ?',array_values($inserts));
                                }
                        }
		}

}

