<?

require_once('geograph/global.inc.php');
$db = GeographDatabaseConnection(true);

$smarty = new GeographPage;
$smarty->assign('responsive', true);
$smarty->display('_std_begin.tpl');

$recordSet = $db->Execute("
select * from sphinx_placenames where binary place not regexp '^[\\\\w /\',\\(\\)&\\.-]+$'
");

$stat = array();
$example = array();
while (!$recordSet->EOF) {
        $row = $recordSet->fields;

	print "{$row['placename_id']}: ".htmlentities2($row['Place']);
	if ($row['images'])
		print " ({$row['images']})";

	if (!empty($_GET['e'])) {
		$e  = str_replace(array('+','%26','%23','%3B'),array(' ','&amp;','#',';'), urlencode($row['Place'])); //convert back some common chars!

		print " &middot; <span style=font-color:green;font-family:monospace>".$e."</span><br>";
		//print "&middot; <span style=font-color:gray;font-family:monospace>".str_replace('+',' ',urlencode(latin1_to_utf8($row['title'])))."</span><br><br>";

		if (preg_match_all('/(%[0-9A-F]{2})/',$e,$m)) {
			foreach ($m[1] as $u) {
				@$stat[$u]++;
				$example[$u] = $row['Place'];
			}
		}
	}

	print "<br>";

	$recordSet->MoveNext();
}
$recordSet->Close();

if (!empty($stat)) {
	print "<br><br>";
	ksort($stat);
	foreach($stat as $u => $c)
		printf('%s -> %s = %d    [ %s ]<br>', $u, urldecode($u), $c, "/place/".urlencode2($example[$u]));

	print "<pre>";
	print_r($stat);
	print "</pre>";
}


$smarty->display('_std_end.tpl');
