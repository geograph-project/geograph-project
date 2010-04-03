{assign var="page_title" value="Contact"}
{include file="_std_begin.tpl"}

 <h2>Contact Geograph</h2>

<p>Geograph Britain and Ireland uses a support ticket system. Please use the form below to contact the Geograph website developers and/or Image moderators.</p> 

{dynamic}

<iframe src="http://www.geograph.org.uk/support/open.php?ref={$referring_page|escape:'url'}{if $user->registered}&amp;user_id={$user->user_id}{/if}" width="100%" height="100%" frameborder="0" name="content"></iframe>

{/dynamic} 

<p>Your message will become a ticket in our system, and will be answered by one of the <a href="/team.php">team</a>.</p>
   
{include file="_std_end.tpl"}
