<html>
<head>
<title>Grouper</title>
<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
</head>
<body style="background-color:#FFFFFF" text="#000000">

{dynamic}

{if $title}
	<h2>Content: {$source}</h2>
	
	<script type="text/javascript">
		window.open("{$url|escape:'html'}",'_main');
	</script>
	<p><a href="{$url|escape:'html'}" target="_main">{$title|escape:'html'}</a></p>
	
	<form action="{$script_name}" target="_self" method="post">
	<input type="hidden" name="content_id" value="{$content_id}"/>
	
	<p><b>Groups</b>: (one per line)<br/>
	<textarea name="groups" rows="6" style="width:100%" wrap="off"></textarea></p>
	<input type="submit" name="save" value="Save"/>
	
	<p><b>Current Groups</b>: (click to use)<br/>
	<select size="20" onchange="this.form.groups.value = this.form.groups.value + this.options[this.selectedIndex].value + '\n'; this.selectedIndex = -1">
		{html_options options=$groups} 
	</select>
	
	
	</form>
	
	
{else}
	<a href="/content/grouper.php?start" target="_search" rel="nofollow">Click to start</a>
{/if}

{/dynamic}

	
</body>
</html>
