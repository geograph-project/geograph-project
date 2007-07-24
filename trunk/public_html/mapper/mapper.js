
/* Replacement layer getURL function, WMS is close to what we need, but for slight request differences */
function geographURL(bounds){
	return this.url + "?e="+Math.round(bounds.left/1000)+"&n="+Math.round(bounds.bottom/1000);
}

//Credit for this script to http://grand.edgemaster.googlepages.com/

/* Replacement draw function for PanZoom to remove the Zoom */
function drawNoZoom(px) {
	// initialize our internal div
	OpenLayers.Control.prototype.draw.apply(this, arguments);
	px = this.position;

	// place the controls
	this.buttons = new Array();

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
	if (!OpenLayers.Event.isLeftClick(evt)) return;
	var notAfterDrag = !this.performedDrag;
	this.performedDrag = false;
	
	if(notAfterDrag) {
		// Global, defined in init(), used in osredraw()
		// Freezes the map position onclick of the map, unfreeze on another click
		if(osposition.update) {
			osposition.update = 0;
		} else {
			osposition.update = 1;
		}
	}
	
	return notAfterDrag;
}

/* Replacement redraw function for MousePosition control, formats as NGR rather than raw numbers */
function showGridRef(evt) {
	var lonLat;

	if(!this.update) {
		return;
	}
	
	if (evt == null) {
		this.element.innerHTML = "loaded.";
		return;
	} else {
		if (this.lastXy == null || Math.abs(evt.xy.x - this.lastXy.x) > this.granularity || Math.abs(evt.xy.y - this.lastXy.y) > this.granularity) {
			this.lastXy = evt.xy;
			return;
		}
		
		lonLat = this.map.getLonLatFromPixel(evt.xy);
		this.lastXy = evt.xy;
	}
        
	var digits = parseInt(this.numdigits);
        		
	var gro = new GT_OSGB();
	gro.northings = lonLat.lat;
	gro.eastings = lonLat.lon;
        		
	var gr = gro.getGridRef(digits);
        		
	var newHtml = this.prefix + gr + this.suffix;


	if (newHtml != this.element.innerHTML) {
		this.element.innerHTML = newHtml;
	}
}


function parseLocation() {
	var coordstr = document.getElementById('coordin').value;
	var coord = new GT_OSGB();
	if(coord.parseGridRef(coordstr)) {
		ll = new OpenLayers.LonLat(coord.eastings, coord.northings);
		//ml.addMarker(new OpenLayers.Marker(ll));
		map.setCenter(ll, 0);
	} else {
		var coord = new GT_Irish();
		if(coord.parseGridRef(coordstr)) {
			window.location = "/gridref/"+coordstr;
		}
	}
}