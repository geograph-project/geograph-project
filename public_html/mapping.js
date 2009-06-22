/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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
 */
 

 var IE = document.all?true:false;

if (IE) {
	document.body.ondragstart = function() {event.returnValue = false;};
	//mozilla based do this by returning false from onmousedown
}

if (ri == 1) {

//// FIXME <- germany
// These arrays hold the valid 100km references
var GridLetters = new Array (2);

GridLetters[0] = ["SV", "SQ", "SL", "SF", "SA", "NV", "NQ", "NL", "NF", "NA", "HV", "HQ", "HL", "HF", "HA"];
GridLetters[1] = ["SW", "SR", "SM", "SG", "SB", "NW", "NR", "NM", "NG", "NB", "HW", "HR", "HM", "HG", "HB"];
GridLetters[2] = ["SX", "SS", "SN", "SH", "SC", "NX", "NS", "NN", "NH", "NC", "HX", "HS", "HN", "HH", "HC"];
GridLetters[3] = ["SY", "ST", "SO", "SJ", "SD", "NY", "NT", "NO", "NJ", "ND", "HY", "HT", "HO", "HJ", "HD"];
GridLetters[4] = ["SZ", "SU", "SP", "SK", "SE", "NZ", "NU", "NP", "NK", "NE", "HZ", "HU", "HP", "HK", "HE"];
GridLetters[5] = ["TV", "TQ", "TL", "TF", "TA", "OV", "OQ", "OL", "OF", "OA", "JV", "JQ", "JL", "JF", "JA"];
GridLetters[6] = ["TW", "TR", "TM", "TG", "TB", "OW", "OR", "OM", "OG", "OB", "JW", "JR", "JM", "JG", "JB"];
GridLetters[7] = ["TX", "TS", "TN", "TH", "TC", "OX", "OS", "ON", "OH", "OC", "JX", "JS", "JN", "JH", "JC"];
GridLetters[8] = ["TY", "TT", "TO", "TJ", "TD", "OY", "OT", "OO", "OJ", "OD", "JY", "JT", "JO", "JJ", "JD"];
GridLetters[9] = ["TZ", "TU", "TP", "TK", "TE", "OZ", "OU", "OP", "OK", "OE", "JZ", "JU", "JP", "JK", "JE"];

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

gridly = 15;
gridlx = 10;
gridpreflen = 2;
griddele = 0;
griddeln = 0;
} else if (ri == 3) {
	var GridLetters = new Array (2);
	// --> north
	// |
	// V east
	GridLetters[0] = ["TKT", "UKU", "UKV", "UKA", "UKB", "UKC", "UKD", "UKE", "UKF", "UKG"];
	GridLetters[1] = ["TLT", "ULU", "ULV", "ULA", "ULB", "ULC", "ULD", "ULE", "ULF", "ULG"];
	GridLetters[2] = ["TMT", "UMU", "UMV", "UMA", "UMB", "UMC", "UMD", "UME", "UMF", "UMG"];
	GridLetters[3] = ["TNT", "UNU", "UNV", "UNA", "UNB", "UNC", "UND", "UNE", "UNF", "UNG"];
	GridLetters[4] = ["TPT", "UPU", "UPV", "UPA", "UPB", "UPC", "UPD", "UPE", "UPF", "UPG"];
	GridLetters[5] = ["TQT", "UQU", "UQV", "UQA", "UQB", "UQC", "UQD", "UQE", "UQF", "UQG"];

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

	gridly = 10
	gridlx = 6;
	gridpreflen = 3;
	griddele = 2;
	griddeln = 52;

} else if (ri == 4) {
	var GridLetters = new Array (2);
	// --> north
	// |
	// V east
	GridLetters[0] = ["TTN", "UTP", "UTQ", "UTR", "UTS", "UTT", "UTU", "UTV", "UTA", "UTB"];
	GridLetters[1] = ["TUN", "UUP", "UUQ", "UUR", "UUS", "UUT", "UUU", "UUV", "UUA", "UUB"];
	GridLetters[2] = ["TVN", "UVP", "UVQ", "UVR", "UVS", "UVT", "UVU", "UVV", "UVA", "UVB"];
	GridLetters[3] = ["TWN", "UWP", "UWQ", "UWR", "UWS", "UWT", "UWU", "UWV", "UWA", "UWB"];

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

	gridly = 10
	gridlx = 4;
	gridpreflen = 3;
	griddele = 2;
	griddeln = 52;

} else if (ri == 5) {
	var GridLetters = new Array (2);
	// --> north
	// |
	// V east
	GridLetters[0] = ["TFN", "UFP", "UFQ", "UFR", "UFS", "UFT", "UFU", "UFV", "UFA", "UFB"];
	GridLetters[1] = ["TGN", "UGP", "UGQ", "UGR", "UGS", "UGT", "UGU", "UGV", "UGA", "UGB"];

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

	gridly = 10
	gridlx = 2;
	gridpreflen = 3;
	griddele = 6;
	griddeln = 52;
}

var marker1left = 14;
var marker1top = 14;

var marker2left = 14;
var marker2top = 14;

var eleontop = 0;
var offX = 0;
var offY = 0;

var w2 = mapw / 2;
var h2 = maph / 2;

var rotrad = rot * Math.PI / 180;
var cosrot = Math.cos(rotrad);
var sinrot = Math.sin(rotrad);

var ratw = (mapb / w2) * 1000;
var rath = (mapb / h2) * 1000;

var currentelement = null;

var eastings1 = 0;
var northings1 = 0;
var grlen1 = 0;
var eastings2 = 0;
var northings2 = 0;
var grlen2 = 0;


function overlayMouseUp(e) {
	if (currentelement != null) {
		if (currentelement.id == 'marker1') {
			if (document.theForm.grid_reference.value == "") {
				currentelement.style.left = (15-marker1left)+'px';
				currentelement.style.top = (maph + 5 -marker1top)+'px';
				eastings1 = 0;
			}
		} else if (currentelement.id == 'marker2') {
			if (document.theForm.photographer_gridref.value == "") {
				currentelement.style.left = (15-marker2left) +'px';
				currentelement.style.top = (maph + 25 - marker2top)+'px';
				eastings2 = 0;
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
	
	m1left = parseInt(m1.style.left)+marker1left;
	m1top = parseInt(m1.style.top)+marker1top;
	
	if (Math.abs(tempX - m1left) < marker1left && Math.abs(tempY - m1top) < marker1top) {
		currentelement = m1;
		offX = tempX - m1left;
		offY = tempY - m1top;
	} 
	
	if (currentelement == null || eleontop == 2) {
		var m2 = document.getElementById('marker2');

		m2left = parseInt(m2.style.left)+marker2left;
		m2top = parseInt(m2.style.top)+marker2top;

		if (Math.abs(tempX - m2left) < marker2left && Math.abs(tempY - m2top) < marker2top) {
			currentelement = m2;
			offX = tempX - m2left;
			offY = tempY - m2top;
		}
	}
	
	return false;
}
function overlayMouseOut(e) {
	eleontop = 2;
	document.getElementById('marker2').style.zIndex = 2;
	document.getElementById('marker1').style.zIndex = 1;
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
			if (currentelement.id == 'marker1') {
				currentelement.style.left = (tempX - marker1left - offX)+'px';
				currentelement.style.top = (tempY - marker1top - offY)+'px';
			} else {
				currentelement.style.left = (tempX - marker2left - offX)+'px';
				currentelement.style.top = (tempY - marker2top - offY)+'px';
			}
		}
	}

	if (tempY > maph) {
		if (currentelement != null) {
			if (currentelement.id == 'marker1') {
				if (document.theForm.grid_reference)
					document.theForm.grid_reference.value = "";
			} else if (currentelement.id == 'marker2') {
				if (document.theForm.photographer_gridref)
					document.theForm.photographer_gridref.value = "";
			}
		}
		document.images['map'].alt = "";
		return;
	}

	if (currentelement == null) {
		var ele = null;
		var m1 = document.getElementById('marker1');
			
		m1left = parseInt(m1.style.left)+marker1left;
		m1top = parseInt(m1.style.top)+marker1top;

		if (Math.abs(tempX - m1left) < marker1left && Math.abs(tempY - m1top) < marker1top) {
			ele = m1;
		}
		
		if (ele == null || eleontop == 2) {
			var m2 = document.getElementById('marker2');

			m2left = parseInt(m2.style.left)+marker2left;
			m2top = parseInt(m2.style.top)+marker2top;

			if (Math.abs(tempX - m2left) < marker2left && Math.abs(tempY - m2top) < marker2top) {
				ele = m2;
			}
		}
		
		if (ele != null) {
			if (ele.id == 'marker1') {
				eleontop = 1;
				document.getElementById('marker1').style.zIndex = 2;
				document.getElementById('marker2').style.zIndex = 1;
			} else {
				eleontop = 2;
				document.getElementById('marker2').style.zIndex = 2;
				document.getElementById('marker1').style.zIndex = 1;
			}
			//alert(ele.id);
		}
		//the alt isnt used anyway so lets give up...
		return;
	}

	tempX = tempX - w2 - offX;
	tempY = tempY - h2 - offY;
	
	var easting  = cene + Math.round((tempX * cosrot - tempY * sinrot) * ratw);
	var northing = cenn - Math.round((tempX * sinrot + tempY * cosrot) * rath);

	var cenXhun = "00000" + (easting % 100000);
	var cenYhun = "00000" + (northing % 100000);
	cenXhun = cenXhun.substr(cenXhun.length - 5, grlen);
	cenYhun = cenYhun.substr(cenYhun.length - 5, grlen);

	var cenXblock = Math.floor(easting / 100000) - griddele;
	var cenYblock = Math.floor(northing / 100000) - griddeln;

	if(cenXblock < 0 || cenYblock < 0 || cenXblock >= gridlx || cenYblock >= gridly) {
		if (currentelement != null) {
			if (currentelement.id == 'marker1') {
				eastings1 = 0;
				northings1 = 0;
				document.theForm.grid_reference.value = "-Invalid-";
			} else if (currentelement.id == 'marker2') {
				eastings2 = 0;
				northings2 = 0;
				document.theForm.photographer_gridref.value = "-Invalid-";
			}
		}
		document.images['map'].alt = "-Invalid Grid Ref-"
	} else {
		grstr = GridLetters[cenXblock][cenYblock] + cenXhun + cenYhun;
		if (currentelement != null) {
			if (currentelement.id == 'marker1') {
				eastings1 = easting;
				northings1 = northing;
				grlen1 = grlen;
				document.theForm.grid_reference.value = grstr;
				if (document.theForm.grid_reference_display) {
					document.theForm.grid_reference_display.value = '??'+ cenXhun + cenYhun
				}
			} else if (currentelement.id == 'marker2') {
				eastings2 = easting;
				northings2 = northing;
				grlen2 = grlen;
				document.theForm.photographer_gridref.value = grstr;
			} 
			if (document.theForm.use6fig && grlen == 4)
				document.theForm.use6fig.checked = true;
		}
		document.images['map'].alt = grstr;
	}
	if (currentelement != null) {
		updateViewDirection();
	}
	if (typeof parentUpdateVariables != 'undefined') {
		parentUpdateVariables();
	}
}

function checkFormSubmission(that_form,mapenabled) {
	if (checkGridReferences(that_form)) {
		message = '';
		if (that_form.grid_reference.value == '' || that_form.grid_reference.value.length < 7) 
			message = message + "* Subject Grid Reference\n";
		if (that_form.photographer_gridref.value == '') 
			message = message + "* Photographer Grid Reference\n";
		if (that_form.view_direction.selectedIndex == 0) 
			message = message + "* View Direction\n";
		if (message.length > 0) {
			message = "We notice that the following fields have been left blank:\n\n" + message;
			message = message + "\nWhile you can continue without providing this information we would appreciate including as much detail as possible as it will make plotting the photo on a map much easier.\n\n";
			if (mapenabled) {
				message = message + "Adding the missing information should be very quick by dragging the icons on the map.\n\n";
			}
			message = message + "Click OK to add the information, or Cancel to continue anyway.";
			return !confirm(message);
		}
		return true;
	} else {
		return false;
	}
}

function checkGridReferences(that_form) {
	if (!checkGridReference(that_form.grid_reference,true)) 
		return false;
	if (!checkGridReference(that_form.photographer_gridref,true)) 
		return false;
	return true;

} 

function checkGridReference(that,showmessage) {
	GridRef = /\b([a-zA-Z]{1,3}) ?(\d{2,5})[ \.]?(\d{2,5})\b/;
	ok = true;
	if (that.value.length > 0) {
		myArray = GridRef.exec(that.value); 
		if (myArray && myArray.length > 0) {
			numbers = myArray[2]+myArray[3];
			if (numbers.length == 0 || numbers.length % 2 != 0) {
				ok = false;
			}
		} else {
			ok = false;
		}
	}
	if (ok == false && showmessage) {
		if (that.name == 'grid_reference') {
			alert("please enter a valid subject grid reference");
		} else {
			alert("please enter a valid photographer grid reference");
		}
		that.focus();
	}
	return ok;
}

function mapMarkerToCenter(that) {
	//TODO
}

function updateMapMarker(that,showmessage,dontcalcdirection) {
	if (!checkGridReference(that,showmessage)) {
		return false;
	}
	if (!document.images['map']) {
		//we have no map! so we only wanted to check the GR
		return;
	}

	if (document.getElementById) {
		if (that.name == 'photographer_gridref') {
			currentelement = document.getElementById('marker2');
		} else {
			currentelement = document.getElementById('marker1');
		}
		gr = that.value;

		GridRef = /\b([a-zA-Z]{1,3}) ?(\d{2,5})[ \.]?(\d{2,5})\b/;

		if (gr.length >= 4 + gridpreflen) { // FIXME
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

				var cenXblock = -1;
				letters = letters.toUpperCase();
				for(x=0;x<gridlx;x++)
					for(y=0;y<gridly;y++)
						if (GridLetters[x][y] == letters) {
							cenXblock = x*100000;
							cenYblock = y*100000;
						}

				if (cenXblock > 0) {
					//we use parseFloat to avoid issues with 0 prefixed numbers!
					easting = parseInt(cenXblock) + parseFloat(cenXhun) + griddele*100000;
					northing = parseInt(cenYblock) + parseFloat(cenYhun) + griddeln*100000;
					
					if (halve == 3) {
					//	easting = easting + 50;
					//	northing = northing + 50;
					}
					
					
					dX = (easting - cene) / ratw;
					dY = (cenn - northing) / rath;

					tempX = ( dX * cosrot + dY * sinrot) + w2;
					tempY = (-dX * sinrot + dY * cosrot) + h2;
					if (currentelement.id == 'marker2' && ( (tempX < 0) || (tempX > mapw) || (tempY < 0) || (tempY > maph) ) ) {
						currentelement.style.left = (15 -marker2left) +'px';
						currentelement.style.top = (maph + 25 - marker2top)+'px';
						eastings2 = easting;
						northings2 = northing;
					} else {
						if (currentelement.id == 'marker1') {
							if (numbers.length == 4 && easting%1000 == 0 && northing%1000 == 0) {
								tempX = 15;
								tempY = maph + marker1top;
								eastings1 = 0;
								northings1 = 0;
							} else if (numbers.length == 6 && easting%100 == 0 && northing%100 == 0) {
								tempX = tempX + (mapw /40);
								tempY = tempY - (maph /40);
								eastings1 = easting + 50;
								northings1 = northing + 50;
								grlen1 = halve;
							} else {
								eastings1 = easting;
								northings1 = northing;
								grlen1 = halve;
							}
							currentelement.style.left = (tempX - marker1left)+'px';
							currentelement.style.top = (tempY - marker1top)+'px';
						} else if (currentelement.id == 'marker2') {
							if (numbers.length == 4 && easting%1000 == 0 && northing%1000 == 0) {
								tempX = 15;
								tempY = maph + 25;
								eastings2 = 0;
								northings2 = 0;
							} else if (numbers.length == 6 && easting%100 == 0 && northing%100 == 0) {
								tempX = tempX + (mapw /40);
								tempY = tempY - (maph /40);
								eastings2 = easting + 50;
								northings2 = northing + 50;
								grlen2 = halve;
							} else {
								eastings2 = easting;
								northings2 = northing;
								grlen2 = halve;
							}
							currentelement.style.left = (tempX - marker2left)+'px';
							currentelement.style.top = (tempY - marker2top)+'px';
						} 
					}
				}
			}
		} else {
			if (currentelement.id == 'marker1') {
				currentelement.style.left = (15-marker1left)+'px';
				currentelement.style.top = (maph + 5 -marker1top)+'px';
			}
			if (currentelement.id == 'marker2') {
				currentelement.style.left = (15 -marker2left) +'px';
				currentelement.style.top = (maph + 25 - marker2top)+'px';
			}
		}
	}
	currentelement = null;
	if (!dontcalcdirection)
		updateViewDirection();
}


function updateViewDirection() {
	if (eastings1 > 0 && eastings2 > 0) {
		
		distance = Math.sqrt( Math.pow(eastings1 - eastings2,2) + Math.pow(northings1 - northings2,2) );
	
		if (distance > (mult*1.4) || (distance > 0 && grlen1 == 5 && grlen2 == 5) ) {
			realangle = Math.atan2( eastings1 - eastings2, northings1 - northings2 ) / (Math.PI/180);

			if (realangle < 0)
				realangle = realangle + 360.0;

			jump = 360.0/16.0;

			newangle = Math.floor(Math.round(realangle/jump)*jump);
			if (newangle == 360)
				newangle = 0;

			var ele = document.theForm.view_direction;
			for(q=0;q<ele.options.length;q++)
				if (ele.options[q].value == newangle)
					ele.selectedIndex = q;

			document.images['camicon'].src = "http://"+static_host+"/img/icons/viewc-"+parseInt(newangle,10)+".png";
			if (document.theForm.photographer_gridref.value == '')
				document.images['subicon'].src = "http://"+static_host+"/img/icons/subc-"+parseInt(newangle,10)+".png";
			else 
				document.images['subicon'].src = "http://"+static_host+"/img/icons/circle.png";
		}
	}
}

function updateCamIcon() {
	if (!document.images['map']) {
		//we have no map!
		return;
	}
	ele = document.theForm.view_direction;
	realangle = ele.options[ele.selectedIndex].value;
	if (realangle == -1) {
		document.images['camicon'].src = "http://"+static_host+"/img/icons/viewc--1.png";
		document.images['subicon'].src = "http://"+static_host+"/img/icons/subc--1.png";
	} else {
		jump = 360.0/16.0;
		newangle = Math.floor(Math.round(realangle/jump)*jump);
		if (newangle == 360)
			newangle = 0;
		document.images['camicon'].src = "http://"+static_host+"/img/icons/viewc-"+parseInt(newangle,10)+".png";
		if (document.theForm.photographer_gridref.value == '')
			document.images['subicon'].src = "http://"+static_host+"/img/icons/subc-"+parseInt(newangle,10)+".png";
		else 
			document.images['subicon'].src = "http://"+static_host+"/img/icons/circle.png";
	}
}
