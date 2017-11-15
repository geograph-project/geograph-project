{assign var="page_title" value="Kontakt"}
{include file="_std_begin.tpl"}

{dynamic}
{if $message_sent}
	<h3>Danke f�r die Nachricht an das Geograph-�sterreich-Team.</h3>
	<p>Die Nachricht wurde versandt, wir versuchen bald zu antworten.</p>
{else}
    <h2>Kontakt-Formular</h2>
 
 	<div style="background-color:#eeeeee;padding:2px; text-align:center">
	Das Geograph-Projekt hat das Ziel, geographisch repr�sentative Photos f�r jeden Quadratkilometer �sterreichs zu sammeln.
	</div>
 
	<p>Wenn Sie ein Anliegen haben, k�nnen Sie sich �ber dieses Formular an uns wenden. Wir versuchen, innerhalb von 24 Stunden zu antworten.</p>

	<p><b>Bitte beachten Sie, dass Mitteilungen �ber dieses Formular an mehrere Personen geschickt werden.
	Wenn Sie eine bestimmte Person oder einen bestimmten Fotografen kontaktieren wollen, sind dazu
	die Mitteilungs-Links auf den Profil-Seiten oder den Foto-Seiten besser geeignet!</b></p>
 
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
