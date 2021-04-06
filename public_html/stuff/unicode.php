<?

require_once('geograph/global.inc.php');
$db = GeographDatabaseConnection(true);

$smarty = new GeographPage;
$smarty->display('_std_begin.tpl');

$result = mysql_query("select gridimage_id,title from gridimage_funny where title is not null order by  gridimage_id IN (1339706,320042,47189,495051,519472,97714,1049262,1036631) desc,reverse(gridimage_id) limit 500");

while ($row = mysql_fetch_assoc($result)) {

	$row['title'] = htmlentities2($row['title']);

	print "{$row['gridimage_id']}: {$row['title']}<br>";
}

$smarty->display('_std_end.tpl');
