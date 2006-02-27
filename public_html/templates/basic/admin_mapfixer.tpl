{assign var="page_title" value="Map Fixer"}
{include file="_std_begin.tpl"}

{dynamic}
<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : {if $gridref_ok}<a href="/admin/mapfixer.php">{/if}Map Fixer</a></h2>

{if !$gridref}
{foreach from=$unknowns item=unknown}
	{if strlen($unknown.grid_reference) == 6}
		{assign var="gbtofix" value="1"}
	{/if}
{/foreach}
{/if}
{if $gridref_ok || $gbtofix}
<div style="border:2px silver solid;background:#eeeeee;padding:10px;">

<div>This instant map updater requires no screen refresh to work - simply
check the OS map and vote on the land percentage{if $gbtofix} and a new square will
be opened up for processing{/if}.</div>

<span id="landvote" style="display:none">
Land percent for <span id="voteref"></span>&nbsp; is 
<input type="button" value="00" onclick="setland(0)">
<input type="button" value="01" onclick="setland(1)">
<input type="button" value="05" onclick="setland(5)">
<input type="button" value="10" onclick="setland(10)">
<input type="button" value="25" onclick="setland(25)">
<input type="button" value="50" onclick="setland(50)">
<input type="button" value="75" onclick="setland(75)">
<input type="button" value="100" onclick="setland(100)">
<input type="button" value="skip" onclick="shownext()">
</span>
<div id="voteinfo"></div>

</div>

<script language="javascript">

var aTodo=new Array();
var aTodoLat=new Array();
var aTodoLong=new Array();
{if $gridref}
	aTodo[aTodo.length]='{$gridref}';
{else}
{foreach from=$unknowns item=unknown}
	{if strlen($unknown.grid_reference) == 6 || $unknown.lat}
		{if $unknown.lat}
			aTodoLat[aTodo.length] = {$unknown.lat};
			aTodoLong[aTodo.length] = {$unknown.long};
		{/if}
		aTodo[aTodo.length]='{$unknown.grid_reference}';
	{/if}
{/foreach}
{/if}

var current=-1;

{literal}

var create_new_xmlhttp=true;
var xmlhttp=false;

function getXMLRequestObject()
{
	if (create_new_xmlhttp==false)
		return xmlhttp;
		
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
	  xmlhttp = new XMLHttpRequest();
	}

	return xmlhttp;
}

function shownext()
{
	var vote=document.getElementById('landvote');
	var gridref=document.getElementById('voteref');
	
	current++;
	if (current<aTodo.length)
	{
		var gr4=new String(aTodo[current]);
		gridref.innerHTML=gr4;

		if (aTodoLat[current] > 0) {
			window.open('http://www.multimap.com/p/browse.cgi?scale=25000&lon='+aTodoLong[current]+'&lat='+aTodoLat[current]+'&GridE='+aTodoLong[current]+'&GridN='+aTodoLat[current],'multimap');
		} else {
			popupOSMap(gr4);
		}

		vote.style.display="";
	}
	else
	{
		voteinfo.innerHTML=voteinfo.innerHTML+"<br/>--no more squares to do--";
		vote.style.display="none";
	}
	
}


function onMapUpdateComplete()
{
	if (xmlhttp.readyState==4) 
	{
		var voteinfo=document.getElementById('voteinfo');
		voteinfo.innerHTML=xmlhttp.responseText;
		
		shownext();
	}
}


function setland(percent)
{
	var gr4=new String(aTodo[current]);
	var url="/admin/mapfixer.php?save=quick&gridref="+gr4+"&percent_land="+percent;
	
	//make the request
	var req=getXMLRequestObject();
	
	req.onreadystatechange=onMapUpdateComplete;
	req.open("GET", url,true);
	req.send(null)


}

//kick off
shownext();

{/literal}
</script>
{/if}
<h3>Manual Update</h3>
<p>To update a specific square, use this form...</p>
<form method="get" action="mapfixer.php">
<label for="gridref">Grid Reference</label>
<input type="text" size="6" name="gridref" id="gridref" value="{$gridref|escape:'html'}">
<span class="formerror">{$gridref_error}</span>
<input type="submit" name="show" value="Check">

{if $gridref_ok}
<br/>{getamap gridref=$gridref text="Check OS Map for $gridref"}<br/><br/>

Land percentage for {$gridref} is
<input type="text" size="3" name="percent_land" value="{$percent_land}">
<input type="submit" name="save" value="Save">
<br/>{$status}
{/if}


</form>


<h3>System created squares</h3>    
<p>The following squares were created by the system when someone tried to view or 
submit a square within 2km of an existing one - click one each one to update its
land percentage. The "instant updater" at the top of the page provides a far
quicker process though!</p>

{if $unknowns}
<ul>
{foreach from=$unknowns item=unknown}
<li><a href="mapfixer.php?gridref={$unknown.grid_reference}">{$unknown.grid_reference} ({$unknown.imagecount} images)</li>
{/foreach}
</ul>
{else}

<p><i>None found!</i></p>

{/if}

{/dynamic}    
{include file="_std_end.tpl"}
