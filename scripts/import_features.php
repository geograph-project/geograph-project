<?

$d = getcwd();

//this whare is intended as a example really
$param = array('execute'=>0, 'table'=>'rsgb_islands','where'=>"where country != 'channel_islands' and mhwsarea_h >= 0.5 limit 10",'ri'=>0,'update'=>1,'replace'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

function myquote($in) {
	if (is_numeric($in))
		return $in;
	global $db;
	return $db->Quote($in);
}

#####################################################

$quoted = $db->Quote($param['table']);
$qwhere = $db->Quote($param['where']);
$feature_type_id = $db->getOne("SELECT feature_type_id FROM feature_type WHERE source_table = $quoted AND source_where IN ('',$qwhere)");

if (empty($feature_type_id)) {
	$db->Execute("INSERT INTO feature_type SET title=$quoted, source_table = $quoted, user_id = 3, source_where = $qwhere");
	$feature_type_id=$db->Insert_ID();
}

#####################################################

$in = $db->getAssoc("DESCRIBE {$param['table']}");
$out = $db->getAssoc("DESCRIBE feature_item");


$cols = array();
function add_by_name($name, $names) {
	global $in;
	global $cols;

	//we want the first from $names that exist on $in
	foreach($names as $key)
		if (isset($in[$key]))
                        return $cols[$name] = "`$key`"; //only want to add one!

}
foreach ($out as $name => $data) {
	switch($name) {
		case 'feature_item_id': break; //never insert - just allow auto-id!
		case 'feature_type_id': $cols['feature_type_id'] = $feature_type_id; break;
		case 'table_id':
			if ($param['table'] == 'tuktrig')
				$cols['table_id'] = "CRC32(id)";
			else foreach ($in as $key => $value)
				if ($value['Key'] == 'PRI') // && $data['Type'] = 'int??
					$cols['table_id'] = $key;
			break;
		case 'name':
			if (isset($in['name1']) && isset($in['name2']))
				 $cols['name'] = "concat_ws(' / ',nullif(name1,''),nullif(name2,''))";
			else
				add_by_name($name, array('name','title','def_name','def_nam','stationName')); break;
		//case 'name': add_by_name($name, array()); break;
		case 'label':
			if ($param['table'] == 'tuktrig')
                                $cols[$name] = "id"; //the TP id is non-numeric
                        else add_by_name($name, array('postcode_district','crsCode'));
			break;
		case 'category':
			if ($param['table'] == 'tuktrig')
                                $cols[$name] = "SUBSTRING_INDEX(`add`,'-',1)";
                        else add_by_name($name, array('local_type','group_','classes','town_class')); break;
		case 'subcategory':
			if ($param['table'] == 'tuktrig')
                                $cols[$name] = "SUBSTRING_INDEX(`add`,'-',-1)";
			else add_by_name($name, array('subgroup')); break;
		case 'county': add_by_name($name, array('county','County','county_unitary')); break;
		case 'country': add_by_name($name, array('country','Country')); break;
		case 'region': add_by_name($name, array('region','coast')); break;
		case 'gridref':
			if ($param['table'] == 'rsgb_islands')
				$cols['gridref'] = "IF(country = 'northern_ireland','',gridref)"; //the table has osgb gridrefs, we want irishgrid!
			else
				 add_by_name($name, array('gridref')); //note we dont use 'grid_reference' or 'km_ref' etc, as the are generally only 4fig. We will compute a more accurate GR based on e/n etc 
			 break;
		case 'gridsquare_id': break;
		case 'e': add_by_name($name, array('e','east','eastings','nateastings','geometry_x')); break;
		case 'n': add_by_name($name, array('n','northings','northings','natnorthings','geometry_y')); break;
		case 'reference_index':
			if ($param['table'] == 'rsgb_islands')
                                $cols['reference_index'] = "IF(country = 'northern_ireland',2,1)"; //the table has osgb gridrefs, we want irishgrid!
                        elseif (!empty($param['ri']))
				$cols['reference_index'] = $param['ri'];
			else
				//guess from country?
				add_by_name($name, array('reference_index'));
			break;
		case 'wgs84_lat': add_by_name($name, array('wgs84_lat','lat','latitude','Y')); break;
		case 'wgs84_long': add_by_name($name, array('wgs84_long','lng','longitude','long','X'));break;
		case 'radius':
			if (isset($in['mbr_xmin']))
				$cols['radius'] = "((mbr_ymax-mbr_ymin)+(mbr_xmax-mbr_xmin))/2";
			elseif (isset($in['mhwsarea_h']))
				$cols['radius'] = "sqrt(mhwsarea_h)*50"; //sqrt gives 'diamater' from area
			break;
		case 'user_id': $cols['user_id'] = 3; break;
		case 'gridimage_id': add_by_name($name, array('gridimage_id','first','last')); break;
		case 'nearby_images': add_by_name($name, array('images')); break;
		case 'stat_updated': break; //we dont add 'images_updated' (some tables have it) - as we want to allow entire table to calculate anyway
		case 'sorter': add_by_name($name, array('population','river_length','least_detail_view_res','most_detail_view_res','meters')); break;
	}
}

foreach ($in as $key => $data) {
	print "$key: ".substr($data['Type'],0,50)."\n";
}
print_r($cols);
if (empty($cols['reference_index'])) //if there is a gridref- we might be able to compute it?
	print "\nWARNING: seems to be no reference_index column, which is usually needed for location based items\n\n";

if (empty($cols['table_id']))
	$cols['table_id'] = 'NULL'; // should be default anyway, but just in case. definitly want null to avoid a UNIQUE INDEX violation

##########################

if ($param['update']) //... adds ON DUPLICATE KEY UPDATE
	//updates rows in place (keeping same ids)
	$sql = "INSERT INTO feature_item (".implode(',',array_keys($cols)).") \n";
elseif ($param['replace'])
	//replaces rows inplace (edits to other cols LOST!) - plus creates NEW feature_item_id!!!!
	$sql = "REPLACE INTO feature_item (".implode(',',array_keys($cols)).") \n";
else //only adds new!
	$sql = "INSERT IGNORE INTO feature_item (".implode(',',array_keys($cols)).") \n";

$sql .= " SELECT "; $sep = '';
foreach($cols as $name => $source) {
	$sql .= "{$sep}$source as $name";
	$sep = ",";
}
$sql .= "\n FROM {$param['table']} {$param['where']}";
if ($param['update']) {
	$sql .= "\n ON DUPLICATE KEY UPDATE "; $sep = '';
        foreach ($cols as $name => $source) {
		if ($name == 'feature_type_id' // doesnt change
		|| $name == 'table_id' //doesnt change, must be same for the duplicate key violiation to have happened anyway
		|| $name == 'user_id' //let it be the user that originally imported be kept
                || $name == 'gridimage_id') //I think we dont ever want to overwrite this!
                        continue;
                $sql .= $sep." $name = VALUES($name)"; //grabs value from particular row, so should use $source :)
                $sep = ',';
        }
}
print "$sql;\n";
if ($param['execute']) {
        $db->Execute($sql);
        print "affected: ".$db->Affected_Rows()."\n";
}

##########################

$sql = "UPDATE feature_type SET source_imported = NOW(),updated=updated WHERE feature_type_id = $feature_type_id";
print "$sql;\n";
if ($param['execute']) {
        $db->Execute($sql);
        print "affected: ".$db->Affected_Rows()."\n";
}

##########################

unset($cols['feature_type_id']); //dont ever want this!
unset($cols['table_id']);
unset($cols['user_id']);
$sql = "UPDATE feature_type SET item_columns = ".$db->Quote(implode(',',array_keys($cols)))." WHERE feature_type_id = $feature_type_id AND item_columns = ''"; //ie only set the first time!
print "$sql;\n";
if ($param['execute']) {
        $db->Execute($sql);
        print "affected: ".$db->Affected_Rows()."\n";
}

##########################

$sql = "UPDATE feature_type SET source_where = ".$db->Quote($param['where'])." WHERE feature_type_id = $feature_type_id AND source_where = ''";
print "$sql;\n";
if ($param['execute']) {
        $db->Execute($sql);
        print "affected: ".$db->Affected_Rows()."\n";
}


