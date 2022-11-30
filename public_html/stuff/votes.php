<?

require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("admin");


$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');


$type = "i53295783";
if (!empty($_GET['type']) && preg_match('/^\w+$/',$_GET['type']))
	$type = $_GET['type'];


$start = 0;
$break = 7;
if (!empty($_GET['one']))
	$break = 2;
if (!empty($_GET['month']))
        $start = 5;


//select type,max(users) as users,avg(avg),count(*) as count,last_vote from vote_stat group by type having count between 52 and 53 order by last_vote;

if (!empty($_GET['simple'])) {
	$sql = "SELECT id,COUNT(*) as votes,avg(vote) FROM vote_log INNER JOIN
		(SELECT MAX(vote_id) AS vote_id FROM vote_log WHERE vote > 0 AND `final` = 1 and type='$type' GROUP BY user_id,ipaddr) t2 USING (vote_id)
		GROUP BY id WITH ROLLUP";
	dump_sql_table($sql,"Simple count of votes per image (only counting last vote per user)");


	$sql = "SELECT id,ts,user_id,ipaddr,useragent,session FROM vote_log INNER JOIN
                (SELECT MAX(vote_id) AS vote_id FROM vote_log WHERE vote > 0 AND `final` = 1 and type='$type' GROUP BY user_id,ipaddr) t2 USING (vote_id)
		 order by ts";

	dump_logs($sql,"Anonymized Vote Log");

	exit;
}

print "<h3>Votes per image (only counting last vote, per image, per user)</h3>";

$query = "select @max := max(users) from vote_stat where type='$type'";
$query = "select @max := COUNT(DISTINCT vote_log.user_id,ipaddr) as max, avg(vote) as avg from vote_log where vote > 0 AND `final` = 1 and type='$type'";
$row = $db->getRow($query);

print "Total Voters = {$row['max']}, Overall Average = {$row['avg']} (used in the baysian calculation)<BR>";


$query = "select imagetaken,id,(@max-users) as v0,' ',v1,v2,v3,v4,v5,'.',users,avg,v.baysian,round(avg*users) as total, (avg*users)/@max as avg0,
	((avg*users)+(@max-users)*3)/@max as avg3,last_vote, realname
	from vote_stat v inner join gridimage_search on (id = gridimage_id) where type='$type' ORDER BY substring(imagetaken,$start+1,$break-$start),v.baysian DESC LIMIT 1000";

dump_sql_table($query,$type);


function dump_sql_table($sql,$title) {
	global $db,$start,$break;
        $recordSet = $db->Execute($sql) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	if ($recordSet->EOF)
		return;

        print "<H3>$title</H3>";

	$max = array();
	while(!$recordSet->EOF) {
		$row = $recordSet->fields;
		$breaker = substr($row['imagetaken'],$start,$break-$start);
		if (!is_null($row['id']))
			foreach ($row as $key => $value)
				if(empty($max[$breaker][$key]) || $value > $max[$breaker][$key])
					$max[$breaker][$key] = $value;
		$recordSet->MoveNext();
	}

	$recordSet->Move(0);

	$row = $recordSet->fields;

        print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
        foreach ($row as $key => $value) {
                print "<TH>$key</TH>";
        }
        print "</TR>";

	$last = '';
	$done = array(); $unique = 1;
        while(!$recordSet->EOF) {
		$row = $recordSet->fields;
		$breaker = substr($row['imagetaken'],$start,$break-$start);
		if ($last != $breaker)
			print "<tr><td colspan=".count($row).">";
		$last = $breaker;

                print "<TR>";
                foreach ($row as $key => $value) {
			if ($value == $max[$breaker][$key]) {
				print "<TD style='background-color:lightgreen'>$value</TD>";
			} else {
	                        print "<TD>$value</TD>";
			}
                }
		$date = substr($row['imagetaken'],5,2); //always just the month, ignore $start/$break
		if (!empty($_GET['dup']) && $row['id'] != 7222444) {
			if (empty($done[$date]) && empty($done[$row['realname']])) {
				print "<td>unique$unique";

				@$done[$date]++;
				@$done[$row['realname']]++;
				$unique++;
			}
			//print "<td align=right>".@($done[$date]+$done[$row['realname']])."</td>";
		}
                print "</TR>";
		$recordSet->MoveNext();
        }
	print_r($done);
        print "</TR></TABLE>";
}



function dump_logs($sql,$title) {
	global $db;

        $recordSet = $db->Execute($sql) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	$row = $recordSet->fields;

        print "<H3>$title</H3>";

        print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
        foreach ($row as $key => $value) {
                print "<TH>$key</TH>";
        }
        print "</TR>";

	$ui = $ip = $ua = array();
        while (!$recordSet->EOF) {
		$row = $recordSet->fields;
		foreach(array('user_id','ipaddr','useragent','session') as $key)
			$row[$key] = $row['user_id']?substr(md5($_SERVER['REQUEST_TIME'].$row[$key]),0,6):'anon';

                print "<TR>";
                foreach ($row as $key => $value) {
                        print "<TD>$value</TD>";
                }
                print "</TR>";
		$recordSet->MoveNext();
        }
        print "</TR></TABLE>";
}

