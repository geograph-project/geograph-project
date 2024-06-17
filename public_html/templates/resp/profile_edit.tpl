{include file="_std_begin.tpl"}

<style>
{literal}
#maincontent *{
	box-sizing:border-box;
}
div.sticky {
  position: -webkit-sticky;
  position: sticky;
  top: 0;
  background-color: white;
  padding: 10px;
  #border:2px solid red;
  #max-width:600px;
  background:cornsilk;
  text-align:center;
  margin: auto;
}
input[type=text] {
  width: 100%;
  margin: 8px 0;
}
input[type=password] {
  width: 100%;
  margin: 8px 0;
}
textarea {
  width: 100%;
  height: 150px;
}
select {
  margin: 8px 0;
  content:"\a";
  white-space: pre;
}
fieldset {
  border: 0px;
}
label {
  font-weight: bold;
  font-size: 1em;
}
label.option {
  font-weight: normal;
}
label:after {
  content:"\a";
  white-space: pre;
}
div.fieldnotes {
  background-color:azure;
  font-size: 0.8em;
  font-family: Arial, Helvetica, sans-serif;
  padding: 2px;
  text-align: center;
  border-radius: 3px;
}
legend {
display:none;
}
.relinquish a:link, .relinquish a:visited {
  background-color: red;
  color: white;
  padding: 5px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
}
{/literal}
</style>

{dynamic}



<div class="tabHolder" style="text-align:right">
        <a href="/profile.php">Back to Profile</a>
        <span class="tabSelected">General Settings</span>
        <a href="/profile.php?notifications=1" class="tab">Email Notifications</a>
        <a href="/choose-search.php" class="tab">Site Search Engine</a>
        <a href="/choose-preview.php" class="tab">Preview Method</a>
        <a href="/switch_tagger.php" class="tab">Tagging Box</a>
</div>
<div style="position:relative;" class="interestBox">
	<h2 style="margin:0">Edit your Profile Settings</h2>
</div>

<br/><br/>


<form method="post" action="/profile.php">
<input type="hidden" name="edit" value="1"/>

{if $errors.general}
<div class="formerror">{$errors.general}</div>
{/if}

<div class="sticky">
 	<input type="submit" name="savechanges" value="Save Changes" style="width:200px; max-width:40vw; font-weight:bold; background-color: #b7e1cd;"/>
 	<input type="submit" name="cancel" value="Cancel" style="width:200px; max-width:40vw; font-weight:bold; background-color: #f4c7c3;"/>
</div>

{*---------------------------Three col setup-------------------------*}


<div class="threecolsetup">



{*------------------------Basic details----------------------------*}
<div class="threecolumn">
<h3>Basic details</h3>

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
	  public or not.
    <br/><br/>
    If you change your email address, you will be sent a verification email. Your email address change will only take place once the email has been verified.

		{if $company_member}
			<br><br><b>Note: If you change your email, may want to also update in the Company Membership database.</b>
			<a href="{$company_link|escape:'html'}" target="_blank">Follow this link to be taken to the company mini-site</a>
			(opens in a new window)
		{/if}


	  </div>
	
	{if $errors.email}</div>{/if}
	
  
  <label>Email visibility:</label>
    <fieldset>
    <legend>Email privacy</legend>
      
	    <input {if $profile->public_email eq 0}checked{/if} type="radio" name="public_email" id="public_email_no" value="0">
	    <label class="option" for="public_email_no">Hide my email address</label>
	    
    
	    <input {if $profile->public_email eq 1}checked{/if} type="radio" name="public_email" id="public_email_yes" value="1">
	    <label class="option" for="public_email_yes">Show my email address.</label>
      
      <div class="fieldnotes">Choose whether your email address is displayed on your profile page. If email is set to hidden, people can still contact you through the site, but will not discover your email address unless you reply.</div>
   
    
    </fieldset>
    
  

</div>

<div class="field">

	<label for="gravatar">Gravatar:</label>
	<img src="https://www.gravatar.com/avatar/{$profile->md5_email}?r=G&amp;d=https://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536%3Fs=30&amp;s=50" align="left" alt="{$profile->realname|escape:'html'}'s Gravatar" style="padding-right:10px"/>
	
	<div class="fieldnotes">To set up or change your Avatar image, go to {external href="http://www.gravatar.com" text="gravatar.com" target="_blank"} and use the same email address as above. <br/>(Tick this box: <input type="checkbox" name="gravatar_reset"/> if you have recently uploaded a Gravatar)</div>

</div>

</fieldset>




{*------------------------Change password----------------------------*}
<h3>Change password</h3>

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
		<div class="fieldnotes">Enter your new password here (Leave blank unless you wish to change your password).</div>
	{if $errors.password1}</div>{/if}
</div>
<div class="field">
	{if $errors.password2}<div class="formerror"><p class="error">{$errors.password2}</p>{/if}
		<label for="password2">Confirm the new password:</label>
		<input id="password2" name="password2" type="password" value="{$profile->password2|escape:'html'}" size="35"/>
		<div class="fieldnotes">Reenter your new password here.</div>
	{if $errors.password2}</div>{/if}
</div>

</fieldset>




</div>


{*------------------------Site preferences----------------------------*}
<div class="threecolumn">
<h3>Site preferences</h3>

<a name="prefs"></a>
<fieldset>
<legend>Site Preferences</legend>
 

<div class="field"> 
	<label for="upload_size" class="nowrap">Default Upload Size</label>
	
	<select name="upload_size" id="upload_size"> 
		<option value="640" {if $profile->upload_size == 640} selected="selected"{/if}>640 x 640 (the original size)</option>
		<option value="800" {if $profile->upload_size == 800} selected="selected"{/if}>800 x 800</option>
		<option value="1024" {if $profile->upload_size == 1024} selected="selected"{/if}>1024 x 1024</option>
		<option value="1600" {if $profile->upload_size == 1600} selected="selected"{/if}>1600 x 1600</aoption>
		<option value="65536" {if $profile->upload_size > 65530} selected="selected"{/if}>As uploaded</option>
	</select>

	 
	<div class="fieldnotes">Choose the default size you wish to use when submitting images. You can still change the resolution on a per submission basis, this option sets the image resolution default.</div>
</div>


<div class="field"> 
	<label for="submission_method" class="nowrap">Desktop Submission Method</label>
	
	<select name="submission_method" id="submission_method"> 
		<option value="submit">Submit Version 2 (Original Submission Method)</option>
		<option value="submit2" {if $profile->submission_method =='submit2'} selected="selected"{/if}>Submit Version 2</option>
		<option value="submit2tabs" {if $profile->submission_method =='submit2tabs'} selected="selected"{/if}>Submit Version 2 Tabs</option>
		<option value="multi" {if $profile->submission_method =='multi'} selected="selected"{/if}>Multi Submit</option>
		<option value="mobile" {if $profile->submission_method =='mobile'} selected="selected"{/if}>Mobile/Tablet Version</option>
	</select>

	 
	<div class="fieldnotes">You can select your default submission method for use on desktop devices here (mobile devices will default to the mobile submission process). You are still able to change submission methods on a per-image basis. <br/><br/><a href="/help/submit" target="_blank">More information on the various submission methods</a>.</div>
</div>

<div class="field"> 
	<label for="submission_new" class="nowrap">Submission Style (only applies to v1)</label>
	<select name="submission_new" id="submission_new"> 
		<option value="{if $profile->submission_new ==2}2{else}1{/if}">Geographical Context (Recommended)</option>
		<option value="0"{if $profile->submission_new ==0} selected="selected"{/if}>Category (Soon to be removed)</option>
	</select>

	 
	<div class="fieldnotes">We are migrating to using Geographical Context, rather than the old Category method. <a href="/article/Transitioning-Categories-to-Tags" target="_blank">Read More</a></div>
</div>


<div class="field"> 
	<label for="message_sig" class="nowrap">Message Signature</label>
	
	<textarea name="message_sig" id="message_sig" rows="4" cols="60">{$profile->message_sig|escape:'html'}</textarea>
  <input type="button" value="Use Suggested Text" onclick="this.form.message_sig.value='-- '+this.form.realname.value+' {$self_host}/profile/{$user->user_id}'"/>
	 
	<div class="fieldnotes">Automatically include this text in messages sent though the site. <br/>
	(250 chars max) 
	</div>
</div>


<div class="field"> 
	<label for="expand_about" class="nowrap">Expand Profile Pages</label>
	
	<select name="expand_about" id="expand_about"> 
	<option value="0" {if $profile->expand_about == 0} selected="selected"{/if}>Show variable length preview</option>
	<option value="1" {if $profile->expand_about == 1} selected="selected"{/if}>Always show full expanded version</option>
	<option value="2" {if $profile->expand_about == 2} selected="selected"{/if}>Always show short preview, with click to expand</option>
	</select>
	
	<div class="fieldnotes">How the 'About Me' box displays when viewing contributor profile pages.</div>
</div>


{if $profile->ticket_public ne 'everyone' && $profile->ticket_public ne ''}
<div class="field"> 
	<label for="ticket_public" class="nowrap">Change Suggestion Anonymity</label>
	<select name="ticket_public" id="ticket_public">
		{if $profile->ticket_public eq 'no'}<option value="no">Do not disclose my name</option>{/if}
		{if $profile->ticket_public eq 'owner'}<option value="owner" selected>Show my name to the photo owner</option>{/if}
		<option value="everyone" {if $profile->ticket_public eq 'everyone'} selected{/if}>Show my name against the suggestion</option>
	</select>
	 
	<div class="fieldnotes">Change how your name is disclosed on suggestions your create from now on. <br/>
	<b>Note: This setting will no longer be honoured. It's only shown here so you can change the setting to "Show my name against the suggestion" and remove the message from the edit page.</b>
	</div>
</div>
{/if}

<div class="field"> 
	<label for="ticket_public_change" class="nowrap">Anonymity for previous suggestions</label>
	<select name="ticket_public_change" id="ticket_public_change">
		<option value="">No change - leave previous suggestions as is</option>
		<!--option value="no">Do not disclose my name</option-->
		<option value="owner">Show my name to the photo owner</option>
		<option value="everyone">Show my name against the suggestion</option>
	</select>
	 
	<div class="fieldnotes">Change all your previous suggestions to a new Anonymity setting (optional).</div>
</div>


<div class="field"> 
	<label for="ticket_option" class="nowrap">Change Suggestion Emails</label>
	<select name="ticket_option" id="ticket_option" size="1"> 
		{html_options options=$ticket_options selected=$profile->ticket_option}
	</select>
	 
	<div class="fieldnotes">You can choose to opt out of email notifications for minor suggestions or all suggestions.</div>
</div>


<div class="field"> 
	<label for="sortBy" class="nowrap">Forum Sort Order</label>
	
	<select name="sortBy" id="sortBy" size="1">
	 	<option value="0">Latest Replies
	 	<option value="1" {if $profile->getForumSortOrder() eq 1}selected{/if}>New Topics</option>
	 </select>
	 
	 <div class="fieldnotes">The default order you will see on the recent discussions page in the forums.</div>
</div>

<div class="field"> 
	<label for="search_results" class="nowrap">Search Results</label>
	<select name="search_results" id="search_results" style="text-align:right" size="1"> 
		{html_options values=$pagesizes output=$pagesizes selected=$profile->search_results}
	</select> per page
	
	<div class="fieldnotes">Configures the default number of search results displayed per page.</div>
</div>
  
  
<div class="field"> 
  
	<label for="slideshow_delay" class="nowrap">Slide Show Delay</label>
	
	<select name="slideshow_delay" id="slideshow_delay" style="text-align:right" size="1">
		{html_options values=$delays output=$delays selected=$profile->slideshow_delay}
	</select> seconds
	
	<div class="fieldnotes">Toggle the number of seconds each image is shown for in slideshows.</div>  
</div>


</fieldset>


</div>

{*------------------------About box----------------------------*}
<div class="threecolumn">
<h3>About you</h3>

<fieldset>
<legend>More about you</legend>


<div class="field">
	
	{if $errors.website}<div class="formerror"><p class="error">{$errors.website}</p>{/if}
	
	
	<label for="website" class="nowrap">Your website:</label>
	<input type="text" id="website" name="website" value="{$profile->website|escape:'html'}" size="50"/>

	<div class="fieldnotes">Display the URL of your of a personal 
	website or blog on your public profile page. </div>
	
	
	{if $errors.website}</div>{/if}
</div>


<div class="field">

	{if $errors.grid_reference}<div class="formerror"><p class="error">{$errors.grid_reference}</p>{/if}
	
	<label for="grid_reference">Home grid square:</label>
	<input type="text" id="grid_reference" name="grid_reference" value="{$profile->grid_reference|escape:'html'}" size="6" />
	
	<div class="fieldnotes">Display the grid reference (Great Britain or Ireland) for your home on your public profile page.</div>

	{if $errors.grid_reference}</div>{/if}
</div>





<div class="field">

	<label for="about_yourself">About Yourself:</label>
	
	 
	<textarea name="about_yourself" id="about_yourself" rows="10" cols="60">{$profile->about_yourself|escape:'html'}</textarea>

	<div class="fieldnotes">A short 'about me' box which will be displayed on your public profile.<br/>
	<span style="color:red">Note: any HTML code will be removed, 
	however basic URLs will be autolinked.</span><br/>
	The main profile page will only show the first 300 characters, (or use <tt>[--more--]</tt> to specify the break point) with a link to show the full details. There is also an image display limit.<br/>
	TIP: use <span style="color:blue">[[TQ7506]]</span> or 
	<span style="color:blue">[[5463]]</span> to link to a grid square or another image.
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


{*------------------------User roles----------------------------*}
{if $company_link} 
<h3>Company Membership</h3>
	<fieldset>
        <legend>Geograph Project Limited - Company Membership</legend>
        <div class="field">
		<label class="nowrap">Apply</label>

		<a href="{$company_link|escape:'html'}" target="_blank">Follow this link to be taken to the company mini-site</a>

		<div class="fieldnotes">You already qualify for membership - from there can apply for Company Membership.</div>
        </div>
	</fieldset>
{/if}


{*------------------------User roles----------------------------*}
<h3>User roles</h3>

{if ($profile->stats.squares gt 20) || ($profile->rights && $profile->rights ne 'basic')}
	<fieldset>
	<legend>User Roles</legend>


	<div class="field"> 

		<label for="moderator" class="nowrap">Moderator</label>
		{if strpos($profile->rights,'moderator') > 0}
			<div class="fieldnotes">If you are no longer able to help with moderation then click the button below (you will have to re-apply).</div>
      <center><div class="relinquish"><a href="/admin/moderation.php?relinquish=1" onclick="return confirm('Are you sure you wish to relinquish your role as a moderator? Press OK to confirm.')">Relinquish moderator rights</a></div></center>
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

		<label for="moderator" class="nowrap">Suggestions</label>
      <div class="fieldnotes">If you are no longer able to help with ticket moderation then click the button below (you will have to re-apply).</div>
      <center><div class="relinquish"><a href="/admin/suggestions.php?relinqush=1" onclick="return confirm('Are you sure you wish to relinquish your role as a suggestion moderator? Press OK to confirm.')">Relinqush suggestion moderator rights</a></div></center>

	</div>
	{/if}

	</fieldset>
{/if}

</div>

</div>


<br style="clear:both"/>





 </form>	

{/dynamic}    
{include file="_std_end.tpl"}
