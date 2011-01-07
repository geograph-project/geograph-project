{assign var="page_title" value="Edit with Picnik"}
{include file="_std_begin.tpl"}
	

{dynamic}
{if $_post}
<h2>Save from Picnik</h2>
	<form action="{$script_name}" method="post" name="theForm" target="_top">
		{foreach from=$_post key=key item=value}
			<input type="hidden" name="{$key}" value="{$value|escape:"html"}"/>
		{/foreach}

{if $error}
<h2><span class="formerror">Changes not submitted - check and correct errors below...<br/>{$error}</span></h2>
{/if}

<table><tr><td>
<textarea id="updatenote" name="updatenote" rows="5" cols="60">{$updatenote|escape:'html'}</textarea>
</td><td>

<div style="float:left;font-size:0.7em;padding-left:5px;width:250px;">
	Please provide a brief note explaining why you have updated this image.
</div>

</td></tr></table>



<input type="submit" name="save" value="Submit Changes" onclick="autoDisable(this)"/>
<input type="button" name="cancel" value="Cancel" onclick="document.location='/photo/{$image->gridimage_id}';"/>


	</form>
	

{else}
 <link rel="stylesheet" href="http://www.picnik.com/css/picnikbox.css" media="screen" type="text/css" />
 <script type="text/javascript" src="http://www.picnik.com/js/picnikbox.js"></script>
<h2>Edit with Picnik</h2>
	
		<p>If Picnik does not open automatically then <a class="pbox" href="{$picnik_url}" id="plink">click here</a></p>
	
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
