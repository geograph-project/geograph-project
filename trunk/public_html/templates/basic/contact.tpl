{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}

{if $message_sent}
	<h2>Thanks!</h2>
	<p>Your message has been sent.</p>
{else}
    <h2>Contact Us</h2>
 
 	<p>Got something to tell us? Then fire away...</p>
 
    <form action="contact.php" method="post">
    
    <label for="from">Your email address</label><br/>
	<input id="from" name="from" value=""/>
    
    <br /><br />
    <label for="msg">Your message</label><br/>
	<textarea id="msg" name="msg" rows="10" cols="50"></textarea>
    
    <br />
    <input type="submit" name="send" value="Send"/>
    </form>
{/if} 
    
{include file="_std_end.tpl"}
