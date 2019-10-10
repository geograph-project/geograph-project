<?

require_once('geograph/global.inc.php');

	$db = GeographDatabaseConnection();
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

########################################################################

print "<table border=1 cellspacing=0 cellpadding=5>";
$keys = explode(",","Name,Engine,Row_format,Rows,Avg_row_length,Data_length,Index_length,Data_free,Update_time");
print "<tr><th>".implode("</th><th>",$keys)."</th></tr>";
$tables = $db->getAll("show table status like '%tag%'");
foreach ($tables as $row) {
	print "<tr>";
	foreach($keys as $key) {
		if (is_numeric($row[$key]))
			print @"<td align=right>".number_format($row[$key],0)."</td>";
		else
			print @"<td>".htmlentities($row[$key])."</td>";
	}
}
print "</table>";

print "If dont have the `tag` and `gridimage_tag` tables, download copy from http://data.geograph.org.uk/dumps/gridimage_tag.mysql.gz (currently about 130Mb compressed)
	and import into your database";

########################################################################
########################################################################

$drop = "DROP TABLE IF EXISTS tag_stat";
$create = "CREATE TABLE tag_stat (tag_id INT UNSIGNED PRIMARY KEY) ";
$insert = "INSERT INTO  tag_stat ";
$range = "SELECT MIN(tag_id) AS min, MAX(tag_id) AS max FROM tag";
$select = "SELECT tag_id,
		IF(prefix='',tag,CONCAT(prefix,':',tag)) AS tagtext,
		COUNT(*) AS images,
		MAX(gt.updated) AS last_used,
		coalesce(NULLIF(canonical,'0'), tag_id) AS canonical
	FROM tag t
		INNER JOIN gridimage_tag gt USING (tag_id)
	WHERE t.status =1 AND gt.status = 2 AND prefix NOT IN('top','type')
		AND tag_id BETWEEN {start} AND {end}
		AND gridimage_id < 4294967296
	GROUP BY tag_id
	ORDER BY NULL";

########################################################################
########################################################################

$total = $live = 0;

$rows = run_sql_time($range, true);
$min = $rows[0]['min']; $max = $rows[0]['max'];
$step = 30000;
print "From $min to $max in $step steps. About ".ceil(($max-$min)/$step)." iterations<hr>";

if (!empty($drop))
	run_sql_time($drop);

########################################################################

foreach (range($min,$max,$step) as $start) {
	$end = $start+$step-1;

	$sql = (($start==$min)?$create:$insert).$select;

	$sql = str_replace('{start}',$start,str_replace('{end}',$end,$sql));

	run_sql_time($sql);
}

########################################################################
########################################################################

printf('Total Time: <b>%.3f</b> seconds;<br>', $total);

if ($live < 3) {
	print "<hr>Showing Cached results. Click to run the queries live: <form method=post><input type=submit name=submit value='Run Live'></form>";
}



function run_sql_time($sql,$getdata = false) {
	global $db, $total, $live;

	print "<pre style=font-size:0.8em>$sql;</pre>";


	$query_hash = md5($sql);

	$cache = $db->getRow("SELECT * FROM timing_info WHERE query_hash = '$query_hash' ORDER BY created DESC LIMIT 1");

	if (empty($cache) || $getdata || !empty($_POST)) { //if returning, cant use cache
		if (!$db->getOne("SHOW TABLES LIKE 'timing_info'")) {
			$db->Execute("CREATE TABLE `timing_info` (
			  `query_hash` varchar(32) NOT NULL,
			  `before` double NOT NULL,
			  `after` double NOT NULL,
			  `taken` float NOT NULL,
			  `affected` int(10) unsigned NOT NULL,
			  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `query` text NOT NULL,
			  PRIMARY KEY (`query_hash`,`created`)
			)");
		};

		$cache = array();
		$cache['before'] = microtime(true);
		if ($getdata)
			$data = $db->getAll($sql);
		else
			$db->Execute($sql);
		$cache['after'] =  microtime(true);

		if ($error = $db->ErrorMsg()) {
			print "Error: <b>".htmlentities($error)."</b><br>";
		}

		if ($getdata)
			$cache['affected'] = count($data);
		else
			$cache['affected'] = $db->affected_rows();

		$cache['taken'] = $cache['after'] - $cache['before'];
		$cache['query_hash'] = $query_hash;
		$cache['query'] = $sql;

		$db->Execute('INSERT INTO timing_info SET `'.implode('` = ?,`',array_keys($cache)).'` = ?',array_values($cache));

		$cache['created'] = "now"; //just used for display below!
		$live++;
	}

	printf('Time: <b>%.3f</b> seconds; Affected Rows: %s     (when query run: %s)<br>', $cache['after'] - $cache['before'], $cache['affected'], $cache['created']);

	print "<hr>";
	flush(); ob_flush();

	$total += $cache['taken'];

	if ($getdata)
		return $data;
}
