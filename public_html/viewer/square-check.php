<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$user_id = intval($USER->user_id);

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##############################################

$uploadmanager=new UploadManager;
$data = $uploadmanager->getUploadedFiles();

##############################################


$squares = array();
foreach($data as $row) {
	if (!empty($row['grid_reference'])) { //its already 4fig GR!
		$squares[$row['grid_reference']]=1;
	}
}

$where = array();
$where[] = "user_id = ".$user_id;
$where[] = "grid_reference in (".implode(',',array_map(array($db,'Quote'),array_keys($squares))).")";

$stats = $db->getAssoc("SELECT grid_reference, imagecount, max_ftf, has_recent FROM user_gridsquare WHERE ".implode(" AND ",$where));

##############################################

$smarty->display('_std_begin.tpl');

print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee class=\"report sortable\" id=\"photolist\">";
print "<thead><tr>";
print "<th>Image";
print "<th>Taken";
print "<th>Square";
print "<th>Images";
print "</tr></thead><tbody>";
			$fiveYearsAgo = new DateTime('-5 years');

foreach($data as $row) {
	print "<tr>";
	print "<td>{$row['transfer_id']}";
	print "<td>".htmlentities($row['imagetaken']);
        if (!empty($row['grid_reference'])) {
		$stat = null;
		print "<td><tt>".htmlentities($row['grid_reference']);
		if (!empty($stats[$row['grid_reference']])) {
			$stat = $stats[$row['grid_reference']];
			print "<td align=right sortvalue=\"{$stat['imagecount']}\">".number_format($stat['imagecount'],0);
			if (!empty($stat['max_ftf']))
				print "<td>Personal";
			if (!empty($stat['max_ftf']))
				print "<td>Recent";
		}
		$is_recent = false;
		if (!empty($row['imagetaken'])) {
			$databaseDate = new DateTime($row['imagetaken']);
			if ($databaseDate >= $fiveYearsAgo) $is_recent = true;
		}
		if (empty($stat) || empty($stat['max_ftf']) || (empty($stat['has_recent']) && $is_recent) ) {
			$url = "/submit2.php?transfer_id={$row['transfer_id']}";
			print "<td sortvalue=0.5><a href=\"$url\">Submit Now!</a>";
		}
	} else {
		print "<td style=color:silver>No Location";
		print "<td sortvalue=0>";
		print "<td>";
		print "<td>";
	}
}
print "</tbody></table>";


##############################################

$smarty->display('_std_end.tpl',md5($_SERVER['PHP_SELF']));

