{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}

{dynamic}
{if $message_sent}
	<h3>Thank you for contacting the Geograph British Isles team.</h3>
	<p>Your message has been sent - you should hear back from us soon.</p>
{else}
    <h2>Contact Us</h2>

{if !$user->registered}
	{if $image}
	    <form onsubmit="return false" action="#" style="text-align:center; background-color:#f9f9f9">
		<hr/><br/>
		First we just need to clarify who you would like to contact, please click one:

		<br/><br/><br/>

		<button style="font-size:1.1em" onclick="alert('********************************\n\nSorry we do NOT represent the locations\n\n our contributors have photographed.\n\n********************************\n\nAnd therefore probably unable to help with your enquiry.\n\nYou will be returned to the photo page.'); history.go(-1)">I'm trying to contact <b>{$image->title|escape:'html'}</b></button><br/><br/>

		<button onclick="alert('We provide a form for this purpose, click OK to go there...');location.href='/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}';">I'm trying to contact the photographer of <b>{$image->grid_reference|escape:'html'} :: {$image->title|escape:'html'}</b></button><br/> (image id <tt>{$image->gridimage_id}</tt>) by <a href="/usermsg.php?to={$image->user_id}&amp;image={$image->gridimage_id}">{$image->realname|escape:'html'}</a> <br/><br/>

		<button style="font-size:1.1em"  onclick="alert('Congratualations, you\'ve come to the right place, please use the form below!')">The developers of the Geograph Project website</button><br/><br/>

		<br/><hr/>
	    </form>
	  <br/><br/><br/><br/><br/>
	{else}
	  <br/>

		<div class="interestBox" style="border:3px solid yellow;padding:20px;text-align:center;background-color:#eeeeee">
		 We do <b>not represent the locations</b> our <i>contributors</i> have photographed <br/> and submitted in our mission to collect<br/>
	 <span style="color:blue">geographically representative photographs and information for <br/><b>every square kilometre of Great Britain and Ireland</b></span>.
	<small><br/><br/>You can read about the project on Wikipedia: {external href="http://en.wikipedia.org/wiki/Geograph_British_Isles" text="Geograph British Isles"},<br/> please do make sure you really do mean to contact us before using the form below.</small>

		</div>
	  <br/>
	{/if}
{/if}
 
  <p><b>Got something to tell the Geograph developers?</b> Then fire away - we try to respond within 24 hours, often much quicker.</p>
  <br/>
 
    <form action="contact.php" method="post">
    <p><input type="hidden" name="referring_page" value="{$referring_page|escape:'html'}"/>
    <label for="from">Your email address</label><br/>
	<input size="40" id="from" name="from" value="{$from|escape:'html'}"/><span class="formerror">{$from_error}</span>
    
    <div style="display:none">
    <br /><br />
    <label for="name">Leave Blank!</label><br/>   
	<input size="40" id="name" name="name" value=""/>
    </div>

    <br /><br />
    
    <label for="subject">Subject</label><br/>
	<input size="40" id="subject" name="subject" value="{$subject|escape:'html'}"/>
	<br /><br />
    
    <label for="msg">Your message</label><br/>
	<textarea id="msg" name="msg" rows="10" cols="50">{$msg|escape:'html'}</textarea>
    	<br /><span class="formerror">{$msg_error}</span> 
    <br />
        <small style="font-size:0.9em">If you are writing in relation to a particular image, please be specific as to which image you are referring!<br/> Please copy &amp; paste a link (from your browser address bar) to the photo page.</small><br /><br />

	<input type="submit" name="send" value="Send"/></p>
    </form>
{/if} 
{/dynamic}    
{include file="_std_end.tpl"}
