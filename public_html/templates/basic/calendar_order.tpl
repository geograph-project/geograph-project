{include file="_std_begin.tpl"}

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<h2>Step 3. Order Geograph Calendar</h2>

<p>{newwin href="/calendar/help.php" text="Open Help Page"} (in new window)</p>

<form method=post>

<fieldset style="background-color:#eee">
	<legend>Edit Calendar</legend>

{dynamic}
<div class="field">
        {if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

        <label for="title">Title:</label>
        <input type="text" name="calendar_title" id="calendar_title" value="{$calendar.title|escape:"html"}" style="font-size:1.1em" maxlength="64" size="47"/>

	<div class="fieldnotes">Optional title. {if !$images}For your reference only{/if}</div>

        {if $errors.title}</div>{/if}

	<script>{literal}
	$(function() {
		$('input#calendar_title').on('keyup input change blur paste', function() {
			$('span#previewtitle').text(this.value);
		});
	});
	{/literal}</script>
</div>

{if $images}
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
	<br>
	{foreach from=$images key=index item=image}
		{if $image->sort_order == 0}
			Cover Preview (only a low resolution preview to show approximate location of text, the final version will be done by hand)<br><br>
			<div style='position:relative;width:640px;height:453px; color: #000066;
background-image:url("{$image->_getFullPath(true,true)}");background-size:cover;
--background-image: url("https://media.geograph.org.uk/files/c81e728d9d4c2f636f067f89cc14862c/Calendar2024_cover1.jpg")
'>
				<img style="position:absolute; top:20px; left:7px; width:177px; height:49px; border:6px solid #000066;"
					src="https://s1.geograph.org.uk/templates/basic/img/xmaslogo.gif">
				<span id="previewtitle" style="position:absolute; top:28px; left:200px; font-size:20px;font-weight:bold;width:280px;text-align:center; font-style:italic">{$calendar.title|escape:"html"|default:"Your Title Here"}</span>
				<span style="position:absolute; top:60px; left:200px; font-size:16px;font-weight:bold;width:280px;text-align:center">Photography by {$cover_name|escape:'html'}</span>
				<span style="position:absolute; top:23px; left:494px; font-size:27px;font-weight:bold;text-align:right">Calendar<br>{$year}</span>
			
			</div>
			Selected Images:
		{else}
			{$image->getThumbnail(120,120)}
		{/if}
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
	(minimum order is 2 calendars, can be split over multiple orders)

        {if $errors.quantity}</div>{/if}
</div>

{/if}

<hr>

<div class="field">
        {if $errors.delivery_name}<div class="formerror"><p class="error">{$errors.delivery_name}</p>{/if}

        <label for="delivery_name">Delivery Name:</label>
        <input type="text" name="delivery_name" value="{$calendar.delivery_name|escape:"html"}" maxlength="64" size="47" required/>*

        {if $errors.delivery_name}</div>{/if}
</div>

{if !$user->user_id}
<div class="field">
        {if $errors.delivery_email}<div class="formerror"><p class="error">{$errors.delivery_email}</p>{/if}

        <label for="delivery_email">Email Address:</label>
        <input type="email" name="delivery_email" value="{$calendar.delivery_email|escape:"html"}" maxlength="128" size="47" required/>*

        {if $errors.delivery_email}</div>{/if}
</div>
{/if}

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


