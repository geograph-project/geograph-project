/*
* A British Grid projection OS OpenSpace Pro baselayer for OpenLayers 2.12
* together with an an OpenLayers.Control
* for the OS OpenSpace logo. This version for Geograph.
* See the accompanying uk-geograph-layer-map.htm and .js for example usage.
*
* This file copyright (c)2012 Bill Chadwick (bill.chadwick2@gmail.com)
* with some inspiration from (amongst others) Charles Harrison, petrdlouhy and Peter Robins
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



// An OpenLayers Control for the OS  logo, CSS class name defaults to olControlOsLogo
// use style in your CSS to position the control
// The control is redrawn on any type of layer change

OpenLayers.Control.OsLogo = OpenLayers.Class(OpenLayers.Control, {

    destroy: function () {
        this.map.events.un({
            "removelayer": this.updateLogo,
            "addlayer": this.updateLogo,
            "changelayer": this.updateLogo,
            "changebaselayer": this.updateLogo,
            scope: this
        });

        OpenLayers.Control.prototype.destroy.apply(this, arguments);
    },

    draw: function () {
        OpenLayers.Control.prototype.draw.apply(this, arguments);

        this.map.events.on({
            'changebaselayer': this.updateLogo,
            'changelayer': this.updateLogo,
            'addlayer': this.updateLogo,
            'removelayer': this.updateLogo,
            scope: this
        });
        this.updateLogo();

        return this.div;
    },

    // draw the logo or nothing
    updateLogo: function () {
        var layer;
        var i;
        this.div.innerHTML = "";
        if (this.map && this.map.layers) {
            for (i = 0; i < this.map.layers.length; i++) {
                layer = this.map.layers[i];
                if (layer.OSLogoUrl && layer.getVisibility()) {
                    this.div.innerHTML = "<img src='" + layer.OSLogoUrl + "'/>";
                    break;
                }
            }
            if (i === this.map.layers.length) {
                this.div.innerHTML = "";
            }
        }
    },

    CLASS_NAME: "OpenLayers.Control.OsLogo"
});



// Now the  OS OpenSpace base layer for OpenLayers (subclassed from OpenLayers.Layer.WMS)
// - construction options must include OSApiKey and OSKeysUrl and can include layerName for layer switcher

OpenLayers.Layer.OsOpenSpaceLayer = OpenLayers.Class(OpenLayers.Layer.WMS, {

    OSWmsServer: "http://osopenspacepro.ordnancesurvey.co.uk/osmapapi/ts", // base WMS URL of OS OpenSpace Pro tile server

    // logoUrl, to comply with the OpenSpace TOU, an OpenLayers.Control.OsLogo should be added to the map (or the logo be otherwise displayed)
    OSLogoUrl: "http://osopenspacepro.ordnancesurvey.co.uk/osmapapi/img_versions/img_1.3/OS/poweredby.png",  // the logo is styled with the class olControlOsLogo 

    initialize: function (options) {
        OpenLayers.Layer.WMS.prototype.initialize.apply(
		    this,
		    [
		        options.layerName || "OS OpenSpace", // layer name for OL layerswitcher
		        this.OSWmsServer, // base WMS URL
		        {format: 'image/png', key: options.OSAPIKey, url: options.OSKeysUrl }, //WMS parameters
		        OpenLayers.Util.extend(
			        options,
			        {
			            // the attribution is styled with the class olControlAttribution
			            // again to to comply with the OpenSpace TOU, an OpenLayers.Control.Attribution should be added to the map (or the attrib be otherwise displayed)
			            attribution: "&copy; Crown Copyright &amp; Database Right 2012. All rights reserved. "
								        + "<a href=\"http://openspace.ordnancesurvey.co.uk/openspace/developeragreement.html#enduserlicense\" target=\"_blank\" title=\"openspace.ordnancesurvey.co.uk\">End User License Agreement</a>",
			            projection: new OpenLayers.Projection.OS(), // our custom projection from OlEpsg27700Projection.js
			            maxExtent: new OpenLayers.Bounds(0, 0, 800000, 1300000), // essential to get the correct WMS requests
			            resolutions: [1000, 500, 200, 100, 50, 25, 10, 5, 2.5, 2, 1], // metres per pixel
			            products: ["OV1", "OV2", "MSR", "MS", "250KR", "250K", "50KR", "50K", "25K", "VMLR", "VML"], //product names			            
			            tile200: new OpenLayers.Size(200, 200), // normal OpenSpace tile size
			            tile250: new OpenLayers.Size(250, 250), // OpenSpace Streetview tile size is different
			            isBaseLayer: true
			        }
			    )
		    ]
	    );
    },

    //layer's maxExtent in Wgs84
    getWgs84Extent: function () {
        return this.maxExtent.clone().transform("EPSG:27700", "EPSG:4326");
    },

    //layer's max extent in Google
    getGoogleExtent: function () {
        return this.maxExtent.clone().transform("EPSG:27700", "EPSG:900913");
    },

    // override moveTo to adjust tile size if needed and setup layer name for WMS requests if zoom has changed
    moveTo: function (bounds, zoomChanged, dragging) {
        if (zoomChanged || !this.params.layers) {
            var resolution = this.getResolution();
            var product = this.products[this.map.getZoom()];

            // OS OpenPace Streetview at 1 and 2m resolution uses a different tile size to the resolutions
            var oTileSize = this.tileSize;
            this.setTileSize(resolution <= 2 ? this.tile250 : this.tile200);
            if (this.tileSize !== oTileSize) {
                this.clearGrid();
            }
            // for OS OpenSpace, the WMS layername is the resolution  
            this.params = OpenLayers.Util.extend(this.params, OpenLayers.Util.upperCaseObject({ "layers": resolution, "product": product }));
        }

        //do superclass moveTo
        OpenLayers.Layer.WMS.prototype.moveTo.apply(this, arguments);
    },

    CLASS_NAME: "OpenLayers.Layer.OsOpenSpaceLayer"
});


/* OL2.12 patch to Map.setBaseLayer, handles recentering at closest zoom/centre of new base layer if projection changes */
/* and reprojects patched Vector and Base layers (see patches below) */


/** 
* APIMethod: setBaseLayer
* Allows user to specify one of the currently-loaded layers as the Map's new base layer.
* 
* Parameters:
* newBaseLayer - {<OpenLayers.Layer>}
*/

OpenLayers.Map.prototype.setBaseLayer = function (newBaseLayer) {

    var oldExtent = null;
    var oldProjection = null;
    var l;

    if (this.baseLayer) {
        oldExtent = this.baseLayer.getExtent();
        oldProjection = this.getProjectionObject();
    }

    if (newBaseLayer !== this.baseLayer) {

        // ensure newBaseLayer is already loaded
        if (OpenLayers.Util.indexOf(this.layers, newBaseLayer) !== -1) {

            // preserve center and scale as best we can when changing base layers
            var center = this.getCachedCenter();

            // either get the center from the old Extent or just from 
            // the current center of the map.
            var newCenter = (oldExtent) ? oldExtent.getCenterLonLat() : center;
            var newExtent = (oldExtent) ? oldExtent.clone() : null;

            // make the old base layer invisible 
            if (this.baseLayer !== null && !this.allOverlays) {
                this.baseLayer.setVisibility(false);
            }

            // set new baselayer
            this.baseLayer = newBaseLayer;

            if (!this.allOverlays || this.baseLayer.visibility) {
                this.baseLayer.setVisibility(true);
                // Layer may previously have been visible but not in range.
                // In this case we need to redraw it to make it visible.
                if (this.baseLayer.inRange === false) {
                    this.baseLayer.redraw();
                }
            }

            // Convert centre point to new layer projection - so we can 
            // maintain roughly the same view
            var newProjection = this.getProjectionObject();
            if (oldProjection) {
                if (!oldProjection.equals(newProjection)) {
                    newCenter.transform(oldProjection.getCode(), newProjection.getCode());
                    newExtent.transform(oldProjection.getCode(), newProjection.getCode());

                    // check that transformed extent and centre lies inside the new base layer's maxExtent if it has one
                    // use the max extent's centre if not and make the map's restricted extent = the baseLayer's maxExtent
                    if (this.baseLayer.maxExtent) {
                        if (!this.baseLayer.maxExtent.containsBounds(newExtent)) {
                            newExtent = this.baseLayer.maxExtent.clone();
                            newCenter = newExtent.getCenterLonLat();
                        }

                        if (!this.baseLayer.maxExtent.contains(newCenter.lon, newCenter.lat)) {
                            newCenter = this.baseLayer.maxExtent.getCenterLonLat();
                        }

                        this.restrictedExtent = this.baseLayer.maxExtent.clone();
                    } else {
                        this.restrictedExtent = null;
                    }

                    // Also convert overlay layers - assuming they are in the same projection as the old base layer 
                    for (l = 0; l < this.layers.length; l++) {
                        if (this.layers[l].transform && (typeof this.layers[l].transform === 'function')) {
                            this.layers[l].transform(oldProjection.getCode(), newProjection.getCode());
                        }
                    }
                }
            }

            // the new zoom will either come from the converted old 
            // Extent or from the current resolution of the map 
            var newZoom = (newExtent) ? this.getZoomForExtent(newExtent, true) : this.getZoomForResolution(this.resolution, true);

            // recenter the map
            if (newCenter !== null) {
                this.setCenter(newCenter, newZoom, false, true);
            }

            this.events.triggerEvent("changebaselayer", {
                layer: this.baseLayer
            });
        }
    }
};

/* New additional methods for marker, markers layer and vector layer to handle reprojection */
/* A lot of transforming to and fro will erode feature/marker locatiuon accuracy - an unavoidable consequence of the sub-optimal design of OL 2.x */

/* APIMethod: transform 
* Transform the Marker object from source to dest projection.  
* 
* Parameters:  
* source  - {<OpenLayers.Projection>} Source projection.  
* dest    - {<OpenLayers.Projection>} Destination projection. 
* 
* Returns: 
* {<OpenLayers.Marker>} Itself, for use in chaining operations. 
*/
OpenLayers.Marker.prototype.transform = function (source, dest) {
    this.lonlat.transform(source, dest);
    return this;
};

/* APIMethod: transform 
* Transform the Markers layer from source to dest projection.  
* 
* Parameters:  
* source - {<OpenLayers.String>} Source projection code.  
* dest   - {<OpenLayers.String>} Destination projection code.  
* 
* Returns: 
* {<OpenLayers.Layer.Markers>} Itself, for use in chaining operations. 
*/
OpenLayers.Layer.Markers.prototype.transform = function (source, dest) {
    var i;
    for (i = 0; i < this.markers.length; i++) {
        var marker = this.markers[i];
        marker.transform(source, dest);
    }
    this.redraw();
    return this;
};

/* APIMethod: transform 
* Transform the Vector layer from source to dest projection. 
* 
* Parameters:  
* source - {<OpenLayers.String>} Source projection code.  
* dest   - {<OpenLayers.String>} Destination projection code.  
* 
* Returns: 
* {<OpenLayers.Layer.Vector>} Itself, for use in chaining operations. 
*/
OpenLayers.Layer.Vector.prototype.transform = function (source, dest) {
    var i;
    for (i = 0; i < this.features.length; i++) {
        var feature = this.features[i];
        feature.geometry.transform(source, dest);
    }
    this.redraw();
    return this;
};



