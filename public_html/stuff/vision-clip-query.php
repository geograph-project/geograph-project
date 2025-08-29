<?

header("Status: 301 Moved Permanently");
$link = "/ai/clip-query.php";
if ($_SERVER['QUERY_STRING'])
        $link .= "?".$_SERVER['QUERY_STRING'];

header("Location: https://{$_SERVER['HTTP_HOST']}$link");

