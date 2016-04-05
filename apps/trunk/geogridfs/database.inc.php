<?

$mounts = '';
foreach (file(__DIR__.'/config.py') as $line)
        if (preg_match("/\s(\w+) = '([\w\/-]+)'/",$line,$m))
                $mounts[$m[1]] = $m[2];

$db =mysql_connect($mounts['hostname'],$mounts['username'],$mounts['password']) or die(mysql_error());
mysql_select_db($mounts['database'],$db) or die(mysql_error());

#####

function dbQuote($in) {
        return "'".mysql_real_escape_string($in)."'";
}

function queryExecute($query,$debug=false) {
        global $db;
	if ($debug) {
		print "#Starting ".date('r')."\n";;
		print "$query\n";
	}
        $result = mysql_query($query, $db) or print('<br>Error queryExecute: '.mysql_error());
	if ($debug) {
		print "#Done ".date('r')." : ".mysql_affected_rows()." Rows\n---------\n";

	}
        return $result;
}

function getOne($query) {
        global $db;
        $result = mysql_query($query, $db) or print("<br>Error getOne [[ $query ]] : ".mysql_error());
        if (mysql_num_rows($result)) {
                return mysql_result($result,0,0);
        } else {
                return FALSE;
        }
}

function getRow($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getRow: '.mysql_error());
        if (mysql_num_rows($result)) {
                return mysql_fetch_assoc($result);
        } else {
                return FALSE;
        }
}

function getCol($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getColAsKeys: '.mysql_error());
        if (!mysql_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        while($row = mysql_fetch_row($result)) {
                $a[] = $row[0];
        }
        return $a;
}

function getColAsKeys($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getColAsKeys: '.mysql_error());
        if (!mysql_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        while($row = mysql_fetch_row($result)) {
                $a[$row[0]] = '';
        }
        return $a;
}


function getAll($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getAll: '.mysql_error());
        if (!mysql_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        while($row = mysql_fetch_assoc($result)) {
                $a[] = $row;
        }
        return $a;
}


function getAssoc($query) {
        global $db;
        $result = mysql_query($query, $db) or print('<br>Error getAssoc: '.mysql_error());
        if (!mysql_num_rows($result)) {
                return FALSE;
        }
        $a = array();
        $row = mysql_fetch_assoc($result);

        if (count($row) > 2) {
                do {
                        $i = array_shift($row);
                        $a[$i] = $row;
                } while($row = mysql_fetch_assoc($result));
        } else {
                $row = array_values($row);
                do {
                        $a[$row[0]] = $row[1];
                } while($row = mysql_fetch_row($result));
        }
        return $a;
}

function print_rp($q) {
        print "<pre style='border:1px solid red; padding:10px; text-align:left; background-color:silver'>";
        print_r($q);
        print "</pre>";
}


###################################
# Create some replicate tasks. Note we dont need to specify a source, as the worker will automatically find source per file.

function write_replicate_task($target,$clause,$avoidover=true,$avoiddup=true,$order = 'NULL') {
	if ($avoidover) {
		$clause .= " AND replica_count < replica_target";
	}

	if ($target == 'ssd|rand') {
		$clause .= " AND replicas NOT RLIKE 's[[:digit:]]'";
		$targetQ = "CONCAT(IF(RAND()>0.5,'tea','cake'),'s',IF(RAND()>0.5,'1','2'))";

	} elseif ($target == 'hard|rand') {
		$clause .= " AND replicas NOT RLIKE 'h[[:digit:]]'";
		$targetQ = "CONCAT(IF(RAND()>0.5,'tea','cake'),'h',IF(RAND()>0.5,'1','2'))";

	} elseif ($target == 'hard|empty') {
		$target = getOne("select mount,(available*1024)-coalesce(sum(bytes),0) as av from mounts left join replica_task on (target=mount and executed < 1) where mount like '%h_' group by mount order by av desc limit 1");

		$clause .= " AND replicas NOT LIKE ".dbQuote("%".preg_replace('/\d$/','',$target)."%");
		$targetQ = dbQuote($target);

	} else {
		$clause .= " AND replicas NOT LIKE ".dbQuote("%$target%");
		$targetQ = dbQuote($target);
	}

	$clauseQ = dbQuote($clause);

	if ($avoiddup) {
		$clause .= " AND shard NOT IN(select distinct shard from replica_task where clause = $clauseQ)";
	}

	queryExecute($sql = "INSERT INTO replica_task
        SELECT NULL,shard,SUM(`count`) AS files,SUM(`bytes`) as bytes,$clauseQ AS `clause`,$targetQ AS target,NOW() as created,0 AS `executed`
        FROM file_stat
        WHERE $clause AND replica_count > 0
        GROUP BY shard ORDER BY $order");

	if (!empty($_GET['debug'])) {
		print "-------\n$sql;\n----------\n";
		print "Affected Rows: ".mysql_affected_rows()."\n\n";
	}
}

###################################
# create some drain tasks

function write_drain_task($target,$clause,$where=NULL,$avoiddup=true,$order = 'NULL') {
	if (empty($where))
		$where = $clause;

	if ($target == 'ssd|rand') {
		die("unimplemtned");
	} elseif ($target == 'hard|rand') {
		die("unimplemtned");
	} elseif ($target == 'hard|empty') {
		die("unimplemtned");

	} else {
		//$clause .= " AND replicas LIKE ".dbQuote("%$target%"); //DONT need this in the clause, because the worker will do it anyway.
		$where .= " AND replicas LIKE ".dbQuote("%$target%"); //but have it on clause just to create the rules correctly
		$targetQ = dbQuote($target);
	}

	$clauseQ = dbQuote($clause);

	if ($avoiddup) {
		$where .= " AND shard NOT IN(select distinct shard from replica_task where clause = $clauseQ)";
	}

	queryExecute($sql = "INSERT INTO replica_task
        SELECT NULL,shard,SUM(`count`) AS files,SUM(`bytes`) as bytes,$clauseQ AS `clause`,$targetQ AS target,NOW() as created,0 AS `executed`
        FROM file_stat
        WHERE $where AND replica_count > 0
        GROUP BY shard ORDER BY $order");

	if (!empty($_GET['debug'])) {
		print "-------\n$sql;\n----------\n";
		print "Affected Rows: ".mysql_affected_rows()."\n\n";
	}
}

