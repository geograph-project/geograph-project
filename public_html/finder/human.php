<?

header("Status: 301 Moved Permanently");
$link = "/finder/collaborative.php";
if ($_SERVER['QUERY_STRING'])
	$link .= "?".$_SERVER['QUERY_STRING'];

header("Location: {$CONF['SELF_HOST']}$link");

