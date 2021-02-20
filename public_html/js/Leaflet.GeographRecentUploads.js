/**
 * $Project: GeoGraph $
 * $Id: Leaflet.GeographRecentUploads.js barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2021  Barry Hunter (geo@barryhunter.co.uk)
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
 */

L.GeographRecentUploads = L.FeatureGroup.extend({
	options: {
		endpoint: "https://www.geograph.org.uk/stuff/submissions.json.php",
	},

///////////////////////////////////////////////////

	initialize: function (options) {
		L.setOptions(this, options);
		L.FeatureGroup.prototype.initialize.call(this);
		this._done = new Array();
		this._last_id = 0;
	},

///////////////////////////////////////////////////

	onAdd: function (map) {
		this._map = map;
		this.imageRequest(); //still make the request, as want to refresh!
		if (this._done.length) //not sure why, but they dont need seem to be added to map automatically. (maybe because not calling the 'parent' onAdd function?
			this.eachLayer(function(layer) { map.addLayer(layer); });
	},

///////////////////////////////////////////////////

	imageRequest:  function () {
                var data = {}

		if (this._last_id) {
			data.since = this._last_id
		}

		var that = this;
                $.getJSON(
       	                this.options.endpoint,
               	        data,
                       	function(data) {
				if (data && data.length) {
					$.each(data,function(index,value) {
						if (that._done[value.gridimage_id])
							return;

						that._done[value.gridimage_id] =
							L.circleMarker([value.wgs84_lat,value.wgs84_long],
							 {title:value.grid_reference+' : '+value.title, radius:4, color:'#f93024'})
							.on('click', function() {
								window.open('/photo/'+value.gridimage_id,'_blank');
							});

						that.addLayer(that._done[value.gridimage_id]);
						if (value.gridimage_id > that._last_id)
							that._last_id = value.gridimage_id;
					});
				}
			}
		);
	}


///////////////////////////////////////////////////

});

L.geographRecentUploads = function (options) {
	return new L.GeographRecentUploads(options);
};


