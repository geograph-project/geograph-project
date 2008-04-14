{assign var="page_title" value="Map Fixer"}
{include file="_std_begin.tpl"}

{dynamic}
<h2><a title="Admin home page" href="/admin/index.php">Admin</a> : {if $gridref_ok}<a href="/admin/mapfixer.php">{/if}Map Fixer</a></h2>

{if $gridref_ok || count($unknowns)}
<div style="border:2px silver solid;background:#eeeeee;padding:10px;">

<div>This instant map updater requires no screen refresh to work - simply
check the OS Map/Google Earth and vote on the land percentage{if $tofix} and a new square will
be opened up for processing{/if}. Note: When the square is substantial Mud-flats, please halve the estimate.</div>

<span id="landvote" style="display:none">
Land percent for <span id="voteref"></span>&nbsp; is 
<input type="button" value="00" onclick="setland(0)" style="padding:5px">
<input type="button" value="01" onclick="setland(1)" style="padding:5px">
<input type="button" value="05" onclick="setland(5)" style="padding:5px">
<input type="button" value="10" onclick="setland(10)" style="padding:5px">
<input type="button" value="25" onclick="setland(25)" style="padding:5px">
<input type="button" value="50" onclick="setland(50)" style="padding:5px">
<input type="button" value="75" onclick="setland(75)" style="padding:5px">
<input type="button" value="100" onclick="setland(100)" style="padding:5px">
<input type="button" value="skip" onclick="shownext()" style="padding:5px">
</span>
<div id="voteinfo"></div>

</div>
<div style="font-size:0.7em">Great Britain open in Get-a-Map, Ireland opens our 'Location' page which displays Google Maps.</div>

<script language="javascript">

var aTodo=new Array();
{if $gridref}
	aTodo[aTodo.length]='{$gridref}';
{else}
{foreach from=$unknowns item=unknown}
	aTodo[aTodo.length]='{$unknown.grid_reference}';
{/foreach}
{/if}

var current=-1;
var currentgr;

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
		currentgr = gr4;

		if (gr4.length == 5) {
			var wWidth = 740;
			var wHeight = 520;
			var wLeft = Math.round(0.5 * (screen.availWidth - wWidth));
			var wTop = Math.round(0.5 * (screen.availHeight - wHeight)) - 20;
			window.open('/location.php?gridref='+gr4,'gam',
			'left='+wLeft+',screenX='+wLeft+',top='+wTop+',screenY='+wTop+',width='+wWidth+',height='+wHeight+',status,scrolling=no');
		} else {
			popupOSMap(gr4,'');
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
		if (currentgr)
			document.getElementById('a'+currentgr).style.color = '#dddddd';
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
<li><a href="mapfixer.php?gridref={$unknown.grid_reference}" id="a{$unknown.grid_reference}">{$unknown.grid_reference} ({$unknown.imagecount} images)</li>
{/foreach}
</ul>
{else}

<p><i>None found!</i></p>

{/if}

{/dynamic}    
{include file="_std_end.tpl"}
