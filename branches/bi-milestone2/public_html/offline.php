<?php

$auto_redirect=false;

if ($auto_redirect)
{
	$url="http://new.geograph.org.uk/".$_SERVER['REQUEST_URI'];
	header("Location:$url");
	exit;
}

?>
<html>
<head>
<title>Geograph British Isles- Photograph Every Grid Square!</title>
<style type="text/css">

body
{
	background-color:#eeeeee;
	font-family:Georgia,Verdana,Arial;
}

h1
{
	font-size:14pt;
}
.main
{
	background-color:white;
	border:3px solid silver;
	padding:30px;
	margin:30px;
}

</style>
</head>

<body>

<div class="main">
<h1>Geograph British Isles is down for essential maintenance</h1>
<p>Sorry about any inconvenience, we aim to be back up shortly!</p>
</div>

</body>
</html>


