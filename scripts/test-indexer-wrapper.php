<?

$param = array('execute'=>0,'single'=>0,'debug'=>1,'prime'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$server_id = $db->Quote(trim(`hostname`));

#####################################################

if (!empty($param['prime'])) {
	//$h = popen("find /var/lib/manticore/data/ -name '*.sph' -printf '%T@ %P\\n'"','r');
	$h = fopen("/var/www/geograph/indexes.txt",'r');
	while ($h && !feof($h)) {
		$line = trim(fgets($h));
		$bits = explode(' ',$line);
		if (!empty($bits[1])) {
			$time = intval($bits[0]);
			$parts = explode('.',$bits[1]); //to remove the .sph extension

			$name = $db->Quote(trim($parts[0]));
			if ($name == 'tickets_closed') $name = 'tickets_closed_delta'; //anoyingly, the index filename, doesnt amtch
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
SELECT sph_index.index_name, preindex, postindex, server_id, last_indexed
FROM sph_index LEFT JOIN sph_server_index ON (sph_index.index_name = sph_server_index.index_name AND server_id = $server_id)
WHERE DATE_ADD(coalesce(last_indexed,'2000-01-01 00:00:00'), interval `minutes` minute) < NOW() ORDER BY type+0");

if (empty($indexes))
	exit;

#####################################################

if (!empty($param['single'])) {
	$done = array();
	foreach ($indexes as $row) {
		if (!empty($done[$row['index_name']])) //may of been done as pre/post on previous run!
			continue;


		$list = array();
		if (!empty($row['preindex']))
			$list = array($row['preindex']=>1)+$list;
		$list[$row['index_name']]=1;
		if (!empty($row['postindex']))
			$list[$row['postindex']]=1;

		$cmd = "indexer --config /etc/sphinxsearch/sphinx.conf ".implode(" ",array_keys($list))." --rotate"; //--sighup-each if large indexes?

		##################

		$start = microtime(true);

		print "$cmd\n";
		sleep(1); //fake!

		$end = microtime(true);

		##################

		$name = $db->Quote(trim($row['index_name']));

		$sql = "REPLACE INTO sph_server_index SET index_name = $name, server_id = $server_id, last_indexed = NOW()";
		print "$sql;\n";
			if ($param['execute'])
				$db->Execute($sql);

		$sql = "          INSERT INTO sph_log SET index_name = $name, server_id = $server_id, created = NOW(), taken = ".($end-$start);
		print "$sql;\n";
			if ($param['execute'])
				$db->Execute($sql);

		$done[$row['index_name']] = 1;
	}
	exit;
}

#####################################################

$list = array();

foreach ($indexes as $row) {
	if (!empty($row['preindex']))
		$list = array($row['preindex']=>1)+$list;

	$list[$row['index_name']]=1;

	if (!empty($row['postindex']))
		$list[$row['postindex']]=1;
}

#####################################################

 # 35 5 * * 3          indexer --config /etc/sphinxsearch/sphinx.conf sample8A --rotate --sighup-each

$cmd = "indexer --config /etc/sphinxsearch/sphinx.conf ".implode(" ",array_keys($list))." --rotate"; //--sighup-each if large indexes?
print "$cmd\n";

foreach ($list as $index => $dummy) {
	$name = $db->Quote(trim($index));

	$sql = "REPLACE INTO sph_server_index SET index_name = $name, server_id = $server_id, last_indexed = NOW()";
	print "$sql;\n";
			if ($param['execute'])
				$db->Execute($sql);
	if ($param['debug']) {
		$sql = "INSERT INTO sph_log SET index_name = $name, server_id = $server_id, created = NOW()";
		print "$sql;\n";
			if ($param['execute'])
				$db->Execute($sql);
	}
}

#####################################################

/*
CREATE TABLE `sph_index` (
  `index_name` varchar(64) NOT NULL,
  `active` tinyint unsigned not null default 1,
  `minutes` MEDIUMINT not null default 15,
  `preindex` varchar(64) NOT NULL,
  `postindex` varchar(64) NOT NULL,
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
  PRIMARY KEY (`index_name`,`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sph_log` (
  `index_name` varchar(64) NOT NULL,
  `server_id` varchar(64) NOT NULL DEFAULT '?',
  `created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
*/
