<?

/**
* basic email address check
*/
function isValidEmailAddress($email) 
{
	return preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._\-\+])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/' , $email)?true:false; 
}


if (!empty($_POST['email'])) {
	if (isValidEmailAddress($_POST['email'])) {
		
		$msg = "Hi,\n\nSomeone, hopefully you, requested to be sent a link to our Feedback form:\n\n";

		$msg .= "http://{$_SERVER['HTTP_HOST']}/help/teaching_feedback\n\n";

		$msg .= "Please consider filling it out when you have a moment.\n\n";

		$msg .= "Thanks,\n Geograph team.\n\n";

		$msg .= "---------------\nForward abuse reports to support@geograph.org.uk\n";

		@mail($_POST['email'], '[Geograph] Link to feedback form', $msg,
						"From: Geograph Website <noreply@geograph.org.uk>");
	} else {
		die("Does not appear to be a valid email address - press back, and try again");
	}
	
}

header("Location: /help/teaching_feedback");
