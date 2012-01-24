{assign var="page_title" value="Kontakt"}
{include file="_std_begin.tpl"}

{dynamic}
{if $message_sent}
	<h3>Danke für die Nachricht an das Geograph-Deutschland-Team.</h3>
	<p>Die Nachricht wurde versandt, wir versuchen bald zu antworten.</p>
{else}
    <h2>Kontakt-Formular</h2>
 
 	<div style="background-color:#eeeeee;padding:2px; text-align:center">
	Das Geograph-Projekt hat das Ziel, geographisch repräsentative Photos für jeden Quadratkilometer Deutschlands zu sammeln.
	</div>
 
 	<p>Wenn Sie ein Anliegen haben, können Sie sich über dieses Formular an uns wenden. Wir versuchen, innerhalb von 24 Stunden zu antworten.</p>
 
    <form action="contact.php" method="post">
    <p><input type="hidden" name="referring_page" value="{$referring_page|escape:'html'}"/>
    <label for="from">Eigene Mail-Adresse</label><br/>
	<input size="40" id="from" name="from" value="{$from|escape:'html'}"/><span class="formerror">{$from_error}</span>
    
    <br /><br />
    
    <label for="subject">Betreff</label><br/>
	<input size="40" id="subject" name="subject" value="{$subject|escape:'html'}"/>
	<br /><br />
    
    <label for="msg">Nachricht</label><br/>
	<textarea id="msg" name="msg" rows="10" cols="50">{$msg|escape:'html'}</textarea>
    	<br /><span class="formerror">{$msg_error}</span> 
    <br />
        <small>Wenn es um ein Bild geht, bitten wir darum, uns mitzuteilen auf welches Sie sich beziehen!</small>
    
    <input type="submit" name="send" value="Send"/></p>
    </form>
{/if} 
{/dynamic}    
{include file="_std_end.tpl"}
