{assign var="page_title" value="Great Britain Map"}
{include file="_std_begin.tpl"}

{dynamic}{if $user->registered}
<div style="text-align:right; width:660px; font-size:0.7em; color:gray;">If parts of the map stop displaying, then <a href="/mapper/captcha.php?token={$token}" style="color:gray;">visit this page to continue</a></div>{/if}{/dynamic}
<table>
	<tbody>
		<tr>
			<td>
<iframe src="/mapper/?inner&amp;t={$token}" width="700" height="850" frameborder="0"></iframe>
			</td>
			<td>
<iframe src="/mapper/blank.html" width="210" height="850" frameborder="1" name="browseframe"></iframe>
			</td>
		</tr>
	</tbody>
</table>

<br/><br/>


{include file="_std_end.tpl"}
