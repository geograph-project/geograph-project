/**
*
* Some of this code was originally ...
*
* Crown Copyright (c) 2009, Secretary of State for Communities and Local Government,
* acting through Ordnance Survey.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without modification,
* are permitted provided that the following conditions are met:
*
*   Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* 
*   Redistributions in binary form must reproduce the above copyright notice,
*   this list of conditions and the following disclaimer in the documentation
*   and/or other materials provided with the distribution.
* 
*   Neither the name of the Secretary of State for Communities and Local Government,
*   acting through Ordnance Survey nor the names of its contributors
*   may be used to endorse or promote products derived from this software
*   without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
* ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
* IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
* INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
* DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
* LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
* OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
* OF THE POSSIBILITY OF SUCH DAMAGE.
* 
*/

/* A generic eastings and northings point, units = metres */
function ENPoint(e, n) {
    this.east = e;
    this.north = n;
}

/* A UKOSGB eastings, northings point, units = metres */
function OgbPoint(e, n) {
    this.east = e;
    this.north = n;
}

/* An IrishGrid eastings, northings point, units = metres */
function IrishPoint(e, n) {
    this.north = n;
    this.east = e;
}

/* A datum non-specific Lat/Lon point, units = degrees -90,+90 lat, -180,+190 lon */
function LonLatPoint(lo, la) {
    this.longi = lo;
    this.lati = la;
}

/* A WGS84 Lat/Lon point, units = degrees -90,+90 lat, -180,+190 lon */
function Wgs84Point(lo, la) {
	this.longi = lo;
	this.lati = la;
}

/* some functions to make Wgs84Point compatible with a Google GLatLng */
Wgs84Point.prototype.lat = function () {
	return this.lati;
};

Wgs84Point.prototype.lng = function () {
	return this.longi;
};

/**
 *  
 * Class: UkGridProjection
 * Static Class providing transformation from WGS84 to British National Grid and
 * from British National Grid to WGS84.
 *
 */
function UkGridProjection() {
}

/* Set up the low resolution adjustment data */

UkGridProjection.low_res_east_shift =  [[92, 89, 85, 93, 99], [96, 96, 97, 99, 104], [102, 105, 108, 106, 107]];
UkGridProjection.low_res_north_shift = [[-82, -75, -58, -47, -44], [-80, -75, -62, -52, -49], [-82, -78, -62, -54, -52]];


/**
 * APIMethod: getOgbPointFromLonLat
 * Convert an WGS84 point into a OSGB36 point
 * 
 * Parameters:
 * pt_LonLat - {<Wgs84Point>} The point to convert
 * 
 * Returns:
 * {<OgbPoint>} The point converted into OSGB36 British National Grid
 */
UkGridProjection.getOgbPointFromLonLat = function (pt_LonLat) {
    var pt_ETRS89 = TMProjection.LonLat_to_E_N(pt_LonLat.lng(), pt_LonLat.lat(), 6378137.0, 6356752.3141, 400000.0, -100000.0, 0.9996012717, 49.0, -2.0);
    var pt_OSGB36 = UkGridProjection.convert_89_36(pt_ETRS89);
    return pt_OSGB36;
};

/**
 * APIMethod: getLonLatFromOgbPoint
 * Convert a OSGB36 point into a WGS84 lon lat point
 * 
 * Parameters:
 * pt_OSGB36 - {<OgbPoint>} The point to convert
 * 
 * Returns:
 * {<Wgs84Point>} The point converted into WGS84 Longitude Latitude
 */
UkGridProjection.getLonLatFromOgbPoint = function (pt_OSGB36) {
    // Transform to ETRS89 first, then project back onto WGS84 ellipsoid
    var pt_ETRS89 = UkGridProjection.convert_36_89(pt_OSGB36);
    var pt_LonLat = TMProjection.E_N_to_LonLat(pt_ETRS89.east, pt_ETRS89.north, 6378137.0, 6356752.3141, 400000.0, -100000.0, 0.9996012717, 49.0, -2.0);
    return new Wgs84Point(pt_LonLat.longi, pt_LonLat.lati);
};


/**
 * Method: convert_89_36
 * Convert an ETRS89 point into a OSGB36 point
 * 
 * Parameters:
 * pt_ETRS89 - {<ENPoint>} The point to convert
 * 
 * Returns:
 * {<OgbPoint>} The point converted into British National Grid
 */
UkGridProjection.convert_89_36 = function (pt_ETRS89) {
    var e = new Array(4);
    var n = new Array(4);
    var dxs = new Array(4);
    var dys = new Array(4);

    var spacing = 350000.0;

    // calculate the appropriate position in the grid
    var east_index = Math.floor(pt_ETRS89.east / spacing);
    var north_index = Math.floor(pt_ETRS89.north / spacing);

    e[0] = e[3] = east_index;
    e[1] = e[2] = east_index + 1;
    n[0] = n[1] = north_index;
    n[2] = n[3] = north_index + 1;

    var i;
    for (i = 0; i < 4; i++) {
        if (e[i] < 0) {
            e[i] = 0;
        }
        if (e[i] > 2) {
            e[i] = 2;
        }
        if (n[i] < 0) {
            n[i] = 0;
        }
        if (n[i] > 4) {
            n[i] = 4;
        }

        dxs[i] = UkGridProjection.low_res_east_shift[e[i]][n[i]];
        dys[i] = UkGridProjection.low_res_north_shift[e[i]][n[i]];
    }

    // calculate shifts using bilinear interpolation
    var shiftX = UkGridProjection.bilinear(dxs, east_index * spacing, east_index * spacing + spacing, north_index * spacing, north_index * spacing + spacing, pt_ETRS89.east, pt_ETRS89.north);

    var shiftY = UkGridProjection.bilinear(dys, east_index * spacing, east_index * spacing + spacing, north_index * spacing, north_index * spacing + spacing, pt_ETRS89.east, pt_ETRS89.north);

    // return OSGB36 position
    var output = new OgbPoint(pt_ETRS89.east + shiftX, pt_ETRS89.north + shiftY);


    return output;
};

/**
 * Method: convert_36_89
 * Convert an OSGB36 point into an ETRS89 point
 * 
 * Parameters:
 * pt_OSGB36 - {<OgbPoint>} The point to convert
 * 
 * Returns:
 * {<ENPoint>} The point converted into ETRS89
 */
UkGridProjection.convert_36_89 = function (pt_OSGB36) {
    // Takes OSGB36 plane co-ordinates and calculates ETRF89 plane co-ordinates
    // resolution should be HIGH_RES or LOW_RES
    // Solves a quadratic to reverse the bilnear interpolation process
    var dxs = new Array(4);
    var dys = new Array(4);
    var v1x;
    var v1y;
    var v2x;
    var v2y;
    var local_pt = new OgbPoint(0, 0);
    var i;
    var a;
    var b;
    var AA;
    var BB;
    var CC;
    var old_a;
    var f_x;
    var f_dx;
    var s;
    var dn;
    var de;
    var t1;
    var t2;
    var t3;
    var t5;
    var l = new Array(7);
    var spacing = 350000.0;
    var gride = Math.floor(pt_OSGB36.east / spacing);
    var gridn = Math.floor(pt_OSGB36.north / spacing);
    // Loop through different gridsquares to find which parameters are applicable
    var indexX;
    var indexY;
    i = 0;
    while (i != 4) {
        for (i = 0; i < 4; i++) {
            indexX = gride + Math.floor(i / 2);
            indexY = gridn + Math.floor(((i + 1) % 4) / 2);
            if (indexX < 0) {
                indexX = 0;
            }
            if (indexX > 2) {
                indexX = 2;
            }
            if (indexY < 0) {
                indexY = 0;
            }
            if (indexY > 4) {
                indexY = 4;
            }

            if (indexX >= 0 && indexX <= 2 && indexY >= 0 && indexY <= 4) {
                dxs[i] = UkGridProjection.low_res_east_shift[indexX][indexY];
                dys[i] = UkGridProjection.low_res_north_shift[indexX][indexY];

            }
        }

        // Check the cross products around the point to successive grid square corners

        for (i = 0; i < 4; i++) {
            indexX = gride + Math.floor(i / 2);
            indexY = gridn + Math.floor(((i + 1) % 4) / 2);
            v1x = dxs[i] + spacing * indexX - pt_OSGB36.east;
            v1y = dys[i] + spacing * indexY - pt_OSGB36.north;
            indexX = gride + Math.floor(((i + 1) % 4) / 2);
            indexY = gridn + 1 - Math.floor(i / 2);
            v2x = dxs[(i + 1) % 4] + spacing * indexX - pt_OSGB36.east;
            v2y = dys[(i + 1) % 4] + spacing * indexY - pt_OSGB36.north;

            // if greater than 0, the point lies on the outside of the square

            if ((v1x * v2y - v2x * v1y) > 0.0) {
                break;
            }
        }

        switch (i) {
        case 0:
            gride--;
            break;
        case 1:
            gridn++;
            break;
        case 2:
            gride++;
            break;
        case 3:
            gridn--;
            break;
        case 4:
            break;
        }
    }

    // drops out of the loop when the appropriate grid square has been found

    // Take co-ordinates from an origin of the bottom-left corner of the gridsquare

    local_pt.east = pt_OSGB36.east - gride * spacing;
    local_pt.north = pt_OSGB36.north - gridn * spacing;

    // The east shift (a) and north shift (b) are calculated by solving a set of 2
    // simultaneous equations.  This involves solving a quadratic for 'a', then
    // substituting this into the equation for 'b'.

    // First solve the quadratic for 'a'.
    // Quadratic is:  AAa^2 + BBa + CC  
    // The grid square in which the point was originally situated has shifts (d):
    //                              
    // gridn+1 _______________      
    //         |\           /|      
    //         | d[1]   d[2] |      
    //         |             |      
    //         |             |      
    //         |             |      
    //         | d[0]   d[3] |      
    //   gridn |/___________\|      
    //     gride         gride+1   
    //                              


    // The array l contains temporary variables used to simplify the calculations

    l[1] = dxs[0] - dxs[3];
    l[2] = dxs[1] - dxs[2];
    l[3] = dys[0] - dys[3];
    l[4] = dys[1] - dys[2];
    l[5] = dys[0] - dys[1];
    l[6] = dxs[0] - dxs[1];


    s = spacing;
    dn = local_pt.north - dys[0];
    de = local_pt.east - dxs[0];
    t1 = s - l[1];
    t2 = l[3] - l[4];
    t3 = l[1] - l[2];
    t5 = s - l[5];

    AA = (t1 * t2 + l[3] * t3) / s;
    BB = t3 * dn - l[3] * l[6] + t1 * t5 - t2 * de;
    CC = -s * dn * l[6] - s * de * (s - l[5]);

    if (AA < BB * 0.0000000001) {
        if (BB == 0) {
            return null;
        }
        a = -CC / BB;
    } else {
        a = (-BB + Math.sqrt(BB * BB - 4.0 * AA * CC)) / (2.0 * AA);
    }

    // Quadratic is ill-conditioned, so use a Newton-Raphson iteration
    // to produce a better estimate for a

    for (i = 0; i < 10; i++) {
        old_a = a;
        f_x = AA * old_a * old_a + BB * old_a + CC;
        f_dx = 2.0 * AA * old_a + BB;
        if (f_dx == 0.0) {
            break;
        }
        a = old_a - (f_x / f_dx);
        if (Math.abs(old_a - a) < 0.00001) {
            break;
        }
    }

    // Having found the east shift (a), substitute into the second equation to give
    // the north shift (b)

    b = ((s * s) * local_pt.north - (s * (s * dys[0] - a * l[3]))) / ((s * s) - (s * l[5] - a * t2));

    var pt_ETRS89 = new ENPoint(a + (gride * spacing), b + (gridn * spacing));

    return pt_ETRS89;
};

/**
 * Method: bilinear
 * Internal interpolation method.
 * 
 * Parameters:
 * 
 * Returns:
 * {Float}
 */
UkGridProjection.bilinear = function (y, x1l, x1u, x2l, x2u, x1, x2) {
    var d1 = x1u - x1l;
    var d2 = x2u - x2l;
    var t = (x1 - x1l) / d1;
    var u = (x2 - x2l) / d2;
    return (1.0 - t) * (1.0 - u) * y[0] + t * (1.0 - u) * y[1] + t * u * y[2] + (1.0 - t) * u * y[3];
};

/**
* APIMethod: gridRefFromEastNorth
* Convert Eastings and Northings to a Grid Ref
* 
* Parameters:
* e,n eastings and northings in metres
* digits for the GR, range 1-5
* 
* Returns:
* grid ref as string
*/

UkGridProjection.gridRefFromEastNorth = function (e, n, digits) {

    //convert northing and easting to letter and number grid system
    //digits 1-5, e & n in metres

    var res = 1;
    var fres = 1.0;
    switch (digits) {
    case 4:
        res = 10;
        fres = 10.0;
        break;
    case 3:
        res = 100;
        fres = 100.0;
        break;
    case 2:
        res = 1000;
        fres = 1000.0;
        break;
    case 1:
        res = 10000;
        fres = 10000.0;
        break;
    default:
        digits = 5;
    }

    //round to wanted precision, use ~~ to force an integer type
    var east = ~~((Math.round(e / fres) * res));
    var north = ~~((Math.round(n / fres) * res));

    var eX = ~~(east / 500000); // count 500's of km as integer
    var nX = ~~(north / 500000);
    var eX1 = ~~((east % 500000) / 100000); // count 100's of km in 500km as integer
    var nX1 = ~~((north % 500000) / 100000);

    var l1 = eX - (5 * nX) + 17; // first letter identifies 500km square
    var l2 = (20 - (nX1 * 5)) + eX1; // second letter identifies one of 25 100km squares

    if (l2 > 7.5) {
        l2 = l2 + 1; // I is not used
    }
    if (l1 > 7.5) {
        l1 = l1 + 1; // I is not used
    }

    var eing = east - ((eX * 500000) + (eX1 * 100000)); // take off 100s of kms
    var ning = north - ((nX * 500000) + (nX1 * 100000));

    var estr = (eing / fres).toFixed(0);
    var nstr = (ning / fres).toFixed(0);
    while (estr.length < digits) {
        estr = "0" + estr;
    }
    while (nstr.length < digits) {
        nstr = "0" + nstr;
    }

    var ngr;
    if ((e < 0) || (e >= 700000) || (n < 0) || (n >= 1300000)) {
        ngr = "**" + " " + estr + " " + nstr;
    } else {
        ngr = String.fromCharCode(l1 + 65) + String.fromCharCode(l2 + 65) + " " + estr + " " + nstr;
    }
    return ngr;

};

/**
* APIMethod: gridRefToEastNorth
* Convert a grid reference string to Eastings and Northings
* 
* Parameters:
* ngr - {<string>} The grid reference to convert
* 
* Returns:
* {<OgbPoint>} The grid ref converted into easting and northings
*/
UkGridProjection.gridRefToEastNorth = function (ngr) {
    var e;
    var n;
    var i;

    ngr = ngr.toUpperCase(ngr);

    var bits = ngr.split(' ');
    ngr = "";
    for (i = 0; i < bits.length; i++) {
        ngr += bits[i];
    }

    var c = ngr.charAt(0);
    if (c == 'S') {
        e = 0;
        n = 0;
    } else if (c == 'T') {
        e = 500000;
        n = 0;
    } else if (c == 'N') {
        n = 500000;
        e = 0;
    } else if (c == 'O') {
        n = 500000;
        e = 500000;
    } else if (c == 'H') {
        n = 1000000;
        e = 0;
    } else {
        return null;
    }

    c = ngr.charAt(1);
    if (c == 'I') {
        return null;
    }

    c = ngr.charCodeAt(1) - 65;
    if (c > 8) {
        c -= 1;
    }
    e += (c % 5) * 100000;
    n += (4 - Math.floor(c / 5)) * 100000;

    c = ngr.substr(2);
    if ((c.length % 2) == 1) {
        return null;
    }
    if (c.length > 10) {
        return null;
    }

    try {
        var s = c.substr(0, c.length / 2);
        while (s.length < 5) {
            s += '0';
        }
        e += parseInt(s, 10);
        if (isNaN(e)) {
            return null;
        }

        s = c.substr(c.length / 2);
        while (s.length < 5) {
            s += '0';
        }
        n += parseInt(s, 10);
        if (isNaN(n)) {
            return null;
        }

        return new OgbPoint(e, n);
    } catch (ex) {
        return null;
    }

};


/*****************************************************************************
*
* IrishProjection holds Irish grid conversion routines in a static class
*
*****************************************************************************/

//IrishProjection is just namespace for all the conversion functions
function IrishProjection() {
}

IrishProjection.prefixes = [
    ["V", "Q", "L", "F", "A"],
    ["W", "R", "M", "G", "B"],
    ["X", "S", "N", "H", "C"],
    ["Y", "T", "O", "J", "D"],
    ["Z", "U", "P", "K", "E"]
];

IrishProjection.zeropad = function (num, len) {
    var str = num.toString();
    while (str.length < len) {
        str = '0' + str;
    }
    return str;
};

IrishProjection.gridRefFromEastNorth = function (eastings, northings, precision) {

    if (precision < 0) {
        precision = 0;
    }
    if (precision > 5) {
        precision = 5;
    }

    var e;
    var n;
    var x;
    var y;
    if (precision > 0) {
        y = Math.floor(northings / 100000);
        x = Math.floor(eastings / 100000);

        e = Math.floor(eastings % 100000);
        n = Math.floor(northings % 100000);

        var div = (5 - precision);
        e = Math.floor(e / Math.pow(10, div));
        n = Math.floor(n / Math.pow(10, div));
    }

    var prefix = "*";
    if ((x < 5) && (y < 5) && (x >= 0) && (y >= 0)) {
        prefix = IrishProjection.prefixes[x][y];
    }

    return prefix + " " + IrishProjection.zeropad(e, precision) + " " + IrishProjection.zeropad(n, precision);
};

IrishProjection.gridRefToEastNorth = function (landranger) {

    var r = new IrishPoint(0, 0);

    var precision;

    for (precision = 5; precision >= 1; precision--) {
        var pattern = new RegExp("^([A-Z]{1})\\s*(\\d{" + precision + "})\\s*(\\d{" + precision + "})$", "i");
        var gridRef = landranger.match(pattern);
        if (gridRef) {
            var gridSheet = gridRef[1];
            var gridEast = 0;
            var gridNorth = 0;

            //5x1 4x10 3x100 2x1000 1x10000 
            if (precision > 0) {
                var mult = Math.pow(10, 5 - precision);
                gridEast = parseInt(gridRef[2], 10) * mult;
                gridNorth = parseInt(gridRef[3], 10) * mult;
            }

            var x, y;
search:     for (x = 0; x < IrishProjection.prefixes.length; x++) {
                for (y = 0; y < IrishProjection.prefixes[x].length; y++) {
                    if (IrishProjection.prefixes[x][y] == gridSheet) {
                        r = new IrishPoint((x * 100000) + gridEast, (y * 100000) + gridNorth);
                        break search;
                    }
                }
            }
        }
    }
    return r;
};

IrishProjection.getLonLatFromIrishPoint = function (pt_Irish) {

    var height = 0;

    //Level 2 Transformation - 95% of points should fall within 40 cm

    // First convert from Transverse Mercator then do a datum shift from Modified Airy to WGS84

    var e = pt_Irish.east;
    var n = pt_Irish.north;

    var llPoint = TMProjection.E_N_to_LonLat(e, n, 6377340.189, 6356034.447, 200000, 250000, 1.000035, 53.50000, -8.00000);

    var x1 = GT_Math_OL.Lat_Long_H_to_X(llPoint.lati, llPoint.longi, height, 6377340.189, 6356034.447);
    var y1 = GT_Math_OL.Lat_Long_H_to_Y(llPoint.lati, llPoint.longi, height, 6377340.189, 6356034.447);
    var z1 = GT_Math_OL.Lat_H_to_Z(llPoint.lati, height, 6377340.189, 6356034.447);
    var x2 = GT_Math_OL.Helmert_X(x1, y1, z1, 482.53, -0.214, -0.631, 8.15);
    var y2 = GT_Math_OL.Helmert_Y(x1, y1, z1, -130.596, -1.042, -0.631, 8.15);
    var z2 = GT_Math_OL.Helmert_Z(x1, y1, z1, 564.557, -1.042, -0.214, 8.15);
    var latitude = GT_Math_OL.XYZ_to_Lat(x2, y2, z2, 6378137.000, 6356752.313);
    var longitude = GT_Math_OL.XYZ_to_Long(x2, y2);

    return new Wgs84Point(longitude, latitude);

};

IrishProjection.getIrishPointFromLonLat = function (pt_LonLat) {

    var height = 0.0;

    //Level 2 Transformation - 95% of points should fall within 40 cm

    // First do a datum shift from WGS84 to Modified Airy, then convert to Transverse Mercator

    var x1 = GT_Math_OL.Lat_Long_H_to_X(pt_LonLat.lati, pt_LonLat.longi, height, 6378137.00, 6356752.313);
    var y1 = GT_Math_OL.Lat_Long_H_to_Y(pt_LonLat.lati, pt_LonLat.longi, height, 6378137.00, 6356752.313);
    var z1 = GT_Math_OL.Lat_H_to_Z(pt_LonLat.lati, height, 6378137.00, 6356752.313);
    var x2 = GT_Math_OL.Helmert_X(x1, y1, z1, -482.53, 0.214, 0.631, -8.15);
    var y2 = GT_Math_OL.Helmert_Y(x1, y1, z1, 130.596, 1.042, 0.631, -8.15);
    var z2 = GT_Math_OL.Helmert_Z(x1, y1, z1, -564.557, 1.042, 0.214, -8.15);
    var latitude2 = GT_Math_OL.XYZ_to_Lat(x2, y2, z2, 6377340.189, 6356034.447);
    var longitude2 = GT_Math_OL.XYZ_to_Long(x2, y2);

    var enPoint = TMProjection.LonLat_to_E_N(longitude2, latitude2, 6377340.189, 6356034.447, 200000, 250000, 1.000035, 53.50000, -8.00000);

    return new IrishPoint(enPoint.east, enPoint.north);

};


/* Static class (non prototype methods, no use of this.) for Transverse Mercator Projection */

TMProjection = function () {
};

TMProjection.DEG_TO_RAD = Math.PI / 180.0;
TMProjection.RAD_TO_DEG = 180.0 / Math.PI;


/**
* Method: marc
* Internal conversion method to Compute meridional arc.
* 
* Parameters:
* 
* Returns:
* {Float}
*/
TMProjection.marc = function (bf0, n, PHI0, PHI) {
// Compute meridional arc.
// Input: -
//   ellipsoid semi major axis multiplied by central meridian scale factor (bf0) in meters;
//   n (computed from a, b and f0);
//   lat of false origin (PHI0) and initial or final latitude of point (PHI) IN RADIANS.

// THIS FUNCTION IS CALLED BY THE - _
// "Lat_Long_to_North" and "InitialLat" FUNCTIONS
// THIS FUNCTION IS ALSO USED ON IT'S OWN IN THE "Projection and Transformation Calculations.xls" SPREADSHEET

    var marc = bf0 *
            (((1.0 + n + ((5.0 / 4.0) * (n * n)) + ((5.0 / 4.0) * (n * n * n))) * (PHI - PHI0)) -
            (((3.0 * n) + (3.0 * (n * n)) + ((21.0 / 8.0) * (n * n * n))) * Math.sin(PHI - PHI0) * Math.cos(PHI + PHI0)) +
            ((((15.0 / 8.0) * (n * n)) + ((15.0 / 8.0) * (n * n * n))) * Math.sin(2.0 * (PHI - PHI0)) * Math.cos(2.0 * (PHI + PHI0))) -
            (((35.0 / 24.0) * (n * n * n)) * Math.sin(3.0 * (PHI - PHI0)) * Math.cos(3.0 * (PHI + PHI0))));
    return marc;
};

/**
* Method: initialLat
* Internal conversion method
* 
* Parameters:
* 
* Returns:
* {Float}
*/
TMProjection.initialLat = function (North, n0, afo, PHI0, n, bfo) {
// Compute initial value for Latitude (PHI) IN RADIANS.
// Input:
//   northing of point (North) and northing of false origin (n0) in meters;
//   semi major axis multiplied by central meridian scale factor (af0) in meters;
//   latitude of false origin (PHI0) IN RADIANS;
//   n (computed from a, b and f0) and
//   ellipsoid semi major axis multiplied by central meridian scale factor (bf0) in meters.

// REQUIRES THE "Marc" FUNCTION

    // First PHI value (PHI1)
    var PHI1 = ((North - n0) / afo) + PHI0;

    // Calculate M
    var M = TMProjection.marc(bfo, n, PHI0, PHI1);

    // Calculate new PHI value (PHI2)
    var PHI2 = ((North - n0 - M) / afo) + PHI1;


    // Iterate to get final value for InitialLat
    while (Math.abs(North - n0 - M) > 0.000001) {
        PHI2 = ((North - n0 - M) / afo) + PHI1;
        M = TMProjection.marc(bfo, n, PHI0, PHI2);


        PHI1 = PHI2;
    }

    return PHI2;
};

/**
* Method: E_N_to_LonLat
* Convert an ETRS89 point into a WGS84 point
* 
* Parameters:
* 
* 
* Returns:
* {<LonLatPoint>} The point converted into longitude and latitude
*/
TMProjection.E_N_to_LonLat = function (East, North, a, b, e0, n0, scl, PHI0, LAM0) {
// Un-project Transverse Mercator eastings and northings back to latitude.
// Input:
//   eastings (East) and northings (North) in meters;
//   ellipsoid axis dimensions (a & b) in meters;
//   eastings (e0) and northings (n0) of false origin in meters;
//   central meridian scale factor (f0) and
//   latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.

// REQUIRES THE "TMProjection.Marc" AND "TMProjection.InitialLat" FUNCTIONS

    // Convert angle measures to radians
    var RadPHI0 = PHI0 * TMProjection.DEG_TO_RAD;
    var RadLAM0 = LAM0 * TMProjection.DEG_TO_RAD;

    // Compute af0, bf0, e squared (e2), n and Et
    var af0 = a * scl;
    var bf0 = b * scl;
    var e2 = ((af0 * af0) - (bf0 * bf0)) / (af0 * af0);
    var n = (af0 - bf0) / (af0 + bf0);
    var Et = East - e0;


    // Compute initial value for latitude (PHI) in radians
    var PHId = TMProjection.initialLat(North, n0, af0, RadPHI0, n, bf0);

    // Compute nu, rho and eta2 using value for PHId
    var sinPHId = Math.sin(PHId);
    var sinPHId2 = sinPHId * sinPHId;
    var nu = af0 / (Math.sqrt(1.0 - (e2 * sinPHId2)));
    var rho = (nu * (1.0 - e2)) / (1.0 - (e2 * sinPHId2));
    var eta2 = (nu / rho) - 1.0;


    // Compute Latitude
    var tanPHId = Math.tan(PHId);
    var tanPHId2 = tanPHId * tanPHId;
    var tanPHId4 = tanPHId2 * tanPHId2;
    var tanPHId6 = tanPHId4 * tanPHId2;

    var VII = (tanPHId) / (2 * rho * nu);
    var VIII = (tanPHId / (24 * rho * (nu * nu * nu))) * (5 + (3 * tanPHId2) + eta2 - (9 * eta2 * tanPHId2));
    var IX = (tanPHId / (720 * rho * (nu * nu * nu * nu * nu))) * (61 + (90 * tanPHId2) + (45 * tanPHId4));
    var E_N_to_Lat = (PHId - ((Et * Et) * VII) + ((Et * Et * Et * Et) * VIII) - ((Et * Et * Et * Et * Et * Et) * IX));

    // Compute Longitude
    var cosPHId = Math.cos(PHId);
    var cosPHId_1 = 1.0 / cosPHId;

    var X = cosPHId_1 / nu;
    var XI = (cosPHId_1 / (6 * (nu * nu * nu))) * ((nu / rho) + (2 * tanPHId2));
    var XII = (cosPHId_1 / (120 * (nu * nu * nu * nu * nu))) * (5 + (28 * tanPHId2 + (24 * tanPHId4)));
    var XIIA = (cosPHId_1 / (5040 * (nu * nu * nu * nu * nu * nu * nu))) * (61 + (662 * tanPHId2 + (1320 * tanPHId4 + (720 * tanPHId6))));


    var E_N_to_Lng = RadLAM0 + (Et * X) - ((Et * Et * Et) * XI) + ((Et * Et * Et * Et * Et) * XII) - ((Et * Et * Et * Et * Et * Et * Et) * XIIA);
    var pt_LonLat = new LonLatPoint(E_N_to_Lng * TMProjection.RAD_TO_DEG, E_N_to_Lat * TMProjection.RAD_TO_DEG);

    return pt_LonLat;
};

/**
* Method: os_arc
* Internal conversion method
* 
* Parameters:
* ellipsoid axis dimensions (a & b) in meters
* central meridian scale factor scl
* k3 - {Float}
* k4 - {Float}
* 
* Returns:
* {Float}
*/
TMProjection.os_arc = function (a, b, scl, k3, k4) {

    var ety = (a - b) / (a + b); /* ellipticity */

    var j3 = (((ety + 1.0) * ety * 5.0 /
              4.0 + 1.0) *
                ety + 1.0) * k3;
    var j4 = ((21.0 * ety / 8.0 + 3.0) *
              ety + 3.0) *
              ety * Math.sin(k3) * Math.cos(k4);
    var j5 = ety * (ety + ety * ety) *
             Math.sin(2.0 * k3) * Math.cos(2.0 * k4) * 15.0 / 8.0;
    var j6 = ety * ety * ety * Math.sin(3.0 * k3) *
             Math.cos(3.0 * k4) * 35.0 / 24.0;
    return (b * scl * (j3 - j4 + j5 - j6));
};

/**
* Method: LonLat_to_E_N
* Input: -
*    Latitude (PHI) and Longitude (LAM) in decimal degrees; _
*    ellipsoid axis dimensions (a & b) in meters; _
*    eastings of false origin (e0) in meters; _
*    central meridian scale factor (scl); _
* latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.
* Output:-
*   {<ENPoint>} The point converted to eastings and northings in metres
*/
TMProjection.LonLat_to_E_N = function (lon, lat, a, b, e0, n0, scl, PHI0, LAM0) {

    // Convert degrees to radians
    var lat_rad = lat * TMProjection.DEG_TO_RAD;
    var lon_rad = lon * TMProjection.DEG_TO_RAD;

    // Set up parameters for projection algorithm
    // see OS leaflet "Mercator Projection, constants formulae and
    // methods" for details

    var k3 = lat_rad - (PHI0 * TMProjection.DEG_TO_RAD);
    var k4 = lat_rad + (PHI0 * TMProjection.DEG_TO_RAD);
    var tan_k = Math.tan(lat_rad);
    var tan_k_2 = tan_k * tan_k;
    var sin_k = Math.sin(lat_rad);
    var cos_k = Math.cos(lat_rad);
    var cos_k_3 = cos_k * cos_k * cos_k;
    var cos_k_5 = cos_k * cos_k * cos_k_3;
    var e2 = 1 - (b * b) / (a * a);


    var m = TMProjection.os_arc(a, b, scl, k3, k4);

    var v = (a * scl) / Math.sqrt(1.0 - e2 * sin_k * sin_k);
    var v_3 = v * v * v;
    var v_5 = v_3 * v * v;
    var v_7 = v_5 * v * v;
    var r = v * (1.0 - e2) / (1.0 - e2 * sin_k * sin_k);
    var h2 = v / r - 1.0;

    var p = lon_rad - (LAM0 * TMProjection.DEG_TO_RAD);
    var j3 = m + n0;
    var j4 = v * sin_k * cos_k / 2.0;
    var j5 = v * sin_k * cos_k_3 * (5.0 - tan_k_2 + 9.0 * h2) / 24.0;
    var j6 = v * sin_k * cos_k_5 * ((tan_k_2 - 58.0) * tan_k_2 + 61.0) / 720.0;
    var gridPointLat = ((j6 * p * p + j5) * p * p + j4) * p * p + j3;
    var j7 = v * cos_k;
    var j8 = v * cos_k_3 * (v / r - tan_k_2) / 6.0;
    var j9 = v * cos_k_5 / 120.0;
    j9 = j9 * ((tan_k_2 - 58.0 * h2 - 18.0) * tan_k_2 + 5.0 + 14.0 * h2);

    var gridPointLon = ((j9 * p * p + j8) * p * p + j7) * p + e0;

    var gridPoint = new ENPoint(gridPointLon, gridPointLat);

    return gridPoint;
};

/*
* Some maths for Helmert Datum Shifting (via cartesian x,y,z coords)
*
* Taken from 
*   http://www.nearby.org.uk/tests/GeoTools2.js
* see
*   http://www.nearby.org.uk/tests/GeoTools2.html
*/
    //GT_Math_OL is just namespace (_OL added over GeoTools2.js) for all the nasty maths functions
function GT_Math_OL() {
}

GT_Math_OL.Lat_Long_H_to_X = function (PHI, LAM, H, a, b) {
    // Convert geodetic coords lat (PHI), long (LAM) and height (H) to cartesian X coordinate.
    // Input: - _
    //    Latitude (PHI)& Longitude (LAM) both in decimal degrees; _
    //  Ellipsoidal height (H) and ellipsoid axis dimensions (a & b) all in meters.

    // Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI = PHI * (Pi / 180);
    var RadLAM = LAM * (Pi / 180);

    // Compute eccentricity squared and nu
    var e2 = (Math.pow(a, 2) - Math.pow(b, 2)) / Math.pow(a, 2);
    var V = a / (Math.sqrt(1 - (e2 * (Math.pow(Math.sin(RadPHI), 2)))));

    // Compute X
    return (V + H) * (Math.cos(RadPHI)) * (Math.cos(RadLAM));
};

GT_Math_OL.Lat_Long_H_to_Y = function (PHI, LAM, H, a, b) {
    // Convert geodetic coords lat (PHI), long (LAM) and height (H) to cartesian Y coordinate.
    // Input: - _
    // Latitude (PHI)& Longitude (LAM) both in decimal degrees; _
    // Ellipsoidal height (H) and ellipsoid axis dimensions (a & b) all in meters.

    // Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI = PHI * (Pi / 180);
    var RadLAM = LAM * (Pi / 180);

    // Compute eccentricity squared and nu
    var e2 = (Math.pow(a, 2) - Math.pow(b, 2)) / Math.pow(a, 2);
    var V = a / (Math.sqrt(1 - (e2 * (Math.pow(Math.sin(RadPHI), 2)))));

    // Compute Y
    return (V + H) * (Math.cos(RadPHI)) * (Math.sin(RadLAM));
};

GT_Math_OL.Lat_H_to_Z = function (PHI, H, a, b) {
    // Convert geodetic coord components latitude (PHI) and height (H) to cartesian Z coordinate.
    // Input: - _
    //    Latitude (PHI) decimal degrees; _
    // Ellipsoidal height (H) and ellipsoid axis dimensions (a & b) all in meters.

    // Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI = PHI * (Pi / 180);

    // Compute eccentricity squared and nu
    var e2 = (Math.pow(a, 2) - Math.pow(b, 2)) / Math.pow(a, 2);
    var V = a / (Math.sqrt(1 - (e2 * (Math.pow(Math.sin(RadPHI), 2)))));

    // Compute X
    return ((V * (1 - e2)) + H) * (Math.sin(RadPHI));
};

GT_Math_OL.Helmert_X = function (X, Y, Z, DX, Y_Rot, Z_Rot, s) {

    // (X, Y, Z, DX, Y_Rot, Z_Rot, s)
    // Computed Helmert transformed X coordinate.
    // Input: - _
    //    cartesian XYZ coords (X,Y,Z), X translation (DX) all in meters ; _
    // Y and Z rotations in seconds of arc (Y_Rot, Z_Rot) and scale in ppm (s).

    // Convert rotations to radians and ppm scale to a factor
    var Pi = 3.14159265358979;
    var sfactor = s * 0.000001;

    var RadY_Rot = (Y_Rot / 3600) * (Pi / 180);

    var RadZ_Rot = (Z_Rot / 3600) * (Pi / 180);

    //Compute transformed X coord
    return (X + (X * sfactor) - (Y * RadZ_Rot) + (Z * RadY_Rot) + DX);
};

GT_Math_OL.Helmert_Y = function (X, Y, Z, DY, X_Rot, Z_Rot, s) {
    // (X, Y, Z, DY, X_Rot, Z_Rot, s)
    // Computed Helmert transformed Y coordinate.
    // Input: - _
    //    cartesian XYZ coords (X,Y,Z), Y translation (DY) all in meters ; _
    //  X and Z rotations in seconds of arc (X_Rot, Z_Rot) and scale in ppm (s).

    // Convert rotations to radians and ppm scale to a factor
    var Pi = 3.14159265358979;
    var sfactor = s * 0.000001;
    var RadX_Rot = (X_Rot / 3600) * (Pi / 180);
    var RadZ_Rot = (Z_Rot / 3600) * (Pi / 180);

    // Compute transformed Y coord
    return (X * RadZ_Rot) + Y + (Y * sfactor) - (Z * RadX_Rot) + DY;

};

GT_Math_OL.Helmert_Z = function (X, Y, Z, DZ, X_Rot, Y_Rot, s) {
    // (X, Y, Z, DZ, X_Rot, Y_Rot, s)
    // Computed Helmert transformed Z coordinate.
    // Input: - _
    //    cartesian XYZ coords (X,Y,Z), Z translation (DZ) all in meters ; _
    // X and Y rotations in seconds of arc (X_Rot, Y_Rot) and scale in ppm (s).
    // 
    // Convert rotations to radians and ppm scale to a factor
    var Pi = 3.14159265358979;
    var sfactor = s * 0.000001;
    var RadX_Rot = (X_Rot / 3600) * (Pi / 180);
    var RadY_Rot = (Y_Rot / 3600) * (Pi / 180);

    // Compute transformed Z coord
    return (-1.0 * X * RadY_Rot) + (Y * RadX_Rot) + Z + (Z * sfactor) + DZ;
};

GT_Math_OL.XYZ_to_Lat = function (X, Y, Z, a, b) {
    // Convert XYZ to Latitude (PHI) in Dec Degrees.
    // Input: - _
    // XYZ cartesian coords (X,Y,Z) and ellipsoid axis dimensions (a & b), all in meters.

    // THIS FUNCTION REQUIRES THE "Iterate_XYZ_to_Lat" FUNCTION
    // THIS FUNCTION IS CALLED BY THE "XYZ_to_H" FUNCTION

    var RootXYSqr = Math.sqrt(Math.pow(X, 2) + Math.pow(Y, 2));
    var e2 = (Math.pow(a, 2) - Math.pow(b, 2)) / Math.pow(a, 2);
    var PHI1 = Math.atan2(Z, (RootXYSqr * (1 - e2)));

    var PHI = GT_Math_OL.Iterate_XYZ_to_Lat(a, e2, PHI1, Z, RootXYSqr);

    var Pi = 3.14159265358979;

    return PHI * (180 / Pi);
};

GT_Math_OL.Iterate_XYZ_to_Lat = function (a, e2, PHI1, Z, RootXYSqr) {
    // Iteratively computes Latitude (PHI).
    // Input: - _
    //    ellipsoid semi major axis (a) in meters; _
    //    eta squared (e2); _
    //    estimated value for latitude (PHI1) in radians; _
    //    cartesian Z coordinate (Z) in meters; _
    // RootXYSqr computed from X & Y in meters.

    // THIS FUNCTION IS CALLED BY THE "XYZ_to_PHI" FUNCTION
    // THIS FUNCTION IS ALSO USED ON IT'S OWN IN THE _
    // "Projection and Transformation Calculations.xls" SPREADSHEET


    var V = a / (Math.sqrt(1 - (e2 * Math.pow(Math.sin(PHI1), 2))));
    var PHI2 = Math.atan2((Z + (e2 * V * (Math.sin(PHI1)))), RootXYSqr);

    while (Math.abs(PHI1 - PHI2) > 0.000000001) {
        PHI1 = PHI2;
        V = a / (Math.sqrt(1 - (e2 * Math.pow(Math.sin(PHI1), 2))));
        PHI2 = Math.atan2((Z + (e2 * V * (Math.sin(PHI1)))), RootXYSqr);
    }

    return PHI2;
};


GT_Math_OL.XYZ_to_Long = function (X, Y) {
    // Convert XYZ to Longitude (LAM) in Dec Degrees.
    // Input: - _
    // X and Y cartesian coords in meters.

    var Pi = 3.14159265358979;
    return Math.atan2(Y, X) * (180 / Pi);
};



