<?

require_once('geograph/global.inc.php');

?>
<title>Geograph Application Test</title>

<h3>Geograph Application Test</h3>

<p>This page runs various tests to ensure the server is online, and fully functiona...

<hr>

<style>
table td { padding:10px; }
table tr.error { background-color:pink; }
table tr.pass {	background-color:lightgreen; }
table tr.notice { background-color:cream; }
table td:nth-child(1) { font-weight:bold; }
table td:nth-child(2) {	font-size:1.3em; text-align:center; }
table td:nth-child(3) { font-size:0.8em; color:gray; }
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
# Files!
#########################################################################################################

$filesystem = new FileSystem;

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

		if ($remote == $local) { //todo, could also do a content check (eg md5)
			$result = 'pass';
			$info = "fetched ok!";
		} else
			$info = "size mismatch (local: $local, remote: $remote)";
	}
} else
	$info = "local file not found";

outputRow('File System + Static File',$result, "tests fetching <a href=$url>$url</a>, $info");


#########################################################################################################
# Mysql
#########################################################################################################


$db = GeographDatabaseConnection(false); //needs to ba master connection

if (!$db) {
	outputRow('MySQL/Master','error','not connected to master');
} elseif ($db->readonly) {
	outputRow('MySQL/Master','error','got a read-only connection');
} else {
	outputRow('MySQL/Master','pass','Connected to master');
}

###################################

if ($db) {
	$x = 586; $y = 201; $d=10; $sql_where = ''; $expected = 7; //example query expects 7 rows in test dataset

					$left=$x-$d;
                                        $right=$x+$d-1;
                                        $top=$y+$d-1;
                                        $bottom=$y-$d;

                                        $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

                                        $sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";

	$sql = "SELECT gridimage_id FROM image_dump WHERE $sql_where";

	$result = $db->getAll($sql) or die(mysql_error());
	$count = count($result);

	if ($count == $expected) {
		outputRow('MySQL Spatial Query','pass',"Run query and got, $count matching rows. Good.");
	} else {
		outputRow('MySQL Spatial Query','error',"didnt obtain $expected rows");
	}

	###################################

	if (!empty($CONF['db_tempdb']) && $CONF['db_tempdb']!=$CONF['db_db']) {
		//test creating someting in the temporaty DB. Its not replicated.

		$sql = "CREATE TEMPORARY TABLE {$CONF['db_tempdb']}.group_test SELECT gridimage_id,COUNT(*),x,y FROM image_dump GROUP BY x DIV 10, y DIV 10";

		$result = $db->Execute($sql) or die(mysql_error());

		$rows = $db->affected_rows();
		outputRow('MySQL Temporary table creation test', $rows>20?'pass':'error', "created table with $rows rows");
	}
}

###################################

if (isset($CONF['db_read_driver'])) {
	$read = GeographDatabaseConnection(10); //say we allow 10 second of lag!
	if (!$read) {
		 outputRow('MySQL/Slave','error','not connected to slave');
	} elseif(!$read->readonly) {
		 outputRow('MySQL/Slave','error','re-connected to master - slave not functional');
	} else {
		outputRow('MySQL/Slave','pass','Connected to slave. And less than 10 second lag.');
	}
} else {
	outputRow('MySQL/Slave','notice','no slave configured');
}


#########################################################################################################
# Sphinx/Manticore
#########################################################################################################


$sph = GeographSphinxConnection();

$result = mysql_query("select * from toy where match('IOM')") or die(mysql_error());
$count = mysql_num_rows($result);

if ($count > 4) {
	outputRow('Sphinx/Manticore Daemon','pass',"Run query and got, $count matching rows. Good.");
} else {
	outputRow('Sphinx/Manticore Daemon','error',"didnt obtain expected results");
}


#########################################################################################################
# Redis
#########################################################################################################


outputRow('Redis Daemon','notice','test not yet implemented');


#########################################################################################################
# Memcache (possibly Redis anyway!)
#########################################################################################################


outputRow('MemCache Daemon','notice','test not yet implemented');


#########################################################################################################
# Smarty
#########################################################################################################


$smarty = new GeographPage;

if (!empty($smarty->compile_dir))
	outputRow('Smarty Compile Dir Writable?', is_writable($smarty->compile_dir)?'pass':'error');

if (!empty($smarty->cache_dir))
	outputRow('Smarty Cache Dir Writable?', is_writable($smarty->cache_dir)?'pass':'error');

//for smarty, use a .tpl template, to render the pass!
$smarty->display('toy.tpl');


$result = $smarty->fetch('toy.tpl');
if (preg_match_all('/class=result>pass/',$result) !== 3) //needs to be three passes!
	outputRow('Smarty Templating', 'error', 'the template didnt appear to render');


#########################################################################################################
# Carrot2 DSC
#########################################################################################################


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


#########################################################################################################
# TimeGate Proxy
#########################################################################################################


if (!empty($CONF['timetravel_url'])) {
	// note we deliberately DONT set a query URL, so sending a bad request. we just testing if a memgate, dont want to triger an external https request.
	$result = file_get_contents($CONF['timetravel_url']);
	$info = null;
	foreach ($http_response_header as $line)
		if (strpos($line,'X-Generator: MemGator') ===0)
			$info = $line;

	outputRow('TimeGate Proxy Online',$info?'pass':'error',$info);
} else
	outputRow('TimeGate Proxy','notice','not configured');


#########################################################################################################
# Cron
#########################################################################################################


if (!empty($db)) {
	$sql = "select sum(instances) from event where posted > date_sub(now(),interval 6 hour)";
	$count = $db->getOne($sql);
	if ($count > 4 && $count < 8) {
		outputRow('Cron Job: Firing Events','pass',"$count events fired in last 6 hours, should be 1/hour");
	} else {
		outputRow('Cron Job: Firing Events','error',"$count event(s) fired in last 6 hours, note can also show fail if not being processed long term");
	}

	$sql = "select count(*) from event where status = 'completed' AND updated > date_sub(now(),interval 6 hour)";
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

$shutdown_result = 'pass'; //this is used in shutdown function to render the last row!

#########################################################################################################

function outputRow($message, $class = 'notice', $text = '') {
	print "<tr class=$class>";
	print "<td>$message</td>";
	print "<td class=result>$class</td>";
	print "<td>$text</td>";
	flush();
}
