{include file="_std_begin.tpl"}
{dynamic}

    <h2>Profile for {$profile->realname|escape:'html'}</h2>
 
 
    <h3>Basic information</h3>
 	<ul>
 	<li>Name: {$profile->realname|escape:'html'}</li>
 	
 	<li>Nickname: 
 	{if $profile->nickname}
	 	{$profile->nickname|escape:'html'}
	{else}
	    <i>n/a</i>
	{/if}
	</li>
 	
 	<li>Website: 
 	{if $profile->website}
	 	<a href="{$profile->website|escape:'html'}">{$profile->website|escape:'html'}</a>
	{else}
	    <i>n/a</i>
	{/if}
	</li>
 	
 	
 	
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
 	  <li>Geograph points: {$profile->stats.ftf} (see <a title="Frequently Asked Questions" href="/faq.php#points">FAQ</a>)</li>
 	  <li>{$profile->stats.total} {if $profile->stats.total eq 1}photograph{else}photographs{/if} submitted
 	  
 	  {if $profile->stats.pending gt 0}
 	 	  ({$profile->stats.pending} awaiting moderation)
 	  {/if}
 	  </li>
   {else}
 	  <li>No photographs submitted</li>
 	{/if}

 	</ul>
 	
 	

 {/dynamic}   
{include file="_std_end.tpl"}
