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
			{if $l eq 2}
			<div style="float:left;width:59px;height:210px">
				<img src="http://data.geograph.org.uk/cards/.rotated_redrawn_geograph_logo.jpg" width="59" height="210">
			</div>
			{else}
			<div style="background-color:#000066; text-align:center;">
				<img src="http://{$http_host}/templates/basic/img/logo.gif">
			</div>
			{/if}
			
			{if $image and $image->isValid()}
			<div class="img-shadow" style="font-size:0.7em;float:right;text-align:center;padding:1px">
				{$image->getThumbnail(120,120)|replace:'_120x120':''|regex_replace:'/\/s\d\./':'/www.'}<br/>
				<tt>{$http_host}/p/{$image->gridimage_id}</tt>
			</div>
			{/if}
			
			{if $v eq 3}
			<div style="font-family:Georgia; text-align:center;font-size:12px;padding:5px">
				I'm <b style="font-size:1.1em">{$user->realname|escape:'html'}</b> and I am taking photographs for an 
				online project called <br/><span style="color:#000066; font-weight:bold">Geograph Britain and Ireland</span>. 
				We are attempting to collect photographs for every
				one kilometre grid square in the British Isles.<small><br style="clear:right"/></small>
				See my page at <tt><span style="color:#000066; font-weight:bold">www.geograph.org.uk/u/{$user->user_id}</span></tt><br/>
				or <tt><span style="color:#000066; font-weight:bold">www.geograph.org.uk/g/{section name=g loop=6}<span style="border-bottom:1px solid silver;border-right:1px solid silver">&nbsp;&nbsp;</span>{/section}</span></tt>
			</div>
			{elseif $v eq 2}
			<div style="font-family:Georgia; text-align:center;font-size:12px;padding:5px">
				Hi, my name is <b style="font-size:1.1em">{$user->realname|escape:'html'}</b> and I am taking photographs for an 
				online project called <br/><span style="color:#000066; font-weight:bold">Geograph Britain and Ireland</span><br/>
				We are attempting to collect photographs for every<br/>
				one kilometre grid square in the British Isles.<small><br/><br style="clear:right"/></small>
				See my page at <tt><span style="color:#000066; font-weight:bold">www.geograph.org.uk/u/{$user->user_id}</span></tt><br/>
				or visit <tt><span style="color:#000066; font-weight:bold">www.geograph.org.uk/g/{section name=g loop=6}<span style="border:1px solid silver">&nbsp;&nbsp;</span>{/section}</span></tt>
			</div>
			{else}
			<div style="font-family:Georgia; text-align:center;font-size:12px;padding:5px">
				I am taking photographs for an online project called 
				<span style="color:#000066; font-weight:bold">Geograph Britain and Ireland</span><br/>
				We are attempting to collect photographs for every<br/>
				one kilometre grid square in the British Isles.<small><br/><br style="clear:right"/></small>
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
