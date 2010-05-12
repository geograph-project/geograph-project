{assign var="page_title" value="Cooperative Searching"}
{include file="_std_begin.tpl"}


	<h2><a href="/finder/">Finder</a> :: Cooperative Searching <small>(<a href="{$script_name}">more...</a>)</small></h2>

{dynamic}
	{if $message}
		<p style="color:red">{$message|escape:"html"}</p>
	{/if}
{/dynamic}

	<p>We have recently introduced a special feature on the site, 'Cooperative Search'. Users create searches, which are recorded; and then later other users suggest images that match the search. You can <a href="{$script_name}">read more about it here</a>, and view previous searches.</p>

	<ul><li>Images may be ones other users happen to know about, have found themselves via the search, or even having found the search request, gone out to photograph especially. </li></ul>


	{dynamic}{if $user->registered}
		<form method="post" action="{$script_name}?create" class="simpleform" style="width:100%">
			
			<fieldset style="width:100%">
				<legend>Create new Search</legend>
				<div class="interestBox" style="text-align:center;font-size:0.8em">
					Psst! You have tried looking for this yourself in the normal <a href="/search.php">image search</a> haven't you?
				</div><br/><br/>
				{$errors}
				
				<span style="font-size:1.3em">I am searching for photos</span>			

				<div class="field" style="margin-top:4px">
					{if $errors.q}<div class="formerror"><p class="error">{$errors.q}</p>{/if}

					<label for="q" style="text-align:right"><span style="font-size:1.8em">of:</span></label>
					<input id="q" type="text" name="q" value="{$item.q|escape:"html"}" size="30" style="font-size:1.3em" maxlength="64"/>

					<div class="fieldnotes">enter what you are searching for here</div>

					{if $errors.q}</div>{/if}
				</div>	

				<div class="field">
					{if $errors.location}<div class="formerror"><p class="error">{$errors.location}</p>{/if}

					<label for="location" style="text-align:right"><span style="font-size:1.8em">Near:</span></label>
					<input id="location" type="text" name="location" value="{$item.location|escape:"html"}" size="30" style="font-size:1.3em" maxlength="64"/>

					<div class="fieldnotes">(optional) for example a town, village or other locality</div>

					{if $errors.location}</div>{/if}
				</div>	

				<div class="field">
					{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference|escape:"html"}</p>{/if}

					<label for="grid_reference" style="text-align:right">Grid Reference:</label>
					<input type="text" id="grid_reference" name="grid_reference" value="{$item.grid_reference|escape:"html"}" size="6" maxlength="10"/> (if known)

					<div class="fieldnotes">(optional) providing a grid-reference will make it easier for others to browse the searches</div>

					{if $errors.grid_reference}</div>{/if}
				</div>	

				<div class="field">
					{if $errors.title}<div class="formerror"><p class="error">{$errors.title}</p>{/if}

					<label for="title" style="text-align:right">Title:</label>
					<input type="text" id="title" name="title" value="{$item.title|escape:"html"}" size="30" maxlength="140"/>

					<div class="fieldnotes">(optional) short descriptive title to help others find this search, shown in the list of searches</div>

					{if $errors.title}</div>{/if}
				</div>	

				<div class="field">
					{if $errors.comment}<div class="formerror"><p class="error">{$errors.comment}</p>{/if}

					<label for="comment" style="text-align:right">Comment:</label>
					<textarea name="comment" id="comment" rows="4" cols="50"
						onKeyDown="textCounter(this,this.form.comLen,255)"
						onKeyUp="textCounter(this,this.form.comLen,255)">{$item.comment|escape:"html"}</textarea>

					<div class="fieldnotes">(optional) extra information that may help people find this location either in photos or in the real world. (<input readonly type="text" name="comLen" size="3" value="255" style="border:0;text-align:right"> characters left)</div>

					{if $errors.comment}</div>{/if}
				</div>	
			
				<div class="field">
					{if $errors.notify}<div class="formerror"><p class="error">{$errors.notify}</p>{/if}

					<label for="notify" style="text-align:right">Notify me:</label>
					<input type="checkbox" name="notify" id="notify" value="1" checked>

					<div class="fieldnotes">Email me when someone 'Finds' an image (not currently functional - no emails will be sent yet)</div>

					{if $errors.notify}</div>{/if}
				</div>	
			
				<ul><li>Remember, results aren't immediate, and can take weeks. If the location is not photographed yet it could take even longer than that.</li></ul>
				
				<div class="field">
					<label for="searchgo">&nbsp;</label>
					<input id="searchgo" type="submit" name="create" value="Create Search..." style="font-size:1.3em"/>
				</div>
				
			</fieldset>	
				
		</form>
		Note: The search is recorded with your profile name, and shown to others. All searches are public - so keep it clean
	{else}
		<div style="position:relative;" class="interestBox">
		During this experimental phrase creating new searches is only available to <a href="/register.php" target="_blank">registered site users</a>. <br/><br/>
		
		Registration is quick and free! Once registered <a href="{$script_name}?create&amp;login">login</a>.<br/><br/>
		
		You can however still <a href="{$script_name}">view previous searches</a>.
		
		</div>
	{/if}{/dynamic}



{literal}<script type="text/javascript"><!-- Begin
function textCounter(field,cntfield,maxlimit) {
	if (field.value.length > maxlimit) // if too long...trim it!
		field.value = field.value.substring(0, maxlimit);
		// otherwise, update 'characters left' counter
	else
		cntfield.value = maxlimit - field.value.length;
}
//  End --></script>{/literal}

{include file="_std_end.tpl"}
