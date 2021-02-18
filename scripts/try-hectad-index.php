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

 run_query("grid_reference LIKE '$prefix%'");
 run_query("grid_reference LIKE '$prefix%'",'FORCE INDEX(grid_reference)');

  //we group using CONF['origins'] rather than gridprefix.origin_x because while gridprefix should contain whole square, it not nesseraily aligned to hectad boundaries!
 $left=$data['origin_x'];
 $right=$data['origin_x']+99; //we could use width, but lets just grab whole square, we ARE filtering by reference_index anyway.
 $top=$data['origin_y']+99;
 $bottom=$data['origin_y'];

 $where = "y BETWEEN $bottom AND $top AND x BETWEEN $left AND $right";

 run_query($where);
 run_query($where,'FORCE INDEX(x)');
 run_query($where,'FORCE INDEX(y)');

 $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";
 $where = "CONTAINS(GeomFromText($rectangle),point_xy)";
 run_query($where);
 run_query($where,'FORCE INDEX(point_xy)');

###############################################


function run_query($where,$joiner = '') {
	global $CONF,$param,$db,$ri,$letterlength,$prefix;

	$start = microtime(true);

        $rows = $db->getAll(($param['explain']?'EXPLAIN ':'')."
                                SELECT SQL_NO_CACHE
                                        reference_index,min(x) as x,min(y) as y,
                                        CONCAT(SUBSTRING(grid_reference,1,".($letterlength+1)."),SUBSTRING(grid_reference,".($letterlength+3).",1)) AS hectad,
                                        COUNT(DISTINCT gs.gridsquare_id) AS landsquares,
                                        COUNT(gridimage_id) AS images,
                                        SUM(moderation_status = 'geograph') AS geographs,
                                        COUNT(DISTINCT gi.gridsquare_id) AS squares,
                                        COUNT(DISTINCT IF(moderation_status='geograph',gi.gridsquare_id,NULL)) AS geosquares,
                                        COUNT(DISTINCT IF(has_recent=1,gs.gridsquare_id,NULL)) AS recentsquares,
                                        COUNT(DISTINCT user_id) AS users,
                                        MIN(IF(ftf=1,submitted,NULL)) AS first_submitted,
                                        MAX(IF(ftf=1,submitted,NULL)) AS last_submitted,
                                        '' AS map_token,
                                        '' AS largemap_token,
                                        COUNT(DISTINCT IF(ftf=1,user_id,NULL)) AS ftfusers
                                        FROM gridsquare gs $joiner
                                        LEFT JOIN gridimage gi ON (gs.gridsquare_id=gi.gridsquare_id AND moderation_status IN ('geograph','accepted'))
                                        WHERE reference_index = $ri AND $where AND percent_land >0
                                        GROUP BY (x-{$CONF['origins'][$ri][0]}) div 10,(y-{$CONF['origins'][$ri][1]}) div 10
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
			$hash .= ".{$row['landsquares']},{$row['last_submitted']},{$row['images']}";
	}

	printf("%3.3f  %s  %s  %s\n", $end-$start, md5($hash), $joiner, $where);
}
