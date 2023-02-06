<?

$d = getcwd();

$param = array('execute'=>0, 'table'=>'rsgb_islands','where'=>"where country != 'channel_islands' and mhwsarea_h >= 0.5 limit 10",'ri'=>0);

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
$feature_type_id = $db->getOne("SELECT feature_type_id FROM feature_type WHERE source_table = $quoted");

if (empty($feature_type_id)) {
	$db->Execute("INSERT INTO feature_type SET title=$quoted, source_table = $quoted, user_id = 3");
	$feature_type_id=$db->Insert_ID();
}

#####################################################

$in = $db->getAssoc("DESCRIBE {$param['table']}");
$out = $db->getAssoc("DESCRIBE feature_item");


$cols = array();
function add_by_name($name, $names) {
	global $in;
	global $cols;
	foreach ($in as $key => $value)
		if (in_array($key, $names)) {
                         $cols[$name] = $key;
			return; //only want to add one!
		}
}
foreach ($out as $name => $data) {
	switch($name) {
		case 'feature_item_id': break; //never insert - just allow auto-id!
		case 'feature_type_id': $cols['feature_type_id'] = $feature_type_id; break;
		case 'table_id':
			foreach ($in as $key => $value)
				if ($value['Key'] == 'PRI') // && $data['Type'] = 'int??
					$cols['table_id'] = $key;
			break;
		case 'name':
			if (isset($in['name1']) && isset($in['name2']))
				 $cols['name'] = "concat_ws(' / ',nullif(name1,''),nullif(name2,''))";
			else
				add_by_name($name, array('name','title','def_name','def_nam')); break;
		//case 'name': add_by_name($name, array()); break;
		case 'label': break; //??
		case 'category': add_by_name($name, array('local_type','group_')); break;
		case 'subcategory': add_by_name($name, array('subgroup')); break;
		case 'county': add_by_name($name, array('county','County')); break;
		case 'country': add_by_name($name, array('country','Country')); break;
		case 'region': add_by_name($name, array('region','coast')); break;
		case 'gridref':
			if ($param['table'] == 'rsgb_islands')
				$cols['gridref'] = "IF(country = 'northern_ireland','',gridref)"; //the table has osgb gridrefs, we want irishgrid!
			else
				 add_by_name($name, array('gridref'));
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
		case 'stat_updated': break;
		case 'sorter': add_by_name($name, array('population')); break;
	}
}

print_r($cols);

##########################

$sql = "REPLACE INTO feature_item (".implode(',',array_keys($cols)).") \n";
$sql .= " SELECT "; $sep = '';
foreach($cols as $name => $source) {
	$sql .= "{$sep}$source as $name";
	$sep = ",";
}
$sql .= "\n FROM {$param['table']} {$param['where']}";
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
$sql = "UPDATE feature_type SET item_columns = ".$db->Quote(implode(',',array_keys($cols)))." WHERE feature_type_id = $feature_type_id";
print "$sql;\n";


