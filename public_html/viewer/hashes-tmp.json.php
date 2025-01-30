<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$user_id = intval($USER->user_id);

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##############################################

customGZipHandlerStart();
customExpiresHeader(600,true);

header("Content-Type:application/json");
print "[";
$sep = '';

##############################################

$uploadmanager=new UploadManager;
$data = $uploadmanager->getUploadedFiles(); //todo, maybe want a wayt to skip exif data?

if (empty($data))
        die('[]');

$done = $db->getAssoc("SELECT gridimage_id,1 FROM gridimage_hash WHERE source = 'tmp' AND user_id = $user_id");

	$fields = array('gid','transfer_id','uploaded','gridref');
        print $sep.json_encode($fields,JSON_PARTIAL_OUTPUT_ON_ERROR);
        $sep = ",\n";

foreach($data as $row) {

        $gid = crc32($row['transfer_id'])+4294967296;
        $gid += $USER->user_id * 4294967296;

	//if dont have a hash, no point including!
        if (empty($done[$gid]))
                continue;

	$row = array($gid, $row['transfer_id'], $row['uploaded'], @$row['grid_reference']);
        print $sep.json_encode($row,JSON_PARTIAL_OUTPUT_ON_ERROR);
}

print "]";

