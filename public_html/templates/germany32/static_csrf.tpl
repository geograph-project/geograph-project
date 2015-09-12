{assign var="page_title" value="Web forms and CSRF"}
{include file="_std_begin.tpl"}


<h2>Forms and preventing cross-site request forgery</h2>

<p>Occasionally our server does not accept user input after submitting a form on the web site.
In a message you are asked to review the input and submit the form again.</p>

<h3>How to react?</h3>

<ul><li>If you actually have filled in the form you simply can submit it again (probably after a short check).
The reason for the message is an expired session (due to lack of activity) in this case.</li>
<li>If the form appears although you were visiting another web site, i.e. you have not filled in the form,
a cross-site request forgery attack has occurred: The web site submitted form data on your behalf.
In this case, <b>you should not submit the form</b>!</li></ul>

<h3>Reasons for the message</h3>

<p>When processing important forms, we check whether the input originates from the user
who visited the page containing the form. Simplified, we create a "password" for every user in every session.
This "password" is sent to the server when the form is submitted. As an attacker does not know the
"password", he can't submit data on the user's behalf.</p>
<p>Sessions expire after a while when no activity is seen. When submitting a form with an expired session,
the "password" sent with the form data does not match the new "password" of the new session.
In this case, we have to presume a cross-site request forgery attack might have happened.</p>
<p>See Wikipedia for more information about <a href="http://en.wikipedia.org/wiki/Cross-site_request_forgery">CSRF attacks</a> and <a href="http://en.wikipedia.org/wiki/Session_ID">sessions</a>.</p>

{include file="_std_end.tpl"}
