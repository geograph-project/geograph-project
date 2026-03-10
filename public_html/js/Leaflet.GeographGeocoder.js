//////////////////////////////////////
//not sure why this missing from geotools2! (hindsight and all that!) 

GT_WGS84.prototype.getGrid = function() {
        if (this.isIreland2())
               grid=this.getIrish(true);
        else
               grid=this.getOSGB();
	return grid;
}

GT_WGS84.prototype.parseGridRef = function(gridref) { //technically this is a feactory method, not a class method!
		var grid=new GT_OSGB();
		var ok = false;
		if (grid.parseGridRef(gridref)) {
			ok = true;
		} else {
			grid=new GT_Irish();
			ok = grid.parseGridRef(gridref);
		}
	        if (ok) {
			//convert to a wgs84 coordinate
			return grid.getWGS84(true);
		}
	return false;
}

//////////////////////////////////////


        function formatJSON(rawjson) {
                var json = {}, key, loc, disp = [];
		if (!rawjson || !rawjson.items)
			return json;

		var items = rawjson.items;
                for(i=0;i<items.length;i++) {
			//results.push({value:item.gr+' '+item.name,label:item.name,gr:item.gr,title:item.localities});

                        key = items[i].gr+' '+items[i].name;
			if (items[i].localities)
				key = key + ", "+items[i].localities;
	
			wgs84=new GT_WGS84();
			wgs84 = wgs84.parseGridRef(items[i].gr);

			if (wgs84) {
	                        loc = L.latLng( wgs84.latitude, wgs84.longitude );
	                        json[ key ]= loc;       //key,value format
			}
                }
                return json;
        }

        L.geographGeocoder = function() { // accepts no option right now - TOFIX!
		return new L.Control.Search({
			url: "https://www.geograph.org.uk/finder/places.json.php?q={s}&new=1", // dont use our API without permission!
			jsonpParam: 'callback',
                        formatData: formatJSON,
			zoom: 12,
                        markerLocation: true,
			hideMarkerOnCollapse: true,
                        autoCollapse: true,
			autoCollapseTime: 3000,
			textPlaceholder: 'Placename/Gridref Search',
                        autoType: false,
			firstTipSubmit: true,
			tipAutoSubmit: true,
                        minLength: 2
                });
	};

	//this is ugly, but we dont have our own .css file yet
	(function() {
	    const style = document.createElement('style');
	    style.type = 'text/css';
	    style.innerHTML = `
	        .leaflet-control-search.search-exp .search-input { font-size: 1.4em; height: 30px; }
	        .leaflet-control-search.search-exp .search-tooltip { font-size: 1.4em; max-height: 200px; }
	        .leaflet-control-search.search-exp .search-button { display: none; }
	    `;
	    document.head.appendChild(style);
	})();

//////////////////////////////////////

