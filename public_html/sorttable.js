//Written by Stuart Langridge
//http://www.kryogenix.org/code/browser/sorttable/
//Additional features added by Paul Dixon (paul@elphin.com)
//Amended by N D Davies: Sort on scalar key data instead of objects.
//                       Perform DOM manipulations on non-display memory construct.

/*
To use this, simply add

	<script src="/sorttable.js"></script>

and give a table "sortable" class and a unique id

	<table class="myclass sortable" id="mytable">

to make a column sortable on hidden data, use <td sortvalue="sortable">human value</td>
(good for human readable dates)

to indicate the current sort order in a header cell, use
<td sorted="asc"> or <td sorted="desc"> and the script will set up the appropriate
indicator

*/


AttachEvent(window, "load", sortables_init,false);

var SORT_COLUMN_INDEX;

	document.getElementsByClassName = function(clsName){
	    var retVal = new Array();
	    var elements = document.getElementsByTagName("*");
	    for(var i = 0;i < elements.length;i++){
		if(elements[i].className.indexOf(" ") >= 0){
		    var classes = elements[i].className.split(" ");
		    for(var j = 0;j < classes.length;j++){
			if(classes[j] == clsName)
			    retVal.push(elements[i]);
		    }
		}
		else if(elements[i].className == clsName)
		    retVal.push(elements[i]);
	    }
	    return retVal;
	}

	String.prototype.nbsptrim = function () {
		var bits = this.split(unescape('%A0'));
		return bits[0];
	}

function sortables_init() {
    // Find all tables with class sortable and make them sortable
    if (!document.getElementsByTagName) return;
    tbls = document.getElementsByTagName("table");
    for (ti=0;ti<tbls.length;ti++) {
        thisTbl = tbls[ti];
        if (((' '+thisTbl.className+' ').indexOf("sortable") != -1) && (thisTbl.id)) {
            //initTable(thisTbl.id);
            ts_makeSortable(thisTbl);
        }
    }
    
    
	if (location.hash.length) {
		// If there are any parameters at the end of the URL,
		// looking something like  "#sort=name%A0%A0%u2191"

		// skip the first character, we are not interested in the "#"
		var query = location.hash.substring(1);

		var pairs = query.split("&");
		for (var i=0; i<pairs.length; i++) {
			// break each pair at the first "=" to obtain the argname and value
			var pos = pairs[i].indexOf("=");
			var argname = pairs[i].substring(0,pos).toLowerCase();
			var value = unescape(pairs[i].substring(pos+1));

			if (argname == "sort") {
				var bits = value.split(unescape('%A0'));
				
				var ele = document.getElementsByClassName('sortheader');
				if (bits[2].length == 1) {
					//ie/firefox do things diffenently with auto unescaping
					bits[2] = escape(bits[2]);
				}
				
				for(var q = 0;q<ele.length;q++) {
					
					if (bits[0] == ts_getInnerText(ele[q]).replace(/  /,unescape('%A0%A0')).nbsptrim()) {
						var link = ele[q];
						
						ts_resortTable(link);
						
						if (bits[2] == '%u2191') {
							
							setTimeout(function() {
								ts_resortTable(link);
							}, 100);
						}
					}
				}
			}
			
		}
	}

	//to stop the page being 'cached' which prevents onload firing when pressing back.
	//http://www.stillnetstudios.com/2008/06/23/reset-javascript-firefox-back-button/
	window.onunload = function(){};

}

function ts_makeSortable(table) {
    if (table.rows && table.rows.length > 0) {
        var firstRow = table.rows[0];
    }
    if (!firstRow) return;
    
    // We have a first row: assume it's the header, and make its contents clickable links
    for (var i=0;i<firstRow.cells.length;i++) {
        var cell = firstRow.cells[i];
        //var txt = ts_getInnerText(cell);
        var txt=cell.innerHTML;
        
        var arrow='&nbsp;';
        var sortorder=cell.getAttribute('sorted');
        if (sortorder=='asc')
        	arrow='&darr;';
        if (sortorder=='desc')
        	arrow='&uarr;';
       
        if (sortorder!='none')
        	cell.innerHTML = '<a href="#" class="sortheader" style="text-decoration:none;" title="Sort on this column" onclick="ts_resortTable(this);return false;">'+txt+'<span class="sortarrow">&nbsp;&nbsp;'+arrow+'</span></a>';
    }
}

function ts_getInnerText(el) {
    if (typeof el == "string") return el;
    if (typeof el == "undefined") { return el };
    if (el.innerText) return el.innerText;    //Not needed but it is faster
    var str = "";
    
    var cs = el.childNodes;
    var l = cs.length;
    for (var i = 0; i < l; i++) {
        switch (cs[i].nodeType) {
            case 1: //ELEMENT_NODE
                str += ts_getInnerText(cs[i]);
                break;
            case 3:    //TEXT_NODE
                str += cs[i].nodeValue;
                break;
        }
    }
    return str;
}

function ts_resortTable(lnk) {
    // get the span
    var span;
    for (var ci=0;ci<lnk.childNodes.length;ci++) {
        if (lnk.childNodes[ci].tagName && lnk.childNodes[ci].tagName.toLowerCase() == 'span') span = lnk.childNodes[ci];
    }
    var spantext = ts_getInnerText(span);
    var td = lnk.parentNode;
    var column = td.cellIndex;
    var table = getParent(td,'TABLE');
    
    // Work out a type for the column
    if (table.rows.length <= 1) return;
    var itm = ts_getInnerText(table.rows[1].cells[column]);
    parsefn = ts_parse_caseinsensitive;
    if (itm.match(/^\d\d[\/-]\d\d[\/-]\d\d\d\d$/)) parsefn = ts_parse_date;
    if (itm.match(/^\d\d[\/-]\d\d[\/-]\d\d$/)) parsefn = ts_parse_date;
    if (itm.match(/^[£$]/)) parsefn = ts_parse_currency;
    if (itm.match(/^\d[\d\.,]*$/)) parsefn = ts_parse_numeric;
    
    if (table.rows[1].cells[column].getAttribute && 
    table.rows[1].cells[column].getAttribute('sortvalue')!=null) {
    	itm = table.rows[1].cells[column].getAttribute('sortvalue');
    	if (itm.match(/^\d[\d\.,]*$/)) {
    		parsefn=ts_parse_hidden_numeric;	
    	} else {
    		parsefn=ts_parse_hidden;
    	}
    }

    // Parse key values into a dedicated array to allow quicker sorting
    SORT_COLUMN_INDEX = column;
    var firstRow = new Array();
    var keylist = new Array();
    var newRows = new Array();
    for (i=0;i<table.rows[0].length;i++) { firstRow[i] = table.rows[0][i]; }

    for (j=1;j<table.rows.length;j++) {
        keylist[j-1] = Array(parsefn(table.rows[j]) , j) ;
    }
    
    // Sort the array of keys
    keylist.sort(function(a,b){return compare(a[0],b[0]);});

    if (span.getAttribute("sortdir") == 'down') {
        ARROW = '&nbsp;&nbsp;&uarr;';
        keylist.reverse();
        span.setAttribute('sortdir','up');
    } else {
        ARROW = '&nbsp;&nbsp;&darr;';
        span.setAttribute('sortdir','down');
    }

    // Build rows in sorted order
    for (j=0;j<keylist.length;j++) { newRows[j] = table.rows[keylist[j][1]]; }

    var newbdy = document.createElement('tbody');

    // Put rows in newbody in required order
    // Append rows not needing to be kept at bottom
    for (i=0;i<newRows.length;i++) { if (!newRows[i].className || (newRows[i].className && (newRows[i].className.indexOf('sortbottom') == -1))) newbdy.appendChild(newRows[i]);}
    // Append rows needing to be kept at bottom
    for (i=0;i<newRows.length;i++) { if (newRows[i].className && (newRows[i].className.indexOf('sortbottom') != -1)) newbdy.appendChild(newRows[i]);}

    // Disconnect old TBODY from displayed document
    var bdy = table.tBodies[0];
    var oldpos = bdy.nextSibling;
    var oldparent = bdy.parentNode;
    oldparent.removeChild(bdy);
    bdy = null;

    // Reconnect new TBODY in the original position
    oldparent.insertBefore(newbdy, oldpos);

    // Delete any other arrows there may be showing
    var allspans = document.getElementsByTagName("span");
    for (var ci=0;ci<allspans.length;ci++) {
        if (allspans[ci].className == 'sortarrow') {
            if (getParent(allspans[ci],"table") == getParent(lnk,"table")) { // in the same table as us?
                allspans[ci].innerHTML = '&nbsp;&nbsp;&nbsp;';
            }
        }
    }
        
    span.innerHTML = ARROW;
    
   	location.hash = "sort="+escape(ts_getInnerText(lnk)).replace(/%20%20/,'%A0%A0');

	if (typeof reGroup != 'undefined') {
		reGroup();
	}
}

function getParent(el, pTagName) {
    if (el == null) return null;
    else if (el.nodeType == 1 && el.tagName.toLowerCase() == pTagName.toLowerCase())	// Gecko bug, supposed to be uppercase
        return el;
    else
        return getParent(el.parentNode, pTagName);
}
function ts_parse_date(a) {
    // y2k notes: two digit years less than 50 are treated as 20XX, greater than 50 are treated as 19XX
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]);
    if (aa.length == 10) {
        dt1 = aa.substr(6,4)+aa.substr(3,2)+aa.substr(0,2);
    } else {
        yr = aa.substr(6,2);
        if (parseInt(yr) < 50) { yr = '20'+yr; } else { yr = '19'+yr; }
        dt1 = yr+aa.substr(3,2)+aa.substr(0,2);
    }
    return dt1;
}

function ts_parse_currency(a) { 
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,'');
    return parseFloat(aa);
}

function ts_parse_numeric(a) { 
    aa = parseFloat(ts_getInnerText(a.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,''));
    if (isNaN(aa)) aa = 0;
    return aa;
}

function ts_parse_caseinsensitive(a) {
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]).toLowerCase();
    return aa;
}

function ts_parse_default(a,b) {
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]);
    return aa;
}

function ts_parse_hidden(a,b) {
    aa = a.cells[SORT_COLUMN_INDEX].getAttribute('sortvalue');
    return aa;
}

function ts_parse_hidden_numeric(a,b) {
    aa = parseFloat(a.cells[SORT_COLUMN_INDEX].getAttribute('sortvalue').replace(/,/,''));
    return aa;
}

function compare(a,b) {
    if (a<b) {return -1;}
    if (a>b) {return 1;}
    return 0;
}