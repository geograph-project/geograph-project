<?

//this script was to test counting images in an area
// ... so loops though all rows in 'table' updating the 'key' column with the number of images
// (where the 'key' is currently NULL!)
// Only works with one grid at a time (assumes the area table is one grid), but can work around with 'where')

// ... also tested doing the CONTAINS check in manticore to see if worked (has issues with large polygons begin larger than packet size!)

// ultimiately the GetCountIE/GetCountGB functions are better, and recommended, instead of this script

$d = getcwd();

$param = array('execute'=>0, 'limit'=>10,
	'table' => 'geograph_staging.ni_counties', //... the table containing POLYGON/GEOMETRY column (called WKT!)
	'pkey' => 'auto_id',
	'key' => 'bound_images',
	'ri'=>2, //determines which gb_images/ie_images is used
		'where'=>'', //defaults to 'bound_images IS NULL'
);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$sph = GeographSphinxConnection('sphinxql',true);

#####################################################

$select = "SELECT asText(WKT) AS area, {pkey} FROM {table} WHERE {where} LIMIT {limit}";

$lookup = "SELECT COUNT(*) FROM {gis_table} WHERE ST_Contains({WKT},point_en)"; //should use index!
		//set also "spatial_join2.php" which shows an alternative way to write a JOIN, that DOES use the index too

$update = "UPDATE {table} SET {key} = {str_value} WHERE {pkey} = {pkey_value}";

#######################################################
//shortcuts

//this is how keep track!
if (empty($param['where']))
	$param['where'] = "`{$param['key']}` IS NULL";
else
	$param['where'] .= " AND `{$param['key']}` IS NULL";

$param['gis_table'] = ($param['ri'] == 2)?'ie_images':'gb_images';

#######################################################

require_once('geograph/conversions.class.php');
$conv = new Conversions;


$sql = preg_replace_callback('/\{(\w+)\}/', function($m) use ($param) { return $param[$m[1]]; }, $select);
	print "\t$sql;\n";
$data = $db->getAll($sql);

$c=0;
foreach ($data as $row) {
	if (true) {
		//WKT == POLYGON((346402.4708 402629.965600001,346430.1275 402598.652000001,346453.3136 402614.4038,3... 402622.034700001,346398.4373 402627.0352,346402.4708 402629.965600001))'
		//splot bits, only want the first exterior ring, if was a mulitopolugon this would also only pick the frist!
		$bits = explode("),(",$row['area']);
		$coords = trim($bits[0],'POLYGON()');

		$out = array();
		foreach(explode(',',$coords) as $coord) {
			list($e,$n) = explode(' ',$coord);
			list($lat,$long) = $conv->national_to_wgs84($e,$n,$param['ri']);
			$out[] = sprintf('%.6f,%.6f',deg2rad($long),deg2rad($lat));
		}
		//count is obtained by show meta!
		$sphinxql = "SELECT id,CONTAINS(GEOPOLY2D(".implode(',',$out)."),wgs84_long,wgs84_lat) AS inside FROM sample8 WHERE inside=1 LIMIT 0";
		//CONTAINS(polygon, x, y)
		//POLY2D(x1,y1,x2,y2,x3,y3...)

		print "\t".substr($sphinxql,0,100)."\n";
		$sph->Execute($sphinxql);

		$assoc = $sph->getAssoc("SHOW META");
		$str_value = $assoc['total_found'];

	} else {
		$param['WKT'] = 'ST_GEOMFROMTEXT('.$db->Quote($row['area']).')';
		$sql = preg_replace_callback('/\{(\w+)\}/', function($m) use ($param) { return $param[$m[1]]; }, $lookup);
			print "\t$sql;\n";

		$str_value = $db->getOne($sql); //adds limit 1 automatically!
	}

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


