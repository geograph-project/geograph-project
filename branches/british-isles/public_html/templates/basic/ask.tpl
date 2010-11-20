{assign var="page_title" value="Ask a Team Member"}
{include file="_std_begin.tpl"}

<h2>Ask a Question</h2>

{if $done}
<div class="interestBox" style="margin-bottom:300px">
	<b>Your question has been received</b>, thank you. A member of the team will take a look and get back to you.
</div>

{/if}


<form action="/support/open.php" method="POST" enctype="multipart/form-data">
 <input type=hidden name="topicId" value="13">
<table align="left" cellpadding=2 cellspacing=1 width="90%">
{dynamic}
    <tr>
        <th width="20%">Full Name:</th>
        <td>

			<input type=hidden name="ref" value="{$referring_page|escape:'html'}"/>
			<input type=hidden name="user_id" value="{$user->user_id}"/>
            <input type="text" name="name" size="25" value="{$user->realname|escape:'html'}">
	                    &nbsp;<font class="error">*&nbsp;</font>
        </td>
    </tr>
    <tr>
        <th nowrap >Email Address:</th>
        <td>
                <input type="text" name="email" size="25" value="{$user->email|escape:'html'}">
                        &nbsp;<font class="error">*&nbsp;</font> <small>(using this form will reveal your email address to support representatives)</small>
        </td>
    </tr>
{/dynamic}

    <tr>
        <th>Subject:</th>
        <td>
            <input type="text" name="subject" size="35" value="">
            &nbsp;<font class="error">*&nbsp;</font>
        </td>
    </tr>
    <tr>
        <th valign="top">Message:</th>
        <td>
              <textarea name="message" cols="35" rows="8" wrap="soft" style="width:85%"></textarea>

	</td>
    </tr>

            <tr height=2px><td align="left" colspan=2 >&nbsp;</td</tr>
    <tr>
        <td></td>
        <td>
            <input class="button" type="submit" name="submit_x" value="Submit Question">
            <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="/"'>      </td>
    </tr>
</table>
</form>
     <div style="text-align:right"><a id="powered_by" target="_blank" href="http://osticket.com"><img src="http://s0.geograph.org.uk/support/images/poweredby.jpg" width="126" height="23" alt="Powered by osTicket"></a></div>

{include file="_std_end.tpl"}
