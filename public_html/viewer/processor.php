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

//include user_id just to make fastInit easy
$sql = "select s.*,gi.user_id from gridimage_search gi inner join gridimage_size s using (gridimage_id) left join gridimage_hash using (gridimage_id)
 where gi.user_id = {$user_id} and auto_id is null limit 50";

$data = $db->getAll($sql);

if (empty($data))
	die('No images to process - yay! - can close this window.');

foreach($data as $row) {
	$image = new GridImage();
        $image->fastInit($row);
//| gridimage_id | width | height | original_width | original_height | original_diff | user_id |

	$path = $image->_getFullpath(false, true);

	$images[] = array('gridimage_id'=>$row['gridimage_id'], 'source'=>'full', 'path'=> $path);

	//we only bother with larger if 'diff' (otherwise the larger would just be same image - full CAME from original!
	if ($row['original_width'] && $row['original_diff'] == 'yes') {
		//... this is when we KNOW it different

		$path = $image->getImageFromOriginal(640,640, true);
		$images[] = array('gridimage_id'=>$row['gridimage_id'], 'source'=>'640px', 'path'=> $path);

	//this is where DONT know, so the hashing we about to do will help know!
	} elseif ($row['original_width'] && $row['original_diff'] == 'unknown') {
		// complication is DONT want to go ahead and CREATE a 640px thumb, as its presence is a tell
		$great = max($row['original_width'],$row['original_height']);
		if ($great > 800) {
			$path = $image->getImageFromOriginal(800,800, true);
			$images[] = array('gridimage_id'=>$row['gridimage_id'], 'source'=>'800px', 'path'=> $path);
		} else {
			$path = $image->_getOriginalpath(false, true);
			$images[] = array('gridimage_id'=>$row['gridimage_id'], 'source'=>'original', 'path'=> $path);
		}
	}
}

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
		result['user_id'] = user_id;
		result['source'] = current['source'];

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

