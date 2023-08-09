<?

require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$imagelist=new ImageList;
$cols = preg_replace('/(\w+)/','gi.$1',$imagelist->cols);
//generally the tables will be joined with gridimage_search, so use the gi alias

/*
The main 'source' should use, to skip 'silenced' rows -
	LEFT JOIN task_result ts ON (ts.gridimage_id = gi.gridimage_id AND ts.user_id = {user_id} AND ts.task_id = {task_id})
and     WHERE ... AND ts.gridimage_id IS NULL

All results are recorded in task_result, but should tasks, should generally record results ongoing with success/failure query and can then use that to keep track
*/

$tasks = $db->getAssoc("SELECT * FROM task");

/*
		'title' => '',
		'question' => '',
		'responces' => 'Good,Ok,Bad,Skip',
		'source' => "SELECT {cols}, label, .... as table_id
		FROM gridimage_search gi INNER JOIN .... USING (gridimage_id)
		 LEFT JOIN task_result ts ON (ts.gridimage_id = gi.gridimage_id AND ts.user_id = {user_id} AND ts.task_id = {task_id})
		WHERE ....
		 AND ts.gridimage_id IS NULL
		",
		'label_column' => 'p.label', //needed to add a label filter
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

		if (!empty($t['score_column'])) {
			$score = $t['default_score'];
			if (!empty($_GET['score']))
				$score = floatval($_GET['score'])/100;
			$t['source'] .= " AND {$t['score_column']} > ".$score;
		}
		$col = $t['label_column'] ?? 'label';
		$sql = preg_replace('/SELECT (.+?)\bFROM /s',"SELECT $col AS label,COUNT(*) AS images FROM ", $t['source'])." GROUP BY $col LIMIT 100";
		$sql = str_replace('{user_id}',$USER->user_id,$sql);
		$sql = str_replace('{task_id}',$db->Quote($task_id),$sql);
		//print $sql;

		$data = $db->getAll($sql);
		if (count($data) == 1) {
			//todo redirect?
		}

		print "<h2>".htmlentities($t['title'])."</h2>";

		if (empty($data)) {
			print "No images available. Check back later, or <a href=\"?\">try another task</a>";
			$smarty->display('_std_end.tpl');
			exit;
		}

		print "<h3>Please select a label... </h3>";

		if (!empty($t['warning']))
			print "<p>".htmlentities($t['warning'])."</p>";

		print "<ul>";
		$link = "?task={$task_id}";
		if (!empty($_GET['score']))
			$link .= "&amp;score=".floatval($_GET['score']);
		foreach ($data as $row) {
			print "<li><a href=\"{$link}&amp;label=".urlencode($row['label'])."\">".htmlentities($row['label'])."</a> ({$row['images']} images)";
		}
		print "</ul>";

                if (!empty($t['score_column']) && !empty($_GET['score'])) {
			$score = sprintf('%.1f',$score*100);
			$default = sprintf('%.1f',$t['default_score']*100);
			print "<form method=get>";
			print "<input type=hidden name=task value=$task_id>";
			print "Minimum score: <input type=number name=score step=0.1 value=\"$score\" min=5 max=99.9 style=text-align:right>%  (about $default% recommended)<br>";
			print "<input type=submit value=Update>";
			print "</form>";
                }

		print "<hr><a href=\"?\">Back to Task List</a>";

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
			if (empty($db) || !empty($db->readonly))
				$db = GeographDatabaseConnection(false);

			$data = json_decode($_POST['results'], true);
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
		}

		$v = $db->Quote($_GET['label']);

		if (!empty($t['score_column'])) {
			$score = $t['default_score'];
			if (!empty($_GET['score']))
				$score = floatval($_GET['score'])/100;
			$t['source'] .= " AND {$t['score_column']} > ".$score;
		}
		$col = $t['label_column'] ?? 'label';
		$sql = $t['source']." AND $col = $v LIMIT 25";
		$sql = str_replace('{cols}',$cols,$sql);
		$sql = str_replace('{user_id}',$USER->user_id,$sql);
		$sql = str_replace('{task_id}',$db->Quote($task_id),$sql);


		if (!empty($_GET['ddd']) && $USER->hasPerm("admin"))
			die($sql);

		$data = $db->getAll($sql);

		if (empty($data)) {
			$smarty->display('_std_begin.tpl');

			$link = "?task={$task_id}";
			if (!empty($_GET['score']))
				$link .= "&amp;score=".floatval($_GET['score']);

			print "No images available. Check back later, or <a href=\"{$link}\">try another term</a>";
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

</form><br><br>

<a href="?task=<?= $task_id ?>">Back to Term/Label List</a>

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

        max-height:95vh;

    left: 50%;
    transform: translate(-50%, 0);

  overflow:auto;
  background-color:silver;
  padding:20px;
  z-index:100;
border-radius:22px;
	text-align:center;
}
@media (max-height: 800px) {
  #lightbox {
    top: -10px;
  }
}
#lightbox .notes {
	color:gray;
}
#lightbox img {
	max-width: 90vw;
	height:auto;
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

