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
*   geotools2
*   ES6+ (using modern web APIs)
*/

/**
 * Leaflet.GeographClickLayer.js (Modernized)
 * Removed jQuery dependency, using native ES6+
 */

L.GeographClickLayer = L.FeatureGroup.extend({
    options: {
        touch: false,
        endpoint: "https://api.geograph.org.uk/api-facetql.php",
        apiKey: 'geograph_demo',
        domain: "https://www.geograph.org.uk",
        bi_bounds: L.latLngBounds([49.863788, -13.688451], [60.860395, 1.795260]),
        ci_bounds: L.latLngBounds([49.150211, -2.702359], [49.731385, -2.005734]),
        de_bounds: L.latLngBounds([47.170071, 5.766899], [55.138900, 15.120222]),
        query: '',
        user_id: null,
        limit: 10,
        data: {}
    },

    initialize: function (options) {
        L.setOptions(this, options);
        L.FeatureGroup.prototype.initialize.call(this);
    },

    onAdd: function (map) {
        const events = this.options.touch 
            ? { move: 'click', click: 'contextmenu' } 
            : { move: 'mousemove', click: 'click' };

        map.on(events.click, this.clickEvent, this);
        map.on(events.move, this.moveEvent, this);
        map.on('baselayerchange', this.baseEvent, this);
        map.on('overlayadd', this.overlayEvent, this);

        this._map = map;
        this.appendToDOM();

        // Check for baseMaps in global scope (legacy support)
        if (window.baseMaps && typeof window.baseMaps === 'object') {
            for (let i in window.baseMaps) {
                if (map.hasLayer(window.baseMaps[i])) {
                    let layer = window.baseMaps[i];
                    if (layer.options?.maxZoom) {
                        this._baseZoom = layer.options.maxZoom;
                    }
                }
            }
        }
    },

    onRemove: function (map) {
        const events = this.options.touch 
            ? { move: 'click', click: 'contextmenu' } 
            : { move: 'mousemove', click: 'click' };

        map.off(events.click, this.clickEvent, this);
        map.off(events.move, this.moveEvent, this);
        map.off('baselayerchange', this.baseEvent, this);
        map.off('overlayadd', this.overlayEvent, this);
        this._map = null;
    },

    clickEvent: function (e) {
        if (this.options.touch) this.moveEvent(e);
        
        let ll = (this._grid?.status === 'OK') 
            ? this._gr.replace(/ /g, '') 
            : `${e.latlng.lat.toFixed(6)},${e.latlng.lng.toFixed(6)}`;

        const p1 = this._map.containerPointToLatLng([window.innerWidth / 2, window.innerHeight / 2]);
        const p2 = this._map.containerPointToLatLng([(window.innerWidth / 2) + 40, (window.innerHeight / 2) + 40]);
        const dist = p1.distanceTo(p2).toFixed(0);

        this.currentLat = e.latlng.lat.toFixed(6);
        this.currentLng = e.latlng.lng.toFixed(6);
        this.currentRadius = dist;
        this.displayThumbs();
    },

    moveEvent: function (e) {
        if (typeof GT_WGS84 === 'undefined') return; // Ensure geotools2 is loaded
        const wgs84 = new GT_WGS84();
        wgs84.setDegrees(e.latlng.lat, e.latlng.lng);

        if (wgs84.isIreland() && wgs84.isIreland2()) {
            this._grid = wgs84.getIrish(true);
        } else if (e.latlng.lat > 49.8 && wgs84.isGreatBritain()) {
            this._grid = wgs84.getOSGB();
        } else {
            this._grid = null;
        }

        if (this._grid?.status === 'OK') {
            const z = this._map.getZoom();
            const precision = z > 15 ? 5 : z > 12 ? 4 : z > 9 ? 3 : 2;
            this._gr = this._grid.getGridRef(precision);
            const el = document.getElementById('gridref');
            if (el && this._gr.indexOf('undefined') == -1) el.innerText = this._gr; //(there some unknown myriads within isGreatBritain check!)
        }
    },

    baseEvent: function (e) {
        this._baseZoom = e.layer?.options?.maxZoom || null;
    },

    overlayEvent: function (e) {
        if (e.name?.startsWith('Photo')) {
            const target = e.name.includes('Viewpoint') ? 'Viewpoint' : 'Subject';
            const select = document.getElementById('clicklayer_select');
            if (select && !select.value.includes(target)) {
                select.value = target;
            }
        }
    },

    hoverOn: function (value) {
        [this._marker, this._marker2, this._line].forEach(l => l?.removeFrom(this._map));

        const bounds = L.latLngBounds();
        if (value.wgs84_lat > 0.6) {
            const lat = this.rad2deg(value.wgs84_lat);
            const lng = this.rad2deg(value.wgs84_long);
            this._marker = L.circleMarker([lat, lng], { color: 'red' }).addTo(this._map);
            bounds.extend([lat, lng]);
        }
        if (value.vlat > 0.6) {
            const lat = this.rad2deg(value.vlat);
            const lng = this.rad2deg(value.vlong);
            this._marker2 = L.circleMarker([lat, lng], { color: 'purple' }).addTo(this._map);
            bounds.extend([lat, lng]);
        }
        if (this._marker && this._marker2) {
            this._line = L.polyline([this._marker.getLatLng(), this._marker2.getLatLng()], { color: 'red' }).addTo(this._map);
        }

        const testBounds = this._map.getBounds().pad(-0.2);
        this._returnwhenoff = false;

        if (testBounds.contains(bounds)) {
            if (bounds.getNorthWest().distanceTo(bounds.getSouthEast()) > 20) {
                let possibleZoom = this._map.getBoundsZoom(bounds);
                if (this._baseZoom && possibleZoom > this._baseZoom) possibleZoom = this._baseZoom;
                if (Math.abs(this._map.getZoom() - possibleZoom) > 4) {
                    this._map.setZoomAround(bounds.getCenter(), possibleZoom - 2);
                    this._returnwhenoff = true;
                }
            }
            return;
        }

        if (bounds.getNorthWest().distanceTo(bounds.getSouthEast()) > 20) {
            let possibleZoom = this._map.getBoundsZoom(bounds);
            if (Math.abs(this._map.getZoom() - possibleZoom) <= 2) {
                this._map.panInsideBounds(bounds.pad(0.1));
            } else {
                const maxZ = this._baseZoom ? { maxZoom: this._baseZoom } : {};
                this._map.fitBounds(bounds, maxZ);
                this._returnwhenoff = true;
            }
        } else {
            this._map.panInsideBounds(bounds.pad(0.1));
        }
    },

    hoverOff: function () {
        [this._marker, this._marker2, this._line].forEach(l => l?.removeFrom(this._map));
        if (this._mapBounds && this._returnwhenoff) {
            this._map.flyToBounds(this._mapBounds, { duration: 0.5 });
        }
    },

    displayThumbs: async function () {
        const lat = this.currentLat;
        const lng = this.currentLng;
        const radius = this.currentRadius;

        if (this._circle) this._circle.removeFrom(this._map);
        this._circle = L.circle([lat, lng], { radius: radius, opacity: 0.2 }).addTo(this._map);

        // Map UI adjustments
        const container = this._map._container;
        window.scrollTo({ top: container.offsetTop, behavior: 'smooth' });
        container.classList.add('click_smallmap');
        this._map.invalidateSize();

        this._mapBounds = this._map.getBounds();
        document.querySelectorAll('.leaflet-control-container .leaflet-top').forEach(el => el.style.display = 'none');

        // Prepare Data
        let queryParams = {
            select: "id,title,grid_reference,realname,hash,natgrlen,wgs84_lat,wgs84_long",
            match: this.options.query + (this.options.user_id ? ` @user user${this.options.user_id}` : ''),
            order: "sequence ASC",
            option: 'ranker=none',
            limit: this.options.limit,
            ...this.options.data
        };

        const selectEl = document.getElementById('clicklayer_select');
        let selected = selectEl.value;
        const ll = L.latLng(lat, lng);

        // Region Bounds Logic
        if (this.options.bi_bounds.contains(ll)) {
	    if (this._gr.indexOf('undefined') != -1) { // maybe be something like "undefined-4038"
		this._showNoResults();
		return;
            }

            if (!this.options.domain.includes('org.uk')) this.options.domain = "https://www.geograph.org.uk";
            queryParams.select += ',vgrlen,vlat,vlong';
            selectEl.disabled = false;
        } else if (this.options.de_bounds.contains(ll)) {
            this.options.domain = "https://geo-en.hlipp.de";
            queryParams.gg = 1;
            //queryParams.select += ',vgrlen,vlat,vlong'; //doesnt currently work!
            selected = 'Subject';
            selectEl.value = 'Subject';
            selectEl.disabled = true;
        } else if (this.options.ci_bounds.contains(ll)) {
            this.options.domain = "http://www.geograph.org.gg";
            queryParams.is = 1;
            queryParams.select += ',vgrlen,vlat,vlong';
            selectEl.disabled = false;
        } else {
            this._showNoResults();
            return;
        }

	let geo1 = 'geodist'; //alas this columnname can change!
        let geo1len = 'natgrlen';

        // Apply filters
        if (selected.startsWith('Subject')) {
            queryParams.geo = `${lat},${lng},${radius}`;
            queryParams.geo_prefix = "wgs84_";
            queryParams.d = 1;
            if (selected.includes('+Viewpoint')) queryParams.geo2 = `${lat},${lng},${radius},v`;
            if (selected.includes('not Viewpoint')) queryParams.geo2 = `${lat},${lng},-${radius},v`;
        } else if (selected.startsWith('Viewpoint')) {
            queryParams.geo = `${lat},${lng},${radius}`;
            queryParams.geo_prefix = "v";
            geo1len = 'vgrlen';

            if (selected.includes('not Subject')) queryParams.geo2 = `${lat},${lng},-${radius},wgs84_`;
        }
        if (queryParams.geo2)
            geo1 = 'geo1';

        const gridref = (this._grid?.status === 'OK') ? this._gr : `${lat},${lng}`;
        const size = (window.innerWidth > 800 && window.innerHeight > 700) ? 'med' : 'small';
        
        const thumbsContainer = document.getElementById('clicklayer_thumbs');
        thumbsContainer.className = `clicklayer_thumbs gridded ${size}`;
        
        document.getElementById('clicklayer_lightback').style.display = 'block';

//if ($('#clicklayer_thumbs:hidden').length) //if not hidden (ie a redraw), then this is ugly as current results disapper

        thumbsContainer.innerHTML = '<div style="height:260px">Loading thumbnails.... please wait.</div>';

        try {
            const url = new URL(this.options.endpoint);
            Object.keys(queryParams).forEach(key => url.searchParams.append(key, queryParams[key]));
            
            const response = await fetch(url);
            const data = await response.json();
            
            this._renderResults(data, gridref, radius, size, geo1, geo1len);
        } catch (err) {
            thumbsContainer.innerHTML = 'Error loading images.';
            console.error(err);
        }
    },

    _renderResults: function(data, gridref, radius, size, geo1, geo1len) {
        document.getElementById('clicklayer_lightfront').style.display = 'block';
        const cleanGridRef = encodeURIComponent(gridref.replace(/ /g, ''));
        const lines = [];
        const dom = this.options.domain;

        if (dom.includes('.org.uk')) {
            lines.push(`<b><a target="_blank" href="${dom}/near/${cleanGridRef}">More Images</a></b>`);
            if (dom === "https://www.geograph.org.uk") {
                lines.push(`<a target="_blank" href="${dom}/browser/#!/loc=${cleanGridRef}/dist=${radius}/sort=spread">Image Browser</a>`);
            }
            lines.push(`<a target="_blank" href="${dom}/gridref/${cleanGridRef}">Grid Square Page</a>`);
        } else {
            lines.push(`<b><a target="_blank" href="${dom}/search.php?go=1&location=${cleanGridRef}">More Images</a></b>`);
        }

        const statsHtml = (data.meta?.total_found && data.rows)
            ? `${data.rows.length} of <b>${data.meta.total_found}</b> images within <b>${(radius/1000).toFixed(2)}km</b>.`
            : '';

        document.getElementById('clicklayer_links').innerHTML = `${statsHtml}<br><br>${gridref}<br><br>${lines.join('<br>')}`;

        const thumbsContainer = document.getElementById('clicklayer_thumbs');
        const georiverLink = document.getElementById('clicklayer_georiver');
        thumbsContainer.innerHTML = '';
        georiverLink.innerHTML = '';

        if (data.rows) {
	    thumbsContainer.classList.add('gridded');


					if (this.options.touch && !this._marker) {
						//bit wierd, but if they've used a marker before, probably dont need be told again about it!
						const message = document.getElementById('clicklayer_message');
						message.style.padding = "4px";
						message.innerHTML = "Tap a thumbnail to see on map above, <nobr>long-press</nobr> to open Geograph Photo Page.";
					}

            const ids = [];
            data.rows.forEach(row => {
                const thumbUrl = this.getGeographUrl(row.id, row.hash, size);
                const div = document.createElement('div');
                div.className = `thumb${size}`;
                div.id = `image${row.id}`;

		if (row[geo1len]>4) {
                      div.innerHTML = 'Dist: '+(row[geo1]/1000).toFixed(1)+'km<br>';
		}

                const img = document.createElement('img');
                img.src = thumbUrl;

                if (this.options.touch) {
                    div.appendChild(img);
                    img.addEventListener('click', () => this.hoverOn(row));
                    img.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        window.open(`${dom}/photo/${row.id}`, '_blank');
                    });
                } else {
                    const anchor = document.createElement('a');
                    anchor.href = `${dom}/photo/${row.id}`;
                    anchor.target = "_blank";
                    anchor.appendChild(img);
		    anchor.title = row.grid_reference+' : '+row.title+' by '+row.realname;
                    div.appendChild(anchor);
                    img.addEventListener('mouseover', () => this.hoverOn(row));
                    img.addEventListener('mouseout', () => this.hoverOff());
                }
                thumbsContainer.appendChild(div);
                ids.push(row.id);
            });

            if (ids.length > 1) {
                const riverUrl = `${dom}/search.php?markedImages=${ids.join(',')}&do=1&displayclass=black`;
                georiverLink.innerHTML = `<a href="${riverUrl}" target="_blank" style="margin:40px">View these ${ids.length} Images as GeoRiver</a>`;
            }
        } else {
		thumbsContainer.classList.remove('gridded');
 		thumbsContainer.innerHTML = `No results within ${(radius/1000).toFixed(2)}km of ${gridref} (the area of the blue circle on the map). Zoom out for a circle covering a bigger area. <br>Tip: can try turning on the [Photo Subjects] and/or [Photo Viewpoints] layer to see the positions of photos, alternatively the [Photo Thumbnails] plots many thumbnails direct on the map (the Photo Subjects layer works well together with Photo Thumbnails layer)`;
        }
    },

    _showNoResults: function() {
        const div = document.createElement('div');
        div.id = 'clicklayer_noresults';
        div.innerText = 'Only works with specific countries.';
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 2500);
    },

    rad2deg: function (angle) {
        return angle * 57.29577951308232;
    },

    getGeographUrl: function (gridimage_id, hash, size) {
        const yz = this.zeroFill(Math.floor(gridimage_id / 1000000), 2);
        const ab = this.zeroFill(Math.floor((gridimage_id % 1000000) / 10000), 2);
        const cd = this.zeroFill(Math.floor((gridimage_id % 10000) / 100), 2);
        const abcdef = this.zeroFill(gridimage_id, 6);

        const fullpath = (yz === '00') 
            ? `/photos/${ab}/${cd}/${abcdef}_${hash}` 
            : `/geophotos/${yz}/${ab}/${cd}/${abcdef}_${hash}`;

        if (this.options.domain.includes('.org.uk')) {
            const server = size === 'full' ? 's0' : `s${gridimage_id % 4}`;
            const suffix = size === 'full' ? '' : size === 'med' ? '_213x160' : '_120x120';
            return `https://${server}.geograph.org.uk${fullpath}${suffix}.jpg`;
        } else {
            const suffix = size === 'full' ? '' : size === 'med' ? '_213x160' : '_120x120';
            return `${this.options.domain}${fullpath}${suffix}.jpg`;
        }
    },

    zeroFill: function (number, width) {
        width -= number.toString().length;
        if (width > 0) return new Array(width + (/\./.test(number) ? 2 : 1)).join('0') + number;
        return number + "";
    },

    closeLight: function () {
        document.getElementById('clicklayer_lightfront').style.display = 'none';
        document.getElementById('clicklayer_lightback').style.display = 'none';
        this.hoverOff();
        if (this._circle) {
            this._circle.removeFrom(this._map);
            this._circle = null;
        }
        this._map._container.classList.remove('click_smallmap');
        this._map.invalidateSize();
        document.querySelectorAll('.leaflet-control-container .leaflet-top').forEach(el => el.style.display = 'block');
        return false;
    },

    appendToDOM: function () {
        if (!document.getElementById('clicklayer_lightback')) {
            const html = `
                <div id="clicklayer_lightback" style="display:none;"></div>
                <div id="clicklayer_lightfront" style="display:none;">
                    <div id="clicklayer_close"><a href="#">Close</a></div>
                    Filter: <select id="clicklayer_select">
                        <option>Subject</option>
                        <option>Viewpoint</option>
                        <option>Subject+Viewpoint</option>
                        <option>Subject, not Viewpoint</option>
                        <option>Viewpoint, not Subject</option>
                    </select><br>
                    <div id="clicklayer_links"></div>
		    <div id="clicklayer_message"></div>
                    <div id="clicklayer_thumbs" class="clicklayer_thumbs"></div>
		    <div id="clicklayer_georiver"></div>
                </div>`;
            document.body.insertAdjacentHTML('beforeend', html);

            document.getElementById('clicklayer_lightback').addEventListener('click', () => this.closeLight());
            document.getElementById('clicklayer_close').addEventListener('click', (e) => {
                e.preventDefault();
                this.closeLight();
            });
            document.getElementById('clicklayer_select').addEventListener('change', () => this.displayThumbs());
            
            document.getElementById('clicklayer_lightback').addEventListener('wheel', (e) => {
                e.preventDefault();
                if (!this._map) return;
                if (e.deltaY < 0) this._map.zoomIn();
                else this._map.zoomOut();
            });
        }
    }
});

L.geographClickLayer = function (options) {
    return new L.GeographClickLayer(options);
};
