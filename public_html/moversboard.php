<?

header("Status: 301 Moved Permanently");
$link = "/statistics/";
$link .= basename($_SERVER['SCRIPT_URL']);
if ($_SERVER['QUERY_STRING'])
	$link .= "?".$_SERVER['QUERY_STRING'];

header("Location: https://{$_SERVER['HTTP_HOST']}$link");
