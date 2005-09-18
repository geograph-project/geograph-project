{include file="_std_begin.tpl"}
{dynamic}

    <h2>Edit Profile</h2>
 
 
 
 <form method="post" action="/profile.php">
 <input type="hidden" name="edit" value="1"/>
 
 <table class="formtable">
 
 <tr><td><label for="realname">Real Name:</label></td>
 <td><input type="text" id="realname" name="realname" value="{$profile->realname|escape:'html'}"/>
 {if $errors.realname}<br/><span class="formerror">{$errors.realname}</span>{/if}</td>
 <td><div class="fieldnotes">Your real name is used to give you a credit on the site for any photographs
 you submit.</div></td>
 </tr>
 
  <tr><td><label for="nickname">Nick Name:</label></td>
  <td><input type="text" id="nickname" name="nickname" value="{$profile->nickname|escape:'html'}"/>
  {if $errors.nickname}<br/><span class="formerror">{$errors.nickname}</span>{/if}</td>
  <td><div class="fieldnotes">Your nickname can be used to login and is also used to identify you on
  the forums. Geocachers may wish to enter their geocaching alias here!</div></td>
 </tr>
 
 <tr><td><label for="website" class="nowrap">Personal home page:</label></td>
 <td><input type="text" id="website" name="website" value="{$profile->website|escape:'html'}"/>
   {if $errors.website}<br/><span class="formerror">{$errors.website}</span>{/if}</td>
 <td><div class="fieldnotes">If you wish, tell us the URL of your personal website to link from your profile page.
 If you are a geocacher, it could be a link to your geocaching profile.</div></td>
 </tr>
 
 <tr><td><label>Email</label></td><td><tt>{$profile->email|escape:'html'}</tt></td></tr>
 <tr><td colspan="2" class="nowrap">
 <input {if $profile->public_email eq 1}checked{/if} type="checkbox" id="public_email" name="public_email" value="1"> 
  <label for="public_email">Allow site visitors to see my email address</label>
 
 </td>
 <td><div class="fieldnotes">If you choose to hide 
  your address, site visitors can still send messages to you through the site.</div></td></tr>
 
 <tr><td><label for="sortBy" class="nowrap">Forum Sort Order</label></td>
 <td><select name="sortBy" id="sortBy" size="1">
 	<option value="0">Latest Replies

 	<option value="1" {if $profile->sortBy eq 1}selected{/if}>New Topics</option>
 </select></td>
 <td><div class="fieldnotes">The default order you will see recent discussions.</div></td></tr>
 

</table>

<br/>
<span class="formerror">{$errors.general}</span>
<br/>


 	<input type="submit" name="savechanges" value="Save Changes"/>
 	<input type="submit" name="cancel" value="Cancel"/>
 </form>	

{/dynamic}    
{include file="_std_end.tpl"}
