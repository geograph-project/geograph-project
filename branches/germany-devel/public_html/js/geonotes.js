/* This is inspired by
 *   http://www.kryogenix.org/code/browser/annimg/annimg.html
 * and
 *   http://www.kryogenix.org/code/browser/nicetitle/
 * which comes with an MIT licence.
 *
 * No idea which licence we should use, as many things work differently, here...
 *
 * Basic idea:
 * We have a container div with position:relative (e.g. class img-shadow)
 * which contains the image of class geonotes and an image map, where
 * the notes can be specified using the title attribute. This works without
 * JavaScript.
 * If an area element of the image map has an id "noteareaNNNN" and an element
 * with id "noteboxNNNN" exists (might be a link with class notebox containing
 * an empty span), this script tries to implement some mouse over event
 * handling.
 * If also an element "notetextNNNN" exists (might be a div with class geonote
 * containing a p element), this script tries to show that element instead of
 * the usual tool tip. While displaying the note, the class of noteboxNNNN will
 * be changed by prepending "cur" to the class name.
 */

var gn = {
	init: function() {
		if (!document.getElementById ||
		    !document.createElement ||
		    !document.getElementsByTagName)
			return;
		var images = document.getElementsByTagName('img');
		for (var i=0;i<images.length;i++) {
			if ((images[i].className.search(/\bgeonotes\b/) != -1) &&
			    (images[i].getAttribute('usemap') != null)) {
				gn.__initImage(images[i]);
			}
		}
	},

	__initImage: function(img) {
		var mapName = img.getAttribute('usemap');
		if (mapName.substr(0,1) == '#') mapName = mapName.substr(1);
		var mapObjs = document.getElementsByName(mapName);
		if (mapObjs.length != 1) return;
		var mapObj = mapObjs[0];
		var boxes = mapObj.getElementsByTagName('area');
		img.boxes = [];
		img.geoareas = [];
		for (var j=boxes.length-1;j>=0;j--) {
			if (boxes[j].getAttribute('shape').toLowerCase() == 'rect' && boxes[j].id.substr(0,8) == 'notearea') {
				var curarea = boxes[j];
				var noteid = curarea.id.substr(8);
				img.geoareas[img.geoareas.length] = curarea;
				curarea.noteid = noteid;
				curarea.geonoteid = noteid;
				curarea.geonotex1 = curarea.getAttribute('geonotex1');
				curarea.geonotex2 = curarea.getAttribute('geonotex2');
				curarea.geonotey1 = curarea.getAttribute('geonotey1');
				curarea.geonotey2 = curarea.getAttribute('geonotey2');
				curarea.geonotewidth = curarea.getAttribute('geonotewidth');
				curarea.geonoteheight = curarea.getAttribute('geonoteheight');
				curarea.geonotestatus = curarea.getAttribute('geonotestatus');
				curarea.geonotependingchanges = curarea.getAttribute('geonotependingchanges')!='0';
				curarea.geonoteunsavedchanges = false;
				curarea.geoimg = img;
				var a=document.getElementById('notebox'+noteid);
				curarea.geobox = a;
				if (a) {
					a.geoarea = curarea;
					curarea.title = '';
					var left=a.style.left;
					var top=a.style.top;
					if (left.substr(left.length-2) == 'px' && top.substr(top.length-2) == 'px') {
						left=parseInt(left.substr(0,left.length-2))
						top=parseInt(top.substr(0,top.length-2))
						var padding = gn.__getPadding(img);
						left+=padding[0];
						top+=padding[1];
						if (img.offsetParent) { // try img.x,img.y otherwise?
							left+=img.offsetLeft;
							top+=img.offsetTop;
						}
						a.style.left = left+'px';
						a.style.top = top+'px';
					}
					img.boxes[img.boxes.length] = a;
					a.geoimg = img;

					gn.addEvent(a,"mouseover",
						function() {
							clearTimeout(gn.hiderTimeout);
						}
					);
					var txt = document.getElementById('notetext'+noteid);
					a.geonote = txt;
					if (txt) {
						a.title = '';
						gn.initBoxWidth(txt);
						txt.geoimg = img;
						txt.geobox = a;
						a.geoclass = a.className;
						gn.addEvent(a,"mouseover",gn.showNoteText);
						gn.addEvent(txt,"mouseout",gn.hideNoteTextEvent);
					}
				}
			}
		}

		gn.addEvent(img,"mouseover",gn.showBoxes);
		gn.addEvent(img,"mouseout",gn.hideBoxes);
	},

	addNote: function(area, box, txt, parea, pbox, ptxt, x1, y1, x2, y2, img, notestatus, pendingchanges, noteid) {
		area.noteid = noteid;    /* corresponds to the element's id, never changes */
		area.geonoteid = noteid; /* when creating a note, this changes to the actual note_id */
		area.geonotex1 = x1;
		area.geonotey1 = y1;
		area.geonotex2 = x2;
		area.geonotey2 = y2;
		area.geonotewidth = img.width;
		area.geonoteheight = img.height;
		area.geoimg = img;
		area.geonotestatus = notestatus;
		area.geonotependingchanges = pendingchanges;
		area.geonoteunsavedchanges = true;
		img.geoareas[img.geoareas.length] = area;
		area.geobox = box;
		if (box) {
			img.boxes[img.boxes.length] = box;
			box.geoarea = area;
			area.title = '';
			box.geoimg = img;
			box.geonote = txt;
			box.geoclass = box.className;
			if (txt) {
				txt.geoimg = img;
				txt.geobox = box;
			}
		}
		gn.recalcBox(area);

		parea.appendChild(area);
		if (box) {
			pbox.appendChild(box);
			gn.addEvent(box,"mouseover",
				function() {
					clearTimeout(gn.hiderTimeout);
				}
			);
			if (txt) {
				ptxt.appendChild(txt);
				gn.initBoxWidth(txt);
				gn.addEvent(box,"mouseover",gn.showNoteText);
				gn.addEvent(txt,"mouseout",gn.hideNoteTextEvent);
			}
		}
	},

	initBoxWidth: function(txt) {
		txt.style.left='0px';
		txt.style.top='0px';

		txt.style.visibility='hidden';
		txt.style.display = 'block';
		txt.style.width='auto'; /* optimal width */
		txt.style.width=(txt.clientWidth+4)+'px'; /* keep width fixed */
		txt.style.display='none';
		txt.style.visibility='visible';
	},

	capitalize: function(s) {
		return s.charAt(0).toUpperCase() + s.slice(1);
	},

	convStyle: function(s) {
		var parts = s.split('-');
		var ret = parts[0];
		for (var i = 1; i < parts.length; ++i) {
			ret += gn.capitalize(parts[i]);
		}
		return ret;
	},

	getStylePX: function(ele, stx) {
		if(window.getComputedStyle) {
			var style = window.getComputedStyle(ele, null);
			var x = style.getPropertyValue(stx);
		} else if (ele.currentStyle) {
			var x = ele.currentStyle[gn.convStyle(stx)];
		} else {
			return 0;
		}
		if (x.substr(x.length-2) != 'px') {
			return 0;
		}
		x=parseInt(x.substr(0,x.length-2));
		return x;
	},

	getStyleXY: function(ele, stx, sty) {
		if(window.getComputedStyle) {
			var style = window.getComputedStyle(ele, null);
			var x = style.getPropertyValue(stx);
			var y = style.getPropertyValue(sty);
		} else if (ele.currentStyle) {
			var x = ele.currentStyle[gn.convStyle(stx)];
			var y = ele.currentStyle[gn.convStyle(sty)];
		} else {
			return [ 0, 0 ];
		}
		if (  x.substr(x.length-2) != 'px'
		    ||y.substr(y.length-2) != 'px') {
			return [ 0, 0 ];
		}
		x=parseInt(x.substr(0,x.length-2));
		y=parseInt(y.substr(0,y.length-2));
		return [ x, y ];
	},

	__getPadding: function(ele) {
		return gn.getStyleXY(ele, 'padding-left', 'padding-top');
	},

	__getParent: function (el, pTagName) {
		if (el == null)
			return null;
		else if (el.nodeType == 1 && el.tagName.toLowerCase() == pTagName.toLowerCase())
			return el;
		else
			return gn.__getParent(el.parentNode, pTagName);
	},

	__setBoxes: function(t,disp) {
		if (!t || !t.boxes) return;
		for (var i=0;i<t.boxes.length;i++) {
			var area = t.boxes[i].geoarea;
			t.boxes[i].style.display = area.geonotehide || area.geonotestatus=='deleted' ? 'none' : disp;
		}
	},

	recalcBox: function(area) { //, dx, dy) {
		var img = area.geoimg;
		var width = img.width;
		var height = img.height;
		var padding = gn.__getPadding(img);
		var dx = padding[0];
		var dy = padding[1];
		if (img.offsetParent) { // try img.x,img.y otherwise?
			dx += img.offsetLeft;
			dy += img.offsetTop;
		}
		var x1 = Math.floor(area.geonotex1 * width / area.geonotewidth);
		var x2 = Math.floor(area.geonotex2 * width / area.geonotewidth);
		var y1 = Math.floor(area.geonotey1 * height / area.geonoteheight);
		var y2 = Math.floor(area.geonotey2 * height / area.geonoteheight);
		area.coords = x1+","+y1+","+x2+","+y2;
		var box = area.geobox;
		var borderbox = 1; // FIXME
		if (box) {
			box.style.left = (x1 + dx) + 'px';
			box.style.top = (y1 + dy) + 'px';
			box.style.width = (x2 - x1 + 1 - 2*borderbox) + 'px';
			box.style.height = (y2 - y1 + 1 - 2*borderbox) + 'px';
			var txt = box.geonote;
			if (txt) {
				txt.style.left='0px';
				txt.style.top='0px';
			}
		}
	},

	recalcBoxes: function(img) { //,width,height) {
		if (!img || !img.geoareas) return;
		gn.hideNoteText();
		/*var width = img.width;
		var height = img.height;
		var padding = gn.__getPadding(img);
		var dx = padding[0];
		var dy = padding[1];
		if (img.offsetParent) { // try img.x,img.y otherwise?
			dx += img.offsetLeft;
			dy += img.offsetTop;
		}*/
		for (var i=0;i<img.geoareas.length;i++) {
			var area = img.geoareas[i];
			gn.recalcBox(area);//, dx, dy);
		}
	},

	hideNoteTextEvent: function(e) {
		/* did we really move _out_? */
		var ele = null;
		var toele = null;
		if (window.event) {
			ele = window.event.srcElement
			toele = window.event.toElement;
		} else if (e) {
			ele = e.target;
			toele = e.relatedTarget;
		}
		if (!ele || !toele)
			return;
		if (ele != current_note)
			return;
		while (ele != toele && toele.nodeName.toLowerCase() != 'body') {
			toele = toele.parentNode;
		}
		if (ele == toele) { /* we only moved to a child element */
			return;
		}

		gn.hideNoteText();
	},

	hideNoteText: function() {
		if (current_note) {
			current_note.geobox.className = current_note.geobox.geoclass;
			current_note.style.display = 'none';
			current_note = null;
		}
	},

	showNoteText: function(e) {
		var lnk = null;
		if (window.event && window.event.srcElement) {
			lnk = window.event.srcElement
		} else if (e && e.target) {
			lnk = e.target
		}
		if (!lnk) return;
		if (lnk.nodeName.toUpperCase() != 'A') {
			// lnk is not actually the link -- ascend parents until we hit a link
			lnk = gn.__getParent(lnk,"A");
		}
		if (!lnk) return;
		if (lnk.id.substr(0,7) != 'notebox') return;
		//var txt = document.getElementById('notetext'+lnk.id.substr(7));
		//var txt = document.getElementById('notetext'+lnk.geoarea.noteid);
		var txt = lnk.geonote;
		if (!txt) return;
		/*if (current_note && txt.id == current_note.id)
			return;*/
		if (current_note) gn.hideNoteText();
		current_note = txt;
		txt.style.visibility = 'hidden';
		txt.style.display = 'block';
		var tw = txt.clientWidth;
		var th = txt.clientHeight;
		var dw = txt.parentNode.clientWidth;
		var dh = txt.parentNode.clientHeight;
		var iw = txt.geoimg.clientWidth;
		var ih = txt.geoimg.clientHeight;
		var mpos = gn.__getMousePosition(e); // TODO compare with mapping1.js
		var epos = gn.__getElePosition(lnk.parentNode);
		var sx = lnk.parentNode.scrollLeft; //FIXME portable?
		var sy = lnk.parentNode.scrollTop;  //FIXME portable?
		var lnkx = parseInt(lnk.style.left.substr(0,lnk.style.left.length-2));
		var lnky = parseInt(lnk.style.top.substr(0,lnk.style.top.length-2));
		var lnkxctr = lnkx + lnk.clientWidth/2;
		var lnkyctr = lnky + lnk.clientHeight/2;
		var x = mpos[0]-epos[0] + sx;
		var y = mpos[1]-epos[1] + sy;

		/* x,y = mouse pointer */
		/* positioning txt:
		   - txt must contain x,y.
		   - No part of txt should be outside the visible part of the image.
		   - txt should not cover too much of the note box (would be
		     bad for overlapping note boxes). This can be done by aligning txt
		     at the side of the note where the mouse pointer is located.
		*/

		if (x < lnkxctr) {
			x -= tw - 5;
		} else {
			x -= 5;
		}

		if (y < lnkyctr) {
			y -= th - 5;
		} else {
			y -= 5;
		}

		if (x+tw >= dw + sx) {
			x = dw - tw - 1 + sx;
		}
		if (x < sx) {
			x = sx;
		}
		if (y+th >= ih) {
			y = ih - th - 1;
		}
		if (y < 0) {
			y = 0;
		}
		txt.style.left = x+'px';
		txt.style.top = y+'px';

		txt.geobox.className = 'cur' + txt.geobox.geoclass;
		txt.style.visibility = 'visible';
	},

	showBoxes: function(e) {
		var t = null;
		if (e && e.target) t = e.target;
		if (window.event && window.event.srcElement) t = window.event.srcElement;
		gn.__setBoxes(t,'block');
	},

	hideBoxes: function(e) {
		var t = null;
		if (e && e.target) t = e.target;
		if (window.event && window.event.srcElement) t = window.event.srcElement;
		clearTimeout(gn.hiderTimeout);
		gn.hiderTimeout = setTimeout(
			function() { gn.__setBoxes(t,'none') },
			300);
	},

	__getMousePosition: function(event) {
		if (window.event) {
			event = window.event;
		}
		var x = event.clientX;
		var y = event.clientY;
		if (document.documentElement) { //FIXME test IE
			x += document.documentElement.scrollLeft + document.body.scrollLeft;
			y += document.documentElement.scrollTop + document.body.scrollTop;
		} else {
			x += window.scrollX;
			y += window.scrollY;
		}
		return [x, y];
	},

	__getElePosition: function(ele) {
		if (ele.offsetParent) { /* FIXME test body */
			var x = 0;
			var y = 0;
			for (; ele.offsetParent; ele = ele.offsetParent) {
				x += ele.offsetLeft;
				y += ele.offsetTop;
			}
			return [ x, y ];
		} else {
			return [ ele.x, ele.y ];
		}
	},


	addEvent: function(elm, evType, fn, useCapture) {
		// cross-browser event handling for IE5+, NS6 and Mozilla
		// By Scott Andrew
		if (elm.addEventListener){
			elm.addEventListener(evType, fn, useCapture);
			return true;
		} else if (elm.attachEvent){
			var r = elm.attachEvent("on"+evType, fn);
			return r;
		} else {
			elm['on'+evType] = fn;
		}
	}
}

current_note = null;
gn.addEvent(window,"load",gn.init);
