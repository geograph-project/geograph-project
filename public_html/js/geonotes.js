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
 * the usual tool tip.
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
		for (var j=boxes.length-1;j>=0;j--) {
			if (boxes[j].getAttribute('shape').toLowerCase() == 'rect' && boxes[j].id.substr(0,8) == 'notearea') {
				var noteid=boxes[j].id.substr(8);
				var a=document.getElementById('notebox'+noteid);
				if (a) {
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

					gn.addEvent(a,"mouseover",
						function() {
							clearTimeout(gn.hiderTimeout);
						}
					);
					var txt = document.getElementById('notetext'+noteid);
					if (txt) {
						a.title = '';
						gn.addEvent(a,"mouseover",gn.showNoteText);
						//gn.addEvent(a,"mouseout",gn.hideNoteText);
						gn.addEvent(txt,"mouseout",gn.hideNoteTextEvent);
					}
				}
			}
		}

		gn.addEvent(img,"mouseover",gn.showBoxes);
		gn.addEvent(img,"mouseout",gn.hideBoxes);
	},

	__getPadding: function(img) {
		if(window.getComputedStyle) {
			var paddingleft=window.getComputedStyle(img, null).getPropertyValue('padding-left');
			var paddingtop=window.getComputedStyle(img, null).getPropertyValue('padding-top');
			var borderleft=window.getComputedStyle(img, null).getPropertyValue('border-left-width');
			var bordertop=window.getComputedStyle(img, null).getPropertyValue('border-top-width');
		} else if (img.currentStyle) {
			var paddingleft = img.currentStyle.paddingLeft;
			var paddingtop = img.currentStyle.paddingTop;
			var borderleft = img.currentStyle.borderLeftWidth;
			var bordertop = img.currentStyle.borderTopWidth;
		} else {
			return [ 0, 0 ];
		}
		if (  paddingleft.substr(paddingleft.length-2) != 'px'
		    ||paddingtop.substr(paddingtop.length-2) != 'px'
		    ||borderleft.substr(borderleft.length-2) != 'px'
		    ||bordertop.substr(bordertop.length-2) != 'px') {
			return [ 0, 0 ];
		}
		paddingleft=parseInt(paddingleft.substr(0,paddingleft.length-2));
		paddingtop=parseInt(paddingtop.substr(0,paddingtop.length-2));
		borderleft=parseInt(borderleft.substr(0,borderleft.length-2));
		bordertop=parseInt(bordertop.substr(0,bordertop.length-2));
		return [ paddingleft+borderleft, paddingtop+bordertop ];
	},

	__getParent: function (el, pTagName) {
		if (el == null)
			return null;
		else if (el.nodeType == 1 && el.tagName.toLowerCase() == pTagName.toLowerCase())	// Gecko bug, supposed to be uppercase
			return el;
		else
			return gn.__getParent(el.parentNode, pTagName);
	},

	__setBoxes: function(t,disp) {
		if (!t || !t.boxes) return;
		for (var i=0;i<t.boxes.length;i++) {
			t.boxes[i].style.display = disp;
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
		var txt = document.getElementById('notetext'+lnk.id.substr(7));
		if (!txt) return;
		/*if (current_note && txt.id == current_note.id)
			return;*/
		if (current_note) gn.hideNoteText();
		var mpos = gn.__getMousePosition(e); // TODO compare with mapping1.js
		var epos = gn.__getElePosition(lnk.parentNode);
		var x = mpos[0]-epos[0]-5; //FIXME move to left when outside visible range
		var y = mpos[1]-epos[1]-5; //FIXME move to top when outside visible range
		x += lnk.parentNode.scrollLeft; //FIXME portable?
		y += lnk.parentNode.scrollTop;
		txt.style.left = x+'px';
		txt.style.top = y+'px';

		current_note = txt;
		current_note.style.display = 'block';
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
		if (document.documentElement) { //FIXME?
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
