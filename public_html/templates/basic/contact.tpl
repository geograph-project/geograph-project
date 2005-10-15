{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}

{dynamic}
{if $message_sent}
	<h2>Thanks!</h2>
	<p>Your message has been sent.</p>
{else}
    <h2>Contact Us</h2>
 
 	<p>Got something to tell us? Then fire away...</p>
 
    <form action="contact.php" method="post">
    
    <p><label for="from">Your email address</label><br/>
	<input id="from" name="from" value="{$from|escape:'html'}"/><span class="formerror">{$from_error}</span>
    
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
