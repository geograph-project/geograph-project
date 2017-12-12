<?php

$MESSAGES['class_user'] = array (
	'name_missing'            => 'Es wurde kein Name angegeben!',
	'name_chars'              => 'Der Name enth�lt ung�ltige Zeichen!',
	'email_invalid'           => 'Bitte g�ltige E-Mail-Adresse eingeben!',
	'password1'               => 'Das Passwort ist zu einfach oder zu kurz!',
	'password2'               => 'Passw�rter stimmen nicht �berein!',
	'already_registered'      => 'E-Mail-Adresse schon registriert!',
	'error_dbupdate'          => 'Datenbank-Update fehlgeschlagen: ',
	'error_dbinsert'          => 'Datenbank-Insert fehlgeschlagen: ',
	'mailbody_register'       => <<<EOT
Danke f�r die Registrierung bei %s !

Vor dem ersten Einloggen muss die Registrierung durch Aufrufen des Links

%s

best�tigt werden. Nach erfolgter Best�tigung ist das Einloggen durch Eingabe von
E-Mail-Adresse (%s) und Passwort m�glich.

Wir w�nschen viel Freude an der Teilnahme am Projekt!

Mit freundlichen Gr��en

Das Geograph-�sterreich-Team
EOT
,
	'mailsubject_register'    => 'Registrierungsbest�tigung',
	'reminder_email_invalid'  => 'Ung�ltige E-Mail-Adresse',
	'mailbody_reminder'       => <<<EOT
Hallo,

wir wurden aufgefordert, das Passwort f�r den Account %s bei %s zu �ndern.
Um das neue Passwort zu best�tigen bitte folgenden Link aufrufen:

%s

Wenn das Passwort nicht ge�ndert werden soll, kann diese Mail einfach ignoriert werden.

Mit freundlichen Gr��en

Das Geograph-�sterreich-Team
EOT
,
	'mailsubject_reminder'    => 'Neues Passwort f�r %s',
	'not_registered'          => 'Diese E-Mail-Adresse ist nicht registriert!',


	'realname'                => 'Es wurde kein Name angegeben!',
	'website'                 => 'Die Adresse scheint ung�ltig zu sein.',
	'nickname_in_use'         => 'Dieser Kurzname ist schon vergeben!',
	'oldpassword'             => 'Bitte aktuelles Passwort angeben, wenn ein Passwortwechsel gew�nscht ist!',
	'mail_change'             => 'Um den E-Mail-Adressen-Wechsel '.
				     'zu best�tigen, bitte die Anweisungen in der E-Mail befolgen, '.
				     'die wir an %s geschickt haben!',
	'mailbody_mail_change'    => <<<EOT
Hallo,

wir wurden aufgefordert, die E-Mail-Adresse f�r den Account bei %s zu %s zu �ndern.
Um die neue E-Mail-Adresse zu best�tigen bitte folgenden Link aufrufen:

%s

Wenn die Adresse nicht ge�ndert werden soll, kann diese Mail einfach ignoriert werden.

Mit freundlichen Gr��en

Das Geograph-�sterreich-Team
EOT
,
	'mailsubject_mail_change' => 'Best�tigung der neuen E-Mail-Adresse f�r %s',
	'new_email_invalid'       => 'Ung�ltige E-Mail-Adresse!',
	'must_confirm'            => 'Vor dem ersten Einloggen muss der Link aus der Best�tigungsmail an %s aufgerufen werden!',
	'invalid_password'        => 'Falsches Passwort oder Zugangssperre',
	'user_unknown'            => 'E-Mail-Adresse bzw. Benutzername ist nicht registriert',
	'user_invalid'            => 'E-Mail-Adresse bzw. Benutzername ung�ltig',
);

?>
