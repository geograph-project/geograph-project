{assign var="page_title" value="Administrator mode"}
{include file="_std_begin.tpl"}

<h2>Administrator mode</h2>
<p>In order to prevent <a href="/help/csrf">CSRF attacks</a>, many Geograph web forms send a hidden token which can be checked by the server.
Unfortunately, this is not feasible for all administrative forms on our site. Therefore, these forms are not accessible by default.</p>
<p>In order to perform administrative tasks, you must <b>close all browser windows and tabs</b> not showing the Geograph web site
and then enter "administrator mode" using the button in the navigation bar. As soon as you have finished these tasks, you should leave
"administrator mode".</p>
<p>The colour of the navigation bar changes while "administrator mode" is active, in order to remind you to leave it as soon as possible.</p>

{include file="_std_end.tpl"}
