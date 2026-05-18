<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$user_id = intval($USER->user_id);

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

require_once('geograph/vectors.inc.php');

if (!empty($_GET['transfer_id']))
	customExpiresHeader(3600,false,true);

##############################################
// need a lookup of gid->transfer_id
$uploadmanager=new UploadManager;
$data = $uploadmanager->getUploadedFiles(); //todo, maybe want a wayt to skip exif data?

$_GET['transfer_id'] = $_GET['transfer_id'] ?? ''; //just to avoid a notice below!
$found = false;

print "<form>";
print "<select name=transfer_id onchange=this.form.submit()>";
print "<option>Select Image</option>";
foreach($data as $row) {
	printf('<option value="%s"%s>%s - %s</option>', $row['transfer_id'], $_GET['transfer_id'] == $row['transfer_id']?' selected':'', $row['transfer_id'], $row['imagetaken']);
	if ($_GET['transfer_id'] == $row['transfer_id']) $found = 1;
}
print "</select></form>";
if (count($data)>3) {
	foreach($data as $idx => $row) {
		if ($_GET['transfer_id'] != $row['transfer_id'])
			print "<a href=?transfer_id={$row['transfer_id']}>";
		print "<img src=\"/submit.php?preview={$row['transfer_id']}\" style=max-height:120px;max-width:300px;border-radius:20px>";
		print "</a> &nbsp; ";
		if ($idx == 10)
			break;
	}
}
print "<hr>";


if (!empty($_GET['transfer_id']) && $found) {



	$transfer_id = str_replace('/[^\w]+/','',$_GET['transfer_id']);
	
        $gid = crc32($transfer_id)+4294967296;
        $gid += $USER->user_id * 4294967296;



        $url = "/submit.php?preview=".$transfer_id;

        $fullpath = $uploadmanager->_pendingJPEG($transfer_id);

	print "<img src=$url style=float:left;margin-right:20px><br>";

//	print $fullpath;

	$r = run_inference(array(
		'image_id' => $gid,
		'clip-image' => getImageEmbedding($fullpath),
		'clip-title' => getTextEmbeddingWrapper("Example"), //wrapper, so uses cache
		'distance' => 'Unknown',
	));

	print "<hr>";
	print "<b>AI Suggestions</b>: (please treat as suggestions only, may be wrong)";
	print "<ul>";
       	foreach($r as $row) {
               	//print implode('; ',$row)."<br>\n";
		print "<li>";
		switch($row['model']) {
			case 'clip': if ($row['label'] != 'None') print "<label><input type=checkbox name=tags[]>";
				print " <span style=color:gray>top:</span> "; break;
			case 'subjects': print "<label><input type=radio name=subject> <span style=color:gray>subject:</span> "; break;
			case 'types': if ($row['label'] == 'From Above') $row['label'] = 'From:Drone';
				 if ($row['label'] == 'Cross Far') $row['label'] = 'Cross Grid (possible)'; //or maybe continue; to skip it? 
		}
		if ($row['score'] < 0.05) {
			print "".htmlentities($row['label'])."";
		} else
			print "<b>".htmlentities($row['label'])."</b>";

		printf(' (%.1f%%)', ($row['score'])*100);
		print "</label>";
	}

//	print "<pre>";
//	print_r($r);


}



#############################################################

//note this function in intended to just run one inference!
function run_inference($inputs) {

    chdir("/mnt/efs/models");

    require_once 'GeographModelBase.php';

    $allResults = [];
    $directories = array_filter(glob('*'), 'is_dir');

//$stats=[];
    foreach ($directories as $dir) {
        $modelPhp = $dir . '/model.php';
        $weightsJson = $dir . '/weights.json';

        if (file_exists($modelPhp) && file_exists($weightsJson.".bin")) {

            require_once $modelPhp;

            // Determine class name from directory name
            // e.g. gallery -> GeographGalleryModel
            // Special case for model_types -> GeographTypesModel
            $className = 'Geograph' . ucfirst(str_replace('model_', '', $dir)) . 'Model';

            if (class_exists($className)) {
                try {
//$start = microtime(true);
                    $model = new $className($weightsJson);
//$loaded = microtime(true);
                    $results = $model->predict_api($inputs);
//$end = microtime(true);
//$stats[$className."Load"] = $loaded-$start;
//$stats[$className."Infer"] = $end-$loaded;

			unset($model); // free memory

                    $allResults = array_merge($allResults, $results);
                } catch (Exception $e) {
                    // Skip if error as requested
                    error_log("Error running model in $dir: " . $e->getMessage());
                }
            }
        }
    }
//print_r($stats);
    return $allResults;
}
