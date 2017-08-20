<?

if (isset($_GET['enable'])){
	if (empty($_GET['enable'])) {
		setcookie('securetest', '', time()-3600*24*365,'/');
		print "Experiment Now Disabled<br><br>";

		require_once('geograph/global.inc.php');
		init_session();

		$USER->logout();

	} else {
		setcookie('securetest', '1', time()+3600*24*14,'/');  //we DONT set secure on this cookie, as need it for http->https redirect
		print "Experiment Now Enabled<br><br>";
		$enabled = true;
	}
} else {
	if (!empty($_COOKIE['securetest'])) {
		print "Experiment Currently Enabled<br><br>";
		$enabled = true;
	} else {
		print "Experiment Currently Disabled<br><br>";
	}
}

if (empty($enabled)) {
	print '<a href="?enable=1">Click here to Enable Secure Remember-Me Test</a> <br><br>';
} else {
	print '<a href="?enable=0">Click here to Disable Secure Remember-Me Test</a> (you will be logged out)<br><br>';
}

print '<a href="/">Back to geograph homepage</a>';

