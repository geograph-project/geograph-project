<?

//these are the arguments we expect
$param=array('limit' => 1000);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

require_once('geograph/conversions.class.php');

require_once('geograph/conversionslatlong.class.php');
$conv = new ConversionsLatLong;


############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
$limit = $param['limit'];

print "max = $max, existing = $existing, limit = $limit\n";

for($start = 1; $start <= $max; $start += $limit) {
        $end = $start + $limit -1;

        $where = "gridimage_id BETWEEN $start AND $end";
	$where .= " AND gi.vlat < 1";

        print "$where\n";

        $tim = time();

        $join = strpos($where,'gi.')?'LEFT JOIN gridimage_search gi USING (gridimage_id)':'';
        $recordSet = $db->Execute("SELECT gridimage_id,viewpoint_eastings,viewpoint_northings,gs.reference_index
                 FROM gridimage g INNER JOIN gridsquare gs USING (gridsquare_id) $join
                 WHERE viewpoint_eastings > 0 AND g.moderation_status != 'rejected' AND $where");

        $count = $recordSet->recordCount();
        printf("got %d rows at %d seconds\n",$count,time()-$tim);
        if (!$count) {
                $recordSet->Close();
                continue;
        }


        $count=0;
        while (!$recordSet->EOF) {

                //copied direct from libs/geograph/gridimage.class.php
                list($lat,$long) = $conv->national_to_wgs84($recordSet->fields['viewpoint_eastings'],$recordSet->fields['viewpoint_northings'],
                        $recordSet->fields['reference_index']);

                $lat = sprintf("%.6f",$lat);//going to be put into decimal anyway, avoids mysql warning
                $long = sprintf("%.6f",$long);//going to be put into decimal anyway, avoids mysql warning

                $sql = "UPDATE gridimage_search SET vlat = $lat, vlong = $long WHERE gridimage_id = ".$recordSet->fields['gridimage_id'];

                $db->Execute($sql) or die(mysql_error($db->_connection));

                $recordSet->MoveNext();
                $count++;
        }
        $recordSet->Close();

        printf("done %d in %d seconds\n",$count,time()-$tim);

}



