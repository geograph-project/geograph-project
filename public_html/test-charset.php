<?

require_once('geograph/global.inc.php');

if (!empty($_GET['charset']) && $_GET['charset'] == 'UTF-8')
	header("Content-Type: text/html; charset=utf-8");
//else, rely on PHP still being setup as default_charset = "ISO-8859-1"

if (!empty($_GET['charset']) && $_GET['charset'] == 'bad')
	header("Content-Type: text/html; charset=utf-8");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Charset Test</title>
</head>
<body>
This page tests
<ol>
	<li>That <a href="http://data.geograph.org.uk/gridimage_funny.mysql">http://data.geograph.org.uk/gridimage_funny.mysql</a> was succesfully imported into mysql
		(as a latin1/ISO-8859-1 database table)
	<li>That the PHP app, can read this ISO-8859-1 from database.
	<li>And either <ol>
		<li>Output the page directly as ISO-8859-1
		<li>or convert and output as proper UTF-8
	</ol></li>
</ol>
Compare to output of https://www.geograph.org.uk/stuff/unicode.php (what it should look like!)
<hr>
Output: <?
	if (!empty($_GET['charset']) && $_GET['charset'] == 'UTF-8') {
		print "<b>UTF8</b> / <a href=\"?charset=ISO-8859-1\">ISO-8859-1</a>";
	} else {
		print "<a href=\"?charset=UTF-8\">UTF8</a> / <b>ISO-8859-1</b>";
	}

	@$url = urlencode("http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?charset=".htmlentities($_GET['charset']));
	print " - <a href=\"https://validator.w3.org/nu/?doc=$url\">test this page on validator.w3.org</a>";
?>
<hr>

<table>
<?

	$db = GeographDatabaseConnection();

	$recordSet = $db->Execute("select gridimage_id,title from gridimage_funny where title is not null order by  gridimage_id IN (1339706,320042,47189,495051,519472,97714,1049262,1036631) desc,reverse(gridimage_id) limit 500");

	while (!$recordSet->EOF) {
		$row = $recordSet->fields;

		print "<tr><td><a href=\"https://www.geograph.org.uk/photo/{$row['gridimage_id']}\">{$row['gridimage_id']}</a></td>";

		if (!empty($_GET['charset']) && $_GET['charset'] == 'UTF-8') {
			$title = htmlentities(latin1_to_utf8($row['title']), ENT_COMPAT, 'UTF-8');
		} elseif (!empty($_GET['charset']) && $_GET['charset'] == 'bad') {
			//deliberate bad attempt!  (coupled with pretending it UTF charset!
			$title = htmlentities($row['title']);
		} elseif (!empty($_GET['charset']) && $_GET['charset'] == 'raw') {
			//no entities to use with encode
			$title = $row['title'];
		} else {
		        $title = htmlentities2($row['title']);
		}
		if (!empty($_GET['encode'])) //urlencode, a nice way to visiualse any 'non-ascii chars!
			$title = str_replace('+',' ',urlencode($title));
		print "<td>$title</td></tr>";

		$recordSet->MoveNext();
	}
	$recordSet->Close();

print "</table></body></html>";


