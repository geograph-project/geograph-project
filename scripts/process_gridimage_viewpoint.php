<?

//these are the arguments we expect
$param=array('limit' => 100000);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

require_once('geograph/conversions.class.php');

require_once('geograph/conversionslatlong.class.php');
$conv = new ConversionsLatLong;

//duplicated here - because the one in conversions.class keep recreating the ConversionsLatLong class!
function national_to_wgs84($e,$n,$reference_index,$usehermert = true,$truncate = false) {
	global $conv;

        $latlong = array();
        if ($reference_index == 1) {
                $latlong = $conv->osgb36_to_wgs84($e,$n);
        } else if ($reference_index == 2) {
                $latlong = $conv->irish_to_wgs84($e,$n,$usehermert);
        }
        if ($truncate) {
                $latlong[0] = sprintf("%.6f",$latlong[0]);
                $latlong[1] = sprintf("%.6f",$latlong[1]);
        }

        return $latlong;
}

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
$limit = $param['limit'];

print "max = $max, limit = $limit\n";

$tim = time();

for($start = 1; $start <= $max; $start += $limit) {
	$end = $start + $limit -1;

	$where = "gridimage_id BETWEEN $start AND $end";

	print "$where; ";


        $recordSet = $db->Execute("SELECT gridimage_id,viewpoint_eastings,viewpoint_northings,reference_index
		 FROM gridimage gi INNER JOIN gridimage_search gv USING (gridimage_id)
		 WHERE viewpoint_eastings > 0 AND vlat = 0 AND $where");

	$count = $recordSet->recordCount();
	printf("got %d rows at %d seconds; ",$count,time()-$tim);
	if (!$count) {
		$recordSet->Close();
		print "\n";
		continue;
	}

	$count=0;
        while (!$recordSet->EOF) {

		//copied direct from libs/geograph/gridimage.class.php
                list($lat,$long) = national_to_wgs84($recordSet->fields['viewpoint_eastings'],$recordSet->fields['viewpoint_northings'],
			$recordSet->fields['reference_index']);

		$lat = sprintf("%.6f",$lat);//going to be put into decimal anyway, avoids mysql warning
		$long = sprintf("%.6f",$long);//going to be put into decimal anyway, avoids mysql warning

		$sql = "UPDATE gridimage_search SET vlat = $lat, vlong = $long, upd_timestamp=upd_timestamp WHERE gridimage_id = ".$recordSet->fields['gridimage_id'];

		$db->Execute($sql) or die("$sql;\n".$db->ErrorMsg()."\n");

                $recordSet->MoveNext();
		$count++;
	}
        $recordSet->Close();

	printf("done %d in %d seconds\n",$count,time()-$tim);

}
