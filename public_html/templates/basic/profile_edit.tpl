{include file="_std_begin.tpl"}
{dynamic}

    <h2>Edit Profile</h2>
 
 
 
 <form method="post" action="/profile.php">
 <input type="hidden" name="edit" value="1"/>
 
 <table class="formtable" border=0 cellspacing=0 cellpadding=1>
 
 <tr><td colspan="3"><h3>Basic Information...</td></tr>
 
 <tr><td><label for="realname">Real Name:</label></td>
 <td><input type="text" id="realname" name="realname" value="{$profile->realname|escape:'html'}"/>
  {if $errors.realname}<br/><span class="formerror">{$errors.realname}</span>{/if}</td>
 </tr>
 <tr>
 <td colspan="3"><div class="fieldnotes">Your real name is used to give you a credit on the site for any photographs
 you submit.</div><br/></td>
 </tr>
 
 <tr><td><label for="nickname">Nick Name:</label></td>
 <td><input type="text" id="nickname" name="nickname" value="{$profile->nickname|escape:'html'}"/>
  {if $errors.nickname}<br/><span class="formerror">{$errors.nickname}</span>{/if}</td>
 </tr>
 <tr>
 <td colspan="3"><div class="fieldnotes">Your nickname can be used to login and is also used to identify you on
  the forums. As well as access your profile at <span class="nowrap">http://{$http_host}/user/<i>nickname</i>/</span>. Geocachers may wish to enter their geocaching alias here!</div><br/></td>
 </tr>
 
 <tr><td><label for="website" class="nowrap">Personal home page:</label></td>
 <td><input type="text" id="website" name="website" value="{$profile->website|escape:'html'}" size="50"/>
  {if $errors.website}<br/><span class="formerror">{$errors.website}</span>{/if}</td>
 </tr>
 <tr>
 <td colspan="3"><div class="fieldnotes">If you wish, tell us the URL of your personal website to link from your profile page.
 If you are a geocacher, it could be a link to your geocaching profile.</div><br/></td>
 </tr>
 
 <tr><td><label>Email:</label></td>
 <td><tt>{$profile->email|escape:'html'}</tt></td>
 <td><select name="public_email" id="public_email">
 <option value="">Don't Show</option>
 <option value="1" {if $profile->public_email} selected="selected"{/if}>Show Publicly</option>
 </select></td>
 </tr>
 <tr>
 <td colspan="3"><div class="fieldnotes">If you choose to hide 
  your address, site visitors can still send messages to you through the site.</div><br/></td>
 </tr>

 <tr><td><label>Home grid square:</label></td>
 <td><input type="text" id="grid_reference" name="grid_reference" value="{$profile->grid_reference|escape:'html'}" size="6" />
 {if $errors.grid_reference}<br /><span class="formerror">{$errors.grid_reference}</span>{/if}</td>
 </tr>
 <tr><td colspan="3"><div class="fieldnotes">If you wish, tell us the OS grid reference of your home.</div><br /></td>
 </tr>


 <tr><td><label for="age_group">Age Group:</label></td>
 <td><select name="age_group" id="age_group"> 
	<option value=""></option>
	<option value="11" {if $profile->age_group == 11} selected="selected"{/if}>11 or under</option>
	<option value="18" {if $profile->age_group == 18} selected="selected"{/if}>12-18</option>
	<option value="25" {if $profile->age_group == 25} selected="selected"{/if}>19-25</option>
	<option value="50" {if $profile->age_group == 50} selected="selected"{/if}>26-50</option>
	<option value="70" {if $profile->age_group == 70} selected="selected"{/if}>51-70</option>
	<option value="90" {if $profile->age_group == 90} selected="selected"{/if}>71+</option>
	</select></td>
 <td><select name="use_age_group" id="use_age_group">
 <option value="">Don't Use</option>
 <option value="1" {if $profile->use_age_group} selected="selected"{/if}>Use Anonymously</option>
 </select></td>
 </tr>

 
 <tr><td colspan="3"><br/><br/><h3>More Information... (optional)</td></tr>
 
 <tr><td><label for="about_yourself">About Yourself:</label></td>
 <td><div style="font-size:0.7em">TIP: use <span style="color:blue">[[TQ7506]]</span> or <span style="color:blue">[[5463]]</span> to link 
to a Grid Square or another Image.</div></td>
 <td><select name="public_about" id="public_about">
 <option value="">Don't Show</option>
 <option value="1" {if $profile->public_about} selected="selected"{/if}>Show Publicly</option>
 </select></td>
 </tr>
 <tr>
 <td colspan="3"><textarea name="about_yourself" id="about_yourself" rows="4" cols="60">{$profile->about_yourself|escape:'html'}</textarea><br/></td>
 </tr>
 
 <tr><td colspan="3"><br/><br/><h3>Site Preferences...</td></tr>
 
 <tr><td><label for="sortBy" class="nowrap">Forum Sort Order</label></td>
 <td><select name="sortBy" id="sortBy" size="1">
 	<option value="0">Latest Replies

 	<option value="1" {if $profile->getForumSortOrder() eq 1}selected{/if}>New Topics</option>
 </select></td>
 </tr>
 <tr>
 <td colspan="3"><div class="fieldnotes">The default order you will see recent discussions.</div><br/></td>
 </tr>
 
 <tr><td><label for="search_results" class="nowrap">Search Results</label></td>
  <td> <select name="search_results" id="search_results" style="text-align:right" size="1"> 
	{html_options values=$pagesizes output=$pagesizes selected=$profile->search_results}
	</select> per page</td>
 </tr>
 <tr>
 <td colspan="3"><div class="fieldnotes">Default number of search results per page.</div><br/></td>
 </tr>
  
 <tr><td><label for="slideshow_delay" class="nowrap">Slide Show Delay</label></td>
  <td> <select name="slideshow_delay" id="slideshow_delay" style="text-align:right" size="1"> 
	{html_options values=$delays output=$delays selected=$profile->slideshow_delay}
	</select> seconds</td>
 </tr>
 <tr>
 <td colspan="3"><div class="fieldnotes">Number of seconds slides are shown for.</div><br/></td>
 </tr>
  
</table>

<br/>
<span class="formerror">{$errors.general}</span>
<br/>


 	<input type="submit" name="savechanges" value="Save Changes"/>
 	<input type="submit" name="cancel" value="Cancel"/>
 </form>	

{/dynamic}    
{include file="_std_end.tpl"}
