<?

require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

//$db = GeographDatabaseConnection(true);
//$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$imagelist=new ImageList;
$cols = preg_replace('/(\w+)/','gi.$1',$imagelist->cols);
//generally the tables will be joined with gridimage_search, so use the gi alias

/*
The main 'source' should use, to skip 'silenced' rows -
	LEFT JOIN task_result ts ON (ts.gridimage_id = gi.gridimage_id AND ts.user_id = {user_id} AND ts.task_id = {task_id})
and     WHERE ... AND ts.gridimage_id IS NULL

All results are recorded in task_result, but should tasks, should generally record results ongoing with success/failure query and can then use that to keep track
*/

$tasks = array(
	'typev2' => array(
		'title' => "Type Tags for Historic Supplementals",
		'question' => 'Does this image look like {label}?',
		'responces' => 'Yes,No,Skip',
		'source' => "
		SELECT $cols, l.label
		FROM gridimage_search gi inner join gridimage using (gridimage_id) inner join gridimage_label_single l using (gridimage_id)
			LEFT JOIN task_result ts ON (ts.gridimage_id = gi.gridimage_id AND ts.user_id = {user_id} AND ts.task_id = {task_id})
		WHERE gi.moderation_status = 'accepted' AND tags NOT like '%type:%' AND model = 'typev2' AND l.score > 0.75
		 AND nateastings div 1000 = viewpoint_eastings div 1000 AND natnorthings div 1000 = viewpoint_northings div 1000
		 AND l.label != 'CrossGrid'
		 AND l.label != 'Geograph'
		 AND ts.gridimage_id IS NULL ",
		'label_column' => 'l.label', //needed to add a label filter

		// in this example 'verified' is used to keep track of verification progress
		'yes' => "UPDATE gridimage_label SET verified = 1,verified_by = {user_id} WHERE gridimage_id = {gridimage_id} AND label = {label} AND model = 'typev2'",
		'no' => "UPDATE gridimage_label SET verified = 0,verified_by = {user_id} WHERE gridimage_id = {gridimage_id} AND label = {label} AND model = 'typev2'",

			//prototype, not used yet!
		'promote' => "INSERT INTO gridimage_tag SELECT gridimage_id, j.tag_id, verified_by AS user_id
				FROM gridimage_label l
				INNER JOIN label_to_tag_id j USING (label,model)
					LEFT JOIN tag_public t ON (t.gridimage_id = l.gridimage_id and prefix = 'type')
				WHERE verified = 1 AND t.tag_id IS NULL
				AND model = 'typev2'",
	),

	'subject' => array(
		'title' => "Subject Tags for your Images",
		'question' => 'Does this image have the primary <b>subject</b> of <big>{label}</big>?',
		'warning' => 'Selecting Yes will actully add the subject tag to your image. Remember these labels are suggested by a computer vision experiment, it can make wrong (or even humorous) suggestions, so just select N in those cases.',
		'responces' => 'Yes,No,Skip',
		'source' => "
		SELECT $cols, l.label
		FROM gridimage_search gi inner join gridimage_label_single l using (gridimage_id)
			LEFT JOIN task_result ts ON (ts.gridimage_id = gi.gridimage_id AND ts.user_id = {user_id} AND ts.task_id = {task_id})
		WHERE tags NOT like '%subject:%' AND model = 'subject' AND l.score > 0.75
		 AND gi.user_id = {user_id}
		 AND ts.gridimage_id IS NULL ",
		'label_column' => 'l.label', //needed to add a label filter

		'yes' => "UPDATE gridimage_label SET verified = 1,verified_by = {user_id} WHERE gridimage_id = {gridimage_id} AND label = {label} AND model = 'typev2'",
		'no' => "UPDATE gridimage_label SET verified = 0,verified_by = {user_id} WHERE gridimage_id = {gridimage_id} AND label = {label} AND model = 'typev2'",

		'list'=>true,
	),

	'curated1' => array(
		'title' => 'Check Initial Curation',
		'question' => 'Does this image <b>really illustrate</b> <big>{label}</big>?',
		'notes' => "It doesn't just need to to match the term, but should be particully representative of {label} and the subject clearly visible in the image. Please only vote on the subject of the photo, not the technical quality or resolution etc of the image (i.e. don't downvote low resolution images).",
		'responces' => 'Good,Ok,Bad,Skip',
		'source' => "SELECT $cols, c.label, curated_id as table_id
		FROM gridimage_search gi INNER JOIN curated1 c USING (gridimage_id)
                 INNER JOIN curated_label USING (label,active)
		 LEFT JOIN task_result ts ON (ts.gridimage_id = gi.gridimage_id AND ts.user_id = {user_id} AND ts.task_id = {task_id})
		WHERE active > 0 AND c.score>7 AND stack != ''
		 AND ts.gridimage_id IS NULL ",
		'label_column' => 'c.label', //needed to add a label filter

		'good' => "UPDATE curated_id SET score=score+1 WHERE curated_id = {table_id}",
		//'ok' => "",
		'bad' => "UPDATE curated_id SET score=score-1 WHERE curated_id = {table_id}",
	),

	'curated_preselect' => array(
		'title' => 'Initial Keyword Curation',
		'question' => 'Does this image <b>really illustrate</b> <big>{label}</big>?',
		'notes' => "It doesn't just need to to match the term, but should be particully representive of {label} and the subject clearly visible in the image.",
		'responces' => 'Yes,No,Skip',
		'source' => "SELECT $cols, p.label
		FROM gridimage_search gi INNER JOIN curated_preselect p USING (gridimage_id)
		 LEFT JOIN curated1 c USING (gridimage_id,label)
		 LEFT JOIN task_result ts ON (ts.gridimage_id = gi.gridimage_id AND ts.user_id = {user_id} AND ts.task_id = {task_id})
		WHERE p.active = 1 AND c.gridimage_id IS NULL
		 AND ts.gridimage_id IS NULL
		",
		'label_column' => 'p.label', //needed to add a label filter

		'yes' => "INSERT IGNORE INTO curated1 SET created=NOW(),user_id={user_id},label={label},active=1,score=5,gridimage_id={gridimage_id},`group`='Geography and Geology'",
		'no' => "UPDATE curated_preselect SET active=0 WHERE label={label} AND gridimage_id={gridimage_id}",

		'list'=>true,
	),
);


/*
		'title' => '',
		'question' => '',
		'responces' => 'Good,Ok,Bad,Skip',
		'source' => "SELECT $cols, label, .... as table_id
		FROM gridimage_search gi INNER JOIN .... USING (gridimage_id)
		 LEFT JOIN task_result ts ON (ts.gridimage_id = gi.gridimage_id AND ts.user_id = {user_id} AND ts.task_id = {task_id})
		WHERE ....
		 AND ts.gridimage_id IS NULL
		",
*/

#####################################
if (empty($_GET['task'])) {

	$smarty->display('_std_begin.tpl');

	print "<h3>Please select a task... </h3>";
	print "<ul>";
	foreach ($tasks as $task_id => $t) {
		if (empty($t['list']))
			continue;
		print "<li><a href=\"?task={$task_id}\">".htmlentities($t['title'])."</a>";
	}
	print "</ul>";

	$smarty->display('_std_end.tpl');
	exit;

#####################################
} elseif (empty($_GET['label'])) {

	$smarty->display('_std_begin.tpl');

	if (isset($tasks[$_GET['task']])) {
		$task_id = $_GET['task'];
		$t = $tasks[$_GET['task']];

		$db = GeographDatabaseConnection(true);
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$col = $t['label_column'] ?? 'label';
		$sql = preg_replace('/SELECT (.+?)\bFROM /s',"SELECT $col AS label,COUNT(*) AS images FROM ", $t['source'])." GROUP BY $col LIMIT 100";
		$sql = str_replace('{user_id}',$USER->user_id,$sql);
		$sql = str_replace('{task_id}',$db->Quote($task_id),$sql);
		//print $sql;

		$data = $db->getAll($sql);

		print "<h2>".htmlentities($t['title'])."</h2>";

		if (empty($data)) {
			print "No images available. Check back later, or <a href=\"?\">try another task</a>";
			$smarty->display('_std_end.tpl');
			exit;
		}

		print "<h3>Please select a label... </h3>";
		print "<ul>";
		foreach ($data as $row) {
			print "<li><a href=\"?task={$task_id}&amp;label=".urlencode($row['label'])."\">".htmlentities($row['label'])."</a> ({$row['images']} images)";
		}
		print "</ul>";

	} else {
		die("huh");
	}

	$smarty->display('_std_end.tpl');
	exit;

#####################################
} else { // (!empty($_GET['label'])) {

	if (isset($tasks[$_GET['task']])) {
		$task_id = $_GET['task'];
		$t = $tasks[$_GET['task']];


		if (!empty($_POST['results'])) {
			$db = GeographDatabaseConnection(false);
/*			print "<pre>";
			print_r($_POST);
			print "</pre>";
*/			$data = json_decode($_POST['results'], true);
			$updates = array();
			$updates['user_id'] = $USER->user_id;
			$updates['task_id'] = $_GET['task'];
			$a = 0;
			foreach ($data as $row) {
				$updates['gridimage_id'] = $row['gridimage_id'];
				$updates['label'] = $row['label'] ?? $_GET['label'];
				$updates['result'] = $row['result'];

				$db->Execute('REPLACE INTO task_result SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
                		$a += $db->Affected_Rows();
			}

		} else {
			$db = GeographDatabaseConnection(true);
		}
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$v = $db->Quote($_GET['label']);

		$col = $t['label_column'] ?? 'label';
		$sql = $t['source']." AND $col = $v LIMIT 25";
		$sql = str_replace('{user_id}',$USER->user_id,$sql);
		$sql = str_replace('{task_id}',$db->Quote($task_id),$sql);
		//print $sql;

		$data = $db->getAll($sql);

		if (empty($data)) {
			$smarty->display('_std_begin.tpl');
			print "No images available. Check back later, or <a href=\"?task={$task_id}\">try another term</a>";
			$smarty->display('_std_end.tpl');
			exit;
		}

		$images = array();
		foreach ($data as $row) {
			$image = new GridImage();
			$image->fastInit($row);
						$image->_setDB($db); //_getFullSize may need the database to loopup dimenions
			$image->full = $image->getFull();
						unset($image->db);
                        foreach (get_object_vars($image) as $key => $value) {
                                if (empty($value))
                                        unset($image->{$key});
                        }
			$image->title = latin1_to_utf8($image->title);
			$image->realname = latin1_to_utf8($image->realname);
			$images[] = $image;
		}

		if (!empty($_GET['json'])) {
			outputJSON($images); //will output the correct content-type
			exit;
		}

	} else {
		die("huh");
	}
}


$smarty->display('_std_begin.tpl');


	print "<script>";
	print "var images = ".json_encode($images, JSON_PARTIAL_OUTPUT_ON_ERROR).";\n";
	print "var title = ".json_encode($t['title']).";\n";
	print "var question = ".json_encode($t['question']).";\n";
	print "var responces = ".json_encode(explode(',',$t['responces'])).";\n";
	print "var warning = ".json_encode($t['warning'] ?? '').";\n";
	print "var notes = ".json_encode($t['notes'] ?? '').";\n";
	print "</script>";


print "<h2>".htmlentities($t['title'])."</h2>";

?>

<form method=post onsubmit="getResults(this)" name=theForm>

<div class="interestBox">
Respond using the popup window, then submit results below...
<?

if (!empty($a))
	print " (".intval($a)." values saved)";

?>
</div>

<input type=hidden name="results">
<input type=submit id="subBtn" value="Submit Results"> (sends what worked on so far, even if not done all images)

</form>
<br><br>
<input type=button value="Reopen Slideshow" onclick="showLightbox(currentIdx)">

<style>
.lightbox-background {
 display:none;
 background:#555555;
 opacity:0.8;
 -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=70)";
 filter:alpha(opacity=80);
 zoom:1;
 position:fixed;
 top:0px;
 left:0px;
 min-width:100%;
 min-height:100%;
 z-index:99;
}
#lightbox {
  display:none;
  position:fixed !important;
  top:110px;
	width:700px;
	max-width:95vw;

    left: 50%;
    transform: translate(-50%, 0);

  overflow:auto;
  background-color:silver;
  padding:20px;
  z-index:100;
border-radius:22px;
	text-align:center;
}

#lightbox .notes {
	color:gray;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>

var currentIdx = null;
function showLightbox(idx) {
	if (idx) {
		currentIdx = idx;
	} else {
		$('#lightbox').show();
		$('.lightbox-background').show('slow');
		currentIdx = 0;
	}

	var source = images[currentIdx];

	$('#lightbox').find('img').replaceWith(source.full)

	$('#lightbox').find('a').attr('href',"/photo/"+source.gridimage_id);
	$('#lightbox').find('span.title').text(source.title);
	$('#lightbox').find('span.realname').text(source.realname);

	var question2 = question.replace(/\{label\}/,source.label);
	$('#lightbox').find('span.question').html(question2);

	if (notes.length) {
		var notes2 = notes.replace(/\{label\}/,source.label);
		$('#lightbox').find('span.notes').html(notes2).prepend('<br>');
	}
}

var results = {};

document.addEventListener('keyup', function(event) { //keyup used, as keydown auto-repeats, and keypress doesnt fire esc/backspace
	if (currentIdx===null)
		return;

	if (event.key.length == 1) {
		var image = images[currentIdx];
		var result = {"gridimage_id":image.gridimage_id, "label":image.label, "result": event.key};
		if (image.table_id)
			result.table_id = image.table_id;
		results[image.gridimage_id] = result;
		$('input#subBtn').val('Submit '+Object.keys(results).length+'/'+images.length+' Results');
		if ((currentIdx+1) < images.length) {
			$('#lightbox img').css('opacity',0.3);//test to see if blanking out the image, makes user seees it change!
			showLightbox(currentIdx+1);
		} else {
			 $('#lightbox').hide();
			 $('.lightbox-background').hide('fast');
			currentIdx = null;
		}

	} else if (event.key == 'Backspace') {
		if (currentIdx > 0)
			showLightbox(currentIdx-1);
	} else if (event.key == 'Escape') {
		$('#lightbox').hide();
		$('.lightbox-background').hide('fast');
	}
});


$(function() {
	showLightbox();

	if (warning.length)
		alert(warning);

	var html = ""; var sep = "Press ";
        for (i = 0; i < responces.length; i++) {
		var name = responces[i];
		var l = name.substr(0,1);
		html = html + sep + "<b>"+l+"</b> for <span style=color:blue>"+name+"</span>";
		sep = ", ";
	}

	$("span.answers").html(html);
});

function getResults(that) {
	that.elements['results'].value = JSON.stringify(results);
}

</script>
<?

$smarty->display('_std_end.tpl');

?>
<div class="lightbox-background"></div>
<div id="lightbox">
<span class="question"></span><span class=notes></span><br><br>
<a href="#" target="_blank"><img src=about:blank onload="$(this).css('opacity',1);"></a><br><a href=""><span class=title></span></a> by <span class=realname></span>
<br><hr> <span class="answers"></span><br> Press <b>ESC</b> to quit, or <b>Backspace</b> to go back.
</div>

