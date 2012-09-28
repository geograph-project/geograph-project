/*
* An Irish Grid projection for OpenLayers 2.12
*
* This file copyright (c)2012 Bill Chadwick (bill.chadwick2@gmail.com)
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
* 
*/

// An Irish Grid Projection class for OpenLayers using transformation code in grid-projections.js rather than proj4.js
// requires OpenLayers.Geometry.Point .Polygon and .LinearRing

OpenLayers.Projection.Irish = OpenLayers.Class(OpenLayers.Projection, {

    // These are the two methods of OpenLayers.Projection that must be overridden
    getCode: function () {
        return "EPSG:29902";
    },

    getUnits: function () {
        return "m";
    }
});

// Static conversion methods */

// WGS84 Lat/Lon to Irish East/North
OpenLayers.Projection.Irish.wgs84ToIrish = function (point) {
    var lonlat = new Wgs84Point(point.x, point.y);
    var irishPoint = IrishProjection.getIrishPointFromLonLat(lonlat);
    point.x = irishPoint.east;
    point.y = irishPoint.north;
    return point;
};

// Irish East/North to WGS84 Lat/Lon
OpenLayers.Projection.Irish.irishToWgs84 = function (point) {
    var irishPoint = new IrishPoint(point.x, point.y);
    var lonlat = IrishProjection.getLonLatFromIrishPoint(irishPoint);
    point.x = lonlat.longi;
    point.y = lonlat.lati;
    return point;
};

// Irish East/North to Google via WGS84
OpenLayers.Projection.Irish.irishToGoogle = function (point) {
    var p2 = OpenLayers.Projection.Irish.irishToWgs84(point);
    var p3 = new OpenLayers.LonLat(p2.x, p2.y);
    p3.transform("EPSG:4326", "EPSG:900913");
    point.x = p3.lon;
    point.y = p3.lat;
    return point;
};

// Google to Irish East/North via WGS84
OpenLayers.Projection.Irish.googleToIrish = function (point) {
    var p2 = new OpenLayers.LonLat(point.x, point.y);
    p2.transform("EPSG:900913", "EPSG:4326");
    var p3 = OpenLayers.Projection.Irish.wgs84ToIrish({ x: p2.lon, y: p2.lat });
    point.x = p3.x;
    point.y = p3.y;
    return point;
};

// register the custom projection transforms
OpenLayers.Projection.addTransform("EPSG:4326", "EPSG:29902", OpenLayers.Projection.Irish.wgs84ToIrish);
OpenLayers.Projection.addTransform("EPSG:29902", "EPSG:4326", OpenLayers.Projection.Irish.irishToWgs84);
OpenLayers.Projection.addTransform("EPSG:900913", "EPSG:29902", OpenLayers.Projection.Irish.googleToIrish);
OpenLayers.Projection.addTransform("EPSG:29902", "EPSG:900913", OpenLayers.Projection.Irish.irishToGoogle);

//If the OS custom projection exists, make and register extra transforms between UKOS and Irish

if (OpenLayers.Projection.OS) {

    // UKOS East/North to Irish via WGS84
    OpenLayers.Projection.Irish.ogbToIrish = function (point) {
        var p2 = OpenLayers.Projection.OS.ogbToWgs84(point);
        return OpenLayers.Projection.Irish.wgs84ToIrish(p2);
    };

    // Irish to UKOS East/North via WGS84
    OpenLayers.Projection.Irish.irishToOgb = function (point) {
        var p2 = OpenLayers.Projection.Irish.irishToWgs84(point);
        return OpenLayers.Projection.OS.wgs84ToOgb(p2);
    };

    OpenLayers.Projection.addTransform("EPSG:27700", "EPSG:29902", OpenLayers.Projection.Irish.ogbToIrish);
    OpenLayers.Projection.addTransform("EPSG:29902", "EPSG:27700", OpenLayers.Projection.Irish.irishToOgb);

}

//rough border for Ireland in EPSG:29902 as an OpenLayers Polygon
OpenLayers.Projection.Irish.bounds = new OpenLayers.Geometry.Polygon(new OpenLayers.Geometry.LinearRing(

	[new OpenLayers.Geometry.Point(0, 0),
	    new OpenLayers.Geometry.Point(300000, 0),
	    new OpenLayers.Geometry.Point(400000, 300000),
	    new OpenLayers.Geometry.Point(370000, 400000),
	    new OpenLayers.Geometry.Point(320000, 470000),
	    new OpenLayers.Geometry.Point(200000, 500000),
	    new OpenLayers.Geometry.Point(0, 500000),
	    new OpenLayers.Geometry.Point(0, 0)]
)
    );

//check epsg:4326 lat/lon for Irishness
OpenLayers.Projection.Irish.isValidLonLat = function(longitude, latitude) {
    return OpenLayers.Projection.Irish.bounds.containsPoint(new OpenLayers.Geometry.Point(longitude, latitude).transform("EPSG:4326", "EPSG:29902"));
};

//stringify a lonLat in epsg:29902
OpenLayers.Projection.Irish.lonLatToString = function(lonLat, mapResolution) {
    var digits = (mapResolution <= 5) ? 5 : (mapResolution <= 50) ? 4 : (mapResolution <= 500) ? 3 : 2;
    return IrishProjection.gridRefFromEastNorth(lonLat.lon, lonLat.lat, digits);
};