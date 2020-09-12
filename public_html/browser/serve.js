/**
 * Code for the Geograph Playground - to interface with Geograph APIs
 *
 * This file copyright (c)2011 barry Hunter (geo@barryhunter.co.uk)
 *

 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 * 
 */
	
	function _get_gridsquare(gridref,callback) {

		if (gridref.length > 5) {
			GridRef = /\b([a-zA-Z]{2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/;
			match = GridRef.exec(gridref);
			var numbers= match[2]+''+match[3];
			if (numbers.length % 2 == 0) {
				halve = numbers.length /2;
				easting = numbers.substr(0, 2);
				northing = numbers.substr(halve, 2);
				gridref = match[1].toUpperCase()+easting+northing;
			}
		}

		var url = "//api.geograph.org.uk/api/Gridref/"+encodeURIComponent(gridref)+"?output=json&callback=?";

                _get_url(url,callback);
	}

	function _get_textsearch(query,callback,location,per) {
                var url = "//www.geograph.org.uk/syndicator.php?text="+encodeURIComponent(query)+"&format=JSON&callback=?";
		if (location && location.length > 0) {
			url = url + "&location="+encodeURIComponent(location);
		}
		if (per && per.length > 0) {
			url = url + "&perpage="+parseInt(per,10);
		}
		_get_url(url,callback);
	}

	function _get_tags(query,method,callback) {
		if (!method)
			method = 'tags/tags';

                var url = "//www.geograph.org.uk/"+method+".json.php?q="+encodeURIComponent(query)+"&callback=?";

		_get_url(url,callback);
	}

	function _get_url(url,callback) {

                $.ajax({
                        url: url,
                        dataType: 'jsonp',
                        jsonpCallback: 'serveCallback',
			cache: true,
                        success: function(data) {
                                callback(data);
                        }
                });

	}

// function to allow using cors if possible
function _call_cors_api(endpoint,data,uniquename,success,error) {
  crossDomain = true; //todo/tofix!
  if (uniquename && crossDomain && !jQuery.support.cors) {
    //use a normal JSONP request - works accorss domain
    endpoint += (endpoint.indexOf('?')>-1?'&':'?')+"callback=?&";
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'jsonp',
      jsonpCallback: uniquename,
      cache: true,
      success: success,
      error: error
    });
  } else {
    //works as a json requrest - either same domain, or a browser with cors support
    $.ajax({
      url: endpoint,
      data: data,
      dataType: 'json',
      cache: true,
      success: success,
      error: error
    });
  }
}

	
	function _fullsize(thumbnail) {
		return thumbnail.replace(/_\d+x\d+\.jpg$/,'.jpg').replace(/s[1-9]\.geograph/,'s0.geograph');
	}



function load_for_thumbnail() {
	return (window.location.hash.indexOf('#autoload') > -1 || window.location.search.indexOf('autoload') > -1);
}
	
//	-	-	-	-	-	-	-	-

	function show_tree(id) {
		document.getElementById("show"+id).style.display='';
		document.getElementById("hide"+id).style.display='none';
	}
	function hide_tree(id) {
		document.getElementById("show"+id).style.display='none';
		document.getElementById("hide"+id).style.display='';
	}
	
//	-	-	-	-	-	-	-	-

function markImage(image) {
	current = readCookie('markedImages');
	newtext = 'marked';
	if (current) {
		re = new RegExp("\\b"+image+"\\b");
		if (current == image || current.search(re) > -1) {
			newCookie = current.replace(re,',').commatrim();
			newtext = 'Mark';
			$('a[href="'+geograph_domain+'/photo/'+image+'"] img').removeClass('marked');
		} else {
			newCookie = current + ',' + image;
			$('a[href="'+geograph_domain+'/photo/'+image+'"] img').addClass('marked');
		}
	} else {
		newCookie = image.toString();
		$('a[href="'+geograph_domain+'/photo/'+image+'"] img').addClass('marked');
	}

	createCookie('markedImages',newCookie,10);

	if (document.getElementById('marked_number')) {
		if (!newCookie) {//chrome needs this... 
			document.getElementById('marked_number').innerHTML = '[0]';
		} else {
			splited = newCookie.commatrim().split(',');
			document.getElementById('marked_number').innerHTML = '['+(splited.length+0)+']';
		}
	}

	if (ele = document.getElementById('mark'+image)) {
		if(ele.innerText != undefined) {
			ele.innerText = newtext;
		} else {
			ele.textContent = newtext;
		}
	}
	
	var i=new Image();
	id = encodeURIComponent(image);
	i.src= geograph_domain+"/stuff/record_mark.php?id="+id;
}

function markAllImages(str) {
	for(var q=0;q<document.links.length;q++) {
		if (document.links[q].text == str) {
			markImage(document.links[q].id.substr(4));
		}
	}
}

String.prototype.commatrim = function () {
	return this.replace(/^,+|,+$/g,"").replace(/,,/g,',');
}

function importToMarkedImages() {
	newCookie = readCookie('markedImages');
	if (!newCookie)
		newCookie = new String();
	list = prompt('Paste your current list, either comma or space separated\n or just surrounded with [[[ ]]] ','');
	if (list && list != '') {
		splited = list.split(/[^\d]+/);
		count=0;	
		for(i=0; i < splited.length; i++) {
			image = splited[i];
			if (image != '')
				if (newCookie.search(new RegExp("\\b"+image+"\\b")) == -1) {
					newCookie = newCookie + ',' + image;
					count=count+1;
				}
		}
		createCookie('markedImages',newCookie,10);
		showMarkedImages();
		leng = newCookie.commatrim().split(',').length;
		alert("Added "+count+" image(s) to your list, now contains "+leng+" images in total.");
	} else {
		alert("Nothing to add");
	}
}

function displayMarkedImages() {
	current = readCookie('markedImages');
	if (current) {
		splited = current.commatrim().split(',');
		newstring = '[[['+splited.join(']]] [[[')+']]]';
		prompt("Copy and Paste the following, for example for use in the forum, or an article/blog post.",newstring);
	} else {
		alert("You haven't marked any images yet. Or cookies are disabled");
	}
}

function returnMarkedImages() {
	current = readCookie('markedImages');
	if (current) {
		splited = current.commatrim().split(',');
		return '[[['+splited.join(']]] [[[')+']]]';
	} else {
		alert("You haven't marked any images yet. Or cookies are disabled");
		return '';
	}
}

function showMarkedImages() {
	current = readCookie('markedImages');
	if (current) {
		splited = current.commatrim().split(',');
		
		var hasInnerText = (document.getElementsByTagName('body')[0].innerText != undefined)?true:false;
		
		for(i=0; i < splited.length; i++) 
			if (document.getElementById('mark'+splited[i])) {
				ele = document.getElementById('mark'+splited[i])
				if(hasInnerText) {
				    ele.innerText = 'marked';
				} else {
				    ele.textContent = 'marked';
				}
			}
		if (document.getElementById('marked_number')) {
			document.getElementById('marked_number').innerHTML = '['+(splited.length+0)+']';
		}
	} 
}


function clearMarkedImages() {
	current = readCookie('markedImages');
	if (current && confirm('Are you sure?')) {
		splited = current.commatrim().split(',');

		var hasInnerText = (document.getElementsByTagName('body')[0].innerText != undefined)?true:false;
		
		for(i=0; i < splited.length; i++) 
			if (document.getElementById('mark'+splited[i])) {
				ele = document.getElementById('mark'+splited[i])
				if(hasInnerText) {
				    ele.innerText = 'Mark';
				} else {
				    ele.textContent = 'Mark';
				}
			}
		eraseCookie('markedImages');
		if (!markedfilter)
			alert('All images removed from your list');
		if (document.getElementById('marked_number')) {
			document.getElementById('marked_number').innerHTML = '[0]';
		}
	} 
}

//	-	-	-	-	-	-	-	-

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var pos = ca[i].indexOf("=");
		var argname = ca[i].substring(0,pos);

		while (argname.charAt(0)==' ') argname = argname.substring(1,argname.length);
		if (argname == name) return ca[i].substring(pos+1);
	}
	return false;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}

//	-	-	-	-	-	-	-	-

//*** This code is copyright 2003 by Gavin Kistner, gavin@refinery.com
//*** It is covered under the license viewable at http://phrogz.net/JS/_ReuseLicense.txt
//*** Reuse or modification is free provided you abide by the terms of that license.
//*** (Including the first two lines above in your source code satisfies the conditions.)

//***Cross browser attach event function. For 'evt' pass a string value with the leading "on" omitted
//***e.g. AttachEvent(window,'load',MyFunctionNameWithoutParenthesis,false);

function AttachEvent(obj,evt,fnc,useCapture){
	if (!useCapture) useCapture=false;
	if (obj.addEventListener){
		obj.addEventListener(evt,fnc,useCapture);
		return true;
	} else if (obj.attachEvent) return obj.attachEvent("on"+evt,fnc);
	else{
		MyAttachEvent(obj,evt,fnc);
		obj['on'+evt]=function(){ MyFireEvent(obj,evt) };
	}
} 

//The following are for browsers like NS4 or IE5Mac which don't support either
//attachEvent or addEventListener
function MyAttachEvent(obj,evt,fnc){
	if (!obj.myEvents) obj.myEvents={};
	if (!obj.myEvents[evt]) obj.myEvents[evt]=[];
	var evts = obj.myEvents[evt];
	evts[evts.length]=fnc;
}
function MyFireEvent(obj,evt){
	if (!obj || !obj.myEvents || !obj.myEvents[evt]) return;
	var evts = obj.myEvents[evt];
	for (var i=0,len=evts.length;i<len;i++) evts[i]();
}


