/**
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * See http://geograph.sourceforge.net for more information
 *
 */


var last_id=-1;
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

var last_id=0;

function onModerationCompleted()
{
	if (xmlhttp.readyState==4) 
	{
		//alert(xmlhttp.responseText);
		var divInfo=document.getElementById('modinfo'+last_id);
		divInfo.innerHTML=xmlhttp.responseText;
	}
}


function moderateImage(gridimage_id, is_ok)
{
	var url="/admin/moderation.php?gridimage_id="+gridimage_id+"&is_ok="+is_ok;
	
	//make the request
	var req=getXMLRequestObject();
	
	last_id=gridimage_id;
	
	req.onreadystatechange=onModerationCompleted;
	req.open("GET", url,true);
	req.send(null)


}

