<html>
<head>
<title></title>
</head>
<body>
{dynamic}
<table cellspacing="0" cellpadding="0">
	<tr>
		<td width="20" height="10" style="border-bottom:1px solid silver;">&nbsp;</td>
		{section name=columns loop=$c}
		<td width="340" height="10" style="border-left:1px solid silver">&nbsp;</td>
		{/section}
		<td width="20" height="10" style="border-bottom:1px solid silver;border-left:1px solid silver">&nbsp;</td>
	</tr>
	{section name=rows loop=$r}
	<tr>
		<td width="20" height="210" style="border-bottom:1px solid silver">&nbsp;</td>
		{section name=columns loop=$c}
		<td width="340" height="210" valign="top">
			<div style="background-color:#000066; text-align:center;">
				<img src="http://{$http_host}/templates/basic/img/logo.gif">
			</div>
			
			{if $v eq 2}
			<div style="font-family:Georgia; text-align:center;font-size:12px;padding:5px">
				Hi, my name is <b style="font-size:1.1em">{$user->realname|escape:'html'}</b> and I am taking photographs for an 
				online project called <br/><span style="color:#000066; font-weight:bold">Geograph British Isles</span><br/>
				We are attempting to collect photographs for every<br/>
				one kilometre grid square in the British Isles.<small><br/><br/></small>
				See my page at <tt><span style="color:#000066; font-weight:bold">www.geograph.org.uk/u/{$user->user_id}</span></tt><br/>
				or goto <tt><span style="color:#000066; font-weight:bold">www.geograph.org.uk/g/{section name=g loop=6}<span style="border:1px solid silver">&nbsp;&nbsp;</span>{/section}</span></tt>
			</div>
			{else}
			<div style="font-family:Georgia; text-align:center;font-size:12px;padding:5px">
				I am taking photographs for an online project called 
				<span style="color:#000066; font-weight:bold">Geograph British Isles</span><br/>
				We are attempting to collect photographs for every<br/>
				one kilometre grid square in the British Isles.<small><br/><br/></small>
				To find my photographs, look for images by<br/>
				<b style="font-size:1.1em">{$user->realname|escape:'html'}</b><br/>
				See my page at <tt style="text-decoration:underline"><span style="color:#000066; font-weight:bold">www.geograph.org.uk</span>/u/{$user->user_id}
			</div>
			{/if}
		</td>
		{/section}
		<td width="20" height="210" style="border-bottom:1px solid silver">&nbsp;</td>
	</tr>
	{/section}
	<tr>
		<td width="20" height="10">&nbsp;</td>
		{section name=columns loop=$c}
		<td width="340" height="10" style="border-left:1px solid silver">&nbsp;</td>
		{/section}
		<td width="20" height="10" style="border-left:1px solid silver">&nbsp;</td>
	</tr>
</table>
{/dynamic}
</body>
</html>