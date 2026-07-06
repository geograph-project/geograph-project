/**
 * [Geograph API Function Collection]
 * * To the extent possible under law, Geograph Project has waived all copyright
 * and related or neighboring rights to this functions wihtin this file
 * via the CC0 1.0 Universal Public Domain Dedication.
 * * http://creativecommons.org/publicdomain/zero/1.0/
 */

//////////////////////////////////////////////////////////

//forming the URL is left to caller, but this is a barebones demo...
//this is only a very basic demo of the a simple keywords query

        /**
         * A basic demo function to initiate a search against the Geograph API.
         * It constructs the API URL and calls the rendering function.
         *
         * @param {string} query An optional keyword query string.
         */
        function geograph_api_demo(query, geo = false) {
            const base = "https://www.geograph.org.uk/api-facetql.php";
            const data = {
                long: 1,
                select: "id,user_id,realname,grid_reference,title,hash",
                limit: 30,
		utf: 1 //request utf8
            };

            if (query) {
                data['match'] = getTextQuery(query); //converts a 'user query' (eg containing tags) into sphinx format!
            }
            if (geo) {
		data['geo'] = geo; //format = "latitude,longitude,distance" (radius in meters)
	    }

            const url = base + '?' + objectToUrlParams(data);
            renderAPIResults(url, 'results', 'results-count');
        }

//////////////////////////////////////////////////////////
// alternate demo that provides decoding of the raw data rows.

	/**
	 * Fetches geographically nearby images from the Geograph API and formats them.
	 *
	 * By default, retrieves up to 20 images sorted by closest distance, maps the raw
	 * API rows into a clean, standardized data structure, and converts coordinates
	 * from radians to degrees.
	 *
	 * @param {number} lat - The latitude of the center point.
	 * @param {number} lng - The longitude of the center point.
	 * @param {number} [dist=300] - The search radius/distance metric from the center point.
	 * @param {Object} [options={}] - Additional API query parameters or overrides (e.g., limit, sort).
	 * @returns {Promise<Array<Object>>} A promise that resolves to an array of formatted image objects,
	 *                                    or an empty array if no results are found or an error occurs.
	 */
	async function getGeographImages(lat, lng, dist = 300, options = {}) {

	    const baseUrl = "https://api.geograph.org.uk/api-facetql.php";
	    try {
		const response = await fetch(`${baseUrl}?${new URLSearchParams({
		    geo: `${lat},${lng},${dist}`, //sets up the 'geodist' attribute automatically, as well as optimizing to use tile based index
		    select: 'id,user_id,realname,grid_reference,title,hash,takenday,width,height,wgs84_lat,wgs84_long',
		    limit: 20,
		    sort: 'geodist asc',
		    ...options // This allows the caller to override 'limit' or 'sort' (or add other options)
		})}`);
	        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

	        const data = await response.json();

	        if (!data.rows || data.rows.length === 0) {
	            return [];
	        }

	        // Convert the rows array, we map to a clean list
	        return data.rows.map(row => {
	            return {
			...row, //start by providing the raw row, but then add/replace fields we can help format...
	                url: `https://www.geograph.org.uk/photo/${row.id}`,
	                profile_url: `https://www.geograph.org.uk/profile/${row.user_id}`,
	                taken: formatTakenDate(space_date(row.takenday)),
	                image: getGeographUrl(row.id, row.hash, 'full'),
	                thumbnail: getGeographUrl(row.id, row.hash, 'med'),
	                size: [row.width, row.height], //the size of the 'full' image.
			lat: rad2deg(row.wgs84_lat ?? 0), lng: rad2deg(row.wgs84_long ?? 0),
	                distance: row['geodist'] // Included since you are sorting by distance
	            };
	        });

	    } catch (error) {
	        console.error("Geograph API Fetch Error:", error);
	        return [];
	    }
	}

//////////////////////////////////////////////////////////

        /**
         * Fetches and renders the results from the Geograph API.
	 * ... intended mainly as a demo, but is a functional implementation
         *
         * @param {string} url The complete API URL to fetch from.
         * @param {string} divId The ID of the HTML element to render the results into.
         * @param {string} [countDivId] The optional ID of an element to display the total count.
         */
        function renderAPIResults(url, divId, countDivId) {
            const divElement = document.getElementById(divId);
            const countDivElement = document.getElementById(countDivId);

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.rows) {
                        // Clear previous results
                        divElement.innerHTML = '';

                        data.rows.forEach(row => {
                            const htmlContent = `
                                <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">
                                    <img src="${getGeographUrl(row.id, row.hash, 'med')}" alt="${escapeHtml(row.title)}" loading="lazy">
                                </a>
                                <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">${escapeHtml(row.title)}</a>
                                <span class="nowrap">by ${escapeHtml(row.realname)}</span>`;

                            const newDiv = document.createElement('div');
                            newDiv.innerHTML = htmlContent;
                            divElement.appendChild(newDiv);
                        });

                        if (data.meta && data.meta.total_found && countDivElement) {
                            countDivElement.textContent = `Showing ${data.rows.length} of ${data.meta.total_found} results.`;
                        }
                    }
                })
                .catch(error => console.error('Error fetching data:', error));
        }
        function mapAPIResults(url, map, autoZoom, countDivId) {
                const countDivElement = document.getElementById(countDivId);
		if (typeof autoZoom === 'undefined') //default to true
			autoZoom = true;

	        fetch(url)
	            .then(response => response.json())
	            .then(data => {
	                if (data.rows) {
                            map.closePopup();
	                    var markerBounds = L.latLngBounds();
	                    data.rows.forEach(row => {
	                        if (row.wgs84_lat && row.wgs84_long) {
	                            var latLng = L.latLng(rad2deg(row.wgs84_lat), rad2deg(row.wgs84_long));
	                            var marker = L.marker(latLng).addTo(map);
	                            var popupContent = `<a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank">` +
	                                               `<img src="${getGeographUrl(row.id, row.hash, 'med')}"><br>` +
	                                               `${escapeHtml(row.title)}</a> by ${escapeHtml(row.realname)}`;
	                            marker.bindPopup(popupContent);
	                            marker.bindTooltip(row.title);
	                            markerBounds.extend(latLng);
	                        }
	                    });
	                    if (markerBounds.isValid() && autoZoom) {
				if (map._map) { //appears we been passed the layergroup!
		                        map._map.fitBounds(markerBounds, {maxZoom:16} );
				} else
		                        map.fitBounds(markerBounds, {maxZoom:16} );
	                    }
	                    if (data.meta && data.meta.total_found && countDivElement)
	                        countDivElement.textContent = "Showing "+data.rows.length+" of "+data.meta.total_found;
                        } else {
                            if (countDivElement)
                                countDivElement.textContent = "No Results";
                            if (typeof wgs84 !== 'undefined' && wgs84 && wgs84.latitude) {
                                L.popup()
                                .setLatLng([wgs84.latitude, wgs84.longitude])
                                .setContent("No Results")
                                .openOn(map);
                            }
	                }
	            });
        }

        /**
         * A helper function to create a URL query string from a JavaScript object.
         * This replaces the jQuery.param() method.
         *
         * @param {object} data The object containing key-value pairs for the query.
         * @returns {string} The formatted URL query string.
         */
        function objectToUrlParams(data) {
            const params = new URLSearchParams();
            for (const key in data) {
                if (data.hasOwnProperty(key)) {
                    params.append(key, data[key]);
                }
            }
            return params.toString();
        }

////////////////////////////////////////////////////////////

function rad2deg (angle) {
    return angle * 57.29577951308232; // angle / Math.PI * 180
}

function getGeographUrl(gridimage_id, hash, size) {

        yz=zeroFill(Math.floor(gridimage_id/1000000),2);
        ab=zeroFill(Math.floor((gridimage_id%1000000)/10000),2);
        cd=zeroFill(Math.floor((gridimage_id%10000)/100),2);
        abcdef=zeroFill(gridimage_id,6);

        if (yz == '00') {
                fullpath="/photos/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        } else {
                fullpath="/geophotos/"+yz+"/"+ab+"/"+cd+"/"+abcdef+"_"+hash;
        }

        switch(size) {
                case 'full': return "https://s0.geograph.org.uk"+fullpath+".jpg"; break; // dont forget: <img src=... style="image-orientation: none" loading="lazy" crossorigin onerror="retryCross(this)">
                case 'med': return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_213x160.jpg"; break;
                case 'small':
                default: return "https://s"+(gridimage_id%4)+".geograph.org.uk"+fullpath+"_120x120.jpg";
        }
}

function zeroFill(number, width) {
        width -= number.toString().length;
        if (width > 0) {
                return new Array(width + (/\./.test(number)?2:1)).join('0') + number;
        }
        return number + "";
}

function retryCross(that) {
    //this function allows retry of tags with crossorigin. Note the query string doesnt do anything on the server, its just to bust the local browser cache (that might have the non-cors image cached)
    if (that.src.indexOf('crossorigin') == -1 && that.hasAttribute('crossorigin')) {
        that.src = that.src + '?crossorigin';
        if (that.hasAttribute('srcset'))
            that.srcset = that.srcset.replace(/\.jpg/g,'.jpg?crossorigin');
    }
}

function space_date(datestr) {
    if (datestr && datestr.length == 8)
       return datestr.substring(0,4)+'-'+datestr.substring(4,6)+'-'+datestr.substring(6,8);
    return datestr;
}

function formatTakenDate(dateStr) {
    if (!dateStr || dateStr < '1000-01-01') return 'unknown';

    const parts = dateStr.split('-');
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);

    const now = new Date();
    const currentYear = now.getFullYear();

    // 1. Case: Only Year (e.g., 1961-00-00)
    if (month === 0) {
        return `${year}`;
    }

    // 2. Case: Year and Month (e.g., 1992-12-00)
    // We use a dummy date (day 1) just to get the month name via Intl
    const tempDate = new Date(year, month - 1, 1);
    const monthName = tempDate.toLocaleDateString('en-GB', { month: 'short' });

    if (day === 0) {
        return year === currentYear ? monthName : `${monthName} ${year}`;
    }

    // 3. Case: Full Date (e.g., 1992-12-15)
    const fullDate = new Date(year, month - 1, day);
    const weekday = fullDate.toLocaleDateString('en-GB', { weekday: 'short' });

    const suffix = (day % 10 === 1 && day !== 11) ? 'st'
                 : (day % 10 === 2 && day !== 12) ? 'nd'
                 : (day % 10 === 3 && day !== 13) ? 'rd' : 'th';

    let formatted = `${weekday} ${day}<sup>${suffix}</sup> ${monthName}`;

    if (year !== currentYear) {
        formatted += ` ${year}`;
    }

    return formatted;
}

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) {
        return '';
    }
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function retryCross(that) {
        //this function allows retry of tags with crossorigin. Note the query string doesnt do anything on the server, its just to bust the local browser cache (that might have the non-cors image cached)
        if (that.src.indexOf('crossorigin') == -1 && that.hasAttribute('crossorigin')) {
                that.src = that.src + '?crossorigin';
                if (that.hasAttribute('srcset'))
                        that.srcset = that.srcset.replace(/\.jpg/g,'.jpg?crossorigin');
        }
}

function getTextQuery(raw) {
    if (raw.length == 0) {
       return '';
    }

    //http: (urls) bombs out the field: syntax
    //$q = str_replace('http://','http ',$q);
    var query = raw.replace(/(https?):\/\//g,'$1 ');

    //remove any colons in tags - will mess up field: syntax
    query  =  query.replace(/\[([^\]]+)[:]([^\]]+)\]/g,'[$1~~~$2]');

    query = query.replace(/(-?)\b([a-z_]+):/g,'@$2 $1');
    query = query.replace(/@(year|month|day) /,'@taken$1 ');
    query = query.replace(/@gridref /,'@grid_reference ');
    query = query.replace(/@by /,'@realname ');
    query = query.replace(/@name /,'@realname ');
    query = query.replace(/@tag /,'@tags ');
    query = query.replace(/@subject /,'@subjects ');
    query = query.replace(/@type /,'@types ');
    query = query.replace(/@context /,'@contexts ');
    query = query.replace(/@placename /,'@place ');
    query = query.replace(/@category /,'@imageclass ');
    query = query.replace(/@text /,'@(title,comment,imageclass,tags,subjects) ');
    query = query.replace(/@user /,'@user user');

    query = query.replace(/\b(\d{3})0s\b/g,'$1tt');
    query = query.replace(/\bOR\b/g,'|');

    //make excluded hyphenated words phrases
    query = query.replace(/(^|[^"\w]+)-(=?\w+)(-[-\w]*\w)/g,function(match,pre,p1,p2) {
        return pre+'-("'+(p1+p2).replace(/-/,' ')+'" | '+(p1+p2).replace(/-/,'')+')';
    });

    //make hyphenated words phrases
    query = query.replace(/(^|[^"\w]+)(=?\w+)(-[-\w]*\w)/g,function(match,pre,p1,p2) {
        return pre+'"'+(p1+p2).replace(/-/,' ')+'" | '+(p1+p2).replace(/-/,'');
    });

    //make excluded aposphies work (as a phrase)
    query = query.replace(/(^|[^"\w]+)-(=?\w+)(\'\w*[\'\w]*\w)/g,function(match,pre,p1,p2) {
        return pre+'-("'+(p1+p2).replace(/\'/,' ')+'" | '+(p1+p2).replace(/\'/,'')+')';
    });

    //make aposphies work (as a phrase)
    query = query.replace(/(^|[^"\w]+)(\w+)(\'\w*[\'\w]*\w)/,function(match,pre,p1,p2) {
        return pre+'"'+(p1+p2).replace(/\'/,' ')+'" | '+(p1+p2).replace(/\'/,'');
    });

    //change single quotes to double
    query = query.replace(/(^|\s)\b\'([\w ]+)\'\b(\s|$)/g, '$1"$2"$3');

    //fix placenames with / (the \b stops it replacing in "one two"/3
    query = query.replace(/\b\/\b/g,' ');

    //seperate out tags!
    if (m = query.match(/(-?)\[([^\]]+)\]/g)) {
       for(i=0;i<m.length;i++) {
          var value = m[i];
          query = query.replace(value,'');
          var bits = value.replace(/[\[\]-]+/g,'').split('~~~');
          var prefix = '*';
          if (bits.length > 1) {
             if (bits[0] == 'subject' || bits[0] == 'type' || bits[0] == 'context' || bits[0] == 'bucket') {
                 prefix = bits[0]+'s';
                 value = bits[1];
             } else if (bits[0] == 'top') {
                 prefix = 'contexts';
                 value = bits[1];
             } else {
                 prefix = 'tags';
                 value = bits[0]+' '+bits[1];
             }
          }
          query = query +' @'+prefix+' '+((value.indexOf('-')==0)?'-':'') + '"_SEP_ '+value.replace(/[\[\]-]+/g,'') + ' _SEP_"';
       }
    }

    return query;
}


////////////////////////////////////////////////////
// simple wrapper for geotools!

function gridref2wgs(gridref) {
        gridref = gridref.trim().toUpperCase().replace(/ /g,'');
        var grid=new GT_OSGB();
        var ok = false;
        if (grid.parseGridRef(gridref)) {
                ok = true;
        } else {
                grid=new GT_Irish();
                ok = grid.parseGridRef(gridref)
        }

        if (ok && gridref.length > 4) {
                if (gridref.length <= 6 && grid.eastings%1000 == 0 && grid.northings%1000 == 0) {
                        grid.eastings = grid.eastings + 500;
                        grid.northings = grid.northings + 500;
                } else if (gridref.length <= 8 && grid.eastings%100 == 0 && grid.northings%100 == 0) {
                        grid.eastings = grid.eastings + 50;
                        grid.northings = grid.northings + 50;
                } else if (gridref.length <= 10 && grid.eastings%10 == 0 && grid.northings%10 == 0) {
                        grid.eastings = grid.eastings + 5;
                        grid.northings = grid.northings + 5;
                }
                return grid.getWGS84(true);
        }
        return {};
}

function wgs2gridref(lat, long, len) {
        let wgs84=new GT_WGS84();
        wgs84.setDegrees(lat, long);

        var grid = false;
        if (wgs84.isIreland2()) {
                grid=wgs84.getIrish(true);
        } else if (wgs84.isGreatBritain()) {
                grid=wgs84.getOSGB();
        }
        if (grid)
                return grid.getGridRef(len/2).replace(/ /g,'');
        return null;
}

//////////////////////////////////////////////////////////

/**
 * Parses a string of WGS84 coordinates, converts them to radians, and formats a GEOPOLY2D filter string.
 * @param {string} polygonStr A string of coordinates in the format 'lng,lat+lng,lat+...'.
 * @returns {string} The formatted GEOPOLY2D filter string.
 */
function getPolygonFilter(polygonStr) {
    // 1. Parse the string into an array of coordinate pairs [lng, lat]
    const coords = polygonStr.split('+').map(coord => {
        const pair = coord.split(',');
        return [parseFloat(pair[0]), parseFloat(pair[1])];
    });

    // 2. Convert each coordinate from degrees to radians
    const radianCoords = coords.map(coordPair => {
        const lngRad = (coordPair[0] * Math.PI / 180).toFixed(6);
        const latRad = (coordPair[1] * Math.PI / 180).toFixed(6);
        return [lngRad, latRad];
    });

    // 3. Flatten the array and join into a comma-separated string
    const radianList = radianCoords.flat().join(',');

    // 4. Return the formatted filter string
    return `CONTAINS(GEOPOLY2D(${radianList}),wgs84_long,wgs84_lat)`;
}

//////////////////////////////////////////////////////////

/**
 * A JavaScript implementation of MySQL's TO_DAYS() function.
 *
 * This function calculates the number of days since year 0 (a theoretical
 * date used by MySQL). It works by using a known anchor point in time
 * ('1970-01-01') and the corresponding TO_DAYS() value from MySQL, then
 * calculating the number of days between the anchor and the input date.
 *
 * The function uses UTC to avoid timezone issues that could affect the
 * day count.
 *
 * @param {string} dateString The date to convert, in 'YYYY-MM-DD' format.
 * @returns {number} The number of days since year 0. Returns 0 for invalid dates.
 */
function toDays(dateString) {
  // Milliseconds in a day.
  const millisecondsPerDay = 1000 * 60 * 60 * 24;

  // MySQL's TO_DAYS() value for the Unix Epoch start date (1970-01-01).
  // We use this as our known anchor point.
  const mysqlEpochOffset = 719528;

  // Create a Date object from the input string. By appending 'T00:00:00Z',
  // we force the date to be interpreted as UTC, which is crucial for
  // consistent day calculations regardless of the user's timezone.
  const inputDate = new Date(`${dateString}T00:00:00Z`);

  // Check if the date is valid. If not, return 0 as MySQL does for '0000-00-00'.
  if (isNaN(inputDate.getTime())) {
    console.error(`Invalid date format: ${dateString}. Please use 'YYYY-MM-DD'.`);
    return 0;
  }

  // Calculate the number of days since the Unix Epoch (January 1, 1970).
  // The getTime() method returns milliseconds since the epoch, so we divide
  // by the number of milliseconds in a day. We use Math.floor() to get a
  // whole number of days.
  const daysSinceEpoch = Math.floor(inputDate.getTime() / millisecondsPerDay);

  // Add the MySQL epoch offset to get the final TO_DAYS() value.
  const result = mysqlEpochOffset + daysSinceEpoch;

  return result;
}

function headingString(deg, dirs = ['north', 'east', 'south', 'west']) {
    // Normalize degrees and calculate 16-point segment
    let rounded = Math.round(deg / 22.5) % 16;
    
    // JavaScript modulo can return negative, so fix it
    if (rounded < 0) rounded += 16;

    let s = "";

    if ((rounded % 4) === 0) {
        // Direct Cardinal: N, E, S, W
        s = dirs[rounded / 4];
    } else {
        // Ordinal (NE, SE, etc)
        const primaryIdx = 2 * Math.floor(((Math.floor(rounded / 4) + 1) % 4) / 2);
        const secondaryIdx = 1 + 2 * Math.floor(rounded / 8);
        
        s = dirs[primaryIdx] + dirs[secondaryIdx];

        // Secondary-Intercardinal (NNE, ENE, etc)
        if (rounded % 2 === 1) {
            // Replicates the 'round($rounded/4) % 4' logic
            const tertiaryIdx = Math.round(rounded / 4) % 4;
            s = dirs[tertiaryIdx] + s;
        }
    }
    return s;
}

// Shortcut version to match your PHP function
function headingStringShort(deg) {
    return headingString(deg, ['N', 'E', 'S', 'W']).replace(/-/g, '');
}
