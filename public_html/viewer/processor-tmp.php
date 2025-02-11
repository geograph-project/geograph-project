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

$data = $uploadmanager->getUploadedFiles();

if (empty($data))
	die('No images to process - yay! - can close this window.');

$done = $db->getAssoc("SELECT gridimage_id,1 FROM gridimage_hash WHERE source = 'tmp' AND user_id = $user_id");

foreach($data as $row) {

        $gid = crc32($row['transfer_id'])+4294967296;
        $gid += $USER->user_id * 4294967296;

	if (!empty($done[$gid]))
		continue;

	$path = "/submit.php?preview=".$row['transfer_id'];

	$images[] = array('gridimage_id'=>$gid, 'source'=>'tmp', 'path'=> $path);
}

if (empty($images))
	die('No images to process - yay! - can close this window.');

print "<p id=msg>Found ".count($images)." image(s) to process...</p><hr>";

	print "<script>\n";
	print "var images = ".json_encode($images, JSON_PARTIAL_OUTPUT_ON_ERROR).";\n";
	print "var user_id = ".intval($USER->user_id).";\n";
	print "delete window.$;\n"; //need to delete the fake jquery geograph.js creates, as conflicts with imagehash
	print "window['$'] = undefined;\n";
	print "</script>";

?>

<img id="img" crossorigin onerror="retryCross(this)"/>

<script src="https://unpkg.com/imagehash-web/dist/imagehash-web.min.js"></script>
<script>
var img = null;
var current = [];
var results = [];


function retryCross(that) {
	//this function allows retry of tags with crossorigin. Note the query string doesnt do anything on the server, its just to bust the local browser cache (that might have the non-cors image cached)
        if (that.src.indexOf('crossorigin') == -1 && that.hasAttribute('crossorigin')) {
                that.src = that.src + '?crossorigin';
		if (that.hasAttribute('srcset'))
			that.srcset = that.srcset.replace(/\.jpg/g,'.jpg?crossorigin');
	}
}

document.addEventListener("DOMContentLoaded", function() {
	img = document.getElementById('img');

	img.onload = function (event) {
		let result = {};
		result['gridimage_id'] = current['gridimage_id'];
		result['source'] = current['source'];
		result['user_id'] = user_id;

		//we dont current use all the hashes, but for now lets compute them anyway
		ahash(img, 8).then(hash => {
			result['ahash'] = hash.toHexString();
		});
		dhash(img, 8).then(hash => {
			result['dhash'] = hash.toHexString();
		});
		phash(img, 8).then(hash => {
			result['phash'] = hash.toHexString();
		});
		whash(img, 8).then(hash => {
			result['whash'] = hash.toHexString();
		});

		/* cropResistantHash doesnt sem to work, doesnt get converted to canvas?
		Uncaught (in promise) TypeError: Cannot assign to read only property 'Symbol(Symbol.toStringTag)' of object '#<HTMLCanvasElement>'
		cropResistantHash(img).then(hash => {
			result['chash'] = hash.toJSON();
		}); */

		results.push(result);

		if (images.length) {
			document.getElementById('msg').innerHTML = images.length+' remain to process';

			current = images.shift();
			img.src = current.path;
		} else {
			//NOTE, we CANT use jqyery here, conflicts with imagehash (both use $)
			if ('sendBeacon' in navigator) {
				document.getElementById('msg').innerHTML = 'Done all in current batch. Submitting...';
				setTimeout(function() {
					done = navigator.sendBeacon("/viewer/processor.json.php", JSON.stringify(results));
					if (done) {
						//with beacon, dont have to wait - should be queued, even if not sent immidately. 
						//.. but we should wait, so it has been processed, otherwise will get the same images again!
						setTimeout(function() {
							window.location.reload();
						}, 5000);
					} else {
						alert('unable to save results. please contact us!');
					}
				}, 1000); //should use await, but for now just to make sure results come back for last limage. 

			} else {
				alert('unable to save results. please contact us!');
			}
		}
	}

	if (images.length) {
		current = images.shift();
		img.src = current.path;
	} else {
		alert('no images to process??');
	}
});

</script>

<?


//$smarty->display('_std_end.tpl',md($_SERVER['PHP_SELF']));

