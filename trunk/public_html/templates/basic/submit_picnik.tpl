{assign var="page_title" value="Submit to Geograph via Picnik"}
{include file="_std_begin.tpl"}
 <link rel="stylesheet" href="http://www.picnik.com/css/picnikbox.css" media="screen" type="text/css" />
 <script type="text/javascript" src="http://www.picnik.com/js/picnikbox.js"></script>

	<h2>Submit Image via Picnik</h2>
	{dynamic}
		<p>If picnik does not open automatically then <a class="pbox" href="{$picnik_url}" id="plink">click here</a></p>
	{/dynamic}
	
	  {literal}
	  <script language="JavaScript" type="text/javascript">
	  
	  function openPicnik() {
	  	valid.activate();
	  }
	  AttachEvent(window,'load',openPicnik,false);
	  
	  </script>
  {/literal}
	
	
{include file="_std_end.tpl"}
