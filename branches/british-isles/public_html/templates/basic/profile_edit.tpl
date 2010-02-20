{include file="_std_begin.tpl"}
{dynamic}

<form class="simpleform" method="post" action="/profile.php">
<input type="hidden" name="edit" value="1"/>

{if $errors.general}
<div class="formerror">{$errors.general}</div>
{/if}

 
<fieldset>
<legend>Basic Information</legend>

<div class="field">
	{if $errors.realname}<div class="formerror"><p class="error">{$errors.realname}</p>{/if}
	 
	<label for="realname">Real Name:</label>
	<input type="text" id="realname" name="realname" value="{$profile->realname|escape:'html'}"/>
	
	<div class="fieldnotes">Your real name is used to give you attribution
	whenever your photographs are displayed.</div>
	
	{if $errors.realname}</div>{/if}
</div>

 
<div class="field">

	{if $errors.nickname}<div class="formerror"><p class="error">{$errors.nickname}</p>{/if}
	
	<label for="nickname">Nick Name:</label>
	<input type="text" id="nickname" name="nickname" value="{$profile->nickname|escape:'html'}"/>
	
	<div class="fieldnotes">Your nickname can be used to login and is also 
	used to identify you on the forums.</div>
	
	{if $errors.nickname}</div>{/if}
</div>
 




<div class="field">
 
	{if $errors.email}<div class="formerror"><p class="error">{$errors.email}</p>{/if}
	
	<label for="email">Email:</label>
	<input type="text" id="email" name="email" value="{$profile->email|escape:'html'}" size="35"/>
	<script type="text/javascript">{literal}
		// really ugly 'fix' for http://code.google.com/p/chromium/issues/detail?id=1854
		// the last text box before the first password field are assumed to be a username,
		//   but the saved username COULD be a nickname OR email...
		// ... so we take away those text boxes!
		if (navigator && navigator.userAgent && navigator.userAgent.search(/Chrome/) != -1) {
			document.getElementById('realname').disabled = true;
			document.getElementById('nickname').disabled = true;
			document.getElementById('email').disabled = true;
			AttachEvent(window,'load',reEnableTextBoxes1,false);
		}
		function reEnableTextBoxes1() {
			//autofill happens jsut after 'onload'
			setTimeout("reEnableTextBoxes2()",400);
		} 
		function reEnableTextBoxes2() {
			document.getElementById('realname').disabled = false;
			document.getElementById('nickname').disabled = false;
			document.getElementById('email').disabled = false;
		}
	{/literal}</script>
	  <div class="fieldnotes">We need your email address to
	  keep you notified about any changes requested or made to your
	  submissions. It also allows anyone who is interested in your photos
	  to contact you, but you can control whether you make your address
	  public or not...</div>
	
	{if $errors.email}</div>{/if}
	
    <fieldset>
    <legend>Email privacy</legend>
    
	    <input {if $profile->public_email eq 0}checked{/if} type="radio" name="public_email" id="public_email_no" value="0">
	    <label for="public_email_no">Hide my email address 
	    (people can still contact you through the site, but will not discover
	    your email address unless you reply).
	    </label>
	    
	    <br/>
	    
	    <input {if $profile->public_email eq 1}checked{/if} type="radio" name="public_email" id="public_email_yes" value="1">
	    <label for="public_email_yes">Show my email address. 
	    </label>
   
    
    </fieldset>
    
  

</div>

<div class="field">

	<label for="gravatar">Gravatar:</label>
	<img src="http://www.gravatar.com/avatar/{$profile->md5_email}?r=G&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536%3Fs=30&amp;s=50" align="left" alt="{$profile->realname|escape:'html'}'s Gravatar" style="padding-right:10px"/>
	
	<div class="fieldnotes">To setup or change your Avatar image, goto {external href="http://www.gravatar.com" text="gravatar.com" target="_blank"} and use the same email address set above.</div>

</div>

</fieldset>

<fieldset>
<legend>Change Password</legend>
<p style="color:green">You only need to fill out this section if you wish to change your password</p>

<div class="field">
	{if $errors.oldpassword}<div class="formerror"><p class="error">{$errors.oldpassword}</p>{/if}
		<label for="oldpassword">Current password:</label>
		<input id="oldpassword" name="oldpassword" type="password" value="{$profile->oldpassword|escape:'html'}" size="35"/>
		<div class="fieldnotes">Please enter your current password.</div>
	{if $errors.oldpassword}</div>{/if}
</div>
<div class="field">
	{if $errors.password1}<div class="formerror"><p class="error">{$errors.password1}</p>{/if}
		<label for="password1">New password:</label>
		<input id="password1" name="password1" type="password" value="{$profile->password1|escape:'html'}" size="35"/>
		<div class="fieldnotes">Enter your new password here. Leave empty if you want to keep your old password.</div>
	{if $errors.password1}</div>{/if}
</div>
<div class="field">
	{if $errors.password2}<div class="formerror"><p class="error">{$errors.password2}</p>{/if}
		<label for="password2">Confirm the new password:</label>
		<input id="password2" name="password2" type="password" value="{$profile->password2|escape:'html'}" size="35"/>
		<div class="fieldnotes">Enter your new password here in order to avoid spelling errors.</div>
	{if $errors.password2}</div>{/if}
</div>

</fieldset>

<fieldset>
<legend>More about you</legend>


<div class="field">
	
	{if $errors.website}<div class="formerror"><p class="error">{$errors.website}</p>{/if}
	
	
	<label for="website" class="nowrap">Your website:</label>
	<input type="text" id="website" name="website" value="{$profile->website|escape:'html'}" size="50"/>

	<div class="fieldnotes">If you wish, tell us the URL of your personal 
	website or blog to link from your profile page. </div>
	
	
	{if $errors.website}</div>{/if}
</div>


<div class="field">

	{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}
	
	<label for="grid_reference">Home grid square:</label>
	<input type="text" id="grid_reference" name="grid_reference" value="{$profile->grid_reference|escape:'html'}" size="6" />
	
	<div class="fieldnotes">If you wish, tell us the OS grid reference of your home.</div>

	{if $errors.grid_reference}</div>{/if}
</div>





<div class="field">

	<label for="about_yourself">About Yourself:</label>
	
	 
	<textarea name="about_yourself" id="about_yourself" rows="10" cols="85">{$profile->about_yourself|escape:'html'}</textarea>

	<div class="fieldnotes"><span style="color:red">Note: HTML code will be removed, 
	however basic URLs will be autolinked.</span><br/>
	The main profile page will only show the first 300 characters, (or use <tt>[--more--]</tt> to specify the break point) with a link to show the full details.<br/>
	TIP: use <span style="color:blue">[[TQ7506]]</span> or 
	<span style="color:blue">[[5463]]</span> to link to a Grid Square or another Image.
	</div>


</div>


<div class="field">


	<label for="age_group">Age Group:</label>

	<select name="age_group" id="age_group"> 
	<option value=""></option>
	<option value="11" {if $profile->age_group == 11} selected="selected"{/if}>11 or under</option>
	<option value="18" {if $profile->age_group == 18} selected="selected"{/if}>12-18</option>
	<option value="25" {if $profile->age_group == 25} selected="selected"{/if}>19-25</option>
	<option value="50" {if $profile->age_group == 50} selected="selected"{/if}>26-50</option>
	<option value="70" {if $profile->age_group == 70} selected="selected"{/if}>51-70</option>
	<option value="90" {if $profile->age_group == 90} selected="selected"{/if}>71+</option>
	</select>
	
	<div class="fieldnotes">This information is not made publicly visible, but
	it provides useful demographic information to help us plan future features.</div>
	
</div> 


</fieldset>
<a name="prefs"></a>
<fieldset>
<legend>Site Preferences</legend>
 

<div class="field"> 
	<label for="upload_size" class="nowrap">Default Upload Size</label>
	
	<select name="upload_size" id="upload_size"> 
		<option value="640" {if $profile->upload_size == 640} selected="selected"{/if}>640 x 640 (the original size)</a>
		<option value="800" {if $profile->upload_size == 800} selected="selected"{/if}>800 x 800</a>
		<option value="1024" {if $profile->upload_size == 1024} selected="selected"{/if}>1024 x 1024 (Recommended)</a>
		<option value="1600" {if $profile->upload_size == 1600} selected="selected"{/if}>1600 x 1600</a>
		<option value="65536" {if $profile->upload_size > 65530} selected="selected"{/if}>As uploaded</a>
	</select>

	 
	<div class="fieldnotes">Choose the default size you wish to preserve. You can still change it per upload, just chooses the option selected by default.</div>
</div>


<div class="field"> 
	<label for="message_sig" class="nowrap">Message Signature</label>
	
	<textarea name="message_sig" id="message_sig" rows="4" cols="60">{$profile->message_sig|escape:'html'}</textarea>

	 
	<div class="fieldnotes">Automatically include this text in messages sent though the site. <br/>
	(250 chars max) 
	<input type="button" value="Use Suggested Text" onclick="this.form.message_sig.value='-- '+this.form.realname.value+' http://{$http_host}/profile/{$user->user_id}'"/></div>
</div>


<div class="field"> 
	<label for="expand_about" class="nowrap">Expand Profile Pages</label>
	
	<select name="expand_about" id="expand_about"> 
	<option value="0" {if $profile->expand_about == 0} selected="selected"{/if}>Show variable length preview</option>
	<option value="1" {if $profile->expand_about == 1} selected="selected"{/if}>Always show full Expanded version</option>
	<option value="2" {if $profile->expand_about == 2} selected="selected"{/if}>Always show short preview, with click to expand</option>
	</select>
	
	<div class="fieldnotes">How the 'About Me' box displays when viewing contributor profile pages.</div>
</div>


<div class="field"> 
	<label for="ticket_public" class="nowrap">Ticket Anonymity</label>
	
	<select name="ticket_public" id="ticket_public">
		<option value="no">Do not disclose my name</option>
		<option value="owner" {if $profile->ticket_public eq 'owner'} selected{/if}>Show my name to the photo owner</option>
		<option value="everyone" {if $profile->ticket_public eq 'everyone'} selected{/if}>Show my name against the ticket</option>
	</select>
	 
	<div class="fieldnotes">Change how your name is disclosed on tickets your create from now on.</div>
</div>


<div class="field"> 
	<label for="ticket_public_change" class="nowrap">Anonymity for previous tickets</label>
	<br/>
	<select name="ticket_public_change" id="ticket_public_change" style="margin-left:10em;">
		<option value="">- no change - leave previous tickets as is</option>
		<option value="no">Do not disclose my name</option>
		<option value="owner">Show my name to the photo owner</option>
		<option value="everyone">Show my name against the ticket</option>
	</select>
	 
	<div class="fieldnotes">Optionally use this box to change all your previous tickets to a new setting.</div>
</div>


<div class="field"> 
	<label for="ticket_option" class="nowrap">Trouble Ticket Emails</label>
	
	<select name="ticket_option" id="ticket_option" size="1"> 
		{html_options options=$ticket_options selected=$profile->ticket_option}
	</select>
	 
	<div class="fieldnotes">Allows opting out of receiving initial notification of certain suggestions. Note you however receive follow up comments and notification of the closure - in-case there is information needed by a moderator.</div>
</div>


<div class="field"> 
	<label for="sortBy" class="nowrap">Forum Sort Order</label>
	
	<select name="sortBy" id="sortBy" size="1">
	 	<option value="0">Latest Replies
	 	<option value="1" {if $profile->getForumSortOrder() eq 1}selected{/if}>New Topics</option>
	 </select>
	 
	 <div class="fieldnotes">The default order you will see recent discussions.</div>
</div>

<div class="field"> 
	<label for="search_results" class="nowrap">Search Results</label>
	<select name="search_results" id="search_results" style="text-align:right" size="1"> 
		{html_options values=$pagesizes output=$pagesizes selected=$profile->search_results}
	</select> per page
	
	<div class="fieldnotes">Default number of search results per page.</div>
</div>
  
  
<div class="field"> 
  
	<label for="slideshow_delay" class="nowrap">Slide Show Delay</label>
	
	<select name="slideshow_delay" id="slideshow_delay" style="text-align:right" size="1">
		{html_options values=$delays output=$delays selected=$profile->slideshow_delay}
	</select> seconds
	
	<div class="fieldnotes">Number of seconds slides are shown for.</div>  
</div>


<div class="field"> 
  
	<label for="use_autocomplete" class="nowrap">Use Auto Complete</label>
	
	<input {if $profile->use_autocomplete eq 1}checked{/if} type="checkbox" name="use_autocomplete" id="use_autocomplete" value="1">
	
	<div class="fieldnotes">Changes the category dropdown to a autocomplete text-field - EXPERIMENTAL</div>  
</div>

</fieldset>



 	<input type="submit" name="savechanges" value="Save Changes"/>
 	<input type="submit" name="cancel" value="Cancel"/>

{if ($profile->stats.squares gt 20) || ($profile->rights && $profile->rights ne 'basic')}
	<br/><br/><br/><br/><br/><br/>
	<fieldset>
	<legend>User Roles</legend>


	<div class="field"> 

		<label for="moderator" class="nowrap">Moderator</label>
		{if strpos($profile->rights,'moderator') > 0}
			<input type="button" value="Relinquish moderator rights" onclick="location.href = '/admin/moderation.php?relinquish=1';"/>

			<div class="fieldnotes">If you are no longer able to help out with moderation then click the button above. (you will have to reapply)</div>  
		{else}
			{if strpos($profile->rights,'traineemod') > 0}
				<input type="button" value="Visit Demo Moderation Page" onclick="location.href = '/admin/moderation.php?apply=1';"/>
			{else}
				<input type="button" value="Apply to become a moderator" onclick="location.href = '/admin/moderation.php?apply=1';"/>
			{/if}

			<div class="fieldnotes">
			{if strpos($profile->rights,'traineemod') > 0}
				or <input type="button" value="Cancel Application" onclick="location.href = '/admin/moderation.php?relinqush=1';"/><br/><br/>
			{/if}

			If you have an interest in helping out with moderation, then click the button above to try a dummy moderation run. There is no commitment to complete the whole application.</div>  
		{/if}
	</div>

	{if strpos($profile->rights,'ticketmod') > 0}
	<div class="field"> 

		<label for="moderator" class="nowrap">Tickets</label>
			<input type="button" value="Relinqush ticket moderator rights" onclick="location.href = '/admin/tickets.php?relinqush=1';"/>

	</div>
	{/if}

	</fieldset>
{/if}


 </form>	

{/dynamic}    
{include file="_std_end.tpl"}
