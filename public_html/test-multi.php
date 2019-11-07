<?

require_once('geograph/global.inc.php');

$times = array();
function timer($key,$result) {
	static $starts = array();
	global $times;
	if ($result) {
		$end = microtime(true);
		$times[$key] = sprintf("%.3f",$end - $starts[$key]);
	} else
		$starts[$key] = microtime(true);
}

if (!empty($_GET['run'])) {

	$d = array();

	#########################################################################################################

	timer('file', false);

	$filesystem = new FileSystem;

	$filename = "photos/test-photo.jpg";

	$d['file'] = $filesystem->filemtime($filename);
	timer('file', true);

	#########################################################################################################

	timer('mysql-master-connect', false);
	$db = GeographDatabaseConnection(false); //needs to ba master connection
	timer('mysql-master-connect', true);


	if ($db) {
		timer('mysql-master-query', false);
		$d['mysql-master'] = $db->getOne("SELECT updated FROM counter");
		timer('mysql-master-query', true);
	}

	#########################################################################################################

	if (isset($CONF['db_read_driver'])) {

		timer('mysql-slave-connect', false);
		$read = GeographDatabaseConnection(true);
		timer('mysql-slave-connect', true);

		if (!$read) {
			$d['mysql-slave'] = 'fail';
		} elseif(!$read->readonly) {
			$d['mysql-slave'] = 'offline';
		} else {
			timer('mysql-slave-query', false);
			$d['mysql-slave'] = $read->getOne("SELECT updated FROM counter");
			timer('mysql-slave-query', true);
		}
	} else {
		$d['mysql-slave'] = 'none';
	}

	#########################################################################################################

	timer('manticore-connect', false);
	$sph = GeographSphinxConnection();
	timer('manticore-connect', true);

	timer('manticore-query', false);
	if ($result = $sph->getOne("select user_id from toy where id=55")) {
		$d['manticore'] = $result;
	} else {
		$d['manticore'] = $sph->errorMsg();
	}
	timer('manticore-query', true);

	#########################################################################################################

	if (!empty($CONF['redis_host'])) {

		timer('redis-connect', false);
		if (empty($redis_handler)) {
			require("3rdparty/RedisServer.php");
		        $redis_handler = new RedisServer($CONF['redis_host'], $CONF['redis_port']);
		}
		$redis_handler->Select($CONF['redis_db']);
		timer('redis-connect', true);

		timer('redis-query', false);
		$test_key = "test.php-timestamp";
		$d['redis'] = $redis_handler->Get($test_key);
		timer('redis-query', true);
	}

	#########################################################################################################

	if (!empty($CONF['memcache']['app'])) {

		timer('memcache-query', false);
		$mkey = "timestamp";
		$d['memcache'] = $memcache->name_get('test.php',$mkey);
		timer('memcache-query', true);
	}

	#########################################################################################################

	$smarty = new GeographPage;

	$resource_name = 'toy.tpl';
	$compile_path = $smarty->_get_compile_path($resource_name);
	timer('smarty-compiled', false);
	$d['smarty-compiled'] = filemtime($compile_path);
	timer('smarty-compiled', true);
	if (!empty($smarty->cache_dir)) { //technically, caching may not be using a directory!
		//doesnt seem to be a nice function to get this...
		timer('smarty-cache', false);
		$cache_path = str_replace('.tpl.php','.tpl',str_replace('/compiled/','/cache/',$compile_path));
		$d['smarty-cache'] = filemtime($cache_path);
		timer('smarty-cache', true);
	}


	timer('smarty-fetch', false);
	$result = $smarty->fetch($resource_name);
	timer('smarty-fetch', true);
	if (strpos($result,'Two times above are same') !== FALSE) { //if the template is first time rendered, wont be cached...
	        sleep(2); // so wait 2 seconds and try again!
		timer('smarty-fetch2', false);
	        $result = $smarty->fetch($resource_name);
		timer('smarty-fetch2', true);
	}

	if (preg_match_all('/class=result>pass/',$result) !== 3) //needs to be three passes!
		$d['smarty-error'] = 'failed render';

	#########################################################################################################

/*
if (!empty($CONF['carrot2_dcs_url'])) {
	require_once('3rdparty/Carrot2.class.php');

	$carrot = new Carrot2($CONF['carrot2_dcs_url']);

	$data= $db->getAll("SELECT gridimage_id,title FROM image_dump LIMIT 20");
        foreach ($data as $row) {
                $carrot->addDocument(
                        (string)$row['gridimage_id'],
                        utf8_encode($row['title']),
			''
                );
        }

        $c = $carrot->clusterQuery();

	if (!empty($c) && count($c) > 5) {
		outputRow('Carrot2 DCS','pass',"Retreived ".count($c)." Clusters. Good.");
	} else {
		outputRow('Carrot2 DCS','notice',"Retreived ".count($c)." Clusters, less than expected. This is not a fail, because DCS not strictly required.");
	}
} else
	outputRow('Carrot2 DCS','notice','not configured');
*/

	#########################################################################################################

	if (!empty($CONF['timetravel_url'])) {
		// note we deliberately DONT set a query URL, so sending a bad request. we just testing if a memgate, dont want to triger an external https request.
		timer('timetravel_url', false);
		$result = file_get_contents($CONF['timetravel_url']);
		timer('timetravel_url', true);
		foreach ($http_response_header as $line)
			if (strpos($line,'X-Generator: MemGator') ===0) {
				$d['memgate'] = $line;
				break;
			}
	}

	#########################################################################################################

	$d['checksum'] = md5(serialize($d));

	$d['hostname'] = trim(`hostname`);
	$d['time'] = time();

	if (!empty($_GET['times'])) {
		$times['total'] = array_sum($times);
		$d['times'] = $times;
	}
	#########################################################################################################

	header("Content-Type:application/json");
	print json_encode($d);
	exit;
}


?>

<p>Shows the results of running a short test, the idea is IF there are multiple servers, inconsistency will show up.
Makes a request every 2 seconds. If the checksum changes, hints at an issue.</p>

Ongoing log:<br>
<textarea id="output" rows=40 cols=100 style="white-space:pre"></textarea><br>

Last Result:<br>
<textarea id="last" rows=6 cols=100></textarea><br>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>
	var startTime = Date.now();

	setInterval(function() {
		$.getJSON( "test-multi.php?run=1<?php if (!empty($_GET['times'])) { echo "&times=1"; } ?>", function( data ) {
			var endTime = Date.now();
			var diff = (endTime-startTime);
			diff = diff.toString().padStart(4,' ');

			document.getElementById('output').value += data['time'] + ', '+diff+'ms: '+ data['hostname'] + ' : ' + data['checksum'] + " :: "+ JSON.stringify(data) +"\n";
			document.getElementById('last').value = JSON.stringify(data);
		});
	}, 2000);


if (!String.prototype.padStart) {
    String.prototype.padStart = function padStart(targetLength, padString) {
        targetLength = targetLength >> 0; //truncate if number, or convert non-number to 0;
        padString = String(typeof padString !== 'undefined' ? padString : ' ');
        if (this.length >= targetLength) {
            return String(this);
        } else {
            targetLength = targetLength - this.length;
            if (targetLength > padString.length) {
                padString += padString.repeat(targetLength / padString.length); //append to original to ensure we are longer than needed
            }
            return padString.slice(0, targetLength) + String(this);
        }
    };
}

</script>
