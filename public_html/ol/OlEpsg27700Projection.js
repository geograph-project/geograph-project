/*
* A British Grid projection for OpenLayers 2.12
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

// An OSGB Projection class for OpenLayers using OS transformation code in grid-projections.js rather than proj4.js

OpenLayers.Projection.OS = OpenLayers.Class(OpenLayers.Projection, {

    // These are the two methods of OpenLayers.Projection that must be overridden
    getCode: function () {
        return "EPSG:27700";
    },

    getUnits: function () {
        return "m";
    }
});

// Static conversion methods */

// WGS84 Lat/Lon to UKOS East/North
OpenLayers.Projection.OS.wgs84ToOgb =  function (point) {
    var lonlat = new Wgs84Point(point.x, point.y);
    var ogbPoint = UkGridProjection.getOgbPointFromLonLat(lonlat);
    point.x = ogbPoint.east;
    point.y = ogbPoint.north;
    return point;
};

// UKOS East/North to WGS84 Lat/Lon
OpenLayers.Projection.OS.ogbToWgs84 = function (point) {
    var ogbPoint = new OgbPoint(point.x, point.y);
    var lonlat = UkGridProjection.getLonLatFromOgbPoint(ogbPoint);
    point.x = lonlat.longi;
    point.y = lonlat.lati;
    return point;
};

// UKOS East/North to Google via WGS84
OpenLayers.Projection.OS.ogbToGoogle = function (point) {
    var p2 = OpenLayers.Projection.OS.ogbToWgs84(point);
    var p3 = new OpenLayers.LonLat(p2.x, p2.y);
    p3.transform("EPSG:4326", "EPSG:900913");
    point.x = p3.lon;
    point.y = p3.lat;
    return point;
};

// Google to UKOS East/North via WGS84
OpenLayers.Projection.OS.googleToOgb = function (point) {
    var p2 = new OpenLayers.LonLat(point.x, point.y);
    p2.transform("EPSG:900913", "EPSG:4326");
    var p3 = OpenLayers.Projection.OS.wgs84ToOgb({ x: p2.lon, y: p2.lat });
    point.x = p3.x;
    point.y = p3.y;
    return point;
};


// register the custom projection transforms
OpenLayers.Projection.addTransform("EPSG:4326", "EPSG:27700", OpenLayers.Projection.OS.wgs84ToOgb);
OpenLayers.Projection.addTransform("EPSG:27700", "EPSG:4326", OpenLayers.Projection.OS.ogbToWgs84);
OpenLayers.Projection.addTransform("EPSG:900913", "EPSG:27700", OpenLayers.Projection.OS.googleToOgb);
OpenLayers.Projection.addTransform("EPSG:27700", "EPSG:900913", OpenLayers.Projection.OS.ogbToGoogle);

// EPSG:27700 border for british grid as an OpenLayers Polygon
OpenLayers.Projection.OS.bounds = new OpenLayers.Geometry.Polygon(new OpenLayers.Geometry.LinearRing(

	[new OpenLayers.Geometry.Point(0, 0),
	    new OpenLayers.Geometry.Point(700000, 0),
	    new OpenLayers.Geometry.Point(700000, 1300000),
	    new OpenLayers.Geometry.Point(0, 1300000),
	    new OpenLayers.Geometry.Point(0,0)]
)
    );

//check epsg:4326 lat/lon for Britishness
OpenLayers.Projection.OS.isValidLonLat = function(longitude, latitude) {
    return OpenLayers.Projection.OS.bounds.containsPoint(new OpenLayers.Geometry.Point(longitude, latitude).transform("EPSG:4326","EPSG:27700"));
};

//stringify a lonLat in epsg:27700
OpenLayers.Projection.OS.lonLatToString = function(lonLat, mapResolution) {
    var digits = (mapResolution <= 5) ? 5 : (mapResolution <= 50) ? 4 : (mapResolution <= 500) ? 3 : 2;
    return UkGridProjection.gridRefFromEastNorth(lonLat.lon, lonLat.lat, digits);
};