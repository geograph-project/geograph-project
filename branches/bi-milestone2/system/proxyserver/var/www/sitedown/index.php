<?
header("HTTP/1.1 503 Service Unavailable");
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1 
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache"); 
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past 
header("Cache-Control: max-age=0",false);

if (preg_match('/\.(js|css|jpg|png|gif)$/',$_SERVER['REDIRECT_URL'])) {
	#header("Content-Length:0");
	print "Error:503 No server";
	exit;
}

?><html>
<head>
<title>Geograph.org.uk - Temporarily Offline</title>
<style type="text/css">
body {background:#cccccc;}
.header {background:#000066;color:white;padding:10px;}
#msg {margin:30px;border: 2px solid black;background:white;}
#msg #inner {padding:10px;}
#warning {padding:10px;background-color:yellow;border:2px solid red;}
h1 {font-family:Georgia,Arial;}
h2 {margin-bottom:0px;margin-right:20px;color:red;font-size:2em;}
p {font-family:Arial;}
</style>
</head>
<body>
<div id="msg">
<div class="header">
<h1>Geograph British Isles</h1>
<h2 align="right">Temporarily Offline</h2>
</div>
<? if (!empty($_SERVER['REDIRECT_REQUEST_METHOD']) && $_SERVER['REDIRECT_REQUEST_METHOD'] == 'POST') { ?>
	<div id="warning">
		<p>You appear to in the middle of submitting information. 
		<b>You may be able to press F5 to resubmit your data.</b></p>
	</div>
<? } ?>
<div id="inner">
<p>All our servers are currently working to capacity - please try again in a few minutes,<br><br>
Thank you for your patience.<br><br>
</p>
<p align="right">TIP: You can use <a href="http://images.google.co.uk/images?q=site:geograph.org.uk">Google Image Search</a> to search a small selection of cached Geograph Images. </p>
</div>
<hr/>
<p align="center"><small>Backup communications: <a href="http://groups.yahoo.com/group/GeographSidetrack/">GeographSideTrack Email Group</a> 
and <a href="http://www.nearby.org.uk/geograph/chat/">Geograph Chat</a></small></p>
</div>
<p align="center">A more technical description: <tt>503 no server was available to handle the request</tt></p> 
</body>
</html>
