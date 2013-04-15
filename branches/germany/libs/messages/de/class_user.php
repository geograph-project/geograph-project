<?php

$MESSAGES['class_user'] = array (
	'name_missing'            => 'Es wurde kein Name angegeben!',
	'name_chars'              => 'Der Name enthält ungültige Zeichen!',
	'email_invalid'           => 'Bitte gültige E-Mail-Adresse eingeben!',
	'password1'               => 'Es wurde kein Passwort angegeben!',
	'password2'               => 'Passwörter stimmen nicht überein!',
	'already_registered'      => 'E-Mail-Adresse schon registriert!',
	'error_dbupdate'          => 'Datenbank-Update fehlgeschlagen: ',
	'error_dbinsert'          => 'Datenbank-Insert fehlgeschlagen: ',
	'mailbody_register'       => <<<EOT
Danke für die Registrierung bei %s !

Vor dem ersten Einloggen muss die Registrierung durch Aufrufen des Links

%s

bestätigt werden. Nach erfolgter Bestätigung ist das Einloggen durch Eingabe von
E-Mail-Adresse (%s) und Passwort möglich.

Wir wünschen viel Freude an der Teilnahme am Projekt!

Mit freundlichen Grüßen

Das Geograph-Deutschland-Team
EOT
,
	'mailsubject_register'    => 'Registrierungsbestätigung',
	'reminder_email_invalid'  => 'Ungültige E-Mail-Adresse',
	'mailbody_reminder'       => <<<EOT
Hallo,

wir wurden aufgefordert, das Passwort für den Account %s bei %s zu ändern.
Um das neue Passwort zu bestätigen bitte folgenden Link aufrufen:

%s

Wenn das Passwort nicht geändert werden soll, kann diese Mail einfach ignoriert werden.

Mit freundlichen Grüßen

Das Geograph-Deutschland-Team
EOT
,
	'mailsubject_reminder'    => 'Neues Passwort für %s',
	'not_registered'          => 'Diese E-Mail-Adresse ist nicht registriert!',


	'realname'                => 'Es wurde kein Name angegeben!',
	'website'                 => 'Die Adresse scheint ungültig zu sein.',
	'nickname_in_use'         => 'Dieser Kurzname ist schon vergeben!',
	'oldpassword'             => 'Bitte aktuelles Passwort angeben, wenn ein Passwortwechsel gewünscht ist!',
	'mail_change'             => 'Um den E-Mail-Adressen-Wechsel '.
				     'zu bestätigen, bitte die Anweisungen in der E-Mail befolgen, '.
				     'die wir an %s geschickt haben!',
	'mailbody_mail_change'    => <<<EOT
Hallo,

wir wurden aufgefordert, die E-Mail-Adresse für den Account bei %s zu %s zu ändern.
Um die neue E-Mail-Adresse zu bestätigen bitte folgenden Link aufrufen:

%s

Wenn die Adresse nicht geändert werden soll, kann diese Mail einfach ignoriert werden.

Mit freundlichen Grüßen

Das Geograph-Deutschland-Team
EOT
,
	'mailsubject_mail_change' => 'Bestätigung der neuen E-Mail-Adresse für %s',
	'new_email_invalid'       => 'Ungültige E-Mail-Adresse!',
	'must_confirm'            => 'Vor dem ersten Einloggen muss der Link aus der Bestätigungsmail an %s aufgerufen werden!',
	'invalid_password'        => 'Falsches Passwort! Bitte Groß-/Kleinschreibung beachten!',
	'user_unknown'            => 'E-Mail-Adresse bzw. Benutzername ist nicht registriert',
	'user_invalid'            => 'E-Mail-Adresse bzw. Benutzername ungültig',
);

?>
