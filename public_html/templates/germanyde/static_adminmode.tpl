{assign var="page_title" value="Administrator-Modus"}
{include file="_std_begin.tpl"}

<h2>Administrator-Modus</h2>
<p>Um <a href="/help/csrf">CSRF-Angriffe</a> zu verhindern, lassen wir wichtige Eingabeformulare einen "Schlüssel" übertragen, der vom Server überprüft werden kann.
Leider ist dies nicht für alle Administrations-Seiten möglich, so dass wir den Zugriff auf selbige standardmäßig nicht erlauben.</p>
<p>Um administrative Aufgaben durchzuführen, müssen <b>alle Browserfenster and Tabs geschlossen</b> werden, die nicht die Geograph-Präsenz zeigen.
Daraufhin kann der Administrator-Modus durch Anklicken des Buttons im Navigationsmenü aktiviert werden. Sobald die Administration abgeschlossen ist,
sollte der Administrator-Modus wieder verlassen werden.</p>
<p>Die Farbe des Navigations-Kastens wird im Administrator-Modus geändert um daran zu erinnern, selbigen möglichst schnell zu verlassen.</p>

{include file="_std_end.tpl"}
