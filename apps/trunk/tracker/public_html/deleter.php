<?php
require ("config.php");
//Check session
session_start();

if (!$_SESSION['admin_logged_in'])
{
	//check fails
	header("Location: authenticate.php?status=session");
	exit();
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Delete Torrent(s) From Database</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link rel="stylesheet" type="text/css" href="./css/style.css" />
	<script language="javascript">
	function selectRow(checkBox)
	{
		if (checkBox.value % 2 == 1) //odd
			var Style = "row1";
		else //even
			var Style = "row0";
		if (checkBox.checked == true)
			var Style = "selected";
		var el = checkBox.parentNode;
		while(el.tagName.toLowerCase() != "tr")
		{
			el = el.parentNode;
    		}
    		el.className = Style;
	}
	</script>
</head>
<body>
<form action="<?php echo $_SERVER["PHP_SELF"];?>"  method="POST">
<?php
require_once("funcsv2.php");

// check database user
if (isset($dbuser) && isset($dbpass))
{
	$db = mysql_connect($dbhost, $dbuser, $dbpass) or die(errorMessage() . "Cannot connect to database. Check your username and password in the config file.</p>");
	mysql_select_db($database) or die(errorMessage() . "Error selecting database.</p>");

	foreach ($_POST as $left => $right)
	{
		if (strlen($left) == 41)
		{
			if (!is_numeric($right) || !verifyHash(substr($left, 1)))
				continue;
			$hash = substr($left, 1);
			//delete torrent file
			$query = "SELECT filename FROM ".$prefix."namemap WHERE info_hash =\"$hash\"";
			$delete_file = mysql_query($query) or die(errorMessage() . "Can't do SQL query - " . mysql_error() . "</p>");
			$delete = mysql_fetch_row($delete_file);
			unlink("torrents/" . $delete[0] . ".torrent");
			//continue deleting information in database
			@mysql_query("DELETE FROM " . $prefix . "summary WHERE info_hash=\"$hash\"");
			@mysql_query("DELETE FROM " . $prefix . "namemap WHERE info_hash=\"$hash\""); 
			@mysql_query("DELETE FROM " . $prefix . "timestamps WHERE info_hash=\"$hash\"");
			@mysql_query("DELETE FROM " . $prefix . "webseedfiles WHERE info_hash=\"$hash\"");
			@mysql_query("DROP TABLE " . $prefix . "y$hash");
			@mysql_query("DROP TABLE " . $prefix . "x$hash");
			//optimize tables, good after major changes have been made to database
			@mysql_query("OPTIMIZE TABLE " . $prefix . "summary");
			@mysql_query("OPTIMIZE TABLE " . $prefix . "namemap");
			@mysql_query("OPTIMIZE TABLE " . $prefix . "timestamps");
			//run RSS generator
			require_once("rss_generator.php");
		}
	}
}
else
{
	$db = mysql_connect($dbhost, $dbuser, $dbpass) or die(errorMessage() . "Tracker error: can't connect to database - " . mysql_error() . "</p>");
	mysql_select_db($database) or die(errorMessage() . "Tracker error: can't open database $database - " . mysql_error() . "</p>");
	$GLOBALS["maydelete"] = false;
}

?>
<h1>Delete Torrent(s) From Database</h1>
<table class="torrentlist" cellspacing="1">
<tr>
	<th>Name/Info Hash</th>
	<th>File Size</th>
	<th>Seeders</th>
	<th>Leechers</th>
	<th>Completed D/Ls</th>
	<th>Bytes Transfered</th>
	<th>Delete?</th>
</tr>
<?php

$results = mysql_query("SELECT ".$prefix."summary.info_hash, ".$prefix."namemap.size, ".$prefix."summary.seeds, ".$prefix."summary.leechers, format(".$prefix."summary.finished,0), format(".$prefix."summary.dlbytes/1073741824,3), ".$prefix."namemap.filename FROM ".$prefix."summary LEFT JOIN ".$prefix."namemap ON ".$prefix."summary.info_hash = ".$prefix."namemap.info_hash ORDER BY ".$prefix."namemap.filename") or die(errorMessage() . "" . mysql_error() . "</p>");

$i = 0;

while ($data = mysql_fetch_row($results)) {
	$writeout = "row" . $i % 2;
	$hash = $data[0];
	if (is_null($data[6]))
		$data[6] = $data[0];
	if (strlen($data[6]) == 0)
		$data[6] = $data[0];
		
	echo "<tr class=\"$writeout\">\n";
	echo "\t<td>".$data[6]."</td>\n";
	echo "\t<td>".bytesToString($data[1])."</td>\n";
	for ($j=2; $j < 5; $j++)
		echo "\t<td class=\"center\">$data[$j]</td>\n";
	echo "\t<td class=\"center\">$data[5] GB</td>\n";
	
	echo "\t<td class=\"center\"><input type=\"checkbox\" name=\"x$hash\" value=\"$i\" onclick=\"selectRow(this);\"/></td>\n";
	echo "</tr>\n";
	$i++;
}

?>
</table>
<p class="error">Warning: there is no confirmation for deleting files. Clicking this button is final.</p>
<p class="center"><input type="submit" value="Delete" /></p>
</form>
<a href="index.php"><img src="images/stats.png" border="0" class="icon" alt="Tracker Statistics" title="Tracker Statistics" /></a><a href="index.php">Return to Statistics Page</a><br>
<a href="admin.php"><img src="images/admin.png" border="0" class="icon" alt="Admin Page" title="Admin Page" /></a><a href="admin.php">Return to Admin Page</a>
</body></html>
