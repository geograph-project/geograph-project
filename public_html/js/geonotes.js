/* This is inspired by
 *   http://www.kryogenix.org/code/browser/annimg/annimg.html
 * and
 *   http://www.kryogenix.org/code/browser/nicetitle/
 * which comes with an MIT licence.
 *
 * No idea which licence we should use, many things work differently, here.
 *
 * Basic idea:
 * We have a container div with position:relative (e.g. class img-shadow)
 * which contains the image of class geonotes and an image map, where
 * the notes can be specified using the title attribute. This works without
 * JavaScript.
 * If an area element of the image map has an id "noteareaNNNN", there must also
 * exist an element with id "noteboxNNNN" (might be a link with class notebox).
 * This script tries to implement some mouse over event handling for that box.
 * Additionally, an element "notetextNNNN" is required (might be a div with class
 * geonote containing a p element). This script tries to show that element instead of
 * the usual tool tip. While displaying the note, the class of noteboxNNNN will
 * be changed by prepending "cur" to the class name.
 */

var gn = {
	images: [],
	notes: {},
	current_note: null,

	init: function() {
		if (!document.getElementById ||
		    !document.createElement ||
		    !document.getElementsByTagName)
			return; // FIXME add other functions we need
		var images = document.getElementsByTagName('img');
		for (var i=0;i<images.length;i++) {
			if ((images[i].className.search(/\bgeonotes\b/) != -1) &&
			    (images[i].getAttribute('usemap') != null)) {
				gn.__initImage(images[i]);
			}
		}
	},

	__initImage: function(img) {
		var imageindex = gn.images.length++;
		gn.images[imageindex] = { 'img' : img, 'notes' : [] }
		var mapName = img.getAttribute('usemap');
		if (mapName.substr(0,1) == '#') mapName = mapName.substr(1);
		var mapObjs = document.getElementsByName(mapName);
		if (mapObjs.length != 1) return;
		var mapObj = mapObjs[0];
		var boxes = mapObj.getElementsByTagName('area');
		var notelist = gn.images[imageindex].notes;
		for (var j=boxes.length-1;j>=0;j--) {
			if (boxes[j].getAttribute('shape').toLowerCase() == 'rect' && boxes[j].id.substr(0,8) == 'notearea') {
				var curarea = boxes[j];
				var noteid = curarea.id.substr(8);
				var a = document.getElementById('notebox'+noteid);
				var txt = document.getElementById('notetext'+noteid);
				if (a && txt) {
					notelist[notelist.length] = noteid;
					gn.notes[noteid] = {
						'area':           curarea,
						'box':            a,
						'note':           txt,
						'img':            img,
						'noteid':         noteid, /* corresponds to the element's id, never changes */
						'id':             noteid, /* when creating a note, this changes to the actual note_id */
						'x1':             curarea.getAttribute('geonotex1'),
						'x2':             curarea.getAttribute('geonotex2'),
						'y1':             curarea.getAttribute('geonotey1'),
						'y2':             curarea.getAttribute('geonotey2'),
						'width':          curarea.getAttribute('geonotewidth'),
						'height':         curarea.getAttribute('geonoteheight'),
						'status':         curarea.getAttribute('geonotestatus'),
						'pendingchanges': curarea.getAttribute('geonotependingchanges')!='0',
						'unsavedchanges': false,
						'hidden'        : false,
						'class'         : a.className,
					};
					curarea.title = '';
					a.title = '';
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
					gn.addEvent(a,"mouseover",
						function() {
							clearTimeout(gn.hiderTimeout);
						}
					);
					gn.initBoxWidth(txt);
					gn.addEvent(a,"mouseover",gn.showNoteText);
					gn.addEvent(txt,"mouseout",gn.hideNoteTextEvent);
				}
			}
		}

		gn.addEvent(img,"mouseover",gn.showBoxes);
		gn.addEvent(img,"mouseout",gn.hideBoxes);
	},
	findImage: function(img) {
		if (!img)
			return -1;
		for (var i = 0; i < gn.images.length; ++i) {
			if (gn.images[i].img === img) {
				return i;
			}
		}
		return -1;
	},
	addNote: function(area, box, txt, parea, pbox, ptxt, x1, y1, x2, y2, img, notestatus, pendingchanges, noteid) {
		var imageindex = gn.findImage(img);
		if (imageindex < 0)
			return;
		noteid = noteid.toString();
		var notelist = gn.images[imageindex].notes;
		notelist[notelist.length] = noteid;
		gn.notes[noteid] = {
			'area':           area,
			'box':            box,
			'note':           txt,
			'img':            img,
			'noteid':         noteid, /* corresponds to the element's id, never changes */           // FIXME rename to noteid_dom?
			'id':             noteid, /* when creating a note, this changes to the actual note_id */ // FIXME rename to noteid_db?
			'x1':             x1,
			'y1':             y1,
			'x2':             x2,
			'y2':             y2,
			'width':          img.width,
			'height':         img.height,
			'status':         notestatus,
			'pendingchanges': pendingchanges,
			'unsavedchanges': true,
			'hidden'        : false,
			'class'         : box.className,
		}
		area.title = '';
		box.title = '';
		gn.recalcBox(noteid);

		parea.appendChild(area);
		pbox.appendChild(box);
		gn.addEvent(box,"mouseover",
			function() {
				clearTimeout(gn.hiderTimeout);
			}
		);
		ptxt.appendChild(txt);
		gn.initBoxWidth(txt);
		gn.addEvent(box,"mouseover",gn.showNoteText);
		gn.addEvent(txt,"mouseout",gn.hideNoteTextEvent);
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

	setBoxes: function(idlist,disp) {
		for (var i = 0; i < idlist.length; i++) {
			var noteinfo = gn.notes[idlist[i]];
			noteinfo.box.style.display = noteinfo.hide || noteinfo.status=='deleted' ? 'none' : disp;
		}
	},

	recalcBox: function(noteid) { //, dx, dy) {
		var noteinfo = gn.notes[noteid];
		var img = noteinfo.img;
		var width = img.width;
		var height = img.height;
		var padding = gn.__getPadding(img);
		var dx = padding[0];
		var dy = padding[1];
		if (img.offsetParent) { // try img.x,img.y otherwise?
			dx += img.offsetLeft;
			dy += img.offsetTop;
		}
		var x1 = Math.floor(noteinfo.x1 * width / noteinfo.width);
		var x2 = Math.floor(noteinfo.x2 * width / noteinfo.width);
		var y1 = Math.floor(noteinfo.y1 * height / noteinfo.height);
		var y2 = Math.floor(noteinfo.y2 * height / noteinfo.height);
		noteinfo.area.coords = x1+","+y1+","+x2+","+y2;
		var borderbox = 1; // FIXME hard coded: determine in init()
		var box = noteinfo.box;
		box.style.left = (x1 + dx) + 'px';
		box.style.top = (y1 + dy) + 'px';
		box.style.width = (x2 - x1 + 1 - 2*borderbox) + 'px';
		box.style.height = (y2 - y1 + 1 - 2*borderbox) + 'px';
		var txt = noteinfo.note;
		txt.style.left='0px';
		txt.style.top='0px';
	},

	recalcBoxes: function(img) { //,width,height) {
		var imageindex = gn.findImage(img);
		if (imageindex < 0)
			return;
		var idlist = gn.images[imageindex].notes;
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
		for (var i = 0; i < idlist.length; i++) {
			gn.recalcBox(idlist[i]);//, dx, dy);
		}
	},

	hideNoteTextEvent: function(e) {
		/* did we really move _out_? */
		if (!gn.current_note) {
			return;
		}
		var ele = null;
		var toele = null;
		if (window.event && window.event.srcElement) {
			ele = window.event.srcElement
			toele = window.event.toElement;
		} else if (e) {
			ele = e.target;
			toele = e.relatedTarget;
		}
		if (!ele || !toele)
			return;
		if (ele != gn.notes[gn.current_note].note)
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
		if (gn.current_note) {
			var noteinfo = gn.notes[gn.current_note];
			noteinfo.box.className = noteinfo.class;
			noteinfo.note.style.display = 'none';
			gn.current_note = null;
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
		var noteid = lnk.id.substr(7);
		
		var noteinfo = gn.notes[noteid];
		var txt = noteinfo.note;
		/*if (current_note && txt.id == current_note.id)
			return;*/
		if (gn.current_note) gn.hideNoteText();
		gn.current_note = noteid;
		txt.style.visibility = 'hidden';
		txt.style.display = 'block';
		var tw = txt.clientWidth;
		var th = txt.clientHeight;
		var dw = txt.parentNode.clientWidth;
		var dh = txt.parentNode.clientHeight;
		var iw = noteinfo.img.clientWidth;
		var ih = noteinfo.img.clientHeight;
		var mpos = gn.getMousePosition(e); // TODO compare with mapping1.js
		var epos = gn.getElePosition(lnk.parentNode);
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

		lnk.className = 'cur' + noteinfo.class;
		txt.style.visibility = 'visible';
	},

	showBoxes: function(e) {
		var t = null;
		if (window.event && window.event.srcElement) {
			t = window.event.srcElement;
		} else if (e && e.target) {
			t = e.target;
		}
		var imageindex = gn.findImage(t);
		if (imageindex < 0)
			return;
		gn.setBoxes(gn.images[imageindex].notes,'block');
	},

	hideBoxes: function(e) {
		var t = null;
		if (window.event && window.event.srcElement) {
			t = window.event.srcElement;
		} else if (e && e.target) {
			t = e.target;
		}
		var imageindex = gn.findImage(t);
		if (imageindex < 0)
			return;
		clearTimeout(gn.hiderTimeout);
		gn.hiderTimeout = setTimeout(
			function() { gn.setBoxes(gn.images[imageindex].notes,'none') },
			300);
	},

	getMousePosition: function(event) {
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

	getElePosition: function(ele) {
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

gn.addEvent(window,"load",gn.init);
