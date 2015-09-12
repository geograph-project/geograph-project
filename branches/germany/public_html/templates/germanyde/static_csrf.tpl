{assign var="page_title" value="Formulare und CSRF"}
{include file="_std_begin.tpl"}


<h2>Formulare und Schutz vor Cross-Site-Request-Forgery</h2>

<p>Hin und wieder kann es vorkommen, dass ein Eingabeformular, das auf einer der
Seiten von Geograph Deutschland ausgef�llt wird, nicht auf Anhieb akzeptiert wird:
Es erscheint die Bitte, die Daten zu �berpr�fen und das Formular gegebenenfalls
erneut abzusenden.</p>

<h3>Wie sollte darauf reagiert werden?</h3>

<ul><li>Wenn das Formular zuvor tats�chlich selbst ausgef�llt worden ist,
kann es (ggf. nach kurzer Pr�fung) einfach erneut abgeschickt werden. In diesen Fall
ist durch fehlende Aktivit�t einfach die Session abgelaufen.</li>
<li>Wenn das Formular pl�tzlich erscheint, obwohl man auf einer anderen Webpr�senz war
und somit das Formular nicht ausgef�llt hat, liegt ein Cross-Site-Request-Forgery-Angriff vor:
Durch jene Seite wurde versucht, im Namen des Nutzers Formulardaten abzuschicken.
In diesem Fall sollte das Formular also <b>nicht erneut best�tigt</b> werden!
</li></ul>

<h3>Was ist der Grund f�r die Meldung?</h3>

<p>Bei wichtigen Formularen �berpr�fen wir, ob die Daten tats�chlich vom Benutzer stammen, der das
Formular aufgerufen hat. Vereinfacht wird dazu jedem Benutzer in jeder Session eine Art "Passwort"
zugeordnet, das nach Best�tigen des Formulars wieder an unseren Server geschickt wird. Da ein Angreifer
dieses "Passwort" nicht kennen kann, kann er anderen Benutzern keine Formulardaten unterschieben.</p>
<p>Allerdings verfallen diese Sessions nach einiger Zeit der Inaktivit�t. Wird dann das
Formular abgeschickt, so stimmt das "Passwort" aus dem Formular nicht mit dem der neuen "Session" �berein.
Aus Sicherheitsgr�nden m�ssen wir dann von einem Cross-Site-Request-Forgery-Angriff ausgehen.</p>
<p>Die Wikipedia beschreibt <a href="http://de.wikipedia.org/wiki/Cross-Site-Request-Forgery">CSRF-Angriffe</a> und <a href="http://de.wikipedia.org/wiki/Sitzungsbezeichner">Sessions</a> etwas detaillierter.</p>

{include file="_std_end.tpl"}
