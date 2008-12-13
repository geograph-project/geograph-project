<?php
//if config.php file not available, error out
if (!file_exists("config.php"))
{
	echo "<font color=red><strong>Error: config.php file is not available.  Did you forget to upload it?" .
	" If you haven't run the installer yet, please do so <a href=\"install.php\">here.</a></strong></font>";
	exit();
}

require_once ("config.php");
require_once ("funcsv2.php");

//Check session only if hiddentracker is TRUE
if ($hiddentracker == true)
{
	session_start();
	
	if (!$_SESSION['admin_logged_in'] && !$_SESSION['upload_logged_in'])
	{
		//check fails
		header("Location: authenticate.php?status=indexlogin");
		exit();
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php
//variables for column totals
$total_disk_usage = 0;
$total_seeders = 0;
$total_leechers = 0;
$total_downloads = 0;
$total_bytes_transferred = 0;
$total_speed = 0;

$scriptname = $_SERVER["PHP_SELF"] . "?";
if (!isset($GLOBALS["countbytes"]))
	$GLOBALS["countbytes"] = true;
?>
<html>
<head>
	<title>Geograph British Isles - Torrent Archive</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link rel="stylesheet" href="./css/style.css" type="text/css" />
	<script type="text/JavaScript" src="/rounded_corners_lite.inc.js"></script>

	<?php
	if ($enablerss == true)
		echo "<link rel=\"alternate\" title=\"" . $rss_title . "\" href=\"rss/rss.xml\" type=\"application/rss+xml\">";
	?>
	
<script type="text/JavaScript">

  window.onload = function()
  {
      /*
      The new 'validTags' setting is optional and allows
      you to specify other HTML elements that curvyCorners
      can attempt to round.

      The value is comma separated list of html elements
      in lowercase.

      validTags: ["div", "form"]

      The above example would enable curvyCorners on FORM elements.
      */
      settings = {
          tl: { radius: 20 },
          tr: { radius: 20 },
          bl: { radius: 20 },
          br: { radius: 20 },
          antiAlias: true,
          autoPad: true,
          validTags: ["div"]
      }

      /*
      Usage:

      newCornersObj = new curvyCorners(settingsObj, classNameStr);
      newCornersObj = new curvyCorners(settingsObj, divObj1[, divObj2[, divObj3[, . . . [, divObjN]]]]);
      */
      var myBoxObject = new curvyCorners(settings, "intro");
      myBoxObject.applyCornersToAll();
  
  
      var myBoxObject2 = new curvyCorners(settings, "intro2");
      myBoxObject2.applyCornersToAll();
     var myBoxObject3 = new curvyCorners(settings, "helpful");
      myBoxObject3.applyCornersToAll();
  }
  
</script>

</head>
<body>
<div id="header" >

  <div id="info">
   <h1>Geograph Torrent Archive</h1>

<?php
if (file_exists("rss/rss.xml"))
{
	echo "<a href='rss/rss.xml'><img src='images/feed-icon-14x14.png' border='0' class='icon' alt='RSS 2.0 Feed' title='RSS 2.0 Feed' /></a><a href='rss/rss.xml'>RSS 2.0 Feed</a>";
}
?>
| <a href="/map.php">Peer Map</a>
  </div>
  
  <div id="logo">
  <a title="View Geograph British Isles website" href="http://www.geograph.org.uk"><img align="right" src="http://s0.geograph.org.uk/templates/basic/img/logo.gif" width="257" height="74" border="0"></a>
  </div>
  
<br style="clear:both"/>
</div>



<div class="intro">  

<p>Here you will find the entire
	archive of the <a href="http://www.geograph.org.uk">Geograph British Isles</a> project readily downloadable in volumes each 
	comprising around 50,000 images plus RDF formatted metadata.</p>


<p>Everything in the torrents -- images and
    metadata -- is licensed under the <a rel="license"
    href="http://creativecommons.org/licenses/by-sa/2.0/">Creative Commons
    Attribution-ShareAlike licence</a>, and the RDF file references
    the licence terms for each item in the volume. Please take care to
    respect these licences when re-using this data.</p>
    

<p><small><tt>Geograph-Dev0.1</tt> is a WMware based virtual machine setup as a pre-configured Development Enviroment - probably only of 
	interest to potential developers, <a href="http://www.geograph.org.uk/discuss/index.php?&action=vthread&forum=12&topic=9237">more on forum</a>.</small></p>

<p></p>

</div>

<div class="intro2">  
<b>What's a torrent?</b><br>

<p>When you download a torrent, you are downloading chunks from all the
other people currently downloading the same data. At the same time,
you are uploading chunks to those people who need a chunk you have.</p>

<p>This means we can offer these large archives without incurring
huge bandwidth bills.</p>

<p>Get yourself a <a href="http://en.wikipedia.org/wiki/BitTorrent_client">BitTorrent client</a> and join in!</p>


</div>


<br style="clear:both"/>





<?php
if ($GLOBALS["persist"])
	$db = mysql_pconnect($dbhost, $dbuser, $dbpass) or die(errorMessage() . "Tracker error: can't connect to database - " . mysql_error() . "</p>");
else
	$db = mysql_connect($dbhost, $dbuser, $dbpass) or die(errorMessage() . "Tracker error: can't connect to database - " . mysql_error() . "</p>");
mysql_select_db($database) or die(errorMessage() . "Tracker error: can't open database $database - " . mysql_error() . "</p>");

if (isset($_GET["seededonly"]))
	$where = " WHERE seeds > 0";
else if (isset($_GET["activeonly"]))
	$where = " WHERE leechers+seeds > 0";
else
	$where = " ";

$query = "SELECT COUNT(*) FROM ".$prefix."summary $where";
$results = mysql_query($query);
$res = mysql_result($results,0,0);

if (isset($_GET["activeonly"]))
	$scriptname = $scriptname . "activeonly=yes&";
if (isset($_GET["seededonly"]))
	$scriptname = $scriptname . "seededonly=yes&";

$pages=ceil($res/10);
if ($pages>1)
{
	echo "<p align='center'>Page: \n";
	$count = 0;
	$page = 1;
	while($count < $res)
	{
		if (isset($_GET["page_number"]) && $page == $_GET["page_number"])
			echo "<b><a href=\"$scriptname" . "page_number=$page\">($page)</a></b>-\n";
		else if (!isset($_GET["page_number"]) && $page == 1)
			echo "<b><a href=\"$scriptname" . "page_number=$page\">($page)</a></b>-\n";
		else
			echo "<a href=\"$scriptname" . "page_number=$page\">$page</a>-\n";
		$page++;
		$count = $count + 10;
	}
	echo "</p>\n";
}
?>

<!--
<table>
<tr>
	<?php 
	if (!isset($_GET["activeonly"]))
		$scriptname = $scriptname . "activeonly=	yes&amp;";
	if (isset($_GET["seededonly"]) && !isset($_GET["activeonly"]))
	{
		$scriptname = $scriptname . "seededonly=yes&";
		$_GET["page_number"] = 1;
	}
	if (isset($_GET["page_number"]))
		$scriptname = $scriptname . "page_number=" . $_GET["page_number"] . "&amp;";
		
	if (isset($_GET["activeonly"]))
		echo "<td><a href=\"$scriptname\">Show all torrents</a></td>\n";
	else
		echo "<td><a href=\"$scriptname\">Show only active torrents</a></td>\n";
		
	$scriptname = $_SERVER["PHP_SELF"] . "?";
	
	if (!isset($_GET["seededonly"]))
		$scriptname = $scriptname . "seededonly=yes&amp;";
	if (isset($_GET["activeonly"]) && !isset($_GET["seededonly"]))
	{
		$scriptname = $scriptname . "activeonly=yes&";
		$_GET["page_number"] = 1;
	}
	if (isset($_GET["page_number"]))
		$scriptname = $scriptname . "page_number=" . $_GET["page_number"] . "&amp;";
		
	if (isset($_GET["seededonly"]))
		echo "<td align=\"right\"><a href=\"$scriptname\">Show all torrents</a></td>\n";
	else
		echo "<td align=\"right\"><a href=\"$scriptname\">Show only seeded torrents</a></td>\n";
		
	$scriptname = $_SERVER["PHP_SELF"] . "?";
	
	?>
</tr>
</table>
-->

<div style="width:90%;margin-left:20px">
<table>
<tr>
	<td>
	<table class="torrentlist">

	<!-- Column Headers -->
	<tr>
		<th>Name/Info Hash</th>
		<th>Size</th>
		<th>Seeds</th>
		<th>Peers</th>
		<th>Completed<br/>Downloads</th>
		<?php
		// Bytes mode off? Ignore the columns
		if ($GLOBALS["countbytes"])
			echo '<th>Bytes<br/>Transferred</th><th>Speed<br/>(estimated)</th>';
		?>
	</tr>
	
<?php

$query = "SELECT ".$prefix."summary.info_hash, ".
		$prefix."summary.seeds, ".
		$prefix."summary.leechers, ".
		"format(".$prefix."summary.finished,0), ".
		$prefix."summary.dlbytes, ".
		$prefix."namemap.filename, ".
		$prefix."namemap.url, ".
		$prefix."namemap.size, ".
		$prefix."summary.speed ".
		"FROM ".$prefix."summary ".
		"LEFT JOIN ".$prefix."namemap ON ".$prefix."summary.info_hash = ".$prefix."namemap.info_hash ".
		"$where ORDER BY ".$prefix."namemap.filename ";
		
if (isset($_GET["page_number"]))
	$query.= "LIMIT 0,10";
else
{
	if ($_GET["page_number"] <= 0) //account for possible negative number entry by user
		$_GET["page_number"] = 1;
	
	$page_limit = ($_GET["page_number"] - 1) * 10;
	$query.= "LIMIT $page_limit,10";
}

$results = mysql_query($query) or die(errorMessage() . "Can't do SQL query - " . mysql_error() . "</p>");
$i = 0;

while ($data = mysql_fetch_row($results)) {
	// NULLs are such a pain at times. isset($nullvar) == false
	if (is_null($data[5]))
		$data[5] = $data[0];
	if (is_null($data[6]))
		$data[6] = "";
	if (is_null($data[7]))
		$data[7] = "";
	if (strlen($data[5]) == 0)
		$data[5] = $data[0];
	$myhash = $data[0];
	$writeout = "row" . $i % 2;
	echo "<tr class=\"$writeout\">\n";
	echo "\t<td>";
	echo "\t<table class=\"nopadding\" border=\"0\"><tr><td valign=\"top\" align=\"left\" width=\"10%\">\n";
	echo "\t<form method='post' action='torrent_functions.php'>\n";
	echo "\t<input type='hidden' name='hash' value='" . $data[0] . "'/>\n";
	echo "\t<input type='submit' value=' + '/></form>\n";
	echo "\t</td><td valign=\"top\" align=\"left\">\n";
	
	/*
	if (strlen($data[6]) > 0)
		echo "<a href=\"${data[6]}\">${data[5]}</a> - ";
	else
		echo $data[5] . " - ";
	if ($hiddentracker == true) //obscure direct link to torrent, use dltorrent.php script
		echo "<a href=\"dltorrent.php?hash=" . $myhash . "\">  (Download Torrent)</a></td></tr>";
	else //just display ordinary direct link
		echo "<a href=\"torrents/" . rawurlencode($data[5]) . ".torrent\">  (Download Torrent)</a></td></tr>";
	*/
	
	echo "<a title=\"download torrent\" href=\"torrents/" . rawurlencode($data[5]) . ".torrent\">{$data[5]}</a>";
	
	
	echo "</td></tr>";
	
	
	
	echo "</table></td>\n";
	
	if (strlen($data[7]) > 0) //show file size
	{
		echo "<td class=\"center\">".bytesToString($data[7])."</td>";
		$total_disk_usage = $total_disk_usage + $data[7]; //total up file sizes
	}
	
	for ($j=1; $j < 4; $j++) //show seeders, leechers, and completed downloads
	{
		echo "\t<td class=\"center\">$data[$j]</td>\n";
		if ($j == 1) //add to total seeders
			$total_seeders = $total_seeders + $data[1];
		if ($j == 2) //add to total leechers
			$total_leechers = $total_leechers + $data[2];
		if ($j == 3) //add to completed downloads
			$total_downloads = $total_downloads + $data[3];
	}

	if ($GLOBALS["countbytes"])
	{
		echo "\t<td class=\"center\">" . bytestoString($data[4]) . "</td>\n";
		$total_bytes_transferred = $total_bytes_transferred + $data[4]; //add to total GB transferred

		// The SPEED column calculations.
		if ($data[8] <= 0)
		{
			$speed = "0";
			$total_speed = $total_speed - $data[8]; //for total speed column
		}
		else if ($data[8] > 2097152)
			$speed = round($data[8] / 1048576, 2) . " MB/sec";
		else
			$speed = round($data[8] / 1024, 2) . " KB/sec";
		echo "\t<td class=\"center\">$speed</td>\n";
		$total_speed = $total_speed + $data[8]; //add to total speed, in bytes
	}
	echo "</tr>\n";
	$i++;
}

if ($i == 0)
	echo "<tr class=\"row0\"><td style=\"text-align: center;\" colspan=\"7\">No torrents</td></tr>";

//show totals in last row
echo "<tr>";
echo "<th colspan=\"2\" align=\"right\">Space Used: " . bytesToString($total_disk_usage) . "</th>";
echo "<th>" . $total_seeders . "</th>";
echo "<th>" . $total_leechers . "</th>";
echo "<th>" . $total_downloads . "</th>";
if ($GLOBALS["countbytes"]) //stop count bytes variable
{
	echo "<th>" . bytestoString($total_bytes_transferred) . "</th>";
	if ($total_speed > 2097152)
		echo "<th>" . round($total_speed / 1048576, 2) . " MB/sec</th>";
	else
		echo "<th>" . round($total_speed / 1024, 2) . " KB/sec</th>";
}

?>
	</tr></table></td></tr>
</table>


</div>


<?php
/*
$query = "SELECT SUM(".$prefix."namemap.size), SUM(".$prefix."summary.seeds), SUM(".$prefix."summary.leechers), SUM(".$prefix."summary.finished), SUM(".$prefix."summary.dlbytes), SUM(".$prefix."summary.speed) FROM ".$prefix."summary LEFT JOIN ".$prefix."namemap ON ".$prefix."summary.info_hash = ".$prefix."namemap.info_hash";
$results = mysql_query($query) or die(errorMessage() . "Can't do SQL query - " . mysql_error() . "</p>");
$data = mysql_fetch_row($results);

?>



<center>
<table>
<tr>
<th class="subheader">Total Space Used</th>
<th class="subheader">Seeders</th>
<th class="subheader">Leechers</th>
<th class="subheader">Completed D/Ls</th>
<th class="subheader">Bytes Transferred</th>
<th class="subheader">Speed (rough estimate)</th>
</tr>
<tr>
<?php
if ($data[0] != null) //if there are no torrents in database, don't show anything
{
	echo "<td align=\"center\">" . bytesToString($data[0]) . "</td>\n";
	echo "<td align=\"center\">" . $data[1] . "</td>\n";
	echo "<td align=\"center\">" . $data[2] . "</td>\n";
	echo "<td align=\"center\">" . $data[3] . "</td>\n";
	echo "<td align=\"center\">" . bytesToString($data[4]) . "</td>\n";
	if ($GLOBALS["countbytes"]) //stop count bytes OFF, OK to do speed calculation
	{
		if ($data[5] > 2097152)
			echo "<td align=\"center\">" . round($data[5] / 1048576, 2) . " MB/sec</td>\n";
		else
			echo "<td align=\"center\">" . round($data[5] / 1024, 2) . " KB/sec</td>\n";
	}
	else
		echo "<td align=\"center\">No Info Available</td>\n";
}
?>
</tr>
</table>
</center>
<br>

<?php
*/
?>


<div class="helpful"> 

<p style="color:#880000"><b>These torrents are large!</b> Ensure than downloading and
	seeding them will not put you over your Internet provider's bandwidth limits.</p>

<p>If you need any help or have questions about this service, contact Paul Dixon
(<a href='ma&#105;lto&#58;&#108;%6Fr%&#54;4&#101;&#108;ph&#37;40g&#109;a&#105;&#108;&#37;2&#69;%6&#51;&#37;&#54;&#70;%6D'>&#108;o&#114;de&#108;p&#104;&#64;g&#109;ail&#46;co&#109;</a>)
</p>
</div>


<div style="text-align:right;font-size:8pt">
<?php
//Display logout option if logged in
if ($hiddentracker == true)
{
	echo "Hello, <i>" . $_SESSION["username"] . "</i> ";
	echo "<a href=\"authenticate?status=logout\"><img src=\"images/logout.png\" border=\"0\" class=\"icon\" alt=\"Logout\" title=\"Logout\" /></a><a href=\"authenticate?status=logout\">Logout</a> | ";
}
?>

<a href="newtorrents.php">Add Torrent to Tracker Database</a> | 
<a href="admin.php">Admin Page</a>

</div>




<?php

if (rand(1, 10) == 1)
{
	//10% of the time, run sanity_no_output.php to prune database and keep users fresh
	include("sanity_no_output.php");
}

?>
</body></html>
