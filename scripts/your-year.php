<?

//these are the arguments we expect
$param=array(
	'build' => 1,
	'year' => date('Y')-1,
	'years' => false,
);

$HELP = <<<ENDHELP
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

set_time_limit(3600*24);


#####################

if (!empty($param['years'])) {
	foreach(range(date('Y'),date('Y')-$param['years']) as $year) {
		//def dont add build=2 as would erase each time
		$cmd = "php {$argv[0]} --config={$param['config']} --year=$year";
		print "$cmd\n";
	}
	exit;
}

#####################


	$build = "select user_id,submitted,year(imagetaken) as year,imagetaken,count(*) cnt,
	substring_index(group_concat(gridimage_id order by hits*COALESCE(gallery_ids.baysian,3.2) desc),',',1) as gridimage_id,
	substring_index(group_concat(hits order by hits*COALESCE(gallery_ids.baysian,3.2) desc),',',1) as hits
	from gridimage_log inner join gridimage_search using (gridimage_id) left join gallery_ids on (id = gridimage_id) where %where group by user_id,imagetaken order by null";

	$max = $db->getOne("SELECT MAX(user_id) FROM user_stat");

	if($param['build'] === '2')
		$db->Execute("DROP TABLE IF EXISTS year_review2");

	$db->Execute("CREATE TABLE IF NOT EXISTS year_review2 LIKE year_review");

//	for (var $start=1; $start < $max; $start+=1000) {
//		$end = $start+999;

	$step = 10; //use exponental step, as many early contributors have big profiles!
	for ($start=1; $start < $max; $start+=$step) {
		$step = intval($step *1.1);

		$end = $start+$step-1;

		$where = "user_id BETWEEN $start AND $end";
		if (!empty($param['year']))
			//LIKE '2015-%' for exmaple, wont use index; BETWEEN will use index!
			$where .= sprintf(" AND imagetaken BETWEEN '%d-01-01' AND '%d-12-31'",$param['year'],$param['year']);

		$db->Execute("INSERT INTO year_review2 ".str_replace('%where',$where,$build));

		print "$where => ".$db->Affected_Rows()."\n";
		flush(); //ob_flush();
	}
	print "DONE!\n";

if (!empty($param['year'])) {
	print "REPLACE INTO year_review SELECT * FROM year_review2; -- #not run!\n";
} else {
	$year = date('Y')-1;
	print "RENAME year_review TO year_review_old$year, year_review2 TO year_review; -- #not run!\n";
}

