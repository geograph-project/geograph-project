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
	<input type="text" id="email" name="email" value="{$profile->email|escape:'html'}"/>
	
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
	
	 
	<textarea name="about_yourself" id="about_yourself" rows="4" cols="60">{$profile->about_yourself|escape:'html'}</textarea>

	<div class="fieldnotes">TIP: use <span style="color:blue">[[TQ7506]]</span> or 
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

<fieldset>
<legend>Site Preferences</legend>
 

<div class="field"> 
	<label for="ticket_option" class="nowrap">Trouble Ticket Emails</label>
	
	<select name="ticket_option" id="ticket_option" size="1"> 
		{html_options options=$ticket_options selected=$profile->ticket_option}
	</select>
	 
	<div class="fieldnotes">Change the amount of automated emails you receive in relation to trouble tickets on your images. (more options to be added later)</div>
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


</fieldset>



 	<input type="submit" name="savechanges" value="Save Changes"/>
 	<input type="submit" name="cancel" value="Cancel"/>

{if ($profile->rank && $profile->rank < 500) || ($profile->rights && $profile->rights ne 'basic')}
	<br/><br/><br/><br/><br/><br/>
	<fieldset>
	<legend>User Roles</legend>


	<div class="field"> 

		<label for="moderator" class="nowrap">Moderator</label>
		{if strpos($profile->rights,'moderator') > 0}
			<input type="button" value="Relinqush moderator rights" onclick="location.href = '/admin/moderation.php?relinqush=1';"/>

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
</div>

 </form>	

{/dynamic}    
{include file="_std_end.tpl"}
