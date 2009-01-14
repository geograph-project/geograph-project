{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}

{dynamic}
{if $message_sent}
	<h3>Thank you for contacting the Geograph British Isles team.</h3>
	<p>Your message has been sent - you should hear back from us soon. In the meantime you can read about the project <a href="/faq.php">here</a>.</p>
{else}
    <h2>Contact Us</h2>
 
 	<p>Got something to tell us? Then fire away - we try to respond within 24 hours, often much quicker.</p>
 
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
    <input type="submit" name="send" value="Send"/></p>
    </form>
{/if} 
{/dynamic}    
{include file="_std_end.tpl"}
