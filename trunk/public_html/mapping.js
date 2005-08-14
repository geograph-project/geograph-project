var IE = document.all?true:false;

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
//	grlen = 3;
//	mult = 100;
//} else if (mapb > 50) {
	grlen = 4;
	mult = 10;
//} else {
//	grlen = 5;
//	mult = 1;
//}

var w2 = mapw / 2;
var h2 = maph / 2;

var ratw = (mapb / w2) * 1000;
var rath = (mapb / h2) * 1000;

function moprocess(e) {
	if (IE) {
		tempX = event.offsetX;
		tempY = event.offsetY;
	} else {
		tempX = e.layerX
		tempY = e.layerY
	}
	if (document.theForm.fmp.checked || document.theForm.fmp2.checked) {
		if (document.getElementById) {
			markerdiv = document.getElementById("marker") ;
			markerdiv.style.left = (tempX - 8)+'px';
			markerdiv.style.top = (tempY - 8)+'px';
		}
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
		if (document.theForm.fmp.checked) {
			if (document.theForm.gridreference)
				document.theForm.gridreference.value = "-Invalid-";
			if (document.theForm.e) {
				document.theForm.e.value = "-Invalid-";
				document.theForm.n.value = "-Invalid-";
			}
		}
		if (document.theForm.fmp2.checked) {
			if (document.theForm.viewpoint_gridreference)
				document.theForm.viewpoint_gridreference.value = "-Invalid-";
		}
		document.images['map'].alt = "-Invalid Grid Ref-"
	} else {
		grstr = GBGridLetters[cenXblock][cenYblock] + cenXhun + cenYhun;
		if (document.theForm.fmp.checked) {
			if (document.theForm.gridreference)
				document.theForm.gridreference.value = grstr;
			if (document.theForm.e) {
				document.theForm.e.value = cenXblock + '' + (cenXhun * mult);
				document.theForm.n.value = cenYblock + '' + (cenYhun * mult);
			}
			document.images['map'].alt = grstr + "\n\nClick to fix this Location";
		} else if (document.theForm.fmp2.checked) {
			if (document.theForm.viewpoint_gridreference)
				document.theForm.viewpoint_gridreference.value = grstr;
			document.images['map'].alt = grstr + "\n\nClick to fix this Location";
		} else {
			document.images['map'].alt = grstr + "\n\nClick TWICE to fix this Location";
		}
		
	}


}

function moclick() {
	if (document.theForm.fmp2.checked) {
		document.theForm.fmp2.checked = false;
		if (document.getElementById) {
			messagediv = document.getElementById("message2");
			messagediv.innerHTML = '';
			messagediv = document.getElementById("message");
			messagediv.innerHTML = '(click map to toggle)';
		}
	} else {
		document.theForm.fmp.checked = !document.theForm.fmp.checked ;
	}
	document.images['map'].alt = '';
}

function fmp2click(that) {
	if (document.getElementById && that.checked) {
		messagediv = document.getElementById("message2");
		messagediv.innerHTML = '(click map to toggle)';
		messagediv = document.getElementById("message");
		messagediv.innerHTML = '';
	}
}

function check_step2(that_form) {
	GridRef = /\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/;
	
	if (that_form.gridreference.value.length > 0 && !GridRef.test(that_form.gridreference.value)) {
		alert("please enter a valid subject grid reference");
		that_form.gridreference.focus();
		return false;
	}
	if (that_form.viewpoint_gridreference.value.length > 0 && !GridRef.test(theForm.viewpoint_gridreference.value)) {
		alert("please enter a valid photographer grid reference");
		that_form.viewpoint_gridreference.focus();
		return false;
	}
	return true;
}