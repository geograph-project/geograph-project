function popupOSMap(gridref)
{
        var wWidth = 740;
        var wHeight = 520;
        var wLeft = Math.round(0.5 * (screen.availWidth - wWidth));
        var wTop = Math.round(0.5 * (screen.availHeight - wHeight)) - 20;
        if (gridref.length > 0) {
        	if (gridref.length < 7) {
			gridref = gridref.substr(0,gridref.length-2)+'5'+gridref.substr(gridref.length-2,2)+'5';
		}
	var newWin = window.open('http://getamap.ordnancesurvey.co.uk/getamap/frames.htm?mapAction=gaz&gazName=g&gazString='+gridref, 
		'gam',
		'left='+wLeft+',screenX='+wLeft+',top='+wTop+',screenY='+wTop+',width='+wWidth+',height='+wHeight+',status,scrolling=no');
	} else {
	var newWin = window.open('http://getamap.ordnancesurvey.co.uk/getamap/frames.htm', 
		'gam',
		'left='+wLeft+',screenX='+wLeft+',top='+wTop+',screenY='+wTop+',width='+wWidth+',height='+wHeight+',status,scrolling=no');
	}
}


function autoDisable(that) {
 	that.value = "Submitting... Please wait...";
 	name = "document."+that.form.name+"."+that.name;
  
 	setTimeout(name+".disabled = true",100); //if we disable in the function then form wont submit
 	setTimeout(name+".value="+name+".defaultValue; "+name+".disabled = false",30000);
 	return true;
}

function di20(id, newSrc) {
    var theImage = FWFindImage(document, id, 0);
    if (theImage) {
        theImage.src = newSrc;
    }
}

function FWFindImage(doc, name, j) {
    var theImage = false;
    if (doc.getElementById) {
    	theImage = doc.getElementById(name);
    }
    if (theImage) {
	    return theImage;
	}
   
    
    if (doc.images) {
        theImage = doc.images[name];
    }
    if (theImage) {
        return theImage;
    }
   
   if (doc.layers) {
        for (j = 0; j < doc.layers.length; j++) {
            theImage = FWFindImage(doc.layers[j].document, name, 0);
            if (theImage) {
                return (theImage);
            }
        }
    }
    return (false);
}


function setdate(name,date,form) {
	parts = date.split('-');
	parts[2] = parseInt(parts[2],10);
	parts[1] = parseInt(parts[1],10);
	ele = form.elements[name+'Year'].options;
	for(i=0;i<ele.length;i++) 
		if (ele[i].value == parts[0]) 
			ele[i].selected = true;
	ele = form.elements[name+'Month'].options;
	for(i=0;i<ele.length;i++) 
		if (parseInt(ele[i].value,10) == parts[1]) 
			ele[i].selected = true;
	ele = form.elements[name+'Day'].options;
	for(i=0;i<ele.length;i++) 
		if (parseInt(ele[i].value,10) == parts[2]) 
			ele[i].selected = true;
}


function onChangeImageclass()
{
	if (document.getElementById('otherblock')) {
		var sel=document.getElementById('imageclass');
		var idx=sel.selectedIndex;

		var isOther=idx==sel.options.length-1

		var otherblock=document.getElementById('otherblock');
		otherblock.style.display=isOther?'':'none';
	}
}

function unescapeHTML_function() {
	var div = document.createElement('div');
	div.innerHTML = this;
	return div.childNodes[0] ? div.childNodes[0].nodeValue : '';
}
function fakeUnescapeHTML_function() {
	return this;
}

if (document.getElementById) {
	String.prototype.unescapeHTML = unescapeHTML_function;
} else {
	String.prototype.unescapeHTML = fakeUnescapeHTML_function;
}

function populateImageclass() 
{
	var sel=document.getElementById('imageclass');
	var opt=sel.options;
	var idx=sel.selectedIndex;
	var idx_value = null;
	if (idx > 0)
		idx_value = opt[idx].value;
	var first_opt = new Option(opt[0].text,opt[0].value);
	var last_opt = new Option(opt[opt.length-1].text,opt[opt.length-1].value);

	//clear out the options
	for(q=opt.length;q>=0;q=q-1) {
		opt[q] = null;
	}
	opt.length = 0; //just to confirm!

	//re-add the first
	opt[0] = first_opt;

	newselected = -1;
	//add the whole list
	for(i=0; i < catList.length; i++) {
		if (catList[i].length > 0) {
			act = catList[i].unescapeHTML();
			var newoption = new Option(act,act);
			if (idx_value == act) {
				newoption.selected = true;
				newselected = opt.length;
			}
			opt[opt.length] = newoption;
		}
	}

	//if our value is not found then use other textbox!
	if (newselected < 1 && idx_value != null) {
		var selother=document.getElementById('imageclassother');
		selother.value = idx_value;
		idx_value = 'Other';
	} else {
		sel.selectedIndex = newselected;
	}

	//re add the other option
	opt[opt.length] = last_opt;
	if (idx_value != null && idx_value == 'Other')
		sel.selectedIndex=opt.length-1;

	onChangeImageclass();
}

var hasloaded = false;
function prePopulateImageclass() {
	if (!hasloaded) {
		var sel=document.getElementById('imageclass');
		//sel.disabled = true;
		var oldText = sel.options[0].text;
		sel.options[0].text = "please wait...";
		
		populateImageclass();
		
		hasloaded = true;
		sel.options[0].text = oldText;
		//sel.disabled = false;
	}
}



function markImage(image) {
	current = readCookie('markedImages');
	if (current) {
		newCookie = current + ',' + image;
	} else {
		newCookie = image.toString();
	}
	
	createCookie('markedImages',newCookie,10);
	
	ele = document.getElementById('mark'+image);
	if(document.all) {
	    ele.innerText = 'marked';
	} else {
	    ele.textContent = 'marked';
	}
}

function displayMarkedImages() {
	current = readCookie('markedImages');
	if (current) {
		splited = current.split(',');
		newstring = '[[['+splited.join(']]] [[[')+']]]';
		prompt("Copy and Paste the following into the forum",newstring);
	} else {
		alert("You haven't marked any images yet. Or cookies are disabled");
	}
}

function showMarkedImages() {
	current = readCookie('markedImages');
	if (current) {
		splited = current.split(',');
		
		for(i=0; i < splited.length; i++) 
			if (document.getElementById('mark'+splited[i])) {
				ele = document.getElementById('mark'+splited[i])
				if(document.all) {
				    ele.innerText = 'marked';
				} else {
				    ele.textContent = 'marked';
				}
			}
	} 
}


function clearMarkedImages() {
	current = readCookie('markedImages');
	if (current) {
		splited = current.split(',');
		
		for(i=0; i < splited.length; i++) 
			if (document.getElementById('mark'+splited[i])) {
				ele = document.getElementById('mark'+splited[i])
				if(document.all) {
				    ele.innerText = 'Mark';
				} else {
				    ele.textContent = 'Mark';
				}
			}
	} 
	eraseCookie('markedImages');
	alert('All images removed from your list');
}


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
		var pair = ca[i].split('=');
		var c = pair[0];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c == name) return pair[1];
	}
	return false;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}