/**
 * $Project: GeoGraph $
 * $Id: Leaflet.GeographClickLayer.js 3657 2007-08-09 18:12:09Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2018  Barry Hunter (geo@barryhunter.co.uk)
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

/* Adds a click event to map to show Geograph Images, not technically a layer, but this seems nice way to make it self contained!
* note also sets a mouse move event, and the grid-ref is reused from that!
*
* Prerequisites:
*   jQuery (around 1.8)
*   geotools2
*/

L.GeographClickLayer = L.FeatureGroup.extend({
	options: {
		touch: false, //is a touch device - affects what map events use!
		endpoint: "https://api.geograph.org.uk/api-facetql.php",
		apiKey: 'geograph_demo', //get your own key: https://www.geograph.org.uk/admin/apikey.php
		domain: "https://www.geograph.org.uk", //may be overwritten!
		bi_bounds: L.latLngBounds([49.863788, -13.688451], [60.860395, 1.795260]),
		ci_bounds: L.latLngBounds([49.150211,-2.702359], [49.731385,  -2.005734]),
		de_bounds: L.latLngBounds([47.170071,5.766899], [55.138900, 15.120222]),
		query: '',  //optional text query to filter results
		user_id: null, // numberic Geograph User-ID to filter images
		limit: 10, //please dont raise this beyond 20 or so.
		data: {} //general array can pass to the API to filter
	},

///////////////////////////////////////////////////

	initialize: function (options) {
		L.setOptions(this, options);
		L.FeatureGroup.prototype.initialize.call(this);
	},

///////////////////////////////////////////////////

		onAdd: function (map) {
			if (this.options.touch) {
				map.on('contextmenu', this.clickEvent, this);
				map.on('click', this.moveEvent, this);
			} else {
				map.on('click', this.clickEvent, this);
				map.on('mousemove', this.moveEvent, this);
			}
			map.on('baselayerchange', this.baseEvent, this);
			map.on('overlayadd', this.overlayEvent, this);
			this._map = map;
			this.appendToDOM();
			//todo loop though all layers, and grab the maxZoom for this._baseZoom of BASE layers

			//this is a bodge, as our maps use baseMaps variable, leaflet doesnt seem to keep a nice reference of baselayer,
			// its a contrivance by the layer Control. there is also no reliable way to get the layercontrol itself (to use its _layers list!)
			// can keep watch via baselayerchange, but need to get the inital value too!
			if (window.baseMaps && typeof window.baseMaps == 'object') {
				for (var i in window.baseMaps) {
					if (i && map.hasLayer(window.baseMaps[i])) {
						var layer = window.baseMaps[i];
						if (layer.options && layer.options.maxZoom) {
							this._baseZoom = layer.options.maxZoom;
						}
					}
				}
			}
		},

		onRemove: function (map) {
			if (this.options.touch) {
				map.off('contextmenu', this.clickEvent, this);
				map.off('click', this.moveEvent, this);
			} else {
				map.off('click', this.clickEvent, this);
				map.off('mousemove', this.moveEvent, this);
			}
			map.off('baselayerchange', this.baseEvent, this);
			map.off('overlayadd', this.overlayEvent, this);
			this._map = null;
		},

///////////////////////////////////////////////////

	clickEvent: function(e) {
		if (this.options.touch)
			this.moveEvent(e); //need this to update the update the GridRef, not needed on Desktop as mousemove events are firing!
		if (this._grid && this._grid.status && this._grid.status == 'OK') {
			var ll = this._gr.replace(/ /g,'');
		} else {
			var ll = e.latlng.lat.toFixed(6)+","+e.latlng.lng.toFixed(6);
		}

		var p1 = this._map.containerPointToLatLng([window.innerWidth/2, window.innerHeight/2]);
		var p2 = this._map.containerPointToLatLng([(window.innerWidth/2) + 40, (window.innerHeight/2) + 40]);
		var dist = p1.distanceTo(p2).toFixed(0);

		//window.open("http://www.geograph.org.uk/browser/#!/loc="+ll+"/dist="+dist+"/display=plus/sort=spread",'browserthumbs');

		this.currentLat = e.latlng.lat.toFixed(6);
		this.currentLng = e.latlng.lng.toFixed(6);
		this.currentRadius = dist;
		this.displayThumbs();
	},

///////////////////////////////////////////////////

	moveEvent: function(e) {
		var wgs84=new GT_WGS84();
		wgs84.setDegrees(e.latlng.lat,e.latlng.lng);

		if (wgs84.isIreland() && wgs84.isIreland2()) //isIsland is a quick BBOX test, so do that first!
			this._grid=wgs84.getIrish(true);
		else if (e.latlng.lat > 49.8 && wgs84.isGreatBritain()) // the isGB test is not accurate enough!
			this._grid=wgs84.getOSGB();
		else
			this._grid = null;

		if (this._grid && this._grid.status && this._grid.status == 'OK') {
			var z = this._map.getZoom();
			if (z > 15) precision = 5;
			else if (z > 12) precision = 4;
			else if (z > 9) precision = 3;
			else precision = 2;

			this._gr = this._grid.getGridRef(precision);
			if (document.getElementById('gridref'))
				document.getElementById('gridref').innerText = this._gr;
		};
	},

///////////////////////////////////////////////////

	baseEvent: function(e) {
		if (e && e.layer && e.layer.options && e.layer.options.maxZoom) {
			this._baseZoom = e.layer.options.maxZoom;
		} else {
			this._baseZoom = null;
		}
	},
  
  overlayEvent: function(e) {
    if (e && e.name && e.name.indexOf('Photo') == 0) {
      if (e.name.indexOf('Viewpoint') > -1)
        target = 'Viewpoint';
      else
        target = 'Subject'; //this way works even for the thumbnail
      var selected = $('#clicklayer_select').val();
      if (selected.indexOf(target) == -1)
        $('#clicklayer_select').val(target);
    }
  },

///////////////////////////////////////////////////

	hoverOn: function(value) { //value is the whole sphinx row!
		if (this._marker)
			this._marker.removeFrom(this._map);
		if (this._marker2)
			this._marker2.removeFrom(this._map);
		if (this._line)
			this._line.removeFrom(this._map);

		var bounds = L.latLngBounds();
		if (value.wgs84_lat && value.wgs84_lat > 0.6) {
			var lat = this.rad2deg(value.wgs84_lat);
			var lng = this.rad2deg(value.wgs84_long);
			this._marker = L.circleMarker([lat,lng],{color:'red'}).addTo(this._map);
			bounds.extend([lat,lng]);
		}
		if (value.vlat && value.vlat > 0.6) {
			var lat = this.rad2deg(value.vlat);
			var lng = this.rad2deg(value.vlong);
			this._marker2 = L.circleMarker([lat,lng], {color:'purple'}).addTo(this._map);
			bounds.extend([lat,lng]);
		}
		if (value.wgs84_lat && value.vlat)
			this._line = L.polyline([this._marker.getLatLng(),this._marker2.getLatLng()], {color: 'red'}).addTo(this._map);

		var testBounds = this._map.getBounds().pad(-0.2);

		this._returnwhenoff = false; //so only return when involve a big jump!
		if (testBounds.contains(bounds)) {
			//todo, might still want to check if worth zooming in. with setZoomAround
			if (bounds.getNorthWest().distanceTo(bounds.getSouthEast()) > 20) {
				var possibleZoom = this._map.getBoundsZoom(bounds);
				if (this._baseZoom && possibleZoom > this._baseZoom)
					possibleZoom = this._baseZoom;

				if (Math.abs(this._map.getZoom() - possibleZoom) > 4) {
					this._map.setZoomAround(bounds.getCenter(),possibleZoom-2);
					this._returnwhenoff = true;
				}
			}
			return;
		}

		if (bounds.getNorthWest().distanceTo(bounds.getSouthEast()) > 20) {

			var possibleZoom = this._map.getBoundsZoom(bounds);
			if (Math.abs(this._map.getZoom() - possibleZoom) <= 2) {
				//if close, just try panning, to minimis jumping around
				this._map.panInsideBounds(bounds.pad(0.1));
			} else {
				//todo, could perhaps note, when target bounds are in view (its just really just zoom!)
					// and instead use setZoomAround to avoid recentering

				//also dont zoom beyond the maxZoom of the baselayer
				if (this._baseZoom) {
					this._map.fitBounds(bounds, {maxZoom: this._baseZoom});
				} else
					this._map.fitBounds(bounds);
				this._returnwhenoff = true;
			}

		} else {
			//if points are same or only a single point, then just make sure in view at current zoom

			this._map.panInsideBounds(bounds.pad(0.1));
		}
	},

	hoverOff: function() {
		if (this._marker)
			this._marker.removeFrom(this._map);
		if (this._marker2)
			this._marker2.removeFrom(this._map);
		if (this._line)
			this._line.removeFrom(this._map);
		if (this._mapBounds && this._returnwhenoff)
			this._map.fitBounds(this._mapBounds);
	},

///////////////////////////////////////////////////

	displayThumbs: function() {
		var lat = this.currentLat;
		var lng = this.currentLng;
		var radius = this.currentRadius;

		if (this._circle)
			this._circle.removeFrom(this._map);
		this._circle = L.circle([lat, lng], {radius: radius,opacity:0.2}).addTo(this._map);

		var offset = $('#map').offset();
		$('html, body').scrollTop(offset.top);
		$('#map').addClass('click_smallmap');	//todo, shouldnt be hardcoded as #map!
		this._map.invalidateSize();

		this._mapBounds = this._map.getBounds();
		$('.leaflet-control-container .leaflet-top').hide(); //hides top controls, but not attribution!

		if (typeof this.options.data !== 'object')
			this.options.data = {};

		var data = L.extend(this.options.data, {
			select: "id,title,grid_reference,realname,hash,natgrlen,wgs84_lat,wgs84_long",
			match: this.options.query+(this.options.user_id?' @user user'+this.options.user_id:''),
			order: "sequence ASC",
			option: 'ranker=none',
			limit: this.options.limit
		});

		var selected = $('#clicklayer_select').val();
		var geo1 = 'geodist'; //alas this columnname can change!
		var geo1len = 'natgrlen';

		delete data.geo;  //because data is reused, need to remove optionally added
		delete data.geo_prefix;
		delete data.geo2;
		delete data.gg;
		delete data.is;

		var ll = L.latLng(lat,lng);
		/////////////////////////
		if (this.options.bi_bounds.contains(ll)) {
			if (this.options.domain.indexOf('org.uk') == -1)
				this.options.domain = "https://www.geograph.org.uk";
			data.vv=1; //use the new viewpoint index!
			data.select = data.select + ',vgrlen,vlat,vlong';
			$('#clicklayer_select').prop('disabled',false);

		/////////////////////////
		} else if (this.options.de_bounds.contains(ll)) {
			this.options.domain = "https://geo-en.hlipp.de";
			data.gg=1;
			selected='Subject'; //tofix
			$('#clicklayer_select').val(selected).prop('disabled',true);

		/////////////////////////
		} else if (this.options.ci_bounds.contains(ll)) {
			this.options.domain = "http://www.geograph.org.gg";
			data.is=1;
			data.select = data.select + ',vgrlen,vlat,vlong';
			$('#clicklayer_select').prop('disabled',false);

		/////////////////////////
		} else {
				$('body').append('<div id=clicklayer_noresults>Only works with specific countries.</div>');

				setTimeout(function() {
					$("#clicklayer_noresults").fadeOut('slow', function(){
					  $(this).remove();
					});
				}, 2500);

				return;
		}
		/////////////////////////

		if (selected.indexOf('Subject') == 0) {
			data.geo = lat+","+lng+","+radius;
			data.geo_prefix="wgs84_"; //the vv index will default to viewcolumns!
			data.d =1; //adds optimiation to geo filter to use bbox.
			if (selected.indexOf('+Viewpoint')>-1)
				data.geo2 = lat+","+lng+","+radius+",v";
			if (selected.indexOf('not Viewpoint')>-1)
				data.geo2 = lat+","+lng+",-"+radius+",v";
		}
		if (selected.indexOf('Viewpoint') == 0) {
			data.geo = lat+","+lng+","+radius;
			//data.geo_prefix="v";
			geo1len = 'vgrlen';
			if (selected.indexOf('not Subject')>-1)
				data.geo2 = lat+","+lng+",-"+radius+",wgs84_";
		}
		if (data.geo2)
			geo1 = 'geo1';

		/////////////////////////

		if (this._grid && this._grid.status && this._grid.status == 'OK') {
			var gridref = this._gr;
		} else {
			var gridref = lat+','+lng;
		}

		var size = 'small';
		if (window.innerWidth>800 && window.innerHeight > 700) {
			size = 'med';
			$('#clicklayer_thumbs').addClass('med');
			$('#clicklayer_thumbs').removeClass('small');
		} else {
			$('#clicklayer_thumbs').addClass('small');
			$('#clicklayer_thumbs').removeClass('med');
		}

		/////////////////////////

		$('#clicklayer_lightback').fadeIn('fast');

		if ($('#clicklayer_thumbs:hidden').length) //if not hidden (ie a redraw), then this is ugly as current results disapper
			$('#clicklayer_thumbs').html('<div style="height:260px">Loading thumbnails.... please wait.</div>');

		var that = this;
		$.getJSON(
			this.options.endpoint,
			data,
			function(data) {
				$('#clicklayer_lightfront').show();

				gridref = encodeURIComponent(gridref.replace(/ /g,''));

				var lines = [];
				if (that.options.domain.indexOf('.org.uk') > -1) {
					lines.push('<b><a target=newinw href="'+that.options.domain+'/near/'+gridref+'">More Images</a></b>');
					if (that.options.domain == "https://www.geograph.org.uk")
						lines.push('<a target=newinw href="'+that.options.domain+'/browser/#!/loc='+gridref+'/dist='+radius+'/sort=spread">Image Browser</a>');
					if (data.meta && data.meta.total_found && data.rows)
						lines.push('<a target=newinw href="'+that.options.domain+'/gridref/'+gridref+'?centi=X">More Images in clicked CentiSquare</a>');
					lines.push('<a target=newinw href="'+that.options.domain+'/gridref/'+gridref+'">Grid Square Page for '+gridref+'</a>');
				} else {
					lines.push('<b><a target=newinw href="'+that.options.domain+'/search.php?go=1&location='+gridref+'">More Images</a></b>');
				}

				if (data.meta && data.meta.total_found && data.rows) {
					$('#clicklayer_links').html(data.rows.length+
						' of '+data.meta.total_found+' images within '+(radius/1000).toFixed(2)+'km. <br><br>'+
						gridref+'<br><br>'+
						lines.join('<br>')+'</div>');
				} else {
					$('#clicklayer_links').html(gridref+'<br><br>'+
						lines.join('<br>')+'</div>');
				}

				if (data && data.rows) {

					$('#clicklayer_thumbs').empty();

					if (that.options.touch && !that._marker) {
						//bit wierd, but if they've used a marker before, probably dont need be told again about it!
						$('#thumbs').html("Tap a thumbnail to see on map above, <nobr>long-press</nobr> to open Geograph Photo Page.<hr>");
					}

					$.each(data.rows,function(index,value) {
						value.thumbnail = that.getGeographUrl(value.id, value.hash, size);
						var dist = '';
						if (value[geo1len]>4) {
						  dist = 'Dist: '+(value[geo1]/1000).toFixed(1)+'km';
						}
						if (that.options.touch) {
							value.html = '<div class="thumb'+size+'" id="image'+value.id+'">'+dist+'<br><img src="'+value.thumbnail+'"/></div>';
						} else {
							value.html = '<div class="thumb'+size+'" id="image'+value.id+'">'+dist+'<br><a target=newinw href="'+that.options.domain+'/photo/'+value.id+'" title="'+value.grid_reference+' : '+value.title+' by '+value.realname+'"><img src="'+value.thumbnail+'"/></a></div>';
						}

						$('#clicklayer_thumbs').append(value.html);

						if (that.options.touch) {
							$("#image"+value.id+" img").on('click',function(e) {
								that.hoverOn(value);
							}).on('contextmenu',function() {
								window.open(that.options.domain+'/photo/'+value.id,'newwin');
							});
						} else {
							$("#image"+value.id+" a img").on('mouseover',function(e) {
								that.hoverOn(value);
							}).on('mouseout',function() {
								that.hoverOff();
							});
						}
					});


				} else {
					$('#clicklayer_thumbs').html('No results within '+(radius/1000).toFixed(2)+'km of '+gridref+'.<br>Tip: Try clicking closer to center of a visible thumbnail.');
				}
			}
		);
	},

///////////////////////////////////////////////////

	rad2deg: function(angle) {
		// Converts the radian number to the equivalent number in degrees
		//
		// version: 1109.2015
		// discuss at: http://phpjs.org/functions/rad2deg
		// +   original by: Enrique Gonzalez
		// +      improved by: Brett Zamir (http://brett-zamir.me)
		// *	     example 1: rad2deg(3.141592653589793);
		// *     returns 1: 180
		return angle * 57.29577951308232; // angle / Math.PI * 180
	},

///////////////////////////////////////////////////

	getGeographUrl: function(gridimage_id, hash, size) {

		yz=this.zeroFill(Math.floor(gridimage_id/1000000),2);
		ab=this.zeroFill(Math.floor((gridimage_id%1000000)/10000),2);
		cd=this.zeroFill(Math.floor((gridimage_id%10000)/100),2);
		abcdef=this.zeroFill(gridimage_id,6);

		if (yz == '00') {
			fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
		} else {
			fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
		}
		if (this.options.domain.indexOf('.org.uk') > -1) {
			switch(size) {
				case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break;
				case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break;
				case 'small':
				default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg";
			}
		} else {
			switch(size) {
				case 'full': return this.options.domain+fullpath+".jpg"; break;
				case 'med': return this.options.domain+fullpath+"_213x160.jpg"; break;
				case 'small':
				default: return this.options.domain+fullpath+"_120x120.jpg";
			}
		}
	},

///////////////////////////////////////////////////

	zeroFill: function(number, width) {
		width -= number.toString().length;
		if (width > 0) {
			return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
		}
		return number + "";
	},

///////////////////////////////////////////////////

	closeLight: function() {
		$('#clicklayer_lightfront').hide();
		$('#clicklayer_lightback').fadeOut('fast');
		this.hoverOff();
		if (this._circle) {
			this._circle.removeFrom(this._map);
			this._circle = null;
		}
		$('#map').removeClass('click_smallmap');
		this._map.invalidateSize();

		$('.leaflet-control-container .leaflet-top').show(); //hides top controls, but not attribution!

		return false;
	},

///////////////////////////////////////////////////

	appendToDOM: function() {
		if ($('body #clicklayer_lightback').length == 0) {
			$('body').append('<div id="clicklayer_lightback" style="display:none;"></div>'+
				'<div id="clicklayer_lightfront" style="display:none;">'+
					'<div id="clicklayer_close"><a href=#>Close</a></div>'+
					'Filter: <select id="clicklayer_select">'+
						'<option>Subject</option>'+
						'<option>Viewpoint</option>'+
						'<option>Subject+Viewpoint</option>'+
						'<option>Subject, not Viewpoint</option>'+
						'<option>Viewpoint, not Subject</option>'+
					'</select><br>'+
					'<div id="clicklayer_links"></div>'+
					'<div id="clicklayer_thumbs" class="clicklayer_thumbs gridded"></div>'+
				'</div>');

			var that = this; //function closure
			$('#clicklayer_lightback, #clicklayer_close').click(function() {
				that.closeLight();
			});
			$('#clicklayer_select').change(function() {
				that.displayThumbs();
			});
		}
	}

///////////////////////////////////////////////////

});

L.geographClickLayer = function (options) {
	return new L.GeographClickLayer(options);
};


