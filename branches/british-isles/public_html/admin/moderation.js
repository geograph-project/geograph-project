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

var remoderate = false;
var doneids = new Object();

Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

function getXMLRequestObject()
{
	var xmlhttp=false;
		
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



function moderateImage(gridimage_id, status, callback)
{
	var url="/admin/moderation.php?gridimage_id="+gridimage_id+"&status="+status;
	if (remoderate)
		url=url+"&remoderate=1";
	
	if (status == 'rejected') {
		comment = prompt("Please leave a comment to explain the reason for rejecting this image.",'');
		if (comment.length > 1) {
			url=url+"&comment="+escape(comment);
		} else {
			return false;
		}
	}
	
	//make the request
	var req=getXMLRequestObject();
	
	//need to exploit function closure
	req.onreadystatechange = function()
	{
		if (req.readyState==4) 
		{
			var divInfo=document.getElementById('modinfo'+gridimage_id);
			divInfo.innerHTML=req.responseText;

			if (callback)
				callback(req.responseText);

			if (document.forms['counter']) {
				var f = document.forms['counter'];
				doneids[gridimage_id]=1;
				f.elements['done'].value = Object.size(doneids);
				if (f.elements['done'].value == f.elements['total'].value) {
					f.style.backgroundColor = 'lightgreen';
					if (document.getElementById("continueButton")) {
						document.getElementById("continueButton").style.display='';	
					}
				}
				f.style.display='';
			}

			//patch the memory leak
			req.onreadystatechange = function() {};
		}
	}
	req.open("GET", url,true);
	req.send(null)


}

function deferTicket(gridimage_ticket_id, hours)
{
	var url="/admin/suggestions.php?gridimage_ticket_id="+gridimage_ticket_id+"&defer=do&hours="+hours;
	
	//make the request
	var req=getXMLRequestObject();
		
	//need to exploit function closure
	req.onreadystatechange = function()
	{
		if (req.readyState==4) 
		{
			var divInfo=document.getElementById('modinfo'+gridimage_ticket_id);
			divInfo.innerHTML=req.responseText;
		
			//patch the memory leak
			req.onreadystatechange = function() {};
		}
	}
	req.open("GET", url,true);
	req.send(null)
}



	function toggleButton(that) {
		if (that.className == 'toggle on') {
			that.className = 'toggle';
		} else {
			that.className = 'toggle on';
		}
		var gridimage_id = parseInt(that.id.replace(/[a-z]+/,''),10);

		document.getElementById('continue'+gridimage_id).value = (getStatus(gridimage_id) == 'geograph')?'Geograph':'Accept';

		if (!remoderate) 
			submitModTag(gridimage_id,"type:"+that.value,(that.className == 'toggle on')?2:0);
	}

	function getStatus(gridimage_id) {
		var status = 'geograph';
		if (document.getElementById('cross'+gridimage_id).className.indexOf(' on') > -1)
			status = 'accepted';
		if (document.getElementById('aerial'+gridimage_id).className.indexOf(' on') > -1)
			status = 'accepted';
		if (document.getElementById('inside'+gridimage_id).className.indexOf(' on') > -1)
			status = 'accepted';
		if (document.getElementById('detail'+gridimage_id).className.indexOf(' on') > -1)
			status = 'accepted';
		return status;
	}


	function submitModTag(gridimage_id,tag,status) {
		var data = new Object;
		data['gridimage_id'] = gridimage_id;
		data['tag'] = tag;
		data['status'] = status;
		data['mod'] = 1;

		$.ajax({
			url: "/tags/tagger.json.php",
			data: data
		});
	}
