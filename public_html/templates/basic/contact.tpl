{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}

{dynamic}
{if $message_sent}
	<h3>Thank you for contacting the Geograph team.</h3>
	<p>Your message has been sent - you should hear back from us soon.</p>
{else}
    <h2>Contact Geograph</h2>

{if !$user->registered}

	<div class="interestBox" style="float:right;width:200px;border:3px solid silver;padding:10px;">
		<b>About Geograph</b><br/><br/>
		The Geograph Britain and Ireland project aims to collect geographically
		representative photographs and information for every square kilometre of <a href="/explore/places/1/">Great Britain</a> and 
		<a href="/explore/places/2/">Ireland</a>.
	</div>
	

	{if $image}
	
		<div class="interestBox" style="background-color:yellow; text-align:center; width:500px">
		<h1 style="color:red;border-bottom:2px solid red;padding-bottom:10px">Stop!</h1>
		Trying to contact <b>{$image->title|escape:'html'}</b>?<br/><br/>
		Geograph is a photo sharing website, and only has a <i>photo</i> by that title, <u>not</u> the means to contact the location photographed. <br/><br/>

		<p><small>| <a href="javascript:history.go(-1)">Back to photo page</a> | <a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">Contact the photographer</a> |</small></p>

		</div>

		<br/><br/><br/><br/>
		&middot; Looking to copy the image you where viewing? <a href="/reuse.php?id={$image->gridimage_id}">See this page</a>.
		<br/><br/><br/>
		<br/><br/><hr/><br/><br/>
		If you do want to contact the <big>Developers of Geograph Britain and Ireland</big>, please scroll down.... 
		<br/><br/><br/><br/><br/><br/><br/>
	{/if}
{/if}
 
  <h3>Got something to tell the <i>Geograph Website</i> developers?</h3>
  </p>Then fire away - we try to respond within 24 hours, often much quicker.</p>
  
	{if $image}
		<div class="interestBox" style="background-color:pink">&middot;
		<small>Remember, we <b>can't</b> put you in contact with <b>{$image->title|escape:'html'}</b> (we just have a photo of it).</small>
		</div>
	{/if}
 <br/>

    <form action="contact.php" method="post">
    <p><input type="hidden" name="referring_page" value="{$referring_page|escape:'html'}"/>
    <label for="from">Your email address</label><br/>
	<input size="40" id="from" name="from" value="{$from|escape:'html'}"/><span class="formerror">{$from_error}</span>
	<br /><br />

    <div style="display:none">
    <label for="name">Leave Blank!</label><br/>   
	<input size="40" id="name" name="name" value=""/>
	<br /><br />
    </div>

    <label for="subject">Subject</label><br/>
	<input size="40" id="subject" name="subject" value="{$subject|escape:'html'}"/>
	<br /><br />
    
    <label for="msg">Your message</label><br/>
	<textarea id="msg" name="msg" rows="10" cols="50">{$msg|escape:'html'}</textarea>
    	<br /><span class="formerror">{$msg_error}</span> 
    <br />
        <small style="font-size:0.9em">If you are writing in relation to a particular image or images, please don't forget to mention which!<br/> Ideally copy &amp; paste the page address (URL) of the photo page. <br/>
        Example: <tt>http://{$http_host}/photo/{if $image}{$image->gridimage_id}{else}1234{/if}</tt></small><br /><br />
    <br />
{if !$user->registered}
    <label for="subject">Anti-Spam</label><br/>
    	<img src="http://{$static_host}/templates/basic/img/logo.gif" align="right">
	<input size="40" id="spam" name="spam" value="{$spam|escape:'html'}"/><br/>
        Please type the <b>biggest word</b> from our project website logo (duplicated on the right) <br /><br />
{/if}

	<input type="submit" name="send" value="Send"/></p>
    </form>
{/if} 
{/dynamic}    
{include file="_std_end.tpl"}
