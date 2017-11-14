{assign var="page_title" value="Send e-Card"}
{include file="_std_begin.tpl"}
{dynamic}

{if $image && $image->isValid()}
    <h2>Send e-Card</h2>
    {if $throttle}
	<h2>Sorry</h2>
	<p>Unable to send card - this service is currently busy</p>
    {else}
	{if $sent}
		<h3>Thank you - your card has been sent</h3>
		<p>Return to the <a href="/photo/{$image->gridimage_id}">Image</a> Page.</p>
	{else}
		<form method="post" action="/ecard.php?image={$image->gridimage_id}">
		<input type="hidden" name="image" value="{$image->gridimage_id|escape:'html'}">

		<div style="position:relative; float:right; width:220px; background-color:#eeeeee; padding: 10px; text-align:center">
			<b>Chosen Image</b> (Sent full size)
			<div class="img-shadow"><a href="/photo/{$image->gridimage_id}" target="_blank">{$image->getThumbnail(213,160)}</a>
				 <div style="font-size:0.7em">
					  <a title="view full size image" href="/photo/{$image->gridimage_id}">{$image->title|escape:'html'}</a>
					  by <a title="view user profile" href="{$image->profile_link}">{$image->realname}</a>
					  for square <a title="view page for {$image->grid_reference}" href="/gridref/{$image->grid_reference}">{$image->grid_reference}</a>
				</div>
			</div>
		</div>

		<p><label for="from_name">Your Name</label><br />
		<input type="text" name="from_name" id="from_name" value="{$from_name|escape:'html'}"/>
		<span class="formerror">{$errors.from_name}</span>

		<br/><br/>
		<label for="from_email">Your Email (Reply Address)</label><br />
		<input type="text" name="from_email" id="from_email" value="{$from_email|escape:'html'}" size="40"/>
		<span class="formerror">{$errors.from_email}</span>

		<br/><br/>
		<label for="to_name">Friend's Name</label><br />
		<input type="text" name="to_name" id="to_name" value="{$to_name|escape:'html'}"/>
		<span class="formerror">{$errors.to_name}</span>

		<br/><br/>
		<label for="to_email">Friend's Email</label><br />
		<input type="text" name="to_email" id="to_email" value="{$to_email|escape:'html'}" size="40"/>
		<span class="formerror">{$errors.to_email}</span>

		<br/><br/>
		<label for="msg">Message</label><br style="clear:both"/>
		<textarea rows="10" cols="60" name="msg" id="msg">{$msg|escape:'html'}</textarea>
		<br/>
		<span class="formerror">{$errors.msg}</span>

		<br/>
		<input type="submit" name="preview" value="Preview">
		<input type="submit" name="send" value="Send">
		</p>
		</form>
	{/if}
    {/if}
{else}
	<h2>Sorry</h2>
	<p>Unable to send card - you must select a valid image</p>
{/if}

{/dynamic}
{include file="_std_end.tpl"}
