<?

if (isset($_GET['enable'])){
	if (empty($_GET['enable'])) {
		setcookie('securetest', '', time()-3600*24*365,'/');

		require_once('geograph/global.inc.php');
		init_session();

		$USER->logout();

		print "Experiment Now Disabled<br><br>";

	} else {
		setcookie('securetest', '1', time()+3600*24*14,'/');  //we DONT set secure on this cookie, as need it for http->https redirect

		if (!empty($_COOKIE['autologin']))
			setcookie('autologin', $_COOKIE['autologin'], time()+3600*24*365,'/',"", true); //secure

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

?><a href="/">Back to geograph homepage</a>

<hr>

NOTE: This is a per browser setting, will need to opt in in any browser you normally use Geograph with.

<hr>

If you have problems after enabling this experiment, please let me know. After copying the page URL from browser address bar and forwarding.

Can return here and here and disable the experiment. However you will immidiately be logged out, but should be able to login once, and continue as before.
