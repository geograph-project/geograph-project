{assign var="page_title" value="Confirmation mail"}
{include file="_std_begin.tpl"}
{dynamic}

{if $lock_seconds}
<script type="text/javascript">
//<![CDATA[
	AttachEvent(window,'load',function() {ldelim}buttontimer('submitbutton', {$lock_seconds});{rdelim},false);
//]]>
</script>
{/if}
<form method="post" action="/register.php">
<input type="hidden" name="CSRF_token" value="{$CSRF_token}" />
<input type="hidden" name="confirm" value="{$query_confirm|escape:'html'}" />
<input type="hidden" name="u" value="{$query_u|escape:'html'}" />


{if $confirmpass}
<h2>Confirm password change</h2>
{elseif $confirmmail}
<h2>Confirm mail address change</h2>
{else}{*$confirmreg*}
<h2>Confirm registration</h2>
{/if}
{if $confirmation_status=='csrf'}
<div class="interestBox" style="background-color:yellow; color:black; border:2px solid orange; padding:5px; font-size:0.9em">
Your confirmation could not be processed due to <a href="/help/csrf">security reasons</a>. Please verify the below form and try again.
</div>
{/if}
<p>Please confirm
{if $confirmpass}
changing your password
{elseif $confirmmail}
changing your mail address
{else}{*$confirmreg*}
your registration
{/if}
entering your password below.</p>

<label for="password">{if $confirmpass}New{else}Current{/if} password:</label>
<br/>
<input id="password" name="password" type="password" value="{$query_pass|escape:'html'}" size="35"/>
{if $confirmation_status=='auth'}<span class="formerror">Wrong password or blocked access{if $lock_seconds} - blocked for {$lock_seconds|format_seconds:120}{/if}</span>{/if}

<br/></br/>

<input type="submit" name="submit" id="submitbutton" value="Confirm"/>
</form>

{/dynamic}
{include file="_std_end.tpl"}
