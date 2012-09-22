{assign var="page_title" value="Elektronische Postkarte senden"}
{include file="_std_begin.tpl"}
{dynamic}

{if $image && $image->isValid()}
    <h2>Elektronische Postkarte</h2>
    {if $throttle}
	<h2>Entschuldigung</h2>
	<p>Momentan können keine Karten versandt werden &ndash; der Server ist überlastet.</p>
    {else}
	{if $sent}
		<h3>Vielen Dank, die Karte wurde versandt.</h3>
		<p>Zum <a href="/photo/{$image->gridimage_id}">Bild</a> zurückkehren.</p>
	{else}
		<form method="post" action="/ecard.php?image={$image->gridimage_id}">
		<input type="hidden" name="image" value="{$image->gridimage_id|escape:'html'}">

		<div style="position:relative; float:right; width:223px; background-color:#eeeeee; padding: 10px; text-align:center">
			<b>Gewähltes Bild</b> (wird in voller Größe verschickt)
			<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a>
				 <div style="font-size:0.7em">
					  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
					  von <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
					  für Planquadrat <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
				</div>
			</div>
		</div>

		<p><label for="from_name">Eigener Name</label><br />
		<input type="text" name="from_name" id="from_name" value="{$from_name|escape:'html'}"/>
		<span class="formerror">{$errors.from_name}</span>

		<br/><br/>
		<label for="from_email">Eigene Mail-Adresse (Antwortadresse)</label><br />
		<input type="text" name="from_email" id="from_email" value="{$from_email|escape:'html'}" size="40"/>
		<span class="formerror">{$errors.from_email}</span>

		<br/><br/>
		<label for="to_name">Name des Empfängers</label><br />
		<input type="text" name="to_name" id="to_name" value="{$to_name|escape:'html'}"/>
		<span class="formerror">{$errors.to_name}</span>

		<br/><br/>
		<label for="to_email">Mail-Adresse des Empfängers</label><br />
		<input type="text" name="to_email" id="to_email" value="{$to_email|escape:'html'}" size="40"/>
		<span class="formerror">{$errors.to_email}</span>

		<br/><br/>
		<label for="msg">Nachricht</label><br style="clear:both"/>
		<textarea rows="10" cols="60" name="msg" id="msg">{$msg|escape:'html'}</textarea>
		<br/>
		<span class="formerror">{$errors.msg}</span>

		<br/>
		<input type="submit" name="preview" value="Vorschau">
		<input type="submit" name="send" value="Abschicken">
		</p>
		</form>
	{/if}
    {/if}
{else}
	<h2>Entschuldigung</h2>
	<p>Zum Versenden einer Karte muss ein gültiges Bild gewählt werden.</p>
{/if}

{/dynamic}
{include file="_std_end.tpl"}
