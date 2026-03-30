<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Processing</title>
</head>
<body>
<?

//cant use a template, as the fake jquery geograph.js creates, as conflicts with imagehash
//$smarty->display('_std_begin.tpl');

$images = array();

$user_id = intval($USER->user_id);
$where = "auto_id is null"; //look for unprocessed ones
$limit = 50;

if (!empty($_GET['retry'])) {
	 //note to do this 'propelly' will need to either delete the old failed record, otherwise will just keep appearing in the join!
	$where = "ahash = 'failed'";
	$limit = 10;
} elseif (!empty($_GET['missing'])) {
	 //note to do this 'propelly' will need to either delete the old failed record, otherwise will just keep appearing in the join!
	$where = "ahash = ''";
	$limit = 10;
}

if (!empty($_GET['all'])) {
	//offset??
	$sql = "select s.*,gi.user_id from gridimage_search gi inner join gridimage_size s using (gridimage_id)
	 inner join (SELECT DISTINCT user_id FROM usage_app) u using (user_id)
	 left join gridimage_hash using (gridimage_id)
	where $where limit $limit";

} else {
	//include user_id just to make fastInit easy
	$sql = "select s.*,gi.user_id from gridimage gi inner join gridimage_size s using (gridimage_id) left join gridimage_hash using (gridimage_id)
	 where gi.user_id = {$user_id} and $where limit $limit";
}

$data = $db->getAll($sql);

if (empty($data)) {
    echo '<h4>No images to process &mdash; yay!</h4>';
    if (!empty($_GET['inner'])) {
        // If in an iframe, refresh the main page
        echo '<p><a href="javascript:window.parent.location.reload();">Click here to continue</a></p>';
    } else {
        // If in a standard popup/tab, close it
        echo '<p>You can <a href="javascript:window.close();">close this window</a>.</p>';
    }
    exit;
}

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
	//todo if (!empty($_GET['failed']) delete from gridimage_hash where gridimage_id = {$row['gridimage_id']} - so doesnt keep appearing in the join
}

print "<p id=msg>Found ".count($images)." image(s) to process...</p><hr>";

	print "<script>\n";
	print "var images = ".json_encode($images, JSON_PARTIAL_OUTPUT_ON_ERROR).";\n";
	print "var user_id = ".intval($user_id).";\n";
//	print "delete window.$;\n"; //need to delete the fake jquery geograph.js creates, as conflicts with imagehash
//	print "window['$'] = undefined;\n";
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

	img.onerror = function (event) {
		//still need to do retryCross!
		if (this.src.indexOf('crossorigin') == -1 && this.hasAttribute('crossorigin')) {
			retryCross(this);
		} else {
			let result = {};
	                result['gridimage_id'] = current['gridimage_id'];
	                result['user_id'] = user_id;
	                result['source'] = current['source'];

			//console.log(event);//doesnt seem to return anything useful? (eg to tell 404 diffent to a 'currupted' image for example)
			result['ahash'] = 'failed'; //using ahash is arbiary!
			results.push(result);

			next_image();
		}
	}

	img.onload = async function (event) {
		let result = {};
		result['gridimage_id'] = current['gridimage_id'];
		result['user_id'] = user_id;
		result['source'] = current['source'];

		//we dont current use all the hashes, but for now lets compute them anyway
		// Run all hashes in parallel and wait for all to finish
	        const [a, d, p, w] = await Promise.all([
        	    ahash(img, 8),
	            dhash(img, 8),
        	    phash(img, 8),
	            whash(img, 8)
        	]);

	        result['ahash'] = a.toHexString();
        	result['dhash'] = d.toHexString();
	        result['phash'] = p.toHexString();
        	result['whash'] = w.toHexString();

		/* cropResistantHash doesnt sem to work, doesnt get converted to canvas?
		Uncaught (in promise) TypeError: Cannot assign to read only property 'Symbol(Symbol.toStringTag)' of object '#<HTMLCanvasElement>'
		cropResistantHash(img).then(hash => {
			result['chash'] = hash.toJSON();
		}); */

		results.push(result);
		next_image();
	}

	if (images.length) {
		current = images.shift();
		img.src = current.path;
	} else {
		alert('no images to process??');
	}
});

// NOTE: Replacing sendBeacon with Fetch to ensure server processing finishes before reload
async function next_image() {
    const msgElement = document.getElementById('msg');
	if (images.length) {
		msgElement.innerHTML = images.length+' remain to process (in current batch only)';

		current = images.shift();
		img.src = current.path; //processing happens in .onload event
	} else {
        msgElement.innerHTML = 'Done all in current batch. Submitting...';

        try {
            const response = await fetch("/viewer/processor.json.php"+window.location.search, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(results)
            });

            if (response.ok) {
                // Success! The server has finished processing.
                msgElement.innerHTML = 'Success! Reloading...';
                window.location.reload();
            } else {
                // Server returned an error (e.g., 404 or 500)
                alert('Unable to save results. Please contact us!');
                //throw new Error('Server responded with an error');
            }

        } catch (error) {
            console.error('Submission failed:', error);
            alert('Unable to save results. Please contact us!');
        }
	}
}

</script>
