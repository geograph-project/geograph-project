<?php

//these are the arguments we expect
$param=array('geo1'=>200, 'geo2'=>200, 'diff'=>22, 'execute'=>0, 'id'=>'auto');

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$sph = GeographSphinxConnection('sphinxql',true);
$db = GeographDatabaseConnection(false);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

$requirement = "natgrlen>=6 and vgrlen>=6 and distance>8 and direction != -1";
$requirement .= " AND MATCH('@status geograph')";

if (!empty($param['id'])) {
	if ($param['id'] === 'auto')
		$id = $db->getOne("SELECT max(gridimage_id) FROM cluster3");
	else
		$id = intval($param['id']);
}
if (!empty($id))
	$filter = "AND id > $id ORDER BY id ASC";
else
	$filter = " ORDER BY id DESC";

$fetch = "select id,wgs84_lat,wgs84_long,vlat,vlong,direction FROM viewpoint WHERE $requirement $filter LIMIT 1000";

$find = 'select id,geodist($wgs84_lat,$wgs84_long,wgs84_lat,wgs84_long) as geo1,
        geodist($vlat,$vlong,vlat,vlong) as geo2,
        180 - abs(abs($direction-direction) - 180) as diff
        from viewpoint
	 where geo1 <= '.$param['geo1'].' and geo2 < '.$param['geo2'].' and diff <= '.$param['diff'].'
         and wgs84_lat>$lat1 and wgs84_lat<$lat2
         and '.$requirement.'
        and id != $id
        limit 1000';

$rads=deg2rad(0.01); //need rads, 0.01degree shoud be bigger than 200m!

############################################

print "$fetch;\n";

$recordSet = $sph->Execute($fetch);
print "Found ".$recordSet->RecordCount()."\n";
while (!$recordSet->EOF) {
        $row =& $recordSet->fields;

                $row['lat1'] = $row['wgs84_lat']-$rads;
                $row['lat2'] = $row['wgs84_lat']+$rads;

            $sql = preg_replace_callback('/\$(\w+)/', function ($m) { return $GLOBALS['row'][$m[1]]; }, $find );

	print implode(', ',$row)."\n";

        if (empty($param['execute'])) {
                print "$sql;\n\n"; exit;
        }

        $results = $sph->getAll($sql);
        if (!empty($results)) {
		foreach ($results as $r) {
			$sql = "INSERT INTO cluster3 SET gridimage_id = {$row['id']}";
			foreach ($r as $key => $value) {
				$sql .= ", $key = $value";
			}
			print "$sql;\n";
			$db->Execute($sql);
		}
	}
        $recordSet->MoveNext();
}
$recordSet->Close();


//todo, will do add something like '$days-takendays as days' to the query once the viewpoint index gains the takendays attribute.
// for now backfill...

$db->Execute("update cluster3 c inner join gridimage_search o using (gridimage_id) inner join gridimage_search t on (t.gridimage_id = c.id)
 set days = datediff(o.imagetaken,REPLACE(t.imagetaken,'-00','-01')) where c.days is null");



