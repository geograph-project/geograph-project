// Copied from http://nls.tileserver.com/api.js
// We use a copy because we dont want Google Analytics. Its use here would set the cookies directly on Geograph domains. Bad Robot. 
// Geograph

/**
 * @preserve TileServer.com Maps API: National Library of Scotland 
 * -----------------------------------------------------
 * This file is providing access to the tileset of the National Library of Scotland.
 * Public functions:
 *
 * - NLSTileUrlOS(x,y,z) - Tiles for Ordnance Survey map from 1920-1947
 *
 * The function returns URL for a tile image at the x,y,z position in the Spherical Mercator.
 * Without parameters it returns Bing SDK compantible for accessing the tiles.
 *
 * Please always get the tile url via this JavaScript API!
 * Direct access to the tile location is not recommended.
 * The server address will change as we update the dataset.
 *
 * Copyright (C) 2010 - Klokan Technologies GmbH
 * http://www.klokantech.com/
 */

// Global variable with the tileserver
var tileserver = "";
var tileserver_default = "uk.tileserver.com/_os1/r0/";
//var _gaq = _gaq || [];

// THE PUBLIC FUNCTIONS:
// =====================

/* Tiles for Ordnance Survey map from 1920-1947
 *
 * with x, y, z parameters it returns an URL for particular tile
 * with bounds paramter it returns expected results for OpenLayers
 * with tile, zoom parameters it behaves like Google getTileUrl function
 * without parameter it gives you the "Bing SDK" compatible universal string to access the tiles
 *
 * The function returns tiles from the tileserver_default, and after the dynamic test is finished from the fastest tileserver available
 */
function NLSTileUrlOS( x, y, z ) {

	// the "MAXZOOM" constant
	if (x == "MAXZOOM") return 14;

	// without given "x" we are returning Bing SDK string
	if (x == undefined) {
		return "http://t%2."+tileserver_default+'%4.jpg';
		// if (tileserver == "") return "http://t%2."+tileserver_default+"%4.jpg";
		// else return "http://t%2."+tileserver+'%4.jpg';
	}

	// with "${x}" we return OpenLayers Array - this will mostly return only the tileserver_default location
	if (x == "${x}") {
		var urls = new Array();
		if (tileserver == "")
			for (no=0;no<5;no++)
				urls.push("http://t"+no+"."+tileserver_default+z+'/'+x+'/'+y+'.jpg');
		else
			for (no=0;no<5;no++)
				urls.push("http://t"+no+"."+tileserver+z+'/'+x+'/'+y+'.jpg');
		return urls;
	}

	// behave like OpenLayers .getURL(bounds):
	if (x['left'] != undefined) {
		var bounds = x;
		var res = this['map']['getResolution']();
		x = Math.round((bounds['left'] - this['maxExtent']['left']) / (res * this['tileSize']['w']));
		y = Math.round((this['maxExtent']['top'] - bounds['top']) / (res * this['tileSize']['h']));
		z = this['map']['getZoom']();
	}

	// behave like Google .getTileUrl(tile, zoom):
	if (x['x'] != undefined && Number( y ) != NaN && z == undefined) {
		z = y;
		y = x['y'];
		x = x['x'];
	}

	// with numbers let's return directly the url to the tile on the server
	var no = (x+y) % 5;
	if (tileserver == "") return "http://t"+no+"."+tileserver_default+z+'/'+x+'/'+y+'.jpg';
	else return "http://t"+no+"."+tileserver+z+'/'+x+'/'+y+'.jpg';
}

/* CHOOSE A TILESERVER ON THE CLIENT SIDE
 * ======================================
 * NOT IMPLEMENTED IN THIS VERSION OF API
 */

// STATISTICS - TRACKING VISITORS WITH GOOGLE ANALYTICS ASYNC
// TODO: record the tile server location as well

/* Hobbled, by Geograph. See comment at start of file. 

_gaq.push(['_setAccount', 'UA-8073932-5']);
_gaq.push(['_setDomainName', 'none']);
_gaq.push(['_setAllowLinker', true]);
_gaq.push(['_setAllowHash', false]);
if (window.location.href.search("nls.tileserver.com") != -1)
	_gaq.push(['_setVar', ( window!=window.top ? "iframe" : "nls.tileserver.com" )]);
else 
	_gaq.push(['_setVar', 'api']);
_gaq.push(['_setCustomVar', 1, 'tileserver', tileserver, 2]);
_gaq.push(['_trackPageview']);

(function() {
  var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
  ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
  var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();

*/
