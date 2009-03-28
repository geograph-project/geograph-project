{assign var="page_title" value="Remote Login"}
{include file="_std_begin.tpl"}
<h2>Remote Login</h2>

{dynamic}

{if $action eq 'authenticate'} 

	<p>An application is requesting to authenticate against your account, click the link below if you wish to continue.</p>


	<div class="interestBox">
		Please note this does not give the application access to your profile, or any private settings such as email or password. This encoded negotiation process only serves to prove you are a Geograph Account Holder, and just passes back your user id, and name.
	</div>

	<p><b><a href="{$final_url}">Continue to Application</a></b> or press <a href="javascript:history.go(-1)">back</a> to not provide these details.</p>


{else}
	unknown error
{/if}

{/dynamic}

{include file="_std_end.tpl"}
