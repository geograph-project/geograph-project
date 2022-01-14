<?

$param = array();
$param['year'] = date('Y')-1;
$param['execute'] = false;

############################################

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);

############################################

$year = $param['year'];
$from = 17; //current/active
$to = 7; //archive

$sql = array();

$sql[] = "select topic_id,regexp_replace(topic_title,'^poty ($year)?[\.:,; ]*Week','PotY $year, Week') from geobb_topics where forum_id = $from order by topic_id";

$sql[] = "update geobb_topics set topic_title = regexp_replace(topic_title,'^poty ($year)?[\.:,; ]*Week','PotY $year, Week') where forum_id = $from";

$sql[] = "update geobb_posts inner join geobb_topics using (topic_id)
set geobb_posts.forum_id = $to, geobb_topics.forum_id = $to
where topic_title like 'poty $year%' and geobb_topics.forum_id = $from";




foreach ($sql as $query) {
	print preg_replace('/\s+/',' ',$query).";\n";

	if (preg_match('/^select/i',$query)) {
		if ($param['execute']) {
			//noop!
		} else {
			$data= $db->getAll($query);
			foreach ($data as $row)
				print implode("\t\t",$row)."\n";
		}
	} elseif($param['execute']) {
		//$db->Execute($query);
	}
	print "\n\n";
}
