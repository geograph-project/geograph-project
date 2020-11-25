<?

print "HOSTNAME: ".trim(`hostname`)."<br>";
if (!empty($_SERVER['REMOTE_ADDR'])) {
	$host = htmlentities(gethostbyaddr($_SERVER['REMOTE_ADDR']));
	print "REMOTE_ADDR: ".htmlentities($_SERVER['REMOTE_ADDR'])."  ($host)<br>";
}
if (!empty($_SERVER['SERVER_PORT'])) {
	 print "SERVER_PORT: ".htmlentities($_SERVER['SERVER_PORT'])."<br>";
}

if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	foreach (explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']) as $idx => $value) {
		$host = htmlentities(gethostbyaddr($value));
		print "HTTP_X_FORWARDED_FOR[$idx]: ".htmlentities($value)."  ($host)<br>";
	}
}
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
	print "HTTP_X_FORWARDED_PROTO: ".htmlentities($_SERVER['HTTP_X_FORWARDED_PROTO'])."<br>";
}

