<?

chdir(__DIR__);

include __DIR__."/database.inc.php";


foreach(explode("\n",`df -P`) as $line) {
	$bits = preg_split('/\s+/',$line);
	if (!empty($bits[5]) && $key = array_search($bits[5],$mounts)) {
		$sql = "REPLACE INTO mounts SET mount = '$key'";
		$sql .= ", total =".intval($bits[1]);
		$sql .= ", used =".intval($bits[2]);
		$sql .= ", available =".intval($bits[3]);
		$sql .= ", capacity =".intval($bits[4]);
		mysql_query($sql);
	}
}
