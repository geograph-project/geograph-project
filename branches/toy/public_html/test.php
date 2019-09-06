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
table td:nth-child(3) { font-size:0.8em;  }
</style>

<table>
<?


###################################
# Files!

$filesystem = new FileSystem;

$filename = "photos/test-photo.jpg";

$url = $filesystem->publicUrl($filename);
if ($filesystem->exists($filename)) {
	$fetched = file_get_contents($url); //this is fetching the file, via the URL, delibeatly to test the file online!

	if (strlen($fetched) == $filesystem->filesize($filename)) {
		$result = 'pass';
		$info = "fetched ok!";
	} else {
		$local = $filesystem->filesize($filename);
		$remote = strlen($fetched);
		$result = 'error';
		$info = "size mismatch (local: $local, remote: $remote)";
	}
	//print "<p>If see small image here: it works!";
	//print "<img src=\"$url\" style=max-width:100px><hr>";
} else {
	$result = 'error';
	$info = "file not found";
}

outputRow('File System + Static File',$result, "tests fetching <a href=$url>$url</a>, $info");


###################################
# Mysql

outputRow('MySQL Daemon','notice','test not yet implemented');

###################################
# Sphinx/Manticore

$sph = GeographSphinxConnection();

$result = mysql_query("select * from user where match('bob')") or die(mysql_error());
$count = mysql_num_rows($result);

if ($count > 10) {
	outputRow('Sphinx/Manticore Daemon','pass',"Run query and got, $count matching rows. Good.");
} else {
	outputRow('Sphinx/Manticore Daemon','error',"didnt obtain expected results");
}

###################################
# Redis

outputRow('Redis Daemon','notice','test not yet implemented');

###################################
# Memcache (possibly Redis anyway!)

outputRow('MemCache Daemon','notice','test not yet implemented');

###################################
# Smarty

$smarty = new GeographPage;

//for smarty, use a .tpl template, to render the pass!
$smarty->display('toy.tpl');

//todo detect failures!

###################################
#

###################################
#

###################################
#

print "</table><hr>. Page Generated: ".date('r')." by ".`hostname`;



#########################################################################################################

function outputRow($message, $class = 'notice', $text = null) {
	print "<tr class=$class>";
	print "<td>$message</td>";
	print "<td class=result>$class</td>";
	print "<td>$text</td>";
	flush();
}
