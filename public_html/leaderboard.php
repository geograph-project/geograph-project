<?

header("Status: 301 Moved Permanently");
$link = "/statistics/";
$link .= basename($_SERVER['SCRIPT_NAME']);
if ($_SERVER['QUERY_STRING'])
	$link .= "?".$_SERVER['QUERY_STRING'];

header("Location: http://{$_SERVER['HTTP_HOST']}$link");

