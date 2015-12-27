{assign var="page_title" value="Administrator-Modus"}
{include file="_std_begin.tpl"}

<h2>Administrator-Modus</h2>
<p>Um <a href="/help/csrf">CSRF-Angriffe</a> zu verhindern, lassen wir wichtige Eingabeformulare einen "Schl�ssel" �bertragen, der vom Server �berpr�ft werden kann.
Leider ist dies nicht f�r alle Administrations-Seiten m�glich, so dass wir den Zugriff auf selbige standardm��ig nicht erlauben.</p>
<p>Um administrative Aufgaben durchzuf�hren, m�ssen <b>alle Browserfenster and Tabs geschlossen</b> werden, die nicht die Geograph-Pr�senz zeigen.
Daraufhin kann der Administrator-Modus durch Anklicken des Buttons im Navigationsmen� aktiviert werden. Sobald die Administration abgeschlossen ist,
sollte der Administrator-Modus wieder verlassen werden.</p>
<p>Die Farbe des Navigations-Kastens wird im Administrator-Modus ge�ndert um daran zu erinnern, selbigen m�glichst schnell zu verlassen.</p>

{include file="_std_end.tpl"}
