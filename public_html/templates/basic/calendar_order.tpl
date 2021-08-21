{include file="_std_begin.tpl"}


<h2>Step 3. Order Geograph Calendar</h2>

<form method=post>

<fieldset style="background-color:#eee">
	<legend>Edit Calendar</legend>

{dynamic}
<div class="field">
        {if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

        <label for="title">Title:</label>
        <input type="text" name="calendar_title" value="{$calendar.title|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

	<div class="fieldnotes">Just for your reference, not printed on calendar!</div>

        {if $errors.title}</div>{/if}
</div>

<hr>

<div class="field">
        <label for="images">Selected Images:</label>

	<br>
	{foreach from=$images key=index item=image}
		{$image->getThumbnail(120,120)}
	{/foreach}

	<div class="fieldnotes">To change images got back to Step 2</div>
</div>

<hr>

<div class="field">
        {if $errors.quantity}<div class="formerror"><p class="error">{$errors.quantity}</p>{/if}

        <label for="title">Quantity Required:</label>
        <input type="number" name="quantity" value="{$calendar.quantity|escape:"html"}" style="font-size:1.3em" size=2 min="1" max="255" step="1"/>*

        {if $errors.quantity}</div>{/if}
</div>

<hr>


<div class="field">
        {if $errors.delivery_name}<div class="formerror"><p class="error">{$errors.delivery_name}</p>{/if}

        <label for="delivery_name">Delivery Name:</label>
        <input type="text" name="delivery_name" value="{$calendar.delivery_name|escape:"html"}" maxlength="64" size="47" required/>*

        {if $errors.delivery_name}</div>{/if}
</div>

<div class="field">
        {if $errors.delivery_address}<div class="formerror"><p class="error">{$errors.delivery_address}</p>{/if}

        <label for="delivery_address">Delivery Address: (*Required)</label>

	<table>
		<tr><td>Line 1:</td><td><input type="text" name="delivery_line1" value="{$calendar.delivery_line1|escape:"html"}" maxlength="128" size="47" required/>*
		<tr><td>Line 2:</td><td><input type="text" name="delivery_line2" value="{$calendar.delivery_line2|escape:"html"}" maxlength="128" size="47"/>
		<tr><td>City:</td><td><input type="text" name="delivery_line3" value="{$calendar.delivery_line3|escape:"html"}" maxlength="128" size="47" required/>*
		<tr><td>County:</td><td><input type="text" name="delivery_line4" value="{$calendar.delivery_line4|escape:"html"}" maxlength="128" size="47"/>
		<tr><td>Postcode:</td><td><input type="text" name="delivery_postcode" value="{$calendar.delivery_postcode|escape:"html"}" maxlength="16" size="10" required/>*
	</table>

	<div class="fieldnotes">UK Addresses only</div>

        {if $errors.delivery_address}</div>{/if}
</div>

</fieldset>

<br><br>

{if $calendar.paid > '2'}
	<input type=submit value="Save Changes">
{else}
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">

(Payment is processed by PayPal. Paypal account not required. Can pay with normal Credit/Debit Cards as a Guest)
{/if}


{/dynamic}

</form>
{include file="_std_end.tpl"}


