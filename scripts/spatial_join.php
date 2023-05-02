<?

$d = getcwd();

$param = array('execute'=>0, 'limit'=>10,
	'table' => 'os_gaz_250_new',
		'point_column' => 'point_en', //either a 'spatial' column, or specify two cols like 'e,n' and will use GEOMFROMTEXT automatially!
		'key' => 'country_region_id',
		'pkey' => 'seq', //needs a primary key from table!
		'where'=>'country_region_id IS NULL',
	'gis_table' => 'country_region',
		'value'=>'auto_id', //this column from gis table to set 'key' to - does NOT need be a real key
);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#####################################################

/*
$param = array(
);

//simple
$sql = "UPDATE {table} INNER JOIN {gis_table} ON ST_Contains(WKT,{point_column}) SET {table}.{key} = {gis_table}.{value} WHERE {where}";

//alternative
$sql = "UPDATE {table} SET {table}.{key} = (SELECT {value} FROM {gis_table}  WHERE ST_Contains(WKT,{point_column}) LIMIT 1) WHERE {where}";

... alas mysql doesnt use indexes, on both the above queries, so we break it down!
*/

$select = "SELECT asText({point_column}) AS point, {pkey} FROM {table} WHERE {where} LIMIT {limit}";

$lookup = "SELECT {value} FROM {gis_table} WHERE ST_Contains(WKT,geomfromtext('{point_value}'))"; //should use index!

$update = "UPDATE {table} SET {key} = {str_value} WHERE {pkey} = {pkey_value}";

#######################################################
// new 'generic' version

	//shortcut so dont have to say --point_column="GEOMFROMTEXT(CONCAT('POINT(',e,' ',n,')'))"
if (preg_match('/^(\w+),(\w+)$/',$param['point_column'],$m))
	$param['point_column'] = "GEOMFROMTEXT(CONCAT('POINT(',{$m[1]},' ',{$m[2]},')'))";



$sql = preg_replace_callback('/\{(\w+)\}/', function($m) use ($param) { return $param[$m[1]]; }, $select);
$sql = preg_replace('/asText\(geomfromtext\((.+?)\)\)/i','$1',$sql); //if point_column was created dymaically, with geomfromtext might as well just use it directly
		print "$sql;\n";
$data = $db->getAll($sql);

$c=0;
foreach ($data as $row) {
	$param['point_value'] = $row['point']; //param used in the placeholder!
	$sql = preg_replace_callback('/\{(\w+)\}/', function($m) use ($param) { return $param[$m[1]]; }, $lookup);
		print "\t$sql;\n";

	$str_value = $db->getOne($sql); //adds limit 1 automatically!

	//if ($str_value) { //still want to set even if empty! (empty string vs null is important!

		$param['str_value'] = $db->Quote($str_value);
		$param['pkey_value'] = $db->Quote($row[$param['pkey']]);

		$sql = preg_replace_callback('/\{(\w+)\}/', function($m) use ($param) { return $param[$m[1]]; }, $update);
		if ($param['execute'])
			$db->Execute($sql);
		else {
			print "\t\t$sql;\n";
		}
	//}

	$c++;
	if (!($c%10))
		print "$c ";
}

print "$c. \n";
exit;


#####################################################
//old hardcoded version

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


