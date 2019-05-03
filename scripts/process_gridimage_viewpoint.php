<?

//these are the arguments we expect
$param=array('limit' => 10000);

chdir(__DIR__);
require "./_scripts.inc.php";

/*
CREATE TABLE `gridimage_viewpoint` (
  `gridimage_id` int(10) unsigned NOT NULL,
  `vlat` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `vlong` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `calculated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`gridimage_id`)
) ENGINE=MyISAM;
*/

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


$existing = $db->getOne("SELECT COUNT(*) FROM gridimage_viewpoint");
$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
$limit = $param['limit'];

print "max = $max, existing = $existing, limit = $limit\n";

for($start = 1; $start <= $max; $start += $limit) {
	$end = $start + $limit -1;

	$where = "gridimage_id BETWEEN $start AND $end";
	if ($existing)
		$where .= " AND (gv.gridimage_id IS NULL OR calculated < upd_timestamp)";

	print "$where\n";

	$tim = time();

	$join = strpos($where,'gv.')?'LEFT JOIN gridimage_viewpoint gv USING (gridimage_id)':'';
        $recordSet = $db->Execute("SELECT gridimage_id,viewpoint_eastings,viewpoint_northings,reference_index
		 FROM gridimage INNER JOIN gridsquare USING (gridsquare_id) $join
		 WHERE viewpoint_eastings > 0 AND moderation_status != 'rejected' AND $where");

	$count = $recordSet->recordCount();
	printf("got %d rows at %d seconds\n",$count,time()-$tim);
	if (!$count) {
		$recordSet->Close();
		continue;
	}

	$count=0;
        while (!$recordSet->EOF) {

		//copied direct from libs/geograph/gridimage.class.php
                list($lat,$long) = national_to_wgs84($recordSet->fields['viewpoint_eastings'],$recordSet->fields['viewpoint_northings'],
			$recordSet->fields['reference_index']);

		$lat = sprintf("%.6f",$lat);//going to be put into decimal anyway, avoids mysql warning
		$long = sprintf("%.6f",$long);//going to be put into decimal anyway, avoids mysql warning

		$sql = "REPLACE INTO gridimage_viewpoint SET vlat = $lat, vlong = $long, gridimage_id = ".$recordSet->fields['gridimage_id'];

		$db->Execute($sql) or die(mysql_error($db->_connection));

                $recordSet->MoveNext();
		$count++;
	}
        $recordSet->Close();

	printf("done %d in %d seconds\n",$count,time()-$tim);

}
