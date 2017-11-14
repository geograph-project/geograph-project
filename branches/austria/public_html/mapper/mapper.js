
/* Replacement layer getURL function, WMS is close to what we need, but for slight request differences */
function geographURL(bounds){
	return this.url + "&e="+Math.round(bounds.left/1000)+"&n="+Math.round(bounds.bottom/1000)+"&z="+map.getZoom();//+"&b="+bounds.toBBOX();
}

//Credit for this script to http://grand.edgemaster.googlepages.com/

/* Replacement draw function for PanZoom to remove the Zoom */
function drawNoZoom(px) {
	// initialize our internal div
	OpenLayers.Control.prototype.draw.apply(this, arguments);
	px = this.position;

	// place the controls
	this.buttons = [];

	var sz = new OpenLayers.Size(18,18);
	var centered = new OpenLayers.Pixel(px.x+sz.w/2, px.y);

	this._addButton("panup", "north-mini.png", centered, sz);
	px.y = centered.y+sz.h;
	this._addButton("panleft", "west-mini.png", px, sz);
	this._addButton("panright", "east-mini.png", px.add(sz.w, 0), sz);
	this._addButton("pandown", "south-mini.png", centered.add(0, sz.h*2), sz);
	return this.div;
}

/* Replacement defaultClick function for MouseDefaults control, onclick will stop the grid ref updating */
function mouseDefaultClick(evt) {
	//if (!OpenLayers.Event.isLeftClick(evt) && !((evt.button) && (evt.button == 0))) return;
	var notAfterDrag = !this.performedDrag;
	this.performedDrag = false;
	
	if(notAfterDrag) {
		// Global, defined in init(), used in osredraw()
		// Freezes the map position onclick of the map, unfreeze on another click
		
		if(osposition.update || osposition.lastgr != osposition.curgr) {
			osposition.update = 0;
			
			osposition.element.innerHTML = '<a href="javascript:void(osposition.update = true);">X</a> ' + osposition.curgr + ' <a href="/gridref/'+escape(osposition.curgr)+'" target="_top">Go</a>';
			if (document.getElementById('clickto').checked) {
				parent.frames.browseframe.location.replace("/gridref/"+osposition.curgr+"?inner");
			} else {
				parent.frames.browseframe.location.replace("/gridref/"+osposition.curgr+"?centi=X&inner");
			}
		} else {
			osposition.update = 1;
			parent.frames.browseframe.location.replace("about:blank");
			osposition.lastXy = new OpenLayers.Pixel(); //can't use null
			osposition.redraw(evt);
		}
		osposition.lastgr = osposition.curgr;
	}
	
	return notAfterDrag;
}

/* Replacement defaultMouseDown function for MouseDefaults control, removed the shift-click functionality that starts rubber-band zoom */
function mouseDefaultMouseDown (evt) {
	if (!OpenLayers.Event.isLeftClick(evt)) { return; }
	this.mouseDragStart = evt.xy.clone();
	this.performedDrag  = false;

	document.onselectstart=function() { return false; };
	OpenLayers.Event.stop(evt);
}

/* Replacement redraw function for MousePosition control, formats as NGR rather than raw numbers */
function showGridRef(evt) {
	var lonLat;

	
	
	if (evt === null) {
		this.element.innerHTML = "loaded.";
		return;
	} else {
		if (this.lastXy === null || Math.abs(evt.xy.x - this.lastXy.x) > this.granularity || Math.abs(evt.xy.y - this.lastXy.y) > this.granularity) {
			if (evt)
				this.lastXy = evt.xy;
			return;
		}
		
		lonLat = this.map.getLonLatFromPixel(evt.xy);
		this.lastXy = evt.xy;
	}

	var digits = parseInt(this.numdigits,10);

	var gro = new GT_OSGB();
	gro.northings = lonLat.lat;
	gro.eastings = lonLat.lon;

	osposition.curgr = gro.getGridRef(digits);
	
	if(!this.update) {
		return;
	}

	var newHtml = this.prefix + osposition.curgr + this.suffix;


	if (newHtml != this.element.innerHTML) {
		this.element.innerHTML = newHtml;
	}
}

String.prototype.trim = function () {
	return this.replace(/^\s+|\s+$/g,"");
};

function parseLocation() {
	var coordstr = document.getElementById('coordin').value.trim().toUpperCase();
	var coord = new GT_OSGB();
	/*if(coord.parseGridRef(coordstr)) {
		ll = new OpenLayers.LonLat(coord.eastings, coord.northings);
		//ml.addMarker(new OpenLayers.Marker(ll));
		map.setCenter(ll);
	} else {
		coord = new GT_Irish();
		if(coord.parseGridRef(coordstr)) {
			window.location = "/gridref/"+coordstr;
		} else {*/
			coord = new GT_German32();
			if(coord.parseGridRef(coordstr)) {
				window.location = "/gridref/"+coordstr;
			} else {
				coord = new GT_German33();
				if(coord.parseGridRef(coordstr)) {
					window.location = "/gridref/"+coordstr;
				} else {
					coord = new GT_German31();
					if(coord.parseGridRef(coordstr)) {
						window.location = "/gridref/"+coordstr;
					}
				}
			}
		/*}
	}*/
}
