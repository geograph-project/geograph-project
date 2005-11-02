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
