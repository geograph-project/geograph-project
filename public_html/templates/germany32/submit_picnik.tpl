{assign var="page_title" value="Submit to Geograph via Picnik"}
{include file="_std_begin.tpl"}
	<h2>Submit Image via Picnik</h2>

{dynamic}
{if $_post}
	<form action="{$script_name}" method="post" name="theForm" target="_top">
		{foreach from=$_post key=key item=value}
			<input type="hidden" name="{$key}" value="{$value|escape:"html"}"/>
		{/foreach}
		<p>If you do not continue automatically then <input type="submit" value="click here"/></p>
	</form>
	
	{literal}
	<script language="JavaScript" type="text/javascript">

		function closePicnik() {
			document.theForm.submit();
		}
		AttachEvent(window,'load',closePicnik,false);

	</script>
	{/literal}{else}
 <link rel="stylesheet" href="http://www.picnik.com/css/picnikbox.css" media="screen" type="text/css" />
 <script type="text/javascript" src="http://www.picnik.com/js/picnikbox.js"></script>

	
		<p>If picnik does not open automatically then <a class="pbox" href="{$picnik_url}" id="plink">click here</a></p>
	
	{literal}
	<script language="JavaScript" type="text/javascript">

		function openPicnik() {
			el = document.getElementById('plink');
			var mypbox = new picnikbox(el);
			mypbox.activate();
		}
		
		function openPicnikStarter() {
			setTimeout("openPicnik()",100);
		}
		
		AttachEvent(window,'load',openPicnikStarter,false);

	</script>
	{/literal}
{/if}
{/dynamic}

{include file="_std_end.tpl"}
