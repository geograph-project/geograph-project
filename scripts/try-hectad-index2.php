<?

//these are the arguments we expect
$param=array('prefix'=>'T','explain'=>true);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$data = $db->getRow("SELECT * FROM gridprefix WHERE prefix = '{$param['prefix']}'");

 $ri = $data['reference_index'];
 $letterlength = 3 - $ri; //or could be strlen($data['prefix']) :)
 $prefix = $data['prefix'];

###############################################

 run_query("grid_reference LIKE '{$prefix}____'");
 run_query("grid_reference LIKE '{$prefix}____'",'FORCE INDEX(grid_reference)');

 $left=$data['origin_x'];
 $right=$data['origin_x']+99; //we could use width, but lets just grab whole square, we ARE filtering by reference_index anyway.
 $top=$data['origin_y']+99;
 $bottom=$data['origin_y'];

 $where = "y BETWEEN $bottom AND $top AND x BETWEEN $left AND $right";
 $where.= " AND reference_index = $ri";

 run_query($where);
 run_query($where,'FORCE INDEX(x)');

 $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";
 $where = "CONTAINS(GeomFromText($rectangle),point_xy)";
 $where.= " AND reference_index = $ri";
 run_query($where);
 run_query($where,'FORCE INDEX(point_xy)');

###############################################


function run_query($where,$joiner = '') {
	global $CONF,$param,$db,$ri,$letterlength,$prefix;

	$start = microtime(true);

        $rows = $db->getAll(($param['explain']?'EXPLAIN ':'')."

			SELECT SQL_NO_CACHE tag_id, gi.user_id, grid_reference, COUNT(*) AS images
                        FROM gridimage_tag
                        INNER JOIN gridimage_search gi $joiner USING (gridimage_id)
                        WHERE $where AND status = 2
                        GROUP BY tag_id, gi.user_id, grid_reference
                        ORDER BY NULL");

	$end = microtime(true);

	if ($param['explain']) {
		print "\n\n";
		print implode("\t",array_keys($rows[0]))."\n";
		$hash = '';
		foreach ($rows as $row) {
			$hash .= implode("\t",$row)."\n";
		}
		print $hash;
	} else {
		$hash = count($rows);
		foreach ($rows as $row)
			$hash .= ".{$row['tag_id']},{$row['grid_reference']},{$row['images']}";
	}

	printf("%3.3f  %s  %s  %s\n", $end-$start, md5($hash), $joiner, $where);
}
