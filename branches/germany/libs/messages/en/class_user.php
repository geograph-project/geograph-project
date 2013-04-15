<?php

$MESSAGES['class_user'] = array (
	'name_missing'            => 'You must give your name',
	'name_chars'              => 'Only letters A-Z, a-z, hyphens and apostrophes allowed',
	'email_invalid'           => 'Please enter a valid email address',
	'password1'               => 'You must specify a password',
	'password2'               => 'Passwords didn\'t match, please try again',
	'already_registered'      => 'Email address is already registered',
	'error_dbupdate'          => 'error updating: ',
	'error_dbinsert'          => 'error inserting: ',
	'mailbody_register'       => <<<EOT
Thankyou for registering at %s

Before you can log in, you must first confirm your registration by following the link below:

%s

Once you have confirmed your registration, you will be able to log in with the email address and password you provided:
    email: %s

We hope you enjoy using and contributing to the site

Kind Regards,

The Geograph Deutschland Team
EOT
,
	'mailsubject_register'    => 'Confirm registration',
	'reminder_email_invalid'  => 'This isn\'t a valid email address',
	'mailbody_reminder'       => <<<EOT
Hello.

You recently requested the password for account %s at %s to be changed.
To confirm, please click this link:

%s

If you do not wish to change your password, simply disregard this message.

Kind Regards,

The Geograph Deutschland Team
EOT
,
	'mailsubject_reminder'    => 'New password for %s',
	'not_registered'          => "This email address isn't registered",


	'realname'                => 'Please enter your real name, we use it to credit your photographs',
	'website'                 => 'This doesn\'t appear to be a valid URL',
	'nickname_in_use'         => 'Sorry, this nickname is already taken by another user',
	'oldpassword'             => 'Please enter your current password if you wish to change it',
	'mail_change'             => 'To change your email address, '.
				     'we\'ve sent an email to %s which contains '.
				     'instructions on how to confirm the change.',
	'mailbody_mail_change'    => <<<EOT
Hello.

You recently requested the email address for your account at %s be changed to %s.
To confirm, please click this link:

%s

If you do not wish to change your address, simply disregard this message.

Kind Regards,

The Geograph Deutschland Team
EOT
,
	'mailsubject_mail_change' => 'Please confirm your email address change for %s',
	'new_email_invalid'       => 'Invalid email address',
	'must_confirm'            => 'You must confirm your registration by following the link in the email sent to %s',
	'invalid_password'        => 'Wrong password - don\'t forget passwords are case-sensitive',
	'user_unknown'            => 'This email address or nickname is not registered',
	'user_invalid'            => 'This is not a valid email address or nickname',
);

?>
