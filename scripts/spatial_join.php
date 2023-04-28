<?

$d = getcwd();

$param = array('execute'=>0, 'limit'=>100);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#####################################################

/* works, but SLOW!


Primary.geograph_staging>update gazjoin set country_region_id = (select auto_id from country_region where ST_Contains(WKT,point_en) limit 1) where country_region_id is null limit 100;
Query OK, 99 rows affected (49.597 sec)
Rows matched: 100  Changed: 99  Warnings: 0

Primary.geograph_staging>update gazjoin set country_region_id = (select auto_id from country_region where ST_Contains(WKT,point_en) limit 1) where country_region_id is null limit 100;
Query OK, 99 rows affected (46.070 sec)
Rows matched: 100  Changed: 99  Warnings: 0


Primary.geograph_staging>update gazjoin set country_region_id = (select auto_id from country_region where ST_Contains(WKT,point_en) limit 1) where country_region_id is null limit 1000;
Query OK, 978 rows affected (7 min 23.100 sec)
Rows matched: 1000  Changed: 978  Warnings: 0


time php scripts/spatial_join.php  --execute --limit=100
10 20 30 40 50 60 70 76.

real    0m13.381s
user    0m0.044s
sys     0m0.021s


*/


$data = $db->getAll("SELECT  asText(point_en) as `point`, seq FROM  os_gaz_250_new  where country_region_id is null LIMIT {$param['limit']}");

$c=0;
foreach ($data as $row) {
	$sql = "select auto_id from country_region where ST_Contains(WKT,geomfromtext('{$row['point']}'))"; //should use index!
//		print "$sql;\n";

	$auto_id = $db->getOne($sql); //adds limit 1 automatically!

	if ($auto_id) {
		$sql = "update os_gaz_250_new SET country_region_id = $auto_id WHERE seq = {$row['seq']}";
//		print "$sql;\n";
		if ($param['execute'])
			$db->Execute($sql);
		else
			exit;
		$c++;
		if (!($c%10))
			print "$c ";
	}
}

print "$c. \n";


