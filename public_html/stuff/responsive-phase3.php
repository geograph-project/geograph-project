<?

require_once('geograph/global.inc.php');
init_session();

ini_set('display_errors',1);

$db = GeographDatabaseConnection(true);

$CONF['template'] = 'resp'; //need to do BEFORE new GeographPage!

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$smarty->assign('responsive',true); //for this page, we force respsonive, so can open this page on mobile!

$smarty->display('_std_begin.tpl',true);

$db->Execute('USE geograph_live');
$domain = $db->getOne("SELECT domain FROM responsive_domain WHERE user_id = {$USER->user_id}");
if (empty($domain))
        $domain = "https://www.geograph.org.uk";

$columns = $db->getAssoc("DESCRIBE responsive_test");

$sql = "SELECT file,url,status, SUM(user_id>0) as users";
foreach ($columns as $column => $data) {
	if ($column == 'test_google') {
		$sql .= ",IF(SUM($column)>0,'Yes','') AS google";
	} elseif (preg_match('/^test_(\w+)/',$column,$m)) {
		$sql .= ",ROUND(AVG($column)*100) AS '{$m[1]}'";
	}
}
$sql .= ", GROUP_CONCAT(comments) AS comment";
$sql .= " FROM responsive_template left join responsive_test using (responsive_id) where status in ('converted','whitelisted') group by responsive_id ORDER BY file";


$folderbase = $_SERVER['BASE_DIR']."/";
$foldertpl = $_SERVER['DOCUMENT_ROOT']."/templates/resp/";
$found = array();
 $recordSet = $db->Execute($sql);

	$row = $recordSet->fields;

	print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";
	print "<TABLE border='1' cellspacing='0' cellpadding='2' class=\"report sortable\" id=\"photolist\"><THEAD><TR>";
	print "<Td>Marker</Td>";
        foreach ($row as $key => $value) {
		if ($key != 'url')
                	print "<Td>$key</Td>";
        }
        print "</TR></THEAD><TBODY>";


        while (!$recordSet->EOF) {
                $row = $recordSet->fields;

		$style = '';
                print "<TR>";
		if (preg_match('/.tpl$/',$row['file'])) {
			if (file_exists($filename = $foldertpl.$row['file'])) {
				print "<td>Found</td>";
				$style = "font-weight:bold;background-color:lightgreen";
				$found[$row['file']] = 1;
			} elseif (strpos($row['file'],'_mobile')) {
				print "<td>Special</td>";
			} elseif ($row['file'] == 'submit_multi_upload.tpl') { //todo, could check for _mobile_begin.tpl
				print "<td>Special</td>";
			} else {
				print "<td style=background-color:pink>Missing</td>";
			}
		} else {
			$regex = escapeshellarg(preg_quote("assign('responsive',")."\s*true");
			if (strlen(`grep -P $regex $folderbase{$row['file']}`) > 3) {
				print "<td>Found</td>";
				$style = "font-weight:bold;background-color:lightgreen";
			} else {
				print "<td style=background-color:pink>Missing</td>";
			}
		}

                foreach ($row as $key => $value) {
			if ($key == 'file') {
				$row['url'] = preg_replace('/^https?:\/\/\w[\w.]+/',$domain,$row['url']);
                                print "<TD style=\"$style\"><a href=\"{$row['url']}\">".htmlentities($value)."</a></TD>";
			} elseif ($key == 'url') {
			} elseif (is_numeric($value)) {
                                print "<TD style=\"$style\" ALIGN=right>".htmlentities($value)."</TD>";
                        } else {
                                print "<TD style=\"$style\">".htmlentities($value)."</TD>";
                        }
                }

                print "</TR>";
                $recordSet->MoveNext();
        }


foreach (glob($foldertpl."*.tpl") as $filename) {
	$base = basename($filename);
	if (strpos($base,'_') ===0)
		continue;
	if (isset($found[$base]))
		continue;
	print "<span style=background-color:pink>$base found in resp/ folder, but NOT marked as either whitelisted, or converted<br></span>";
}

	print "</TR></TBODY></TABLE>";






$smarty->display('_std_end.tpl',true);




