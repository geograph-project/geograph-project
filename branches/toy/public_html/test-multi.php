<?

require_once('geograph/global.inc.php');

if (!empty($_GET['run'])) {

	$d = array();

	#########################################################################################################

	$filesystem = new FileSystem;

	$filename = "photos/test-photo.jpg";

	$d['file'] = $filesystem->filemtime($filename);

	#########################################################################################################

	$db = GeographDatabaseConnection(false); //needs to ba master connection

	if ($db) {
		$d['mysql-master'] = $db->getOne("SELECT updated FROM counter");
	}

	#########################################################################################################

	if (isset($CONF['db_read_driver'])) {
		$read = GeographDatabaseConnection(true);

		if (!$read) {
			$d['mysql-slave'] = 'fail';
		} elseif(!$read->readonly) {
			$d['mysql-slave'] = 'offline';
		} else {
			$d['mysql-slave'] = $read->getOne("SELECT updated FROM counter");
		}
	} else {
		$d['mysql-slave'] = 'none';
	}

	#########################################################################################################

	$sph = GeographSphinxConnection();

	if ($result = $sph->getOne("select user_id from toy where id=55")) {
		$d['manticore'] = $result;
	} else {
		$d['manticore'] = $sph->errorMsg();
	}

	#########################################################################################################

	if (!empty($CONF['redis_host'])) {

		if (empty($redis_handler)) {
			require("3rdparty/RedisServer.php");
		        $redis_handler = new RedisServer($CONF['redis_host'], $CONF['redis_port']);
		}
		$redis_handler->Select($CONF['redis_db']);

		$test_key = "test.php-timestamp";
		$d['redis'] = $redis_handler->Get($test_key);
	}

	#########################################################################################################

	if (!empty($CONF['memcache']['app'])) {

		$mkey = "timestamp";
		$d['memcache'] = $memcache->name_get('test.php',$mkey);

	}

	#########################################################################################################

	$smarty = new GeographPage;

	$resource_name = 'toy.tpl';
	$compile_path = $smarty->_get_compile_path($resource_name);
	$d['smarty-compiled'] = filemtime($compile_path);
	if (!empty($smarty->cache_dir)) { //technically, caching may not be using a directory!
		//doesnt seem to be a nice function to get this...
		$cache_path = str_replace('.tpl.php','.tpl',str_replace('/compiled/','/cache/',$compile_path));
		$d['smarty-cache'] = filemtime($cache_path);
	}


	$result = $smarty->fetch($resource_name);
	if (strpos($result,'Two times above are same') !== FALSE) { //if the template is first time rendered, wont be cached...
	        sleep(2); // so wait 2 seconds and try again!
	        $result = $smarty->fetch($resource_name);
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
		$result = file_get_contents($CONF['timetravel_url']);
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

	#########################################################################################################

	header("Content-Type:application/json");
	print json_encode($d);
	exit;
}


?>

<p>Shows the results of running a short test, the idea is IF there are multiple servers, inconsistency will show up.
Makes a request every 2 seconds. If the checksum changes, hints at an issue.</p>

Ongoing log:<br>
<textarea id="output" rows=40 cols=100></textarea><br>

Last Result:<br>
<textarea id="last" rows=6 cols=100></textarea><br>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>

	setInterval(function() {
		$.getJSON( "test-multi.php?run=1", function( data ) {
			document.getElementById('output').value += data['time'] + ': '+ data['hostname'] + ' : ' + data['checksum'] + "\n";
			 document.getElementById('last').value = JSON.stringify(data);
		});
	}, 2000);
</script>
