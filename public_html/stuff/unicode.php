<?

require_once('geograph/global.inc.php');
$db = GeographDatabaseConnection(true);

$smarty = new GeographPage;
$smarty->assign('responsive', true);
$smarty->display('_std_begin.tpl');

$and = '';
if (!empty($_GET['type'])) {
	if ($_GET['type'] == 'ascii') {
		$and = "AND title regexp '^[\\\\x20-\\\\x7F]+$'";
		print "NOTE: might still show extended chars as they are encoded as html entities (which are ascii)<hr>";
	} elseif ($_GET['type'] == 'extended') {
		$and = "AND title regexp '[\\\\x80-\\\\xFF]'";
	} elseif ($_GET['type'] == 'control') {
		$and = "AND title regexp binary '[\\\\x00-\\\\x1F]'";
	} elseif ($_GET['type'] == 'unicode') {
		//this ends up only matching control chars anyway, as the titel column is latin1, so doesnt contain utf8 (they are entities instead!)
		$and = "AND title regexp binary '[^\\\\x20-\\\\xFF]'";
	} elseif ($_GET['type'] == 'three') {
		 $and = "AND title LIKE '%&#___;%'";
	} elseif ($_GET['type'] == 'plus') {
		 $and = "AND title LIKE '%&#____%;%'";
	}
}
print "$and<hr>";

$recordSet = $db->Execute("select gridimage_id,title from gridimage_funny where title is not null $and order by  gridimage_id IN (1339706,320042,47189,495051,519472,97714,1049262,1036631) desc,reverse(gridimage_id) limit 500");

while (!$recordSet->EOF) {
        $row = $recordSet->fields;

	$row['title'] = htmlentities2($row['title']);

	print "{$row['gridimage_id']}: {$row['title']}<br>";

	if (!empty($_GET['e'])) {
		print "&middot; <span style=font-color:green;font-family:monospace>".str_replace(array('+','%26','%23','%3B'),array(' ','&amp;','#',';'), urlencode($row['title']))."</span><br>";
		print "&middot; <span style=font-color:gray;font-family:monospace>".str_replace('+',' ',urlencode(latin1_to_utf8($row['title'])))."</span><br><br>";
	}

	$recordSet->MoveNext();
}
$recordSet->Close();

$smarty->display('_std_end.tpl');
