<!doctype html public "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
	<head>
		<title>Finding images... :: Geograph British Isles</title>
	</head>
	<frameset rows="100, *">
		{dynamic}
		<frame src="{$script_name}?id={$search_id}&amp;mode=top" name="top" noresize="noresize"  frameborder="0">
		<frame src="/search.php" name="mainframe">
		{/dynamic}
		<noframes>
			<p>You need frames to view this content/>
		</noframes>
	</frameset>
</html>
