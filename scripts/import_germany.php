<?

$param = array('execute'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#####################################################

$prefix = 'de:';
$key = "geographkey_".trim($prefix,':');

$table = "geograph_germany.gridimage_search";
$urlprefix = "http://geo.hlipp.de/dumps/";

#####################################################

$url = "{$urlprefix}images_max.json.gz";

if (!empty($CONF[$key]))
     	$url .= "?key=".$CONF[$key];

$h = gzopen($url,'r');
while($h && !feof($h)) {
        $str = fgets($h);
$str = str_replace("'",'"',$str);
        $r = json_decode($str,TRUE);
	$max_id = intval($r['gridimage_id']);
}

if (empty($max_id))
	die("Unable to fetch/decode images_max\n");

#####################################################

$db->Execute("SET NAMES utf8"); //dont know if this really enough!
$c=0;
for($start = 1; $start < $max_id; $start+=20000) {
	$url = sprintf("{$urlprefix}images_combined.%08d.json.gz",$start);
	print "$url\n";

	if (!empty($CONF[$key]))
        	$url .= "?key=".$CONF[$key];

	$h = gzopen($url,'r');

	fseek($h,1); //skip over the opening [
	while($h && !feof($h)) {
		$str = fgets($h);
		$r = json_decode(rtrim($str,"\n,]"),TRUE);

		if (!$db->Execute($sql = 'REPLACE INTO '.$table.' SET `'.implode('` = ?,`',array_keys($r)).'` = ?',array_values($r))) {
			print "$c. $str";
			print_r($r);
			print "$sql;\n\n";

			print $db->ErrorMsg()."\n\n";
			exit;
		}
		$c++;

		if (!($c%100))
			print "$c ";
	}

	print "$c.\n";
}

#####################################################

$url = "{$urlprefix}images_rejected.json.gz";

if (!empty($CONF[$key]))
       	$url .= "?key=".$CONF[$key];

$h = gzopen($url,'r');
while($h && !feof($h)) {
        $str = fgets($h);
	$r = json_decode($str,TRUE);

        if (!$db->Execute($sql = 'DELETE FROM '.$table.' WHERE gridimage_id in ('.implode(',',$r).')') ) {
                print_r($r);
                print "$sql;\n\n";

                print $db->ErrorMsg()."\n\n";
                exit;
        }
	print "deleted = ".$db->Affected_Rows()."\n";
}

#####################################################

//we can now mark them as 'needing' building in the database, which automatically triggers indexer!
$db->Execute("update sph_server_index set last_indexed = '2000-01-01 00:00:00' where index_name = 'germany'");


//$cmd = "sudo -u manticore indexer germany --rotate";

//print "now run...\n";
//print "$cmd\n";

//todo run automatically?
//if (hostname = 'tea') passthru($cmd); ??
