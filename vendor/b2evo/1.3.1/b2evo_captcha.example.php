<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<TITLE>b2evo Captcha Class :: DEMO</TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
</head>
<body>
<h3>This is a demo of b2evo_captcha.class.php</h3>
<?PHP
require_once('b2evo_captcha.config.php');
require_once('b2evo_captcha.class.php');

	//Initialize the captcha object with our configuration options
	$captcha =& new b2evo_captcha($CAPTCHA_CONFIG);
	if (isset($_POST['image'])) {
		switch($captcha->validate_submit($_POST['image'],$_POST['attempt']))
		{
	
			// form was submitted with incorrect key
			case 0:
				echo '<p><br>Sorry. Your code was incorrect.';
				echo '<br><br><a href="'.$_SERVER['PHP_SELF'].'">Try AGAIN</a></p>';
				break;

			// form was submitted and has valid key
			case 1:
				echo '<p><br>Congratulations. You will get the resource now.';
				echo '<br><br><a href="'.$_SERVER['PHP_SELF'].'">New DEMO</a></p>';
				break;			
		}
	}
	else {
	$imgLoc = $captcha->get_b2evo_captcha();
	echo '<img src="'.$imgLoc.'" alt="This is a captcha-picture. It is used to prevent mass-access by robots." title=""><br>'."\n";
	echo '<form name="captcha" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	echo '<input type="hidden" name="image" value="'.$imgLoc.'">'."\n";
	echo '<input type="text" name="attempt" value="" size="10">&nbsp;&nbsp;';
	echo '<input type="submit" value="Submit">'."<br>\n";
	echo '</form>'."\n";
	}
?>
</body>
</html>
