<?

//note, this script isnt intended for regular use, it just used to backfill data.
// normally the gridsquare table is updated automatically, on an ongoing basis

//these are the arguments we expect
$param=array('limit' => 100000);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$db->Execute("DROP TABLE IF EXISTS gridsquare_tmp");


$max = $db->getOne("SELECT MAX(gridsquare_id) FROM gridsquare");
$limit = $param['limit'];

print "max = $max, limit = $limit\n";

$tim = time();

$create = "create table gridsquare_tmp (primary key(gridsquare_id))";
$insert = "insert into gridsquare_tmp";


for($start = 0; $start <= $max; $start += $limit) {
	$end = $start + $limit -1;

	$where = "gridsquare_id BETWEEN $start AND $end";

	print "$where; ";

	$tim = microtime(true);
        $recordSet = $db->Execute($sql = ($start?$insert:$create)."
			SELECT
			  gridsquare_id,
                          COUNT(*) AS `imagecount`,
			  IF(SUM(moderation_status='geograph')>0,1,0) AS has_geographs,
                          IF(SUM(imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR)) AND moderation_status='geograph')>0,1,0) AS has_recent,
                          COALESCE(MAX(ftf),0) AS max_ftf,
                          COALESCE(SUM(moderation_status = 'geograph' and imagetaken LIKE '1%'),0) AS premill,
			  group_concat(if(ftf<=1,gridimage_id,null) order by ftf desc, seq_no limit 1) AS first,
			  MAX(upd_timestamp) AS last_timestamp
                        FROM gridimage
                        WHERE $where
			GROUP BY gridsquare_id
			ORDER BY NULL");

	$count = $db->Affected_Rows();

	printf("done %d (%d) in %.3f seconds\n",$count,$start,microtime(true)-$tim);

}


$columns = $db->getAssoc("describe gridsquare_tmp");

$sql = "UPDATE gridsquare INNER JOIN gridsquare_tmp USING (gridsquare_id) SET "; $sep = '';

foreach($columns as $column => $row) {
	if ($column == 'gridsquare_id')
		continue;
	$sql .= "$sep gridsquare.$column = gridsquare_tmp.$column"; $sep = ',';
}

$sql .= " WHERE gridsquare.first = 0 and gridsquare.imagecount > 0";

print "$sql;\n";
