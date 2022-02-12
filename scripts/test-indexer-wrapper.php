<?

$param = array('execute'=>0,'single'=>1,'debug'=>1,'prime'=>0, 'server_id'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($param['server_id']))
	$server_id = $db->Quote(trim($param['server_id']));
else
	$server_id = $db->Quote(trim(`hostname`));
$pid = getmypid();

#####################################################

if (!empty($param['prime'])) {
	//$h = popen("find /var/lib/manticore/data/ -name '*.sph' -printf '%T@ %P\\n'",'r');
	$h = fopen("/var/www/geograph/indexes.txt",'r');
	while ($h && !feof($h)) {
		$line = trim(fgets($h));
		$bits = explode(' ',$line);
		if (!empty($bits[1])) {
			$time = intval($bits[0]);
			$parts = explode('.',$bits[1]); //to remove the .sph extension

			$name = $db->Quote(trim($parts[0]));
			if ($name == "'tickets_closed'") $name = "'tickets_closed_delta'"; //anoyingly, the index filename, doesnt amtch
			$sql = "REPLACE INTO sph_server_index SET index_name = $name, server_id = $server_id, last_indexed = FROM_UNIXTIME($time)";
			print "$sql;\n";
			if ($param['execute'])
				$db->Execute($sql);
		}
	}
	exit;
}

#####################################################


$indexes = $db->getAll("
SELECT sph_index.index_name, preindex, postindex, posttrigger, server_id, last_indexed, criteria
FROM sph_index LEFT JOIN sph_server_index ON (sph_index.index_name = sph_server_index.index_name AND server_id = $server_id)
WHERE (DATE_ADD(coalesce(last_indexed,'2000-01-01 00:00:00'), interval `minutes` minute) < NOW() OR triggered > 0) AND active = 1 ORDER BY type+0");

if (empty($indexes))
	exit;

#####################################################

$done = array();
$trigger = array();

function trigger_post() {
	global $db, $done, $trigger, $server_id, $param;
	if (!empty($trigger)) {
		foreach ($trigger as $name => $dummy) {
			if (empty($done[$name])) {
				$sql = "UPDATE sph_server_index SET triggered=1 WHERE index_name = $name AND server_id = $server_id";
		                print "$sql;\n";
				if ($param['execute'])
					$db->Execute($sql);
			}
		}
	}
}

register_shutdown_function('trigger_post');

#####################################################

function process_list($list, $log = null) {
	global $db, $server_id, $done, $pid, $param;

	$cmd = "indexer --config /etc/sphinxsearch/sphinx.conf ".implode(" ",array_keys($list))." --rotate"; //--sighup-each if large indexes?

	##################

	$start = microtime(true);

	print "$cmd\n";
	//sleep(1); //fake!

	$end = microtime(true);

	foreach ($list as $index => $dummy) {

		$name = $db->Quote(trim($index));

		$sql = "REPLACE INTO sph_server_index SET index_name = $name, server_id = $server_id, last_indexed = NOW()";
		print "$sql;\n";
			if ($param['execute'])
				$db->Execute($sql);

		$sql = "          INSERT INTO sph_indexer_log SET index_name = $name, server_id = $server_id, created = NOW(), pid = $pid";
		if ($index == $log)
			$sql .=", taken = ".($end-$start);
		print "$sql;\n";
			if ($param['execute'])
				$db->Execute($sql);
		$done[$index]=1;
	}
}


#####################################################
# run each index as a seperate process

if (!empty($param['single'])) {
	foreach ($indexes as $row) {
		if (!empty($done[$row['index_name']])) //may of been done as pre/post on previous run!
			continue;

		if (!empty($row['criteria']) && strpos($row['criteria'],'#') !== 0) { //just so can 'comment out' the query
			if ($server_id == "'development-0'")
				$server_id = "'manticore-0'";
			$query = str_replace("'\$server_id'",$server_id,$row['criteria']);

			if (preg_match('/\((SELECT[^)]+)\)/',$query,$m)) {
				//run the inner query seperately, to allow the outer query to be 'query cached'
				$result = $db->getOne($m[1]);
				$query = str_replace($m[0],$result,$query);
			}

			$result = $db->getOne($query);

			print "$result from $query;\n\n";

			if ($result < $row['last_indexed']) { //also skip if there are no rows!
				print "Skipping {$row['index_name']} ($result < {$row['last_indexed']})\n";
				continue;
			}
		}

		$list = array();
		if (!empty($row['preindex']))
			$list = array($row['preindex']=>1)+$list;
		$list[$row['index_name']]=1;
		if (!empty($row['postindex']))
			$list[$row['postindex']]=1;
		if (!empty($row['posttrigger']))
			$trigger[$row['posttrigger']]=1;

print implode(' ',array_keys($list))."\n";
foreach ($list as $index => $dummy) $done[$index]=1;
continue;

		process_list($list, $row['index_name']);
	}
	exit;
}

#####################################################
# else build just a single list (so it deduplicates)

$list = array();

foreach ($indexes as $row) {
	if (!empty($row['preindex']))
		$list = array($row['preindex']=>1)+$list;

	$list[$row['index_name']]=1;

	if (!empty($row['postindex']))
		$list[$row['postindex']]=1;
	if (!empty($row['posttrigger']))
		$trigger[$row['posttrigger']]=1;
}

process_list($list);

#####################################################

/*
CREATE TABLE `sph_index` (
  `index_name` varchar(64) NOT NULL,
  `active` tinyint unsigned not null default 1,
  `minutes` MEDIUMINT not null default 15,
  `preindex` varchar(64) NOT NULL,
  `postindex` varchar(64) NOT NULL,
  `posttrigger` varchar(64) NOT NULL,
  `type` enum('master','main','delta','single','static') not null default 'single',
  `created` DATETIME not null,
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`index_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sph_server_index` (
  `index_name` varchar(64) NOT NULL,
  `server_id` varchar(64) NOT NULL DEFAULT '?',
  `last_indexed` DATETIME not null,
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `triggered` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`index_name`,`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sph_indexer_log` (
  `index_name` varchar(64) NOT NULL,
  `server_id` varchar(64) NOT NULL DEFAULT '?',
  `created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
*/
