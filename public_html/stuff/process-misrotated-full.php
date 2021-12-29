<?

require_once('geograph/global.inc.php');

init_session();

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(false);

//WARNING: Careful to NOT load basic.css ass it will add a blanket rule to ignore EXIF flag!

###########################################

if (!empty($_POST['failed'])) {
	print "Sorry, it appears your browser is NOT misrotating images, hence this test is not reliable. Can try a different browser (eg Chrome/Firefox)";
	exit;

###########################################

} elseif (empty($_POST['confirm'])) { ?>


To get started, please let us know how see this test image here (Only as see it below, not anywhere else!):<br><br>

<img src="https://data.geograph.org.uk/6400488_f978ee09_1024x1024.jpg" style="max-width:640px;max-height:480px"><br>
<? echo htmlentities("ST5445 :: St Cuthbert's Church, Wells by Eirian Evans"); ?><br><br>

<form method=post>
	Click ONE:
	<button type=submit name="failed" value="1" style="background-color:lightgreen">This appears upright/correct</button>
	<button type=submit name="confirm" value="1" style="background-color:pink">This image appears misrotated</button>
</form>

<?
exit;
}

###########################################

if (!empty($_POST['result'])) {
	$updates = array();

	//technically we actully testing the mid size version!
	$updates['result_full'] = $_POST['result'];
	$updates['result_user_id'] = $USER->user_id;

	$where = "gridimage_id = ".intval($_POST['gridimage_id']);

	$db->Execute('UPDATE exif_rotated SET result_date=NOW(), `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE '.$where,
		array_values($updates));
	//$count += $db->Affected_Rows();
}

###########################################

$limit = 2; //load 2, so can 'preload' the second image!
$where = '';
if (!empty($_GET['mine']) && !empty($USER->user_id))
        $where .= " and user_id = ".intval($USER->user_id);

$rows = $db->getAll("select gridimage_id,user_id,title,grid_reference,realname, orient_full
	 from exif_rotated
		inner join gridimage_search using (gridimage_id)
	where orient_full like 'Orientation%' and orient_full not like '%1'
	and result_full IS NULL $where
        order by gridimage_id desc
	 limit $limit");

if (empty($rows))
	die("no more images to check. Whoop! Thanks for interest. (although doesnt mean more wont be found later, please check back again)");

foreach ($rows as $idx => $row) {
	$image = new GridImage();
	$image->fastInit($row);

	$path = $image->_getFullpath(true,true);

	if ($idx > 0) {
		print "<img src=\"$path\" style=display:none>";
		break;
	}

	?>
Please let us know how see this image (Only as see it below, not anywhere else!):<br><br>
<a href="/photo/<? echo $image->gridimage_id; ?>">
<img src="<? echo $path; ?>" style="max-width:640px;max-height:480px"><br>
<? echo htmlentities("{$row['grid_reference']} :: {$row['title']} by {$row['realname']}"); ?>
</a>
<br><br>

<form method=post>
	<input type=hidden name="confirm" value="1">
	<input type=hidden name="gridimage_id" value="<? echo $image->gridimage_id; ?>">

	Click ONE:
	<button type=submit name="result" value="correct" style="background-color:lightgreen">This appears upright/correct</button>
	<button type=submit name="result" value="rotated" style="background-color:pink">This image appears misrotated</button>
	<button type=submit name="result" value="unknown" style="background-color:red">Unsure</button>
</form>
	<?

}
