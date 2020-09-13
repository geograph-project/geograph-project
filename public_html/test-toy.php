<?


require_once('geograph/global.inc.php');

ini_set('display_errors',1);

?>
<title>Geograph Application Test</title>

<h3>Geograph Application Test</h3>

<p>This page runs various tests to ensure the server is online, and all required services are functional...

<hr>

<style>
body { font-family:verdana; }
table td { padding:3px; }
table tr.error { background-color:pink; }
table tr.pass {	background-color:lightgreen; }
table tr.notice { background-color:#ffbf00; }
table td:nth-child(1) { font-weight:bold; }
table td:nth-child(2) {	font-size:1.3em; text-align:center; }
table td:nth-child(3) { font-size:0.8em; color:gray; }
table tr.break { background-color:gray; color:white; font-size:0.8em; }
table tr.break td { font-weight:normal; }
</style>

<table>
<?

$shutdown_result = 'error'; //setup so will only pass if changed at geniune end!
function shutdown() {
	outputRow('Test Script Reached End',$GLOBALS['shutdown_result'],'if this fails, means script was prematurely aborted. probably a fatal error');

	print "</table><hr>. Page Generated: ".date('r')." by ".`hostname`;
}
register_shutdown_function('shutdown');

#########################################################################################################
outputBreak("Webserver Tests");
#########################################################################################################

$result = file_get_contents("{$CONF['CONTENT_HOST']}/help/stats_faq");

outputRow('RewriteRule Test',strpos($result,"How do TPoints work?")===FALSE?'error':'pass',"Tests that /help/static internal rewrite rule is working (the URL should be directed to staticfile.php if functional");

$content='';
foreach ($http_response_header as $line)
       	if (strpos($line,'Content-Type:') ===0)
               	$content = $line;

outputRow('Content-Type Test',strpos($content,"charset=ISO-8859-1")===FALSE?'error':'pass',"The above URL, served by PHP should have ISO-8859-1 as declared charset");


//todo, test https?

#########################################################################################################
outputBreak("Command Line Tools");
#########################################################################################################

$list = "mogrify convert exiftool jpegexiforient jpegtran";

$found = 0; $info = array();
foreach (explode("\n",`whereis $list`) as $line) {
	if (preg_match('/^(\w+):\s*(\/[\w\/\.]+)?/',$line,$m)) {
		$short = $m[1];
		$path = $m[2];
		if (!empty($path) && is_executable($path))
			$found++;
		else
			$info[] = "$short not found!";
	}
}
if (empty($info)) $info[] = "all of <tt>$list</tt> found";

outputRow('Commands', $found == count(explode(" ",$list))?'pass':'error', implode(', ',$info));

#########################################################################################################
outputBreak("Files System Tests");
#########################################################################################################

if (class_exists('FileSystem', false)) { //dont call autoload!

$filesystem = new FileSystem;

###################################

if (!empty($filesystem->s3)) {
	outputRow('Photo Dir Writable?', 'pass', 'setup writing to S3, assume it functional, not tested here');
} else
	outputRow('Photo Dir Writable?', is_writable("photos/")?'pass':'error');

outputRow('Upload Dir Writable?', is_writable($CONF['photo_upload_dir'])?'pass':'error');

###################################

$filename = "photos/test-photo.jpg";
$result = 'error';

$url = $filesystem->publicUrl($filename);
if ($filesystem->exists($filename)) {
	$local = $filesystem->filesize($filename);

	if ($local < 1024) {
		$info = "local file too small";
	} else {
		$fetched = file_get_contents($url); //this is fetching the file, via the URL, delibeatly to test the file online!
		$remote = strlen($fetched);
		$server = 'Server: unknown';
        	foreach ($http_response_header as $line)
                	if (strpos($line,'Server:') ===0)
                        	$server = $line;

		if ($remote == $local) { //todo, could also do a content check (eg md5)
			$result = 'pass';
			$info = "fetched ok! $server";
		} else
			$info = "size mismatch (local: $local, remote: $remote) $server";

		//todo, if file not found, and we reading from S3, then maybe file not copied there yet. So copy it?
	}
} else
	$info = "local file not found";

outputRow('File Readable',$result, "tests fetching <a href=$url>$url</a>, $info");

###################################
//tests writing a file!

if (!empty($filesystem->s3) && strpos($CONF['STATIC_HOST'], $CONF['awsS3Bucket']) !== FALSE) {
        $local = filesize($filename); //delibairy using FS function as want to read the file, not S3 file!

        $db = GeographDatabaseConnection(false);
        $db->Execute("UPDATE counter SET count=count+1");
        $counter = $db->getOne("SELECT count FROM counter");

        $destination = sprintf('photos/%02d/%06d.jpg',$counter/100,$counter);

        $result = $filesystem->put($destination, $filename);

        $url = $filesystem->publicUrl($destination);
        $fetched = file_get_contents($url); //this is fetching the file, via the URL, delibeatly to test the file online!

        if (empty($fetched)) {
                sleep(3); //allow for eventual consistency!
                $fetched = file_get_contents($url);
        }
	$remote = strlen($fetched);
	$server = 'Server: unknown';
        foreach ($http_response_header as $line)
                if (strpos($line,'Server:') ===0)
                        $server = $line;

	if ($local == $remote) {
		outputRow('File Written to S3', 'pass', "Image copied to <a href=$url>$url</a>. $server");
	} else {
		outputRow('File Written to S3', 'error', "Size Mismatch, expected:$local, got:$remote. put said ($result). $server");
	}
} else {
	outputRow('File Written to S3', 'notice', "Amazon S3 not configured. Test Skipped.");
}

} else
	outputRow('FileSystem','notice','FileSystem Class not installed. This test only works with that class, not normal Fileystem');


#########################################################################################################
outputBreak("MySQL Server");
#########################################################################################################

if (empty($db)) //might already connected!
	$db = GeographDatabaseConnection(false); //needs to ba master connection

if (!$db) {
	outputRow('MySQL/Master','error','not connected to master');
} elseif ($db->readonly) {
	outputRow('MySQL/Master','error','got a read-only connection');
} else {
	$info = $db->ServerInfo();
	outputRow('MySQL/Master','pass','Connected to master. Server: '.$info['description']);
}

###################################

if ($db) {
	$x = 586; $y = 201; $d=10; $sql_where = '';

	$table = "image_dump";
	 $expected = 7; //example query expects 7 rows in test dataset

	if ($db->getOne("SHOW TABLES LIKE 'gridimage_search'")) {
		$table = "gridimage_search";
		$expected = 381; // on the initial staging dataset anyway!
	}

					$left=$x-$d;
                                        $right=$x+$d-1;
                                        $top=$y+$d-1;
                                        $bottom=$y-$d;

                                        $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

                                        $sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";

	$sql = "SELECT gridimage_id FROM $table WHERE $sql_where";

	$result = $db->getAll($sql) or outputRow('MySQL Spatial Query','error',"mysql error: ".$db->ErrorMsg());

	$count = count($result);

	if ($count == $expected) {
		outputRow('MySQL Spatial Query','pass',"Run query and got, $count matching rows. Good.");
	} else {
		outputRow('MySQL Spatial Query','error',"didnt obtain $expected rows");
	}

	###################################

	/*
	$result = $db->getOne("select PREG_REPLACE('/test/','worked','test')");
	if ($result == 'worked') {
		outputRow('MySQL PREG UDF','pass',"PREG_REPLACE() function appears functional");
	} else {
		outputRow('MySQL PREG UDF','notice',"PREG_REPLACE() not found, not a fail, as not strictly required");
	}*/

	###################################

	if (!empty($CONF['db_tempdb']) && $CONF['db_tempdb']!=$CONF['db_db']) {
		//test creating someting in the temporaty DB. Its not replicated.

		$sql = "CREATE TEMPORARY TABLE {$CONF['db_tempdb']}.group_test SELECT gridimage_id,COUNT(*),x,y FROM $table GROUP BY x DIV 10, y DIV 10";

		$result = $db->Execute($sql) or outputRow('MySQL Temporary table creation test','error',"mysql error: ".$db->ErrorMsg());;

		$rows = $db->affected_rows();
		outputRow('MySQL Temporary table creation test', $rows>20?'pass':'error', "created table with $rows rows");
	}

	###################################
	//test charset!

	//an example image from gridimage_funny, that makes a nice test of both ISO-8859-1 and UTF8 conversions!
	$id = 1785100;
	$latin1 = "Frampton%3A+Keeper%92s+Cottage+and+postbox+%26%238470%3B+DT2+48";
	$urf8 = "Frampton%3A+Keeper%26rsquo%3Bs+Cottage+and+postbox+%E2%84%96+DT2+48";

	$value = $db->getOne("SELECT title FROM gridimage_funny WHERE gridimage_id = $id");

	outputRow('latin1 data to ISO-8859-1 HTML', (urlencode(htmlentities2($value)) == $latin1)?'pass':'error', 'tests both fetchinf from database, and converting to HTML');

	outputRow('latin1 data to UTF-8 HTML', (urlencode(htmlentities(latin1_to_utf8($value), ENT_COMPAT, 'UTF-8')) == $urf8)?'pass':'error');
}

###################################

if (isset($CONF['db_read_driver'])) {
	$read = GeographDatabaseConnection(10); //say we allow 10 second of lag!
	if (!$read) {
		 outputRow('MySQL/Slave','error','not connected to slave');
	} elseif(!$read->readonly) {
		 outputRow('MySQL/Slave','error','re-connected to master - slave not functional');
	} else {
		$info = $read->ServerInfo();
		outputRow('MySQL/Slave','pass','Connected to slave. And less than 10 second lag. Server: '.$info['description']);
	}
} else {
	outputRow('MySQL/Slave','notice','no slave configured');
}


#########################################################################################################
outputBreak("Sphinx/Manticore");
#########################################################################################################

if (!empty($CONF['sphinx_host'])) {

	$index = 'gi_stemmed'; $attr = "auser_id";

	if (strpos($_SERVER['HTTP_HOST'],'toy') !== FALSE) {
		$index = 'toy';  $attr = "user_id";
	}

	$sph = GeographSphinxConnection();


	$result = $sph->getAll("select * from $index where match('IOM')");

	if (!empty($result) && count($result) > 4) {
		//$info = $sph->ServerInfo(); //doesnt work on Sphinx! 
		$info['description'] =  mysql_get_server_info();
		$count = count($result);
		outputRow('Sphinx/Manticore Daemon','pass',"Run query and got $count matching rows. Good. Server: ".$info['description']);
	} else {
		outputRow('Sphinx/Manticore Daemon','error',"didnt obtain expected results. ".$sph->ErrorMsg());
	}


	$result = $sph->getAll("select id, $attr, uniqueserial($attr) as sn from $index order by sn asc");

	if (!empty($result) && count($result) == 20) { //default sphinx LIMIT
		$count = count($result);
		outputRow('Sphinx/Manticore Daemon UDF/Plugin','pass',"Run query with uniqueserial() and got $count matching rows. Good");
	} else {
		outputRow('Sphinx/Manticore Daemon UDF/Plugin','error',"uniqueserial() functiona appears non-functional. ".$sph->ErrorMsg());
	}


	if ( $index == 'toy') {
		//unused, but keeps a counter going.. May be used for sync testing later.
		if ($result = $sph->getOne("select user_id from toy where id = 55")) {
			$sph->Execute("update toy set user_id = ".($result+1)." WHERE id=55")
				or outputRow('Sphinx/Manticore Daemon', 'error', 'Update failed: '.$sph->ErrorMsg());
		}
	}

} else
	outputRow('Sphinx/Manticore','notice','not configured');

#########################################################################################################
outputBreak("Redis/Memcache");
#########################################################################################################

if (!empty($CONF['redis_host'])) {

	if (empty($redis_handler)) {
		require_once("3rdparty/RedisServer.php");
	        $redis_handler = new RedisServer($CONF['redis_host'], $CONF['redis_port']);
	}
	$redis_handler->Select($CONF['redis_db']);

	$test_key = "test.php-timestamp";
	$value = $redis_handler->Get($test_key);

	if ($value && $value > (time()-604800) && $value < time()) {
		outputRow('Redis Daemon','pass','read a recent timestamp from Redis: '.$value);
	} else {
		$redis_handler->Set($test_key, time());
		sleep(1);
		$value = $redis_handler->Get($test_key);
		if ($value && $value > (time()-3) && $value < time()) {
                	outputRow('Redis Daemon','pass','tested writing and reading: '.$value);
	        } else {
			outputRow('Redis Daemon','error','not read a value from redis');
		}
	}

	//set for next time!
	$redis_handler->Set($test_key, time());
} else
	outputRow('Redis Daemon','notice','redis not configured');

#############################

if (!empty($CONF['memcache']['app'])) {
	$title = ($CONF['memcache']['app'] == 'redis')?'Memcache Interface to Redis':'Memcache Daemon(s)';

	$mkey = "timestamp";
	$value = $memcache->name_get('test.php',$mkey);

	if ($value && $value > (time()-604800) && $value < time()) {
		outputRow($title,'pass','read a recent timestamp: '.$value);
	} else {
		$memcache->name_set('test.php',$mkey, time());
		sleep(1);
		$value = $memcache->name_get('test.php',$mkey);
		if ($value && $value > (time()-3) && $value < time()) {
			outputRow($title,'pass','tested writing and reading: '.$value);
		} else {
			outputRow($title,'error','not able to read via memcache');
		}
	}

	//set for next time!
	$memcache->name_set('test.php',$mkey, time());
} else
	outputRow('MemCache Daemon','notice','memcache not configured');


#########################################################################################################
outputBreak("Smarty Templating");
#########################################################################################################


$smarty = new GeographPage;
$smarty->assign('smarty_version',$smarty->_version);

if (!empty($smarty->compile_dir)) {
	outputRow('Smarty Compile Dir Writable?', is_writable($smarty->compile_dir)?'pass':'error');

	//todo, this is very fagile, only checking if the compiled is a symlink to a folder that appears mounted on EFS!
	if (is_link($smarty->compile_dir)) {
		$dest = readlink($smarty->compile_dir);
		$result = `df $dest`;
		if (strpos($result,'.efs.') !== FALSE) {
			$files = trim(`find $dest -type f | wc -l`);
			outputRow('Compile Dir symlink to Amazon-EFS',$files>1?'pass':'error',"linked to $dest, appear to be mounted EFS share. Not a thorough test");
		} else {
			outputRow('Compile Dir symlink','notice',"linked to $dest, which doesnt appear to be EFS");
		}
	}
}

if (!empty($smarty->cache_dir))
	outputRow('Smarty Cache Dir Writable?', is_writable($smarty->cache_dir)?'pass':'error');

//for smarty, use a .tpl template, to render the pass!

$result = $smarty->fetch('toy.tpl');
if (strpos($result,'Two times above are same') !== FALSE) { //if the template is first time rendered, wont be cached...
	sleep(2); // so wait 2 seconds and try again!
	$result = $smarty->fetch('toy.tpl');
}

print $result;

if (preg_match_all('/class=result>pass/',$result) !== 3) //needs to be three passes!
	outputRow('Smarty Templating', 'error', 'the template didnt appear to render');


#########################################################################################################
outputBreak("Carrot2 DSC");
#########################################################################################################


if (!empty($CONF['carrot2_dcs_url'])) {
	require_once('3rdparty/Carrot2.class.php');

	$carrot = new Carrot2($CONF['carrot2_dcs_url']);

	$data= $db->getAll("SELECT gridimage_id,title FROM $table LIMIT 20");
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


#########################################################################################################
outputBreak("TimeGate Proxy");
#########################################################################################################


if (!empty($CONF['timetravel_url'])) {
	if (!empty($_GET['full'])) {//make it a param, as the API makes real external APIs
		$result = file_get_contents($CONF['timetravel_url']."/api/json/20160801/http://www.devizesheritage.org.uk/railway_devizes.html");
	} else {
		// note we deliberately DONT set a query URL, so sending a bad request. we just testing if a memgate, dont want to triger an external https request.
		$result = file_get_contents($CONF['timetravel_url']);
	}
	$info = null;
	foreach ($http_response_header as $line)
		if (strpos($line,'X-Generator: MemGator') ===0)
			$info = $line;
		elseif (strpos($line,'Server: MemGator') ===0)
			$info = $line;

	outputRow('TimeGate Proxy Online',$info?'pass':'error',$info);

	if (!empty($_GET['full'])) {
		$decode = json_decode($result,true);

		if (!empty($decode['mementos']) && !empty($decode['mementos']['closest'])) {

			$a = $decode['mementos']['closest']['uri'];
			$updates = array();
                        $updates['archive_url'] = is_array($a)?array_shift($a):$a; //memgator returns string, whereas mementoweb.org returns array!
                        $updates['archive_date'] = $db->BindTimeStamp(strtotime($decode['mementos']['closest']['datetime']));

			outputRow('TimeGate Proxy','pass','Found archive at '.$updates['archive_url']);
		} else {
			outputRow('TimeGate Proxy','error','unable to decode json from reply. Reply: '.strlen($result).' bytes');
		}
	}

} else
	outputRow('TimeGate Proxy','notice','not configured');


#########################################################################################################
outputBreak("Cron");
#########################################################################################################


if (!empty($db)) {
	$sql = "select sum(instances) from event where posted > date_sub(now(),interval 6 hour)";
	$count = $db->getOne($sql);
	if ($count > 4 && $count < 8) {
		outputRow('Cron Job: Firing Events','pass',"$count events fired in last 6 hours, should be 1/hour");
	} else {
		outputRow('Cron Job: Firing Events','error',"$count event(s) fired in last 6 hours, note can also show fail if not being processed long term");
	}

	$sql = "select count(*) from event where status = 'completed' AND `processed` > date_sub(now(),interval 6 hour)";
	$count = $db->getOne($sql);
	if ($count > 4 && $count < 8) {
		outputRow('Cron Job: Processing Events','pass',"$count events processed in last 6 hours, should be 1/hour");
	} else {
		outputRow('Cron Job: Processing Events','error',"$count event(s) processed in last 6 hours, should match now many fired");
	}

} else {
	outputRow('Cron Jobs','notice','not connected to database unable to test');
}


#########################################################################################################
outputBreak("Finally");

$shutdown_result = 'pass'; //this is used in shutdown function to render the last row!

#########################################################################################################

function outputRow($message, $class = 'notice', $text = '') {
	print "<tr class=$class>";
	print "<td>$message</td>";
	print "<td class=result>$class</td>";
	print "<td>$text</td>";
	flush();
}

function outputBreak($header) {
	print "<tr class=break>";
	print "<td colspan=3>$header</td>";

}
