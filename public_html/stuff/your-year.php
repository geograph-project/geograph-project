<?


require_once('geograph/global.inc.php');

init_session();

 customNoCacheHeader();

$smarty = new GeographPage;


if (!empty($_GET['build'])) {
	$USER->mustHavePerm("admin");
	$db = GeographDatabaseConnection(false);

	$build = "select user_id,submitted,year(imagetaken) as year,imagetaken,count(*) cnt,
	substring_index(group_concat(gridimage_id order by hits*COALESCE(gallery_ids.baysian,3.2) desc),',',1) as gridimage_id,
	substring_index(group_concat(hits order by hits*COALESCE(gallery_ids.baysian,3.2) desc),',',1) as hits
	from gridimage_log inner join gridimage_search using (gridimage_id) left join gallery_ids on (id = gridimage_id) where %where group by user_id,imagetaken order by null";

	$max = $db->getOne("SELECT MAX(user_id) FROM user_stat");

	if($_GET['build'] === '2')
		$db->Execute("DROP TABLE IF EXISTS year_review2");

	$db->Execute("CREATE TABLE year_review2 LIKE year_review");

//	for (var $start=1; $start < $max; $start+=1000) {
//		$end = $start+999;

	$step = 10; //use exponental step, as many early contributors have big profiles!
	for ($start=1; $start < $max; $start+=$step) {
		$step = intval($step *1.1);

		$end = $start+$step-1;

		$where = "user_id BETWEEN $start AND $end";

		$db->Execute("INSERT INTO year_review2 ".str_replace('%where',$where,$build));

		print "$where => ".mysql_affected_rows()."<br>";
		flush(); ob_flush();
	}
	print "DONE!";

	exit;
}

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(true);

$user_id = $USER->user_id;

if (!empty($_GET['force']))
	$user_id = intval($_GET['force']);

if (!empty($_GET['choose'])) {
	$smarty->display('_std_begin.tpl');
	print "<h2>Your Pictures by year</h2>";
	print "<p>Click a year to view a selection of your images taken that year...</p>";
	$rows = $db->getAll("select `year`,count(*) c,sum(cnt) as cnt from year_review where user_id = $user_id and year > 0 and year < year(now()) group by year desc having c > 3");
	if (empty($rows)) {
		print("Sorry it doesnt appear you have submitted enough images for this feature to work. However you might be able to get a <a href='your-year-shard.php?all=1&year=2'>small all-time selection.");
		$smarty->display('_std_end.tpl');
	        exit;
	}
	print "<ul>";
	foreach ($rows as $row) {
		if ($row['c'] > 30)
			$row['c'] = 30;
		if ($row['c'] < $row['cnt'])
			$row['c'] .= "/".$row['cnt'];
		print "<li><b><a href='?year={$row['year']}'>{$row['year']}</a></b> [{$row['c']}] ";
		print " (<a href='your-year-shard.php?year={$row['year']}&all=1'>Alternative Selection</a>)";
		print " (<a href='/mapper/quick.php?q=user$user_id+@takenyear+{$row['year']}'>Full-Screen map</a>)";
		if ($row['year'] == 2015)
			print "<br><br>";
		if ($row['year'] == 2014)
			print " (<a href='2014.php'>Special one-off page for 2014</a>)";
		if ($row['year'] == 2013)
			print " (<a href='?'>Original 2013 static selection</a>)";
		print "</li>";
	}
	print "</ul>";
	print "Can also get a <a href='?alltime=1'>All Time Selection</a> - also available in <a href='your-year-shard.php?alltime=1'>Alternative</a> version (and <a href='/mapper/quick.php?q=user$user_id'>map</a>).";

	print "<p>These selections created, ".$db->getOne("select date(UPDATE_TIME) from information_schema.tables where table_name = 'year_review'")." (except the original 2013 selection, which isnt updated)</p>";

	print "<hr/>";
	print "Can also get a <a href='/browser/#!/q=user$user_id/display=group/group=takenyear/n=10/gorder=alpha%20desc/sort=score'>yearly breakdown via the browser</a>,";
	print " and a <a href=\"http://www.nearby.org.uk/geograph/trips.php?q=user$user_id\">list of trips</a> by GeographTools.";
	$smarty->display('_std_end.tpl');
	exit;
}

if (isset($_GET['2014']))
	$_GET['year'] = 2014;


if (!empty($_GET['alltime'])) {
	$year = 'all time';
	$sql = "select * from (select gridimage_id,imagetaken,hits/datediff(now(),imagetaken)*356 as yearcount from year_review where user_id = {$user_id} order by yearcount desc limit 30) t1 order by imagetaken";
} elseif (!empty($_GET['year'])) {
	$year = intval($_GET['year']);
	$sql = "select * from (select gridimage_id,imagetaken,hits/datediff(now(),imagetaken)*356 as yearcount from year_review where user_id = {$user_id} and `year` = ".intval($_GET['year'])." order by yearcount desc limit 30) t1 order by imagetaken";
} else {
	$year = 2013;
	$sql = "select * from (select gridimage_id,imagetaken,hits/datediff(now(),imagetaken)*356 as yearcount from 2013_review where user_id = {$user_id} order by yearcount desc limit 30) t1 order by imagetaken";
}

$rows = $db->getAssoc($sql);


if (!$rows || count($rows) < 3) {
	die("Sorry it doesnt appear you have submitted enough images taken in $year for this feature to work");
}

$ids = array_keys($rows);

$url = "/search.php?markedImages=".implode(',',$ids)."&resultsperpage=100&displayclass=black";

header("Location: $url",true,302);

#create table 2013_review_raw select gridimage_id,user_id,imagetaken,hits,baysian from gridimage_log inner join gridimage_search using (gridimage_id) left join gallery_ids on (id = gridimage_id) where gridimage_id >=3277506 and imagetaken like '2013%';
#create table 2013_review select user_id,imagetaken,count(*) cnt,substring(group_concat(gridimage_id order by hits*COALESCE(baysian,3.2) desc),1,7) as gridimage_id,max(hits*COALESCE(baysian,3.2)) as score,substring_index(group_concat(hits order by hits*COALESCE(baysian,3.2) desc),',',1) as hits from 2013_review_raw group by user_id,imagetaken\G

# create table year_review select user_id,submitted,year(imagetaken) as year,imagetaken,count(*) cnt,substring(group_concat(gridimage_id order by hits*COALESCE(baysian,3.2) desc),1,7) as gridimage_id,max(hits*COALESCE(baysian,3.2)) as score,substring_index(group_concat(hits order by hits*COALESCE(baysian,3.2) desc),',',1) as hits from gridimage_log inner join gridimage_search using (gridimage_id) left join gallery_ids on (id = gridimage_id) group by user_id,imagetaken order by null;
#Query OK, 357368 rows affected, 2 warnings (33.17 sec)
# alter table year_review add index(user_id,year);


#create table year_review select user_id,submitted,year(imagetaken) as year,imagetaken,count(*) cnt,substring_index(group_concat(gridimage_id order by hits*COALESCE(baysian,3.2) desc),',',1) as gridimage_id,substring_index(group_concat(hits order by hits*COALESCE(baysian,3.2) desc),',',1) as hits from gridimage_log inner join gridimage_search using (gridimage_id) left join gallery_ids on (id = gridimage_id) group by user_id,imagetaken order by null;
#Query OK, 388866 rows affected, 2 warnings (41.77 sec)
#Records: 388866  Duplicates: 0  Warnings: 0

