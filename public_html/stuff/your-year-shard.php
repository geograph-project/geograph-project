<?


require_once('geograph/global.inc.php');

init_session();

 customNoCacheHeader();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(true);

$user_id = $USER->user_id;

if (!empty($_GET['force']))
	$user_id = intval($_GET['force']);

if (isset($_GET['2014']))
        $_GET['year'] = 2014;

if (!empty($_GET['all'])) {
        $year = intval($_GET['year']);
        $sql = "select gridimage_id,imagetaken,substring(imagetaken,1,7) as month,hits/datediff(now(),imagetaken)*356 as yearcount from gridimage_search inner join gridimage_log using (gridimage_id) where user_id = {$user_id} and `imagetaken` LIKE '".intval($_GET['year'])."%' order by gridimage_id";
} elseif (!empty($_GET['alltime'])) {
        $year = 'all years';
        $sql = "select gridimage_id,imagetaken,substring(imagetaken,1,7) as month,hits/datediff(now(),imagetaken)*356 as yearcount from year_review where user_id = {$user_id} order by gridimage_id";
} elseif (!empty($_GET['year'])) {
        $year = intval($_GET['year']);
        $sql = "select gridimage_id,imagetaken,substring(imagetaken,1,7) as month,hits/datediff(now(),imagetaken)*356 as yearcount from year_review where user_id = {$user_id} and `year` = ".intval($_GET['year'])." order by gridimage_id";
} else {
        $year = 2013;
        $sql = "select gridimage_id,imagetaken,substring(imagetaken,1,7) as month,hits/datediff(now(),imagetaken)*356 as yearcount from 2013_review where user_id = {$user_id} order by gridimage_id";
}


$rows = $db->getAll($sql);

if (!$rows || count($rows) < 10) {
	die("Sorry it doesnt appear you have submitted enough images taken in $year for this feature to work");
}

if (count($rows) > 50) {
	$number = 30;
} else {
	$number = intval(count($rows)/4);
}

$size = floor(count($rows)/$number);


#print "Rows = ".count($rows)."<hr>";
#print "number = $number<hr>";
#print "size = $size<hr>";


$ids = array();

$chunks = array_chunk($rows,$size);

#print "Chunks = ".count($chunks)."<hr>";
#exit;

foreach ($chunks as $chunk) {
	$best = array('yearcount'=>0);
	foreach ($chunk as $row) {
		if ($row['yearcount'] > $best['yearcount'])
			$best = $row;
	}
	if (isset($best['gridimage_id'])) {
		$ids[$best['imagetaken']] = $best['gridimage_id'];
	}
}


if (!$ids || count($ids) < 2) {
        die("Sorry it doesnt appear you have submitted enough images taken in $year for this feature to work");
}

ksort($ids);

$url = "/search.php?markedImages=".implode(',',$ids)."&resultsperpage=100&displayclass=black";

header("Location: $url",true,302);

#create table 2013_review_raw select gridimage_id,user_id,imagetaken,hits,baysian from gridimage_log inner join gridimage_search using (gridimage_id) left join gallery_ids on (id = gridimage_id) where gridimage_id >=3277506 and imagetaken like '2013%';
#create table 2013_review select user_id,imagetaken,count(*) cnt,substring(group_concat(gridimage_id order by hits*COALESCE(baysian,3.2) desc),1,7) as gridimage_id,max(hits*COALESCE(baysian,3.2)) as score,substring_index(group_concat(hits order by hits*COALESCE(baysian,3.2) desc),',',1) as hits from 2013_review_raw group by user_id,imagetaken\G

