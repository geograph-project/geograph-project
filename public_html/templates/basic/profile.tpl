{include file="_std_begin.tpl"}


    <h2>Profile for {$profile->realname|escape:'html'}</h2>
 
 
    <h3>Basic information</h3>
 	<ul>
 	<li>Name: {$profile->realname|escape:'html'}</li>
 	
 	{if $profile->website}
 	<li>Website: <a href="{$profile->website|escape:'html'}">{$profile->website|escape:'html'}</a></li>
 	{/if}
 	
 	{if $user->user_id ne $profile->user_id}
 		{if $profile->public_email eq 1}
	 		<li>Email: {mailto address=$profile->email encode="javascript"}</li> 	
	 	{else}
	 		<li><a title="Contact {$profile->realname|escape:'html'}" href="/usermsg.php?to={$profile->user_id}">Send message to {$profile->realname|escape:'html'}</a></li> 	
 		{/if}
	{/if}
 
 	</ul>
 	
 	{if $user->user_id eq $profile->user_id}
 	 <p><a href="/profile.php?edit=1">Edit your profile</a> if there's anything you'd like to change.</p> 	
 	{/if}
 	
 	
 	<h3>Statistics</h3>
 	<ul>
 	
 	{if $profile->stats.total gt 0}
 	  <li>{$profile->stats.total} {if $profile->stats.total eq 1}photograph{else}photographs{/if} submitted</li>
      <li>First to photograph {$profile->stats.ftf} grid {if $profile->stats.ftf eq 1}square{else}squares{/if}</li>
 	{else}
 	  <li>No photographs submitted</li>
 	{/if}

 	</ul>
 	
 	

    
{include file="_std_end.tpl"}
