// This shows a lat/lon graticule on the map. Interval is automatic
// Bill Chadwick, 

function OGBGraticule() {
}
OGBGraticule.prototype = new GOverlay();

OGBGraticule.prototype.initialize = function(map) {

  //save for later
  this.map_ = map;

  //array for lines 
  this.lines_ = new Array();
  
  //array for labels
  this.divs_ = new Array();
      
}

OGBGraticule.prototype.remove = function() {

  try{
		var i = 0;				
		for(i=0; i< this.lines_.length; i++)
			this.map_.removeOverlay(this.lines_[i]);	
			
	    var div = this.map_.getPane(G_MAP_MARKER_SHADOW_PANE);
        for(i=0; i< this.divs_.length; i++)
	        div.removeChild(this.divs_[i]);

	}
  catch(e){
  }

}

OGBGraticule.prototype.copy = function() {
  return new OGBGraticule();
}

// Redraw the graticule based on the current projection and zoom level
OGBGraticule.prototype.redraw = function(force) {

  //clear old
  this.remove();

  //best color for writing on the map
  this.color_ = this.map_.getCurrentMapType().getTextColor();

  //determine graticule interval
  var bnds = this.map_.getBounds();
  
  var l = bnds.getSouthWest().lng();
  var b = bnds.getSouthWest().lat();
  var t = bnds.getNorthEast().lat();
  var r = bnds.getNorthEast().lng();

  //sanity - limit to os grid area
  if (t < 49.0)
	return;
  if(b > 61.0)
	return;
  if(r < -8.0)
    return;  
  if(l > 2.0)
    return;
    

  //grid interval in km   

  var gr = this.enclosingOsgbRect(l,b,t,r);
  
  var west = gr.bl.east / 1000.0;
  var south = gr.bl.north / 1000.0;
  var east = gr.tr.east / 1000.0;
  var north = gr.tr.north / 1000.0;
  
  var width = east-west;
  var height = north-south; 
  
  var ww = Math.max(width,height); 
  
  var d;//km initial/min interval
  ww /= 10;//want about 10 grid lines
  if(ww < 0.2)
	d = 0.1;
  else if(ww < 0.5)
	d = 0.2;
  else if(ww < 0.7)
	d = 0.5;
  else if(ww < 2.0)
	d = 1.0;
  else if(ww < 3.0)
    d = 2.0;
  else if(ww < 7.0)
    d = 5.0;
  else if(ww < 13.0)
    d = 10.0;
  else if(ww < 27.0)
    d = 20.0;
  else if(ww < 70.0)
    d = 50.0;
  else 
    d = 100.0;
    	

  //round iteration limits to the computed grid interval
  east = Math.ceil(east/d)*d;
  west = Math.floor(west/d)*d;
  north = Math.ceil(north/d)*d;
  south = Math.floor(south/d)*d;



  //Sanity / limit
  if (west <= 0.0)
	west = 0.0;
  if(east >= 700.0)
	east = 700.0;
  if(south < 0.0)
    south = 0.0;  
  if(north > 1300.0)
    north = 1300.0;
      
  this.lines_ = new Array();
  this.divs_ = new Array();
  
  var i=0;//count inserted lines
  var j=0;//count labels
  
  //pane/layer to write on
  var mapDiv = this.map_.getPane(G_MAP_MARKER_SHADOW_PANE);

     
  //horizontal lines
  var s = south;
  while(s<=north){
  
         var pts = new Array();	
         //under 10km grid squares draw as straight line top to bottom	 
         if(d < 10.0){
			pts[0] = this.GLatLngFromEN(east,s);
			pts[1] = this.GLatLngFromEN(west,s);		
		 }
         //over 10km grid squares draw as set of segments
		 else{
			var e = west;
			var q = 0;
			while(e<=east){
				pts[q] = this.GLatLngFromEN(e,s);
			    q++;
				e += d;
			}
		 }
		 

		 //line
		 this.lines_[i] = new GPolyline(pts,this.color_,1);
		 this.map_.addOverlay(this.lines_[i]);
		 i++;
		 
		 //label at height of second horz line
		 try{
		 var p = this.map_.fromLatLngToDivPixel(this.GLatLngFromEN(west+d+d,s));
		 
		 var dv = document.createElement("DIV");
		 var x = p.x + 3;
		 dv.style.position = "absolute";
         dv.style.left = x.toString() + "px";
         dv.style.top = p.y.toString() + "px";
		 dv.style.color = this.color_;
		 dv.style.fontFamily='Arial';
		 dv.style.fontSize='x-small';
		 dv.style.cursor = "help";
		 dv.title = NE2NGR( (Math.floor(west+0.04)+d+d+0.4)*1000.0,(Math.floor(s+0.04)+0.4)*1000.0 ).substr(0,2) + " (" + Math.floor(s+0.04).toString() + " km North)";
		 var km = (Math.round(s)%100).toString();
		 if (km.length < 2)
			km = "0" + km;
			
		 if(d < 1.0){
		    km = (Math.round(s*10)%1000).toString();
		    if (km.length < 3)
			    km = "0" + km;
		    if (km.length < 3)
			    km = "0" + km;
			km = km.substr(0,2) + "." + km.substr(2,1);
		 }	
		 if(d >= 100.0)
			km = dv.title.substr(0,2);
			
         dv.innerHTML = km;
		 mapDiv.insertBefore(dv,null);
		 this.divs_[j] = dv;
		 j++;
		 }
		 catch(ex){
		 }


		 s += d; 	
		 
		 	 			 
  }
  

  //vertical lines
  var e = west;
  while(e<=east){

         var pts = new Array();		 

         //under 10km grid squares draw as straight line top to bottom	 
         if(d < 10.0){
		 pts[0] = this.GLatLngFromEN(e,north);
		 pts[1] = this.GLatLngFromEN(e,south);		
		 }
         //over 10km grid squares draw as set of segments
		 else{
			var s = south;
			var q = 0;
			while(s<=north){
				pts[q] = this.GLatLngFromEN(e,s);
			    q++;
				s += d;
			}
		 }


		 //line
		 this.lines_[i] = new GPolyline(pts,this.color_,1);
		 this.map_.addOverlay(this.lines_[i]);
		 i++;

		 //label on second vert line 
		    try{
			var p = this.map_.fromLatLngToDivPixel(this.GLatLngFromEN(e,south+d+d));

			var dv = document.createElement("DIV");
			var y = p.y + 3;
			dv.style.position = "absolute";
			dv.style.left = p.x.toString() + "px";
			dv.style.top = y.toString() + "px";
			dv.style.color = this.color_;
			dv.style.fontFamily='Arial';
			dv.style.fontSize='x-small';
		    dv.style.cursor = "help";
		    dv.title = NE2NGR( (Math.floor(e+0.04)+0.4)*1000.0,(Math.floor(south+0.04)+d+d+0.4)*1000.0 ).substr(0,2) + " (" + Math.floor(e+0.04).toString() + " km East)";
			var km = (Math.round(e)%100).toString();
			if (km.length < 2)
				km = "0" + km;
			if(d < 1.0){
			    km = (Math.round(e*10)%1000).toString();
			    if (km.length < 3)
				    km = "0" + km;
			    if (km.length < 3)
				    km = "0" + km;
				km = km.substr(0,2) + "." + km.substr(2,1);
			}
			if(d >= 100.0)
				km = dv.title.substr(0,2);
			
			if( e != (west+d+d) ){
				dv.innerHTML = km;
				mapDiv.insertBefore(dv,null);
				this.divs_[j] = dv;
				j++;
				}
			}
			catch(ex){
			}
			


		 e += d; 		 		
		 
  }
 
}

//from OS east, north in KM to WGS84 Lat/Lon in a GLatLng
OGBGraticule.prototype.GLatLngFromEN = function(eastKm,northKm) {
		 var ogb = NEtoLL(eastKm*1000.0,northKm*1000.0);
		 var wg84 = OGBToWGS84(ogb.lat,ogb.lon,0);
		 return new GLatLng(wg84.lat,wg84.lon);
}

//return rect in OSGB N/E coords that encloses the given wgs84 rect
OGBGraticule.prototype.enclosingOsgbRect = function(WGleft,WGbottom,WGtop,WGright){

	var blOGB = WGS84ToOGB(WGbottom,WGleft,0);
	var trOGB = WGS84ToOGB(WGtop,WGright,0);
	var brOGB = WGS84ToOGB(WGbottom,WGright,0);
	var tlOGB = WGS84ToOGB(WGtop,WGleft,0);
	
	var blEN = LLtoNE(blOGB.lat,blOGB.lon);
	var trEN = LLtoNE(trOGB.lat,trOGB.lon);
	var brEN = LLtoNE(brOGB.lat,brOGB.lon);
	var tlEN = LLtoNE(tlOGB.lat,tlOGB.lon);
	
	var e = Math.min(blEN.east,tlEN.east); 
	var w = Math.max(brEN.east,trEN.east); 
	var s = Math.min(blEN.north,brEN.north); 
	var n = Math.max(trEN.north,tlEN.north); 
	
	return new OGBRect(new OGBNorthEast(e,s),new OGBNorthEast(w,n));
}
  







