var IE = document.all?true:false;

if (IE) {
document.body.ondragstart = function() {event.returnValue = false;};
}

// These arrays hold the valid 100km references
var GBGridLetters = new Array (2);

GBGridLetters[0] = ["SV", "SQ", "SL", "SF", "SA", "NV", "NQ", "NL", "NF", "NA", "HV", "HQ", "HL", "HF", "HA"];
GBGridLetters[1] = ["SW", "SR", "SM", "SG", "SB", "NW", "NR", "NM", "NG", "NB", "HW", "HR", "HM", "HG", "HB"];
GBGridLetters[2] = ["SX", "SS", "SN", "SH", "SC", "NX", "NS", "NN", "NH", "NC", "HX", "HS", "HN", "HH", "HC"];
GBGridLetters[3] = ["SY", "ST", "SO", "SJ", "SD", "NY", "NT", "NO", "NJ", "ND", "HY", "HT", "HO", "HJ", "HD"];
GBGridLetters[4] = ["SZ", "SU", "SP", "SK", "SE", "NZ", "NU", "NP", "NK", "NE", "HZ", "HU", "HP", "HK", "HE"];
GBGridLetters[5] = ["TV", "TQ", "TL", "TF", "TA", "OV", "OQ", "OL", "OF", "OA", "JV", "JQ", "JL", "JF", "JA"];
GBGridLetters[6] = ["TW", "TR", "TM", "TG", "TB", "OW", "OR", "OM", "OG", "OB", "JW", "JR", "JM", "JG", "JB"];
GBGridLetters[7] = ["TX", "TS", "TN", "TH", "TC", "OX", "OS", "ON", "OH", "OC", "JX", "JS", "JN", "JH", "JC"];
GBGridLetters[8] = ["TY", "TT", "TO", "TJ", "TD", "OY", "OT", "OO", "OJ", "OD", "JY", "JT", "JO", "JJ", "JD"];
GBGridLetters[9] = ["TZ", "TU", "TP", "TK", "TE", "OZ", "OU", "OP", "OK", "OE", "JZ", "JU", "JP", "JK", "JE"];

//if (mapb > 100) {
	grlen = 3;
	mult = 100;
//} else if (mapb > 50) {
//	grlen = 4;
//	mult = 10;
//} else {
//	grlen = 5;
//	mult = 1;
//}

var w2 = mapw / 2;
var h2 = maph / 2;

var ratw = (mapb / w2) * 1000;
var rath = (mapb / h2) * 1000;

var currentelement = null;

function overlayMouseUp(e) {
	if (currentelement != null) {
		if (currentelement.id == 'marker1') {
			if (document.theForm.gridreference.value == "") {
				currentelement.style.left = w2+'px';
				currentelement.style.top = h2+'px';
			}
		} else if (currentelement.id == 'marker2') {
			if (document.theForm.viewpoint_gridreference.value == "") {
				currentelement.style.left = 5+'px';
				currentelement.style.top = (maph + 5)+'px';
			}
		}
	}
	currentelement = null;
}

function overlayMouseDown(e) {
	if (currentelement != null) {
		//huh why we here?
		return;
	}
	
	if (IE) {
		tempX = event.offsetX;
		tempY = event.offsetY;
	} else {
		tempX = e.layerX
		tempY = e.layerY
	}
	
	var m1 = document.getElementById('marker1');
	
	m1left = parseInt(m1.style.left)+8;
	m1top = parseInt(m1.style.top)+8;
	
	if (Math.abs(tempX - m1left) < 10 && Math.abs(tempY - m1top) < 10) {
		currentelement = m1;
	} else {
		var m2 = document.getElementById('marker2');

		m2left = parseInt(m2.style.left)+8;
		m2top = parseInt(m2.style.top)+8;

		if (Math.abs(tempX - m2left) < 10 && Math.abs(tempY - m2top) < 10) {
			currentelement = m2;
		}
	}
	
	return false;
}

function overlayMouseMove(e) {
	if (IE) {
		tempX = event.offsetX;
		tempY = event.offsetY;
	} else {
		tempX = e.layerX
		tempY = e.layerY
	}
	if (currentelement != null) {
		if (document.getElementById) {
			currentelement.style.left = (tempX - 8)+'px';
			currentelement.style.top = (tempY - 8)+'px';
		}
	}

	if (tempY > maph) {
		if (currentelement != null) {
			if (currentelement.id == 'marker1') {
				if (document.theForm.gridreference)
					document.theForm.gridreference.value = "";
			} else if (currentelement.id == 'marker2') {
				if (document.theForm.viewpoint_gridreference)
					document.theForm.viewpoint_gridreference.value = "";
			}
		}
		document.images['map'].alt = "";
		return;
	}

	tempX = tempX - w2;
	tempY = tempY - h2;
	
	var easting = cene + Math.round(tempX * ratw);
	var northing = cenn - Math.round(tempY * rath);

	var cenXhun = "00000" + (easting % 100000);
	var cenYhun = "00000" + (northing % 100000);
	cenXhun = cenXhun.substr(cenXhun.length - 5, grlen);
	cenYhun = cenYhun.substr(cenYhun.length - 5, grlen);

	var cenXblock = Math.floor(easting / 100000);
	var cenYblock = Math.floor(northing / 100000);

	if(cenXblock < 0 || cenYblock < 0 || cenXblock > 9 || cenYblock > 15) {
		if (currentelement != null) {
			if (currentelement.id == 'marker1') {
				document.theForm.gridreference.value = "-Invalid-";
			} else if (currentelement.id == 'marker2') {
				document.theForm.viewpoint_gridreference.value = "-Invalid-";
			}
		}
		document.images['map'].alt = "-Invalid Grid Ref-"
	} else {
		grstr = GBGridLetters[cenXblock][cenYblock] + cenXhun + cenYhun;
		if (currentelement != null) {
			if (currentelement.id == 'marker1') {
				document.theForm.gridreference.value = grstr;
			} else if (currentelement.id == 'marker2') {
				document.theForm.viewpoint_gridreference.value = grstr;
			} 
		}
		document.images['map'].alt = grstr;
	}
}

function checkGridReferences(that_form) {
	if (!checkGridReference(that.gridreference,true)) 
		return false;
	if (!checkGridReference(that.viewpoint_gridreference,true)) 
		return false;
	return true;

} 

function checkGridReference(that,showmessage) {
	GridRef = /\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/;
	if (that.length > 0) {
		myArray = GridRef.exec(that.value);
		numbers = myArray[2]+myArray[3];
		if (numbers.length == 0 || !numbers.length % 2 != 0) {
			if (!showmessage) 
				return false;
			if (that.name == 'gridreference') {
				alert("please enter a valid subject grid reference");
			} else {
				alert("please enter a valid photographer grid reference");
			}
			that.focus();
			return false;
		}
	}
	return true;
}

function updateMapMarker(that,showmessage) {
	if (!checkGridReference(that,showmessage)) {
		return false;
	}
	if (!document.images['map']) {
		//we have no map! so we only wanted to check the GR
		return;
	}

	if (document.getElementById) {
		if (that.name == 'viewpoint_gridreference') {
			currentelement = document.getElementById('marker2');
		} else {
			currentelement = document.getElementById('marker1');
		}
		gr = that.value;

		GridRef = /\b([a-zA-Z]{2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/;

		if (gr.length > 5) {
			myArray = GridRef.exec(gr);
			letters = myArray[1];
			numbers = myArray[2]+myArray[3];
			if (numbers.length % 2 == 0) {
				halve = numbers.length /2;
				easting = numbers.substr(0, halve);
				northing = numbers.substr(halve, halve);

				var cenXhun = easting + "00000";
				var cenYhun = northing + "00000";
				cenXhun = cenXhun.substr(0, 5);
				cenYhun = cenYhun.substr(0, 5);

				var cenXblock = 0;
				letters = letters.toUpperCase();
				for(x=0;x<10;x++)
					for(y=0;y<15;y++)
						if (GBGridLetters[x][y] == letters) {
							cenXblock = x*100000;
							cenYblock = y*100000;
						}

				if (cenXblock > 0) {
					//we use parseFloat to avoid issues with 0 prefixed numbers!
					easting = parseInt(cenXblock) + parseFloat(cenXhun);
					northing = parseInt(cenYblock) + parseFloat(cenYhun);

					tempX = (easting - cene) / ratw;
					tempY = (cenn - northing) / rath;

					tempX = tempX + w2;
					tempY = tempY + h2;
					if (currentelement.id == 'marker2' && ( (tempX < 0) || (tempX > mapw) || (tempY < 0) || (tempY > maph) ) ) {
						currentelement.style.left = 5+'px';
						currentelement.style.top = (maph + 5)+'px';
					} else {
						currentelement.style.left = (tempX - 8)+'px';
						currentelement.style.top = (tempY - 8)+'px';
					}
				}
			}
		}
	}
	currentelement = null;
}