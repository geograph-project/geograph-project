<?

$param = array('execute'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#####################################################

$h = gzopen("http://geo.hlipp.de/dumps/images_max.json.gz",'r');
while($h && !feof($h)) {
        $str = fgets($h);
$str = str_replace("'",'"',$str);
        $r = json_decode($str,TRUE);
	$max_id = intval($r['gridimage_id']);
}


$db->Execute("SET NAMES utf8"); //dont know if this really enough!
$c=0;
for($start = 1; $start < $max_id; $start+=20000) {
	$url = sprintf("http://geo.hlipp.de/dumps/images_combined.%08d.json.gz",$start);
	print "$url\n";

	$h = gzopen($url,'r');
	//$h = gzopen("/tmp/images_combined.00140001.json.gz",'r');
	//$h = gzopen("http://geo.hlipp.de/dumps/images_combined.json.gz",'r'); $max_id = 1;
	//$h = gzopen("/tmp/images_combined.json.gz",'r'); $max_id = 1;

	fseek($h,1); //skip over the opening [
	while($h && !feof($h)) {
		$str = fgets($h);
		$r = json_decode(rtrim($str,"\n,]"),TRUE);

		if (!$db->Execute($sql = 'REPLACE INTO geograph_germany.gridimage_search SET `'.implode('` = ?,`',array_keys($r)).'` = ?',array_values($r))) {
			print "$c. $str";
			print_r($r);
			print "$sql;\n\n";

			print mysql_error()."\n\n";
			exit;
		}
		$c++;

		if (!($c%100))
			print "$c ";
	}

	print "$c.\n";
}

$h = gzopen("http://geo.hlipp.de/dumps/images_rejected.json.gz",'r');
while($h && !feof($h)) {
        $str = fgets($h);
	$r = json_decode($str,TRUE);

        if (!$db->Execute($sql = 'DELETE FROM geograph_germany.gridimage_search WHERE gridimage_id in ('.implode(',',$r).')') ) {
                print_r($r);
                print "$sql;\n\n";

                print mysql_error()."\n\n";
                exit;
        }
	print "deleted = ".mysql_affected_rows()."\n";
}

$cmd = "sudo -u manticore indexer germany --rotate";

print "now run...\n";
print "$cmd\n";

//todo run automatically?
//if (hostname = 'tea') passthru($cmd); ??
