<?

$d = getcwd();

$param = array('execute'=>0, 'file'=>'islands.latng.csv', 'table'=>'rsgb_islands', 'create'=>1, 'auto_id'=>false, 'print'=>1, 'limit'=>10, 'bom'=>true, 'replace'=>0, 'split'=>0, 'drop'=>0, 'break'=>100);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

function myquote($in) {
	if (is_numeric($in))
		return $in;
	global $db;
	//if (str_starts_with($in,'MULTILINESTRING')) //not purfect, but ok for now!
	if (preg_match('/^(MULTI)?(LINESTRING|POLYGON|POINT) /',$in))
		return "ST_GeomFromText(".$db->Quote($in).")";
	return $db->Quote($in);
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

#####################################################

chdir($d);

if ($param['create']) {
	$s = array();

	//this allows importing multiple files into one table. but it assumes they all have the same schema!
	foreach(explode(',',$param['file']) as $filename) {
		$h = gzopen($filename,'r');
		if ($param['bom'])
			fseek($h, 3);

		$head = fgetcsv($h);

		while($h && !feof($h)) {
			$line = fgetcsv($h);
			foreach($line as $idx => $value) {
				if (empty($value)) {
					@$s[$idx]['empty']++;
				} elseif (is_numeric($value)) {
					if (floor($value) == $value) {
						@$s[$idx]['int']++; //dont bother checking sign, to allow for unsigned. e/n is best signed to allow for distnace math easier
						if ($value > @$s[$idx]['max'])
							$s[$idx]['max'] = $value;
					} else
						@$s[$idx]['float']++;
				} else {
					$len = strlen($value);
					if ($len > @$s[$idx]['maxlen'])
						$s[$idx]['maxlen'] = $len;
					@$s[$idx]['totlen'] += $len;
					@$s[$idx]['count']++;
					@$s[$idx]['values'][$value]++;
				}

//if ($param['split'] && $idx > $param['limit'] && !$param['execute'])
//	break;

			}
		}
	}

	$str= "CREATE TABLE {$param['table']}"; $sep = "(\n";
	foreach ($head as $idx => $name) {
		$str .= "$sep `$name`";
		if ($name == 'WKT')
			$str .= " GEOMETRYCOLLECTION NOT NULL";
		elseif (empty($s[$idx]['count'])) { // means as string!
			if (@$s[$idx]['float'])
				$str .= " FLOAT NOT NULL";
			elseif (@$s[$idx]['int'])
				$str .= " INT NOT NULL COMMENT 'max={$s[$idx]['max']}'";
			else
				//can only mean all empty?
				$str .= " CHAR(1) NOT NULL";
		} elseif (count($s[$idx]['values']) <=10) { //todo and doesnt contain commas!
			$str .= " ENUM(".implode(',',array_map(array($db,'Quote'),array_keys($s[$idx]['values']))).") NOT NULL";
		} else {
			$str .= " VARCHAR({$s[$idx]['maxlen']}) NOT NULL COMMENT 'avglen=".sprintf('%.2f',$s[$idx]['totlen']/$s[$idx]['count']).", unique=".count($s[$idx]['values'])."'";
		}

		$sep = ",\n";
	}
	if (!empty($param['auto_id'])) {
		$str .= "$sep `auto_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY\n";
	}
	$str .=")";

	print "$str;\n";

	if ($param['execute']) {
		if ($param['drop'] && $param['split']) //help prevent accidental use TODO is only allow dropping created tables?
			$db->Execute("DROP TABLE IF EXISTS `{$param['table']}`");
		$db->Execute($str);
	}
}

#####################################################

foreach(explode(',',$param['file']) as $filename) {
	$h = gzopen($filename,'r');

	if ($param['bom'])
		fseek($h, 3);
	else
		fseek($h, 0);

	$head = fgetcsv($h); //we already read it, do again just to move to next line

	$db->Execute("SET NAMES utf8"); //dont know if this really enough!

	$c=0;
	$break = $param['break'];

	if ($param['replace']) {
		$str = $insert = "REPLACE INTO {$param['table']} (`".implode("`,`",$head)."`) VALUES ";
	} else {
		$str = $insert = "INSERT INTO {$param['table']} (`".implode("`,`",$head)."`) VALUES ";
	}
	$sep = "\n";

	while($h && !feof($h)) {
		$line = fgetcsv($h);
		if ($param['split']) {
					//filter all three brackets, as want to split on double brackets only. Polygones also mutli-value! (holes!)
			$line[0] = preg_replace('/^MULTIPOLYGON \(\(\(/','',$line[0]);
			$line[0] = preg_replace('/\)\)\)$/','',$line[0]);
			foreach(explode(')),((',$line[0]) as $bit) {
				$line[0] = "POLYGON ((".$bit."))"; //still wants double brackets
				$str .= $sep.'('.implode(',',array_map('myquote',$line)).')';
				$sep = ",\n";

				$c++;

				if (!($c%$break))
					execute($str, $sep);
			}
		} else {
			$str .= $sep.'('.implode(',',array_map('myquote',$line)).')';
			$sep = ",\n";

			$c++;
			if ($c == $param['limit'])
				break;
			if (!($c%$break))
				execute($str, $sep);
		}
	}
}

execute($str, $sep);

#######################

function execute(&$str, &$sep) {
	global $insert, $db, $param;

	if ($param['print'])
		print "$str;\n";
	if ($param['execute']) {
                $db->Execute($str);
		print "affected: ".$db->Affected_Rows()." ($c)\n";
	}
	$str = $insert;  $sep = "\n";
}
