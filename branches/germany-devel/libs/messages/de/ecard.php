<?php

$MESSAGES['ecard'] = array (
	'message_template'        => "Hallo,\r\n\r\nich habe dieses Bild entdeckt und dachte, es könnte Dir gefallen.\r\n\r\nViele Grüße,\r\n\r\n",
	'email_invalid'           => 'Bitte gültige E-Mail-Adresse eingeben!',
	'name_chars'              => 'Der Name enthält ungültige Zeichen!',
	'empty_message'           => "Bitte Nachricht eingeben!",
	'mail_subject'            => "%s schickt eine elektronische Postkarte",
	'preview_title'           => "<title>Postkarte: Vorschau</title>",
	'preview_html'            => <<<EOT
<br/><p align="center"><font face="Georgia">Unten ist die Vorschau der Karte an %s zu sehen</font>
<input type="submit" name="edit" value="Bearbeiten">
<input type="submit" name="send" value="Abschicken"></p>
</FORM>
<h3 align=center><font face="Georgia">Betreff: %s</font></h3>
EOT
,
);

?>
