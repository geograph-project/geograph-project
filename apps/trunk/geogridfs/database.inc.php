<?

#####
// bring in the live mounts

$mounts = '';
foreach (file(__DIR__.'/config.py') as $line)
        if (preg_match("/\s(\w+) = '([\w\/-]+)'/",$line,$m))
                $mounts[$m[1]] = $m[2];

//this is odd, but the mounts array, also includes the database config!
$db =mysql_connect($mounts['hostname'],$mounts['username'],$mounts['password']) or die(mysql_error());
mysql_select_db($mounts['database'],$db) or die(mysql_error());

#####
// bring in the CONF array (needed for photohashing secret, and the live DB config

include "/var/www/geograph_live/libs/conf/www.geograph.org.uk.conf.php";

function liveQuery($sql) {
        global $dblive,$CONF;

        if (empty($dblive) || !mysql_ping($dblive)) {
                if ($dblive)
                         mysql_close($dblive);

                @$dblive = mysql_connect($CONF['db_connect'],$CONF['db_user'],$CONF['db_pwd']) or die(mysql_error());
                mysql_select_db($CONF['db_db'],$dblive);
        }
	if (!empty($sql)) { //smalll trick to allow just to be used to connect!
	        $result = mysql_query($sql,$dblive) or die("mysql error\n$sql\n".mysql_error($dblive));
	        return $result;
	}
}


#####
// general database function

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
		print "#Done ".date('r')." : ".mysql_affected_rows($db)." Rows\n---------\n";

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

function write_replicate_task($target,$clause,$avoidover=true,$avoiddup=true,$order = 'shard',$return_query=false) {
	global $db;
	if ($avoidover) {
		$clause .= " AND replica_count < replica_target";
	}

	if ($target == 'ssd|rand') {
		$clause .= " AND replicas NOT RLIKE 's[[:digit:]]'";
		$targetQ = "CONCAT(IF(RAND()>0.5,'tea','cake'),'s',IF(RAND()>0.5,'1','2'))";

	} elseif ($target == 'hard|rand') {
		$clause .= " AND replicas NOT RLIKE 'h[[:digit:]]'";
		$targetQ = "CONCAT(IF(RAND()>0.5,'tea','cake'),'h',IF(RAND()>0.5,'1','2'))";

	} elseif (preg_match('/^(.*)\|empty/',$target,$m)) {
		$f = ($m[1] == 'hard')?'%h_':$m[1];
		$target = getOne($sql = "select mount,(available*1024)-coalesce(sum(bytes),0) as av from mounts left join replica_task on (target=mount and executed < 1) where mount like '$f' group by mount order by av desc limit 1");

		$clause .= " AND replicas NOT LIKE ".dbQuote("%".preg_replace('/\d$/','',$target)."%");
		$targetQ = dbQuote($target);

	} else {
		$clause .= " AND replicas NOT LIKE ".dbQuote("%$target%");
		$targetQ = dbQuote($target);
	}

	$where = $clause;
	$clauseQ = dbQuote($clause);

	if ($avoiddup) {
		//if latest shard, always run!
		$current = getOne("select max(file_id) div 10000 as shard from file");
		$where .= " AND (shard=$current OR shard NOT IN(select distinct shard from replica_task where clause = $clauseQ))";
	}

	$sql = "
        SELECT NULL,shard,SUM(`count`) AS files,SUM(`bytes`) as bytes,$clauseQ AS `clause`,$targetQ AS target,NOW() as created,0 AS `executed`
        FROM file_stat
        WHERE $where AND replica_count > 0
        GROUP BY shard ORDER BY $order";

	if ($return_query)
		return $sql;

	queryExecute($sql = "INSERT INTO replica_task $sql");

	if (!empty($_GET['debug'])) {
		print "-------\n$sql;\n----------\n";
		print "Affected Rows: ".mysql_affected_rows($db)."\n\n";
	}
}

###################################
# create some drain tasks

function write_drain_task($target,$clause,$where=NULL,$avoiddup=true,$order = 'NULL',$return_query=false) {
	global $db;
	if (empty($where))
		$where = $clause;

	$join = ''; $group = 'shard';
	if ($target == 'mount' || preg_match('/[%_]/',$target)) {
		$join = "INNER JOIN mounts ON (replicas LIKE CONCAT('%',mount,'%'))";
		//dont need to modify clause, because the join will make filter for target.
	        if (preg_match('/[%_]/',$target)) {
	                $where .= ' AND mount LIKE '.dbQuote($target);
        	}
		$targetQ = 'mount';
		//$group = "mount,shard"; //if the same file is on multiple mounts, will be included in multiple tasks,
					// so to be careful, that the file doesnt get drained from each mount (eg replica_count > replica_target in caluse, would help avoid draining to much, first mount to exercute wins!)
	} else {
		//$clause .= " AND replicas LIKE ".dbQuote("%$target%"); //DONT need this in the clause, because the worker will do it anyway.
		$where .= " AND replicas LIKE ".dbQuote("%$target%"); //but have it on clause just to create the rules correctly
		$targetQ = dbQuote($target);
	}

	$clauseQ = dbQuote($clause);

	if ($avoiddup) {
		$where .= " AND shard NOT IN(select distinct shard from drain_task where clause = $clauseQ)";
	}

	$sql = "
        SELECT NULL,shard,SUM(`count`) AS files,SUM(`bytes`) as bytes,$clauseQ AS `clause`,$targetQ AS target,NOW() as created,0 AS `executed`,0 AS defer_until
        FROM file_stat $join
        WHERE $where AND replica_count > 1
        GROUP BY $group ORDER BY $order";

        if ($return_query)
                return $sql;

	queryExecute($sql = "INSERT INTO drain_task $sql");

	if (!empty($_GET['debug'])) {
		print "-------\n$sql;\n----------\n";
		print "Affected Rows: ".mysql_affected_rows($db)."\n\n";
	}
}

###################

function runfixes($where,$where2,$fix, $limit = 10000, $order = 'count DESC', $maxbytes = null) {
	global $db;

	$rows = getAll($sql = "select shard,sum(count) as count,sum(bytes) as bytes from file_stat where $where GROUP BY shard ORDER BY $order LIMIT $limit");

print "$sql;\n";
//print_r($rows);
//return;

	$running = 0;
	if (count($rows))
	foreach ($rows as $row) {
		print "### {$row['shard']} / {$row['count']}\n";
		$start = $row['shard']*10000;
		$end = $start+9999;

		$result = queryExecute("UPDATE file SET $fix WHERE $where AND $where2 AND file_id BETWEEN $start AND $end");

			print "$where,$where2,$fix. = ".mysql_affected_rows($db)."\n";

		if (!empty($maxbytes)) {
			$running += $row['bytes'];
			if ($running > $maxbytes) {
				print "Stopped at $running\n";
				return; //`return` rather than `break`, so dont run the final query
			}
		}
	}

	//last loop to catch files not yet in file_stat
	$start = getOne("SELECT MAX(file_id) FROM file")-20000;

	$result = queryExecute("UPDATE file SET $fix WHERE $where AND $where2 AND file_id > $start", true);
}

############

function getGeographPath($gridimage_id,$hash,$size ='small') {

       $yz=sprintf("%02d", floor($gridimage_id/1000000));
       $ab=sprintf("%02d", floor(($gridimage_id%1000000)/10000));
       $cd=sprintf("%02d", floor(($gridimage_id%10000)/100));
       $abcdef=sprintf("%06d", $gridimage_id);

        if ($yz == '00') {
                $fullpath="photos/$ab/$cd/{$abcdef}_{$hash}";
        } else {
                $fullpath="geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}";
        }

       switch($size) {
                case 'orig': return "{$fullpath}_original.jpg"; break;
               case 'full': return "$fullpath.jpg"; break;
               case 'med': return "{$fullpath}_213x160.jpg"; break;
               case '800': return "{$fullpath}_800x800.jpg"; break;
               case '1024': return "{$fullpath}_1024x1024.jpg"; break;
               case 'small':  return "{$fullpath}_120x120.jpg";
               default: return "{$fullpath}{$size}.jpg"; //this is a custom version for geogridfs
       }
}

function getGeographUrl($gridimage_id,$hash,$size = 'small') {
        $ab=sprintf("%02d", floor(($gridimage_id%1000000)/10000));
      $cd=sprintf("%02d", floor(($gridimage_id%10000)/100));
      $abcdef=sprintf("%06d", $gridimage_id);
                if ($gridimage_id<1000000) {
                        $fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}";
                } else {
                        $yz=sprintf("%02d", floor($gridimage_id/1000000));
                        $fullpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}";
                }
      $server =  "https://s".($gridimage_id%4).".geograph.org.uk";

      switch($size) {
              case 'full': return "https://s0.geograph.org.uk$fullpath.jpg"; break;
              case 'med': return "$server{$fullpath}_213x160.jpg"; break;
              case '800': return "$server{$fullpath}_800x800.jpg"; break;
              case '1024': return "$server{$fullpath}_1024x1024.jpg"; break;
              case 'small':
              default: return "$server{$fullpath}_120x120.jpg";

        }
}

function opt_and_inp($opt) {
	global $options;
	global $inputs;
	global $argv;
	$options = getopt($opt);
	//http://php.net/manual/en/function.getopt.php#74190

	$inputs = $argv; array_shift($inputs);
	foreach( $options as $o => $a )
	{
	    while( ($k = array_search( "-" . $o, $inputs ) ) !== FALSE )
	    {
	      unset( $inputs[$k] );
	      if( preg_match( "/".$o.":/i", $opt ) && $inputs[$k+1] == $a )
	        unset( $inputs[$k+1] );
	    }
	}
}

