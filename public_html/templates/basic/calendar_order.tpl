{include file="_std_begin.tpl"}


<h2>Step 3. Order Geograph Calendar</h2>

<p>{newwin href="/calendar/help.php" text="Open Help Page"} (in new window)</p>

<form method=post>

<fieldset style="background-color:#eee">
	<legend>Edit Calendar</legend>

{dynamic}
<div class="field">
        {if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

        <label for="title">Title:</label>
        <input type="text" name="calendar_title" value="{$calendar.title|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

	<div class="fieldnotes">Optional title. <b>If entered WILL appear on the cover page of calendar!</b></div>

        {if $errors.title}</div>{/if}
</div>

<br>

<div class="field">
        {if $errors.print_title}<div class="formerror"><p class="error">{$errors.print_title}</p>{/if}

        <label for="print_title">Show Title on Cover:</label>
        <input type="checkbox" name="print_title" value="1" {if $calendar.print_title} checked{/if}>

	<div class="fieldnotes"> Show your custom title (above) on front cover - otherwise will just be for reference purposes here</div>

        {if $errors.print_title}</div>{/if}
</div>

<br>

<div class="field">
        {if $errors.background}<div class="formerror"><p class="error">{$errors.background}</p>{/if}

        <label for="show_id">Black Background:</label>
        <input type="checkbox" name="background" id="background" value="1" {if $calendar.background} checked{/if} onclick="setBackAll(this)">

	<div class="fieldnotes">Use a black background around photos (optional) - otherwise will use white+shadow frame</div>

        {if $errors.show_id}</div>{/if}
</div>

<hr>

<div class="field">
        <label for="images">Selected Images:</label>

	<br>
	{foreach from=$images key=index item=image}
		{$image->getThumbnail(120,120)}
		{if $image->sort_order == 0 || $image->sort_order == 4 || $image->sort_order == 8}
			<br/>
		{/if}
	{/foreach}

	<div class="fieldnotes">To change images got back to <a href="edit.php?id={$calendar.calendar_id}">Step 2</a></div>
</div>

<hr>

<div class="field">
        {if $errors.quantity}<div class="formerror"><p class="error">{$errors.quantity}</p>{/if}

        <label for="title">Quantity Required:</label>
        <input type="number" name="quantity" value="{$calendar.quantity|escape:"html"}" style="font-size:1.3em" size=2 min="1" max="255" step="1"/>*
	(miniumum order is 2 calendars, can be split over multiple orders)

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

	<div class="fieldnotes">UK Addresses only (Note, the Calendar(s) will be delivered to the address <b>above</b>, not the delivery address set in Paypal)</div>

        {if $errors.delivery_address}</div>{/if}
</div>

</fieldset>

<br><br>

{if $calendar.paid > '2'}
	<input type=submit value="Save Changes">
{else}
	<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">

	(Payment is processed by PayPal. PayPal Account not required. Can pay with normal Credit/Debit Cards as a Guest)
{/if}


{/dynamic}

</form>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>{literal}
//prevent enter submitting the form (which will be an arbitary move button!) 
$('form input').keydown(function (e) {
    if (e.keyCode == 13) {
        e.preventDefault();
        return false;
    }
});
</script>
{/literal}


{include file="_std_end.tpl"}


