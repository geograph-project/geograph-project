<?php

$MESSAGES['ecard'] = array (
	'message_template'        => "Hallo,\r\n\r\nich habe dieses Bild entdeckt und dachte, es k�nnte Dir gefallen.\r\n\r\nViele Gr��e,\r\n\r\n",
	'email_invalid'           => 'Bitte g�ltige E-Mail-Adresse eingeben!',
	'name_chars'              => 'Der Name enth�lt ung�ltige Zeichen!',
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
