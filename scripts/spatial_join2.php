<?

//this marks all points in 'table', by what area they are in - from 'gis_table'
// does it by looping each row in gis_table and running a bulk update

// note there is NO process tracking, so have to do whole table at once. (todo would be to add tracking!)

//... the example founds what county each place in the 250k gazetter is in

$d = getcwd();

$param = array('execute'=>0, 'limit'=>10,
	'table' => 'os_gaz_250', //... the table containing a POINT column - that want to find which area the point is in
		'point_column' => 'point_en', //the 'spatial' column, WITH A SPATIAL INDEX!!
		'key' => 'full_county_id',
		'where'=>'1',
	'gis_table' => 'full_county', //... the table containiing a POLYGON/GEOMETRY column (called WKT!)
		'value'=>'auto_id', //this column from gis table to set 'key' to - but SHOULD BE the primary key too!
	'hectad'=>false,
	'tracker'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


#######################################################
//shortcuts

//check if need to add a spatial index
if (!$db->getOne("show indexes from {$param['table']} where Column_name = '{$param['point_column']}'"))
	$db->Execute("alter table {$param['table']} add spatial index ({$param['point_column']})");

if ($db->getOne("SHOW TABLES LIKE '{$param['gis_table']}_hectad'"))
	$param['hectad'] = true;

if ($param['hectad'])
	$param['gis_table'] .= "_hectad";

if ($param['tracker']) {
	if (!$db->getOne("SHOW COLUMNS FROM {$param['gis_table']} LIKE '{$param['table']}_done'"))
		$db->Execute("alter table {$param['gis_table']} add {$param['table']}_done tinyint unsigned not null default 0");
	$param['where'] = "{$param['table']}_done = 0";
}
#####################################################

/* //simple
$sql = "UPDATE {table} INNER JOIN {gis_table} ON ST_Contains(WKT,{point_column}) SET {table}.{key} = {gis_table}.{value} WHERE {where}";
... alas mysql doesnt use indexes, on both the above queries, so we break it down!

... mysql DOES use an index on a ST_Contains JOIN if the WHERE matches EXACTLY one row on gis_table!!
*/

if ($param['hectad']) {
	//$param['gis_table'] already had _hectad postfix!
	$select = "SELECT {value},hectad FROM {gis_table} WHERE {where} AND area > 1 LIMIT {limit}";

	$update = "UPDATE {table} INNER JOIN {gis_table} ON ST_Contains(WKT,{point_column}) SET {key} = {value} WHERE {where} AND {value} = {str_value} AND hectad = {hectad}";

} else {
	$select = "SELECT {value} FROM {gis_table} WHERE {where} LIMIT {limit}";

	$update = "UPDATE {table} INNER JOIN {gis_table} ON ST_Contains(WKT,{point_column}) SET {key} = {value} WHERE {where} AND {value} = {str_value}";
}

if ($param['tracker']) {
	//if there ARE matches, we can set done on the main query
	$update = str_replace(' SET '," SET {$param['table']}_done = 1 , ", $update);
}

#######################################################

$sql = preg_replace_callback('/\{(\w+)\}/', function($m) use ($param) { return $param[$m[1]]; }, $select);
$data = $db->getAll($sql);

$c=0;
foreach ($data as $row) {

	$param['str_value'] = $db->Quote($row[$param['value']]);
	if (!empty($row['hectad']))
		$param['hectad'] = $db->Quote($row['hectad']);

	$sql = preg_replace_callback('/\{(\w+)\}/', function($m) use ($param) { return $param[$m[1]]; }, $update);
	if ($param['execute']) {
		$db->Execute($sql);
		//... if there are no matches still need to update the tracker
		if ($param['tracker'] && !$db->Affected_Rows() && preg_match('/ WHERE (.+)/',$sql,$m)) //need to copy the WHERE cluase!
			$db->Execute("UPDATE {$param['gis_table']} SET {$param['table']}_done = 1 WHERE {$m[1]}");
	} else {
		print "\t\t$sql;\n";
	}

	$c++;
	if (!($c%10))
		print "$c ";
}

print "$c. \n";
