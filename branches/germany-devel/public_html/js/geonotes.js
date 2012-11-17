/*
 * Copyright (c)2012 Hansjoerg Lipp (hjlipp@web.de) - Licensed under: GNU Public License 2 or later
 *
 * Basic idea:
 * We have a container div with position:relative which contains the image of
 * class geonotes and an image map, where the notes can be specified using the
 * title attribute. This works without JavaScript.
 * If an area element of the image map has an id "noteareaNNNN", there must also
 * exist an element with id "noteboxNNNN" (might be a div with class notebox).
 * This script tries to implement some mouse over event handling for that box.
 * Additionally, an element "notetextNNNN" is required (might be a div with class
 * geonote). This script tries to show that element instead of the usual tool tip.
 * While displaying the note, the class of noteboxNNNN will be changed by prepending
 * "cur" to the class name.
 * The image should be located at a fixed position inside the container div. The div
 * containing the container div may be smaller than the container div, scrolling
 * is taken into account.
 *
 * This is inspired by
 *   http://www.kryogenix.org/code/browser/annimg/annimg.html
 * and
 *   http://www.kryogenix.org/code/browser/nicetitle/
 * but working differently in many ways.
 *
 */

var gn = {
	images: [],
	notes: {},
	current_note: null,
	current_image: null,

	init: function() {
		if (!document.getElementById ||
		    !document.createElement ||
		    !document.getElementsByTagName ||
		    !document.body // is there any browser which does that?
		   )
			return; // FIXME add other functions we need
		var images = document.getElementsByTagName('img');
		for (var i=0;i<images.length;i++) {
			if (images[i].offsetParent &&
			    images[i].getAttribute('usemap') != null &&
			    images[i].className.search(/\bgeonotes\b/) != -1) {
				gn.__initImage(images[i]);
			}
		}
	},

	__initImageInfo: function(img) {
		var imageinfo = {
			'img' : img,
			'notes' : [],
		};
		// FIXME skip image if padding-left, padding-top, border-left-width, border-top-width can't be read (or don't end with 'px')?
		var paddinglt = gn.getStyleXY(img, 'padding-left', 'padding-top');
		var borderlt  = gn.getStyleXY(img, 'border-left-width', 'border-top-width');
		//var borderrb  = gn.getStyleXY(img, 'border-right-width', 'border-bottom-width');

		//imageinfo.paddborderx = paddinglt[0] + borderlt[0];
		//imageinfo.paddbordery = paddinglt[1] + borderlt[1];
		//imageinfo.bordersx = borderlt[0] + borderrb[0]
		//imageinfo.bordersy = borderlt[1] + borderrb[1]
		imageinfo.paddborderoffsetx = paddinglt[0] + borderlt[0] + img.offsetLeft;
		imageinfo.paddborderoffsety = paddinglt[1] + borderlt[1] + img.offsetTop;
		return imageinfo;
	},

	__initImage: function(img) {
		var imageindex = gn.images.length++;
		var imageinfo = gn.__initImageInfo(img);
		gn.images[imageindex] = imageinfo;
		var mapName = img.getAttribute('usemap');
		img.setAttribute('usemap', null);
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
				var box = document.getElementById('notebox'+noteid);
				var txt = document.getElementById('notetext'+noteid);
				if (box && txt) {
					notelist[notelist.length] = noteid;
					var borderlt  = gn.getStyleXY(box, 'border-left-width', 'border-top-width');
					var borderrb  = gn.getStyleXY(box, 'border-right-width', 'border-bottom-width');
					var noteinfo = {
						'area':           curarea,
						'box':            box,
						'note':           txt,
						'img':            img,
						'imageinfo':      gn.images[imageindex],
						'noteid':         noteid, /* corresponds to the element's id, never changes */
						'id':             noteid, /* when creating a note, this changes to the actual note_id */
						'x1':             curarea.getAttribute('data-geonote-x1'),
						'x2':             curarea.getAttribute('data-geonote-x2'),
						'y1':             curarea.getAttribute('data-geonote-y1'),
						'y2':             curarea.getAttribute('data-geonote-y2'),
						'width':          curarea.getAttribute('data-geonote-width'),
						'height':         curarea.getAttribute('data-geonote-height'),
						'status':         curarea.getAttribute('data-geonote-status'),
						'pendingchanges': curarea.getAttribute('data-geonote-pendingchanges')!='0',
						'unsavedchanges': false,
						'lasterror'     : '',
						'hidden'        : false,
						'class'         : box.className,
						'bordersx'      : borderlt[0] + borderrb[0],
						'bordersy'      : borderlt[1] + borderrb[1],
					};
					if (noteinfo.status === null || noteinfo.status === "") {
						/* custom attributes not supported or provided */
						noteinfo.status = 'visible';
						noteinfo.pendingchanges = false;
						noteinfo.width = img.width;
						noteinfo.height = img.height;
						var coords = curarea.getAttribute('coords').split(',');
						if (coords.length == 4) {
							noteinfo.x1 = parseInt(coords[0]);
							noteinfo.y1 = parseInt(coords[1]);
							noteinfo.x2 = parseInt(coords[2]);
							noteinfo.y2 = parseInt(coords[3]);
						}
					}
					//alert([noteinfo.x1,noteinfo.y1,noteinfo.x2,noteinfo.y2,noteinfo.width,noteinfo.height].join(', '));
					gn.notes[noteid] = noteinfo;
					curarea.title = '';
					var left=box.style.left;
					var top=box.style.top;
					if (left.substr(left.length-2) == 'px' && top.substr(top.length-2) == 'px') {
						left=parseInt(left.substr(0,left.length-2))
						top=parseInt(top.substr(0,top.length-2))
						left += imageinfo.paddborderoffsetx;
						top += imageinfo.paddborderoffsety;
						box.style.left = left+'px';
						box.style.top = top+'px';
					}
					var width=box.style.width;
					var height=box.style.height;
					if (width.substr(width.length-2) == 'px' && height.substr(height.length-2) == 'px') {
						width=parseInt(width.substr(0,width.length-2))
						height=parseInt(height.substr(0,height.length-2))
						width -= noteinfo.bordersx;
						height -= noteinfo.bordersy;
						box.style.width = width+'px';
						box.style.height = height+'px';
					}
					/*AttachEvent(box,"mouseover",
						function() {
							clearTimeout(gn.hiderTimeout);
						}
					);*/
					gn.initBoxWidth(txt);
					AttachEvent(box,"mouseover",gn.showNoteText);
					AttachEvent(txt,"mouseout",gn.hideNoteTextEvent);
					//AttachEvent(box,"mouseout",gn.hideBoxesEvent);
					//AttachEvent(txt,"mouseout",gn.hideBoxesEvent);
				}
			}
		}

		AttachEvent(img,"mouseover",gn.showBoxes);
		AttachEvent(img.parentNode,"mouseout",gn.hideBoxesEvent);
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
		var noteinfo = {
			'area':           area,
			'box':            box,
			'note':           txt,
			'img':            img,
			'imageinfo':      gn.images[imageindex],
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
			'lasterror'     : '',
			'class'         : box.className,
		}
		gn.notes[noteid] = noteinfo;

		area.title = '';

		parea.appendChild(area);
		pbox.appendChild(box);
		var borderlt  = gn.getStyleXY(box, 'border-left-width', 'border-top-width');
		var borderrb  = gn.getStyleXY(box, 'border-right-width', 'border-bottom-width');
		noteinfo.bordersx = borderlt[0] + borderrb[0];
		noteinfo.bordersy = borderlt[1] + borderrb[1];
		gn.recalcBox(noteid);
		/*AttachEvent(box,"mouseover",
			function() {
				clearTimeout(gn.hiderTimeout);
			}
		);*/
		ptxt.appendChild(txt);
		gn.initBoxWidth(txt);
		AttachEvent(box,"mouseover",gn.showNoteText);
		AttachEvent(txt,"mouseout",gn.hideNoteTextEvent);
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

	scanParents: function (el, idprefix) {
		var len = idprefix.length;
		while (el !== null) {
			if (el.nodeType == 1 && el.id.slice(0, len) == idprefix) {
				return el;
			}
			el = el.parentNode;
		}
		return el;
	},

	setBoxes: function(idlist,disp) {
		for (var i = 0; i < idlist.length; i++) {
			var noteinfo = gn.notes[idlist[i]];
			noteinfo.box.style.display = noteinfo.hide || noteinfo.status=='deleted' ? 'none' : disp;
		}
	},

	recalcBox: function(noteid) { //, dx, dy) {
		var noteinfo = gn.notes[noteid];
		var imageinfo = noteinfo.imageinfo;
		var img = noteinfo.img;
		var width = img.width;
		var height = img.height;
		var dx = imageinfo.paddborderoffsetx;
		var dy = imageinfo.paddborderoffsety;
		var x1 = Math.floor(noteinfo.x1 * width / noteinfo.width);
		var x2 = Math.floor(noteinfo.x2 * width / noteinfo.width);
		var y1 = Math.floor(noteinfo.y1 * height / noteinfo.height);
		var y2 = Math.floor(noteinfo.y2 * height / noteinfo.height);
		noteinfo.area.coords = x1+","+y1+","+x2+","+y2;
		var box = noteinfo.box;
		box.style.left = (x1 + dx) + 'px';
		box.style.top = (y1 + dy) + 'px';
		box.style.width = (x2 - x1 + 1 - noteinfo.bordersx) + 'px';
		box.style.height = (y2 - y1 + 1 - noteinfo.bordersy) + 'px';
		var txt = noteinfo.note;
		txt.style.left='0px';
		txt.style.top='0px';
	},

	recalcBoxes: function(img) {
		var imageindex = gn.findImage(img);
		if (imageindex < 0)
			return;
		var idlist = gn.images[imageindex].notes;
		gn.hideNoteText();
		for (var i = 0; i < idlist.length; i++) {
			gn.recalcBox(idlist[i]);
		}
	},

	hideBoxesEvent: function(e) {
		/* did we really move _out_? */
		if (!gn.current_image) {
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
		/*var log = document.getElementById('log');
		if (log) {
			log.value += ele + (ele && ele.id !=='' ? ' (' +  ele.id + ')' : '') + ' -> ' + toele + (toele && toele.id !=='' ? ' (' +  toele.id + ')' : '') + '\n';
		}*/
		if (!ele || !toele)
			return;
		/*if (ele != gn.current_image.img.parentNode)
			return;
		while (ele != toele && toele.nodeName.toLowerCase() != 'body') {
			toele = toele.parentNode;
		}*/
		var findele = gn.current_image.img.parentNode;
		while (toele != findele && toele != document.body && toele) {
			toele = toele.parentNode;
		}
		if (findele == toele) { /* we only moved to a child element */
			return;
		}

		gn.hideBoxes();
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
		/*if (ele != gn.notes[gn.current_note].note)
			return;
		while (ele != toele && toele.nodeName.toLowerCase() != 'body') {
			toele = toele.parentNode;
		}*/
		var findele = gn.notes[gn.current_note].note;
		while (toele != findele && toele != document.body && toele) {
			toele = toele.parentNode;
		}
		if (findele == toele) { /* we only moved to a child element */
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
		var ele = null;
		if (window.event && window.event.srcElement) {
			ele = window.event.srcElement
		} else if (e && e.target) {
			ele = e.target
		}
		box = gn.scanParents(ele, "notebox");
		if (!box) {
			return;
		}
		var noteid = box.id.substr(7);
		
		var noteinfo = gn.notes[noteid];
		var txt = noteinfo.note;
		/*if (current_note && txt.id == current_note.id)
			return;*/
		if (gn.current_note) gn.hideNoteText();
		gn.current_note = noteid;
		txt.style.visibility = 'hidden';
		txt.style.display = 'block';
		var tw = txt.offsetWidth;
		var th = txt.offsetHeight;
		var dw = txt.parentNode.parentNode.clientWidth;
		var dh = txt.parentNode.parentNode.clientHeight;

		var ix1 = noteinfo.img.offsetLeft;
		var iy1 = noteinfo.img.offsetTop;
		var ix2 = ix1 + noteinfo.img.offsetWidth - 1;
		var iy2 = iy1 + noteinfo.img.offsetHeight - 1;
		var mpos = gn.getMousePosition(e); // TODO compare with mapping1.js
		var epos = gn.getElePosition(txt.parentNode);
		var sx = txt.parentNode.parentNode.scrollLeft;
		var sy = txt.parentNode.parentNode.scrollTop;
		var boxx = box.offsetLeft;
		var boxy = box.offsetTop;
		var boxxctr = boxx + (box.offsetWidth-1)/2;
		var boxyctr = boxy + (box.offsetHeight-1)/2;
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

		if (x < boxxctr) {
			x -= tw - 5 - 1;
		} else {
			x -= 5;
		}

		if (y < boxyctr) {
			y -= th - 5 - 1;
		} else {
			y -= 5;
		}

		if (x+tw > dw + sx) {
			x = dw - tw + sx;
		}
		if (x < sx) {
			x = sx;
		}
		if (y+th > dh + sy) {
			y = dh - th + sy;
		}
		if (y < sy) {
			y = sy;
		}
		if (x + tw - 1 > ix2) {
			x = ix2 - tw + 1;
		}
		if (x < ix1) {
			x = ix1;
		}
		if (y + th - 1 > iy2) {
			y = iy2 - th + 1;
		}
		if (y < iy1) {
			y = iy1;
		}
		txt.style.left = x+'px';
		txt.style.top = y+'px';

		box.className = 'cur' + noteinfo.class;
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
		if (gn.current_image) {
			gn.hideBoxes();
		}
		gn.current_image = gn.images[imageindex];
		gn.setBoxes(gn.current_image.notes,'block');
	},

	hideBoxes: function(/*e*/) {
		/*var t = null;
		if (window.event && window.event.srcElement) {
			t = window.event.srcElement;
		} else if (e && e.target) {
			t = e.target;
		}
		var imageindex = gn.findImage(t);
		if (imageindex < 0)
			return;*/
		/*clearTimeout(gn.hiderTimeout);
		gn.hiderTimeout = setTimeout(
			function() { gn.setBoxes(gn.images[imageindex].notes,'none') },
			300);*/
		//gn.setBoxes(gn.images[imageindex].notes,'none');
		if (gn.current_image) {
			gn.setBoxes(gn.current_image.notes,'none');
			gn.current_image = null;
		}
		gn.hideNoteText();
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
		if (ele.offsetParent) {
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

}
