<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


//cant use a template, as the fake jquery geograph.js creates, as conflicts with imagehash
//$smarty->display('_std_begin.tpl');

$images = array();

$user_id = intval($USER->user_id);

$uploadmanager=new UploadManager;

if (!empty($_POST['status'])) {

	$updates = $_POST;
	$updates['user_id'] = $user_id;

//	print_r($updates);

	$db->Execute('INSERT INTO mini_audit SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
}


$data = $uploadmanager->getUploadedFiles();

if (empty($data))
	die('No images to process - yay! - can close this window.');

$done = $db->getAssoc("SELECT preview_key,md5sum FROM mini_audit WHERE user_id = $user_id");


$uploadmanager=new UploadManager;

foreach($data as $row) {

//	print_r($row);
	//Array ( [transfer_id] => 043a3fa768a46c0e045baa22e91373ba [uploaded] => 1741456545 [photographer_gridref] => [grid_reference] => [gridsquare] => [imagetaken] => 2025-01-11 13:06:58 )


	if (!empty($done[$row['transfer_id']]))
		continue;

	$url = "/submit.php?preview=".$row['transfer_id'];

	$fullpath = $uploadmanager->_pendingJPEG($row['transfer_id']);

?>
<form method=post>
<input type=hidden name=preview_key value="<? echo $row['transfer_id']; ?>">
Uploaded: <? echo date('r',$row['uploaded']); ?><br>
<img src="<? echo $url; ?>"><hr>
<input type=hidden name=md5sum value="<? echo md5_file($fullpath); ?>">
<input type=hidden name=file_created value="<? echo date('Y-m-d H:i:s',$row['uploaded']); ?>">
<input type=hidden name=file_size value="<? echo filesize($fullpath); ?>">

For the above image, I think :-<br><br>

<input type=radio required name=status value="abandon" id="r1"><label for="r1">Uploaded, but decided not to submit</label><br><br>
<input type=radio required name=status value="done" id="r12"><label for="r12">Uploaded, but found had already submitted it</label><br><br>
<input type=radio required name=status value="ongoing" id="r2"><label for="r2">Uploaded, not got round to submitting yet</label><br><br>
<input type=radio required name=status value="tweaked" id="r3"><label for="r3">Uploaded a tweaked version and submitted that instead</label><br><br>
<input type=radio required name=status value="dup1" id="r4"><label for="r4">Pretty sure image has been submitted</label> (if know square <input type=text name=location maxlength=128 size=10> optional)<br><br>
<input type=radio required name=status value="dup2" id="r5"><label for="r5">Know it has already been submitted</label>  (as id: <input type=number min=1 max=9000000 step=1 name=image_id> optional)<br><br>

<input type=radio required name=status value="skip" id="r10"><label for="r10">Unknown. please skip this image</label><br><br>

<input type=submit>
</form>


<?



	exit;
}




die('No images to process - yay! - can close this window.');
