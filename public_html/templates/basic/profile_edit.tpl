{include file="_std_begin.tpl"}


    <h2>Edit Profile</h2>
 
 
 
 <form method="post" action="/profile.php">
 <input type="hidden" name="edit" value="1"/>
 
 <table class="formtable">
 
 <tr><td><label for="realname">Real Name:</label></td>
 <td><input type="text" id="realname" name="realname" value="{$profile->realname|escape:'html'}"/>
 <span class="formerror">{$errors.realname}</span></td>
 <td><div class="fieldnotes">Your real name is used to give you a credit on the site for any photographs
 you submit.</div></td>
 </tr>
 
  <tr><td><label for="nickname">Nick Name:</label></td>
  <td><input type="text" id="nickname" name="nickname" value="{$profile->nickname|escape:'html'}"/>
  <span class="formerror">{$errors.nickname}</span></td>
  <td><div class="fieldnotes">A nickname is an alternative name others may know you by. Geocachers
  may wish to enter their geocaching alias here!</div></td>
 </tr>
 
 <tr><td><label for="website">Personal home page:</label></td>
 <td><input type="text" id="website" name="website" value="{$profile->website|escape:'html'}"/>
 <span class="formerror">{$errors.website}</span></td>
 <td><div class="fieldnotes">If you wish, tell us the URL of your personal website to link from your profile page.
 If you are a geocacher, it could be a link to your geocaching profile.</div></td>
 </tr>
 
 <tr><td><label>Email</label></td><td>{$profile->email|escape:'html'}</td></tr>
 <tr><td colspan="2">
 <input {if $profile->public_email eq 1}checked{/if} type="checkbox" id="public_email" name="public_email" value="1"> 
  <label for="public_email">Allow site visitors to see my email address (if you choose to hide 
  your address, site visitors can still send messages to you through the site)</label>
 
 </td></tr>
 
 
 

</table>

<br/>
<span class="formerror">{$errors.general}</span>
<br/>


 	<input type="submit" name="savechanges" value="Save Changes"/>
 	<input type="submit" name="cancel" value="Cancel"/>
 </form>	

    
{include file="_std_end.tpl"}
