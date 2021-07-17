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

        {if $errors.title}</div>{/if}
</div>

<hr>

<div class="field">
        {if $errors.quantity}<div class="formerror"><p class="error">{$errors.quantity}</p>{/if}

        <label for="title">Quantity Required:</label>
        <input type="number" name="quantity" value="{$calendar.quantity|escape:"html"}" style="font-size:1.3em" size=2 min="1" max="255" step="1"/>

        {if $errors.title}</div>{/if}
</div>

<hr>


<div class="field">
        {if $errors.delivery_name}<div class="formerror"><p class="error">{$errors.delivery_name}</p>{/if}

        <label for="delivery_name">Delivery Name:</label>
        <input type="text" name="delivery_name" value="{$calendar.delivery_name|escape:"html"}" maxlength="64" size="47" required/>

        {if $errors.delivery_name}</div>{/if}
</div>

<div class="field">
        {if $errors.delivery_address}<div class="formerror"><p class="error">{$errors.delivery_address}</p>{/if}

        <label for="delivery_address">Delivery Address:</label>
        <textarea name="delivery_address" rows=3 cols=80 required>{$calendar.delivery_address|escape:"html"}</textarea>

	<div class="fieldnotes">UK Addresses only. Please rememeber to include your Postcode!</div>

        {if $errors.delivery_address}</div>{/if}
</div>


{/dynamic}



</fieldset>


<input type=submit name="proceed" value="Proceed to Payment"> (not yet available in demo!)


</form>
{include file="_std_end.tpl"}


