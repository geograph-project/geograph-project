/**
     * Guiders are created with guider.createGuider({settings}).
     *
     * You can show a guider with the .show() method immediately
     * after creating it, or with guider.show(id) and the guider's id.
     *
     * guider.next() will advance to the next guider, and
     * guider.hideAll() will hide all guiders.
     *
     * By default, a button named "Next" will have guider.next as
     * its onclick handler.  A button named "Close" will have
     * its onclick handler set to guider.hideAll.  onclick handlers
     * can be customized too.
     */

$(function() {

    guiders._wireEscape = function() {};  //these trample on the events setup by the application. 
    guiders._unWireEscape = function() {}; 
   
    guiders._defaultSettings.buttons = [{name: "Close"},{name: "Next"}]; //add an automatic next button
    guiders._defaultSettings.xButton = true;
    guiders._defaultSettings.overlay = true;
    guiders._defaultSettings.isHashable = false; //can never be used as we will have changed the hash already 

    guiders.createGuider({
      title: "Welcome to our quick guided tour of the Geograph Coverage Map",
      id: "g_welcome",
      next: "g_zoom",
      description: "This demo will guide you through some of the major features of the application. At various points in the tour, you can actually interact with the application to try out the feature described."
    });

    guiders.createGuider({
      attachTo: ".leaflet-control-zoom", highlight: ".leaflet-control-zoom",
	autoFocus:true,
      position: 'right',
      title: "Zoom Control",
      id: "g_zoom",
      next: "g_personal",
      description: "Click to zoom in/out on the map. Tip: can also use the mouse scrollwheel.",
      onShow: function() {
		var offset = $('#map').offset();
		$('html, body').scrollTop(offset.top);
      }
    });

    guiders.createGuider({
      attachTo: ".fa-user,.fa-user-o", highlight: ".leaflet-bar.easy-button-container",
      position: 'right',
      title: "Personalized Mode",
      id: "g_personal",
      next: "g_opacity",
      description: "Click to change the Geograph overlays to just show YOUR images.", 
	onShow: function() {
		if (!overlayMaps['(Personalize Coverage)']) {
			setTimeout(function() {
				 guiders.next();
			},100);
		}
	}
    });

    guiders.createGuider({
      attachTo: ".fa-adjust", highlight: ".leaflet-control-container.leaflet-top.leaflet-left",
      position: 'right',
      title: "Opacity Control",
      id: "g_opacity",
      next: "g_location",
      description: "Tri-State toogle to vary the transpancy of overlays and/or the basemap. Useful with some combinations that are not easy to see otherwise"
    });

    guiders.createGuider({
      attachTo: ".leaflet-control-search", highlight: ".leaflet-control-container.leaflet-top.leaflet-left",
      position: 'right',
      title: "Location Search",
      id: "g_location",
      next: "g_locate",
      description: "Search box to recenter the map, by placename, UK Postcode, or Grid Reference"
    });

    guiders.createGuider({
      attachTo: ".leaflet-control-locate", highlight: ".leaflet-control-container.leaflet-top.leaflet-left",
      position: 'right',
      title: "GeoLocate Button",
      id: "g_locate",
      next: "g_upload",
      description: "Click to center the map on your current location, if your browser is able to figure it out!"
    });

    guiders.createGuider({
      attachTo: ".leaflet-control-filelayer", highlight: ".leaflet-control-container.leaflet-top.leaflet-left",
      position: 'right',
      title: "Upload Control",
      id: "g_upload",
      next: "g_enlarge",
      description: "Upload a KML (not KMZ), or GPX file to display on the map. 1Mb file limit, should display points and line/shape features"
    });

    guiders.createGuider({
      attachTo: "#enlargelink", highlight: "#enlargelink",
	autoFocus:true,
      position: 'right',
      title: "Enlarge Link",
      id: "g_enlarge",
      next: "g_gr",
      description: "Click to give the map a larger area"
    });

    guiders.createGuider({
      attachTo: "#gridref", highlight: "#map",
	autoFocus:true,
      position: 'bottom',
      title: "Dynamic Grid Ref",
      id: "g_gr",
      next: "g_layers",
      description: "Shows the grid-reference of the map cursor"
    });

    guiders.createGuider({
      attachTo: ".leaflet-control-layers", highlight: ".leaflet-control-layers",
      position: 'left',
      title: "Layers Control",
      id: "g_layers",
      next: "g_other",
      description: "This map has a large selection of different layers."
    });

    guiders.createGuider({
      attachTo: "#mapLinkSelector", highlight: "#mapLinkSelector",
	autoFocus:true,
      position: 'left',
      title: "Other Map Links",
      id: "g_other",
      next: "g_welcome",
      description: "Can this control to access a number of other maps, where possible centered the same as the current map. <br/><br/>Click close to return to the application. Or Next to go back to the start of the tour!"
    });


/*

    guiders.createGuider({
      attachTo: "", highlight: "",
      position: 'right',
      title: "",
      id: "g_",
      next: "g_",
      description: ""
    });

*/

});


