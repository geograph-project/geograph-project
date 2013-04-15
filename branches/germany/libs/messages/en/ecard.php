<?php

$MESSAGES['ecard'] = array (
	'message_template'        => "Hi,\r\n\r\nI recently saw this image, and thought you might like to see it too.\r\n\r\nRegards,\r\n\r\n",
	'email_invalid'           => 'Please specify a valid email address',
	'name_chars'              => 'Only letters A-Z, a-z, hyphens and apostrophes allowed',
	'empty_message'           => "Please enter a message to send",
	'mail_subject'            => "%s is sending you an e-Card",
	'preview_title'           => "<title>eCard Preview</title>",
	'preview_html'            => <<<EOT
<br/><p align="center"><font face="Georgia">Below is a preview the card as will be sent to %s</font>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="send" value="Send"></p>
</FORM>
<h3 align=center><font face="Georgia">Subject: %s</font></h3>
EOT
,
);

?>
