<html>
<head>
<title>Submit Image</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" type="text/css" title="Monitor" href="{"/templates/basic/css/basic.css"|revision}" media="screen" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico"/>
<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
<style>{literal}
	form {
		text-align:center;
	}
	button {
		display:block;
		width:100%;
		max-width:400px;
		padding:10px;
		margin-bottom:30px;
		margin-left:auto;
		margin-right:auto;
	}
{/literal}</style>
</head>
<body style="background-color:#e4e4fc">

<div style="background-color:#000066">
<a target="_top" href="https://www.geograph.org.uk/"><img src="{$static_host}/templates/basic/img/logo.gif" height="50"></a>
</div>

	{dynamic}
		{if $id}
			<b>Submission Successful</b><br>
			ID: <a href="/photo/{$id}" target=_blank>{$id}</a>
			<hr>
			Submit another image below... (or <a href="/">return to homepage</a>)
			<hr>
		{/if}
	{/dynamic}


<form method=post action="/submit-mobile.php">
<br>
Choose submission method:

<button type=submit name=choose value=single>Submit a Single Image (new!)</button>

(upload in bulk now, and submit later on desktop)
<button type=submit name=choose value=multi>Upload a Batch of Images</button>

<br><br>

You can also still use the original desktop processes, but they dont work very well on mobile

<button type=submit name=choose value=v1>Normal Submit V1</button>


<button type=submit name=choose value=v2>Normal Submit V2</button>

<hr>

<input type=checkbox name=save id=save> <label for="save">Save as Default</label> (will automatically goto that method in future)

</form>








<hr>
- <a href="https://www.geograph.org.uk/">Homepage</a>
- <a href="https://www.geograph.org.uk/help/submit">View Desktop Page</a>
- <a href="https://www.geograph.org.uk/help/submit" style=white-space:nowrap>read more about above methods</a>

</body>
</html>

