{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}

{dynamic}
{if $message_sent}
	<h3>Thank you for contacting the Geograph Deutschland team.</h3>
	<p>Your message has been sent - you should hear back from us soon.</p>
{else}
    <h2>Contact Us</h2>
 
 	<div style="background-color:#eeeeee;padding:2px; text-align:center">
	The Geograph Deutschland project aims to collect geographically
	representative photographs and information for every square kilometre of Germany.
	</div>
 
	<p>Got something to tell us? Then fire away - we try to respond within 24 hours, often much quicker.</p>

	<p><b>Please note that contact messages are sent to several people. If you prefer to contact a specific person
	or photographer, you should consider sending a personal message via the message links provided on the user's
	profile page or a photo page.</b></p>

    <form action="contact.php" method="post">
    <p><input type="hidden" name="referring_page" value="{$referring_page|escape:'html'}"/>
    <label for="from">Your email address</label><br/>
	<input size="40" id="from" name="from" value="{$from|escape:'html'}"/><span class="formerror">{$from_error}</span>
    
    <br /><br />
    
    <label for="subject">Subject</label><br/>
	<input size="40" id="subject" name="subject" value="{$subject|escape:'html'}"/>
	<br /><br />
    
    <label for="msg">Your message</label><br/>
	<textarea id="msg" name="msg" rows="10" cols="50">{$msg|escape:'html'}</textarea>
    	<br /><span class="formerror">{$msg_error}</span> 
    <br />
   </p>
			<div style="padding:10px; border:2px solid yellow; font-size:0.7em">
			<img src="//{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="30" height="24" align="left" style="margin-right:10px"/>
			Please note that we generate emails to the members of our support team from your input.
			Therefore, we transmit your email address and, to help them to deal with our user's problems, the
			referring geograph page and browser type. See also our <a href="/help/privacy">privacy statement</a>.</div>
   <p>
        <small>If you are writing in relation to a particular image, don't forget to mention which!</small>
    
    <input type="submit" name="send" value="Send"/></p>
    </form>
{/if} 
{/dynamic}    
{include file="_std_end.tpl"}
