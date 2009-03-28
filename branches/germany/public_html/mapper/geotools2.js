/**
 * GeoTools javascript coordinate transformations
 * http://files.dixo.net/geotools.html
 *
 * This file copyright (c)2005 Paul Dixon (paul@elphin.com)
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

 * --------------------------------------------------------------------------- 
 * 
 * Credits

 *

 * The algorithm used by the script for WGS84-OSGB36 conversions is derived 
 * from an OSGB spreadsheet (www.gps.gov.uk) with permission. This has been
 * adapted into PHP by Ian Harris, and Irish added by Barry Hunter. Conversion
 * accuracy is in the order of 7m for 90% of Great Britain, and should be 
 * be similar to the conversion made by a typical GPSr
 *

 * See accompanying documentation for more information
 * http://www.nearby.org.uk/tests/GeoTools2.html
 */
 
 
 
 
 
 
 
 
/*****************************************************************************
*
* GT_OSGB holds OSGB grid coordinates
*
*****************************************************************************/

function GT_OSGB()
{
	this.northings=0;
	this.eastings=0;
	this.status="Undefined";
}

GT_OSGB.prefixes = new Array (
	new Array("SV","SW","SX","SY","SZ","TV","TW"),
	new Array("SQ","SR","SS","ST","SU","TQ","TR"),
	new Array("SL","SM","SN","SO","SP","TL","TM"),
	new Array("SF","SG","SH","SJ","SK","TF","TG"),
	new Array("SA","SB","SC","SD","SE","TA","TB"),
	new Array("NV","NW","NX","NY","NZ","OV","OW"),
	new Array("NQ","NR","NS","NT","NU","OQ","OR"),
	new Array("NL","NM","NN","NO","NP","OL","OM"),
	new Array("NF","NG","NH","NJ","NK","OF","OG"),
	new Array("NA","NB","NC","ND","NE","OA","OB"),
	new Array("HV","HW","HX","HY","HZ","JV","JW"),
	new Array("HQ","HR","HS","HT","HU","JQ","JR"),
	new Array("HL","HM","HN","HO","HP","JL","JM"));
			

GT_OSGB.prototype.setGridCoordinates = function(eastings,northings)
{
	this.northings=northings;
	this.eastings=eastings;
	this.status="OK";
}

GT_OSGB.prototype.setError = function(msg)
{
	this.status=msg;
}

GT_OSGB.prototype._zeropad = function(num, len)
{
	var str=new String(num);
	while (str.length<len)
	{
		str='0'+str;
	}
	return str;
}

GT_OSGB.prototype.getGridRef = function(precision)
{
	
	

	if (precision<0)
		precision=0;
	if (precision>5)
		precision=5;
		
	var e="";

	var n="";
	if (precision>0)
	{
		var y=Math.floor(this.northings/100000);
		var x=Math.floor(this.eastings/100000);


		var e=Math.floor(this.eastings%100000);
		var n=Math.floor(this.northings%100000);


		var div=(5-precision);
		e=Math.floor(e / Math.pow(10, div));
		n=Math.floor(n / Math.pow(10, div));
	}
	
	var prefix=GT_OSGB.prefixes[y][x];
	
    return prefix+" "+this._zeropad(e, precision)+" "+this._zeropad(n, precision);
}

GT_OSGB.prototype.parseGridRef = function(landranger)
{
	var ok=false;

	
	this.northings=0;
	this.eastings=0;
	
	var precision;

	for (precision=5; precision>=1; precision--)
	{
		var pattern = new RegExp("^([A-Z]{2})\\s*(\\d{"+precision+"})\\s*(\\d{"+precision+"})$", "i")
		var gridRef = landranger.match(pattern);
		if (gridRef)
		{
			var gridSheet = gridRef[1];
			var gridEast=0;
			var gridNorth=0;
			
			//5x1 4x10 3x100 2x1000 1x10000 
			if (precision>0)
			{
				var mult=Math.pow(10, 5-precision);
				gridEast=parseInt(gridRef[2],10) * mult;
				gridNorth=parseInt(gridRef[3],10) * mult;
			}
			
			var x,y;
			search: for(y=0; y<GT_OSGB.prefixes.length; y++) 
			{
				for(x=0; x<GT_OSGB.prefixes[y].length; x++)
					if (GT_OSGB.prefixes[y][x] == gridSheet) {
						this.eastings = (x * 100000)+gridEast;
						this.northings = (y * 100000)+gridNorth;
						ok=true;
						break search;
					}
			
			}
		
		}
	}

	

	return ok;
}


GT_OSGB.prototype.getWGS84 = function()
{
	
	var height = 0;

	var lat1 = GT_Math.E_N_to_Lat (this.eastings,this.northings,6377563.396,6356256.910,400000,-100000,0.999601272,49.00000,-2.00000);
	var lon1 = GT_Math.E_N_to_Long(this.eastings,this.northings,6377563.396,6356256.910,400000,-100000,0.999601272,49.00000,-2.00000);

	var x1 = GT_Math.Lat_Long_H_to_X(lat1,lon1,height,6377563.396,6356256.910);
	var y1 = GT_Math.Lat_Long_H_to_Y(lat1,lon1,height,6377563.396,6356256.910);
	var z1 = GT_Math.Lat_H_to_Z     (lat1,      height,6377563.396,6356256.910);

	var x2 = GT_Math.Helmert_X(x1,y1,z1,446.448 ,0.2470,0.8421,-20.4894);
	var y2 = GT_Math.Helmert_Y(x1,y1,z1,-125.157,0.1502,0.8421,-20.4894);
	var z2 = GT_Math.Helmert_Z(x1,y1,z1,542.060 ,0.1502,0.2470,-20.4894);

	var latitude = GT_Math.XYZ_to_Lat(x2,y2,z2,6378137.000,6356752.313);
	var longitude = GT_Math.XYZ_to_Long(x2,y2);

	var wgs84=new GT_WGS84();
	wgs84.setDegrees(latitude, longitude);
	return wgs84;
}

/*****************************************************************************
*
* GT_German32 holds German grid coordinates (Zone 32)
*
*****************************************************************************/

function GT_German32()
{
	this.northings=0;
	this.eastings=0;
	this.status="Undefined";
}

GT_German32.prefixes = new Array (
	new Array("TKT", "UKU", "UKV", "UKA", "UKB", "UKC", "UKD", "UKE", "UKF", "UKG"),
	new Array("TLT", "ULU", "ULV", "ULA", "ULB", "ULC", "ULD", "ULE", "ULF", "ULG"),
	new Array("TMT", "UMU", "UMV", "UMA", "UMB", "UMC", "UMD", "UME", "UMF", "UMG"),
	new Array("TNT", "UNU", "UNV", "UNA", "UNB", "UNC", "UND", "UNE", "UNF", "UNG"),
	new Array("TPT", "UPU", "UPV", "UPA", "UPB", "UPC", "UPD", "UPE", "UPF", "UPG"),
	new Array("TQT", "UQU", "UQV", "UQA", "UQB", "UQC", "UQD", "UQE", "UQF", "UQG"));

GT_German32.prototype.setGridCoordinates = function(eastings,northings)
{
	this.northings=northings;
	this.eastings=eastings;
	this.status="OK";
}

GT_German32.prototype.setError = function(msg)
{
	this.status=msg;
}

GT_German32.prototype._zeropad = function(num, len)
{
	var str=new String(num);
	while (str.length<len)
	{
		str='0'+str;
	}
	return str;
}

GT_German32.prototype.getGridRef = function(precision)
{
	
	

	if (precision<0)
		precision=0;
	if (precision>5)
		precision=5;
		
	var e="";

	var n="";
	if (precision>0)
	{
		//FIXME round _before_ modulo ...
		var y=Math.floor(this.northings/100000);
		var x=Math.floor(this.eastings/100000);
		y -= 52;
		x -= 2;


		var e=Math.round(this.eastings%100000);
		var n=Math.round(this.northings%100000);


		var div=(5-precision);
		e=Math.round(e / Math.pow(10, div));
		n=Math.round(n / Math.pow(10, div));
	} //FIXME else: x, y =?
	
	var prefix=GT_German32.prefixes[x][y];
	
    return prefix+" "+this._zeropad(e, precision)+" "+this._zeropad(n, precision);
}

GT_German32.prototype.parseGridRef = function(landranger)
{
	var ok=false;

	
	this.northings=0;
	this.eastings=0;
	
	var precision;

	for (precision=5; precision>=1; precision--)
	{
		var pattern = new RegExp("^([A-Z]{3})\\s*(\\d{"+precision+"})\\s*(\\d{"+precision+"})$", "i")
		var gridRef = landranger.match(pattern);
		if (gridRef)
		{
			var gridSheet = gridRef[1];
			var gridEast=0;
			var gridNorth=0;
			
			//5x1 4x10 3x100 2x1000 1x10000 
			if (precision>0)
			{
				var mult=Math.pow(10, 5-precision);
				gridEast=parseInt(gridRef[2],10) * mult;
				gridNorth=parseInt(gridRef[3],10) * mult;
			}
			
			var x,y;
			search: for(x=0; x<GT_German32.prefixes.length; x++) 
			{
				for(y=0; y<GT_German32.prefixes[x].length; y++)
					if (GT_German32.prefixes[x][y] == gridSheet) {
						this.eastings = ((x + 2) * 100000)+gridEast;
						this.northings = ((y + 52) * 100000)+gridNorth;
						ok=true;
						break search;
					}
			
			}
		
		}
	}

	

	return ok;
}


GT_German32.prototype.getWGS84 = function(uselevel2)
{
	var coord = GT_Math.utm_to_wgs84(this.eastings, this.northings, 32);

	var wgs84=new GT_WGS84();
	wgs84.setDegrees(coord[0],coord[1]);
	return wgs84;
}

/*****************************************************************************
*
* GT_German33 holds German grid coordinates (Zone 33)
*
*****************************************************************************/

function GT_German33()
{
	this.northings=0;
	this.eastings=0;
	this.status="Undefined";
}

GT_German33.prefixes = new Array (
	new Array("TTN", "UTP", "UTQ", "UTR", "UTS", "UTT", "UTU", "UTV", "UTA", "UTB"),
	new Array("TUN", "UUP", "UUQ", "UUR", "UUS", "UUT", "UUU", "UUV", "UUA", "UUB"),
	new Array("TVN", "UVP", "UVQ", "UVR", "UVS", "UVT", "UVU", "UVV", "UVA", "UVB"),
	new Array("TWN", "UWP", "UWQ", "UWR", "UWS", "UWT", "UWU", "UWV", "UWA", "UWB"));

GT_German33.prototype.setGridCoordinates = function(eastings,northings)
{
	this.northings=northings;
	this.eastings=eastings;
	this.status="OK";
}

GT_German33.prototype.setError = function(msg)
{
	this.status=msg;
}

GT_German33.prototype._zeropad = function(num, len)
{
	var str=new String(num);
	while (str.length<len)
	{
		str='0'+str;
	}
	return str;
}

GT_German33.prototype.getGridRef = function(precision)
{
	
	

	if (precision<0)
		precision=0;
	if (precision>5)
		precision=5;
		
	var e="";

	var n="";
	if (precision>0)
	{
		//FIXME round _before_ modulo ...
		var y=Math.floor(this.northings/100000);
		var x=Math.floor(this.eastings/100000);
		y -= 52;
		x -= 2;


		var e=Math.round(this.eastings%100000);
		var n=Math.round(this.northings%100000);


		var div=(5-precision);
		e=Math.round(e / Math.pow(10, div));
		n=Math.round(n / Math.pow(10, div));
	} //FIXME else: x, y =?
	
	var prefix=GT_German33.prefixes[x][y];
	
    return prefix+" "+this._zeropad(e, precision)+" "+this._zeropad(n, precision);
}

GT_German33.prototype.parseGridRef = function(landranger)
{
	var ok=false;

	
	this.northings=0;
	this.eastings=0;
	
	var precision;

	for (precision=5; precision>=1; precision--)
	{
		var pattern = new RegExp("^([A-Z]{3})\\s*(\\d{"+precision+"})\\s*(\\d{"+precision+"})$", "i")
		var gridRef = landranger.match(pattern);
		if (gridRef)
		{
			var gridSheet = gridRef[1];
			var gridEast=0;
			var gridNorth=0;
			
			//5x1 4x10 3x100 2x1000 1x10000 
			if (precision>0)
			{
				var mult=Math.pow(10, 5-precision);
				gridEast=parseInt(gridRef[2],10) * mult;
				gridNorth=parseInt(gridRef[3],10) * mult;
			}
			
			var x,y;
			search: for(x=0; x<GT_German33.prefixes.length; x++) 
			{
				for(y=0; y<GT_German33.prefixes[x].length; y++)
					if (GT_German33.prefixes[x][y] == gridSheet) {
						this.eastings = ((x + 2) * 100000)+gridEast;
						this.northings = ((y + 52) * 100000)+gridNorth;
						ok=true;
						break search;
					}
			
			}
		
		}
	}

	

	return ok;
}


GT_German33.prototype.getWGS84 = function(uselevel2)
{
	var coord = GT_Math.utm_to_wgs84(this.eastings, this.northings, 33);

	var wgs84=new GT_WGS84();
	wgs84.setDegrees(coord[0],coord[1]);
	return wgs84;
}

/*****************************************************************************
*
* GT_German31 holds German grid coordinates (Zone 31)
*
*****************************************************************************/

function GT_German31()
{
	this.northings=0;
	this.eastings=0;
	this.status="Undefined";
}

GT_German31.prefixes = new Array (
	new Array("TFN", "UFP", "UFQ", "UFR", "UFS", "UFT", "UFU", "UFV", "UFA", "UFB"),
	new Array("TGN", "UGP", "UGQ", "UGR", "UGS", "UGT", "UGU", "UGV", "UGA", "UGB"));

GT_German31.prototype.setGridCoordinates = function(eastings,northings)
{
	this.northings=northings;
	this.eastings=eastings;
	this.status="OK";
}

GT_German31.prototype.setError = function(msg)
{
	this.status=msg;
}

GT_German31.prototype._zeropad = function(num, len)
{
	var str=new String(num);
	while (str.length<len)
	{
		str='0'+str;
	}
	return str;
}

GT_German31.prototype.getGridRef = function(precision)
{
	
	

	if (precision<0)
		precision=0;
	if (precision>5)
		precision=5;
		
	var e="";

	var n="";
	if (precision>0)
	{
		//FIXME round _before_ modulo ...
		var y=Math.floor(this.northings/100000);
		var x=Math.floor(this.eastings/100000);
		y -= 52;
		x -= 6;


		var e=Math.round(this.eastings%100000);
		var n=Math.round(this.northings%100000);


		var div=(5-precision);
		e=Math.round(e / Math.pow(10, div));
		n=Math.round(n / Math.pow(10, div));
	} //FIXME else: x, y =?
	
	var prefix=GT_German31.prefixes[x][y];
	
    return prefix+" "+this._zeropad(e, precision)+" "+this._zeropad(n, precision);
}

GT_German31.prototype.parseGridRef = function(landranger)
{
	var ok=false;

	
	this.northings=0;
	this.eastings=0;
	
	var precision;

	for (precision=5; precision>=1; precision--)
	{
		var pattern = new RegExp("^([A-Z]{3})\\s*(\\d{"+precision+"})\\s*(\\d{"+precision+"})$", "i")
		var gridRef = landranger.match(pattern);
		if (gridRef)
		{
			var gridSheet = gridRef[1];
			var gridEast=0;
			var gridNorth=0;
			
			//5x1 4x10 3x100 2x1000 1x10000 
			if (precision>0)
			{
				var mult=Math.pow(10, 5-precision);
				gridEast=parseInt(gridRef[2],10) * mult;
				gridNorth=parseInt(gridRef[3],10) * mult;
			}
			
			var x,y;
			search: for(x=0; x<GT_German31.prefixes.length; x++) 
			{
				for(y=0; y<GT_German31.prefixes[x].length; y++)
					if (GT_German31.prefixes[x][y] == gridSheet) {
						this.eastings = ((x + 6) * 100000)+gridEast;
						this.northings = ((y + 52) * 100000)+gridNorth;
						ok=true;
						break search;
					}
			
			}
		
		}
	}

	

	return ok;
}


GT_German31.prototype.getWGS84 = function(uselevel2)
{
	var coord = GT_Math.utm_to_wgs84(this.eastings, this.northings, 31);

	var wgs84=new GT_WGS84();
	wgs84.setDegrees(coord[0],coord[1]);
	return wgs84;
}

/*****************************************************************************
*
* GT_OSGB holds Irish grid coordinates
*
*****************************************************************************/

function GT_Irish()
{
	this.northings=0;
	this.eastings=0;
	this.status="Undefined";
}

GT_Irish.prefixes = new Array (
	new Array("V", "Q", "L", "F", "A"),
	new Array("W", "R", "M", "G", "B"),
	new Array("X", "S", "N", "H", "C"),
	new Array("Y", "T", "O", "J", "D"),
	new Array("Z", "U", "P", "K", "E"));

GT_Irish.prototype.setGridCoordinates = function(eastings,northings)
{
	this.northings=northings;
	this.eastings=eastings;
	this.status="OK";
}

GT_Irish.prototype.setError = function(msg)
{
	this.status=msg;
}

GT_Irish.prototype._zeropad = function(num, len)
{
	var str=new String(num);
	while (str.length<len)
	{
		str='0'+str;
	}
	return str;
}

GT_Irish.prototype.getGridRef = function(precision)
{
	
	

	if (precision<0)
		precision=0;
	if (precision>5)
		precision=5;
		
	var e="";

	var n="";
	if (precision>0)
	{
		var y=Math.floor(this.northings/100000);
		var x=Math.floor(this.eastings/100000);


		var e=Math.floor(this.eastings%100000);
		var n=Math.floor(this.northings%100000);


		var div=(5-precision);
		e=Math.floor(e / Math.pow(10, div));
		n=Math.floor(n / Math.pow(10, div));
	}
	
	var prefix=GT_Irish.prefixes[x][y];
	
    return prefix+" "+this._zeropad(e, precision)+" "+this._zeropad(n, precision);
}

GT_Irish.prototype.parseGridRef = function(landranger)
{
	var ok=false;

	
	this.northings=0;
	this.eastings=0;
	
	var precision;

	for (precision=5; precision>=1; precision--)
	{
		var pattern = new RegExp("^([A-Z]{1})\\s*(\\d{"+precision+"})\\s*(\\d{"+precision+"})$", "i")
		var gridRef = landranger.match(pattern);
		if (gridRef)
		{
			var gridSheet = gridRef[1];
			var gridEast=0;
			var gridNorth=0;
			
			//5x1 4x10 3x100 2x1000 1x10000 
			if (precision>0)
			{
				var mult=Math.pow(10, 5-precision);
				gridEast=parseInt(gridRef[2],10) * mult;
				gridNorth=parseInt(gridRef[3],10) * mult;
			}
			
			var x,y;
			search: for(x=0; x<GT_Irish.prefixes.length; x++) 
			{
				for(y=0; y<GT_Irish.prefixes[x].length; y++)
					if (GT_Irish.prefixes[x][y] == gridSheet) {
						this.eastings = (x * 100000)+gridEast;
						this.northings = (y * 100000)+gridNorth;
						ok=true;
						break search;
					}
			
			}
		
		}
	}

	

	return ok;
}


GT_Irish.prototype.getWGS84 = function(uselevel2)
{

	var height = 0;

	if (uselevel2) {
		e = this.eastings;
		n = this.northings;
	} else {
		//fixed datum shift correction (instead of fancy hermert translation below!)
		e = this.eastings-49;
		n = this.northings+23.4;
	}

	var lat1 = GT_Math.E_N_to_Lat (e,n,6377340.189,6356034.447,200000,250000,1.000035,53.50000,-8.00000);
	var lon1 = GT_Math.E_N_to_Long(e,n,6377340.189,6356034.447,200000,250000,1.000035,53.50000,-8.00000);

	var wgs84=new GT_WGS84();
	if (uselevel2) {
		var x1 = GT_Math.Lat_Long_H_to_X(lat1,lon1,height,6377340.189,6356034.447);
		var y1 = GT_Math.Lat_Long_H_to_Y(lat1,lon1,height,6377340.189,6356034.447);
		var z1 = GT_Math.Lat_H_to_Z     (lat1,     height,6377340.189,6356034.447);

		var x2 = GT_Math.Helmert_X(x1,y1,z1, 482.53 ,0.214,0.631,8.15);
		var y2 = GT_Math.Helmert_Y(x1,y1,z1,-130.596,1.042,0.631,8.15);
		var z2 = GT_Math.Helmert_Z(x1,y1,z1, 564.557,1.042,0.214,8.15);

		var latitude = GT_Math.XYZ_to_Lat(x2,y2,z2,6378137.000,6356752.313);
		var longitude = GT_Math.XYZ_to_Long(x2,y2);
		wgs84.setDegrees(latitude, longitude);
	} else {
		wgs84.setDegrees(lat1,lon1);
	}
	return wgs84;
}

/*****************************************************************************
*
* GT_WGS84 holds WGS84 latitude and longitude
*
*****************************************************************************/

function GT_WGS84()
{
	this.latitude=0;
	this.longitude=0;
}

GT_WGS84.prototype.setDegrees = function(latitude,longitude)
{
	this.latitude=latitude;
	this.longitude=longitude;
}

GT_WGS84.prototype.parseString = function(text)
{
	var ok=false;

	var str=new String(text);

	//N 51° 53.947 W 000° 10.018

	var pattern = /([ns])\s*(\d+)[°\s]+(\d+\.\d+)\s+([we])\s*(\d+)[°\s]+(\d+\.\d+)/i;
	var matches=str.match(pattern);
	if (matches)
	{
		ok=true;
		var latsign=(matches[1]=='s' || matches[1]=='S')?-1:1;
		var longsign=(matches[4]=='w' || matches[4]=='W')?-1:1;
		
		var d1=parseFloat(matches[2]);
		var m1=parseFloat(matches[3]);
		var d2=parseFloat(matches[5]);
		var m2=parseFloat(matches[6]);
		
		this.latitude=latsign * (d1 + (m1/60.0));
		this.longitude=longsign * (d2 + (m2/60.0));
		
		
	}
	
	return ok;
}



GT_WGS84.prototype.isGreatBritain = function()
{
	return this.latitude > 49 &&
		this.latitude < 62 &&
		this.longitude > -9.5 &&
		this.longitude < 2.3;
}

GT_WGS84.prototype.isGermany32 = function()
{
	return this.latitude > 47 &&
		this.latitude < 56 &&
		this.longitude >= 6 &&
		this.longitude <= 12; //FIXME
}

GT_WGS84.prototype.isGermany33 = function()
{
	return this.latitude > 47 &&
		this.latitude < 56 &&
		this.longitude > 12 &&
		this.longitude < 16; //FIXME
}

GT_WGS84.prototype.isGermany31 = function()
{
	return this.latitude > 47 &&
		this.latitude < 56 &&
		this.longitude > 4 &&
		this.longitude < 6; //FIXME
}

GT_WGS84.prototype.isIreland = function()
{
	return this.latitude > 51.2 &&
		this.latitude < 55.73 &&
		this.longitude > -12.2 &&
		this.longitude < -4.8;
}

GT_WGS84.prototype.getIrish = function(uselevel2)
{
	var irish=new GT_Irish();
	if (this.isIreland())
	{
		var height = 0;

		if (uselevel2) {
			var x1 = GT_Math.Lat_Long_H_to_X(this.latitude,this.longitude,height,6378137.00,6356752.313);
			var y1 = GT_Math.Lat_Long_H_to_Y(this.latitude,this.longitude,height,6378137.00,6356752.313);
			var z1 = GT_Math.Lat_H_to_Z     (this.latitude,height,6378137.00,6356752.313);

			var x2 = GT_Math.Helmert_X(x1,y1,z1,-482.53 ,-0.214,-0.631,-8.15);
			var y2 = GT_Math.Helmert_Y(x1,y1,z1, 130.596,-1.042,-0.631,-8.15);
			var z2 = GT_Math.Helmert_Z(x1,y1,z1,-564.557,-1.042,-0.214,-8.15);

			var latitude2  = GT_Math.XYZ_to_Lat (x2,y2,z2,6377340.189,6356034.447);
			var longitude2 = GT_Math.XYZ_to_Long(x2,y2);
		} else {
			var latitude2  = this.latitude;
			var longitude2 = this.longitude;
		}

		var e = GT_Math.Lat_Long_to_East (latitude2,longitude2,6377340.189,6356034.447, 200000,1.000035,53.50000,-8.00000);
		var n = GT_Math.Lat_Long_to_North(latitude2,longitude2,6377340.189,6356034.447, 200000,250000,1.000035,53.50000,-8.00000);

		if (!uselevel2) {
			//Level 1 Transformation - 95% of points within 2 metres
			//fixed datum shift correction (instead of fancy hermert translation above!)
			//source http://www.osni.gov.uk/downloads/Making%20maps%20GPS%20compatible.pdf
			e=e+49;
			n=n-23.4;
		}

		irish.setGridCoordinates(Math.round(e), Math.round(n));
	}
	else 
	{
		irish.setError("Coordinate not within Ireland");
	}

	return irish;
}

GT_WGS84.prototype.getGerman32 = function(uselevel2)
{
	var german=new GT_German32();
	if (this.isGermany32())
	{
		var coord = GT_Math.wgs84_to_utm(this.latitude, this.longitude, 32);
		german.setGridCoordinates(Math.round(coord[0]), Math.round(coord[1]));
	}
	else 
	{
		german.setError("Coordinate not within Germany (Zone 32)");
	}

	return german;
}

GT_WGS84.prototype.getGerman33 = function(uselevel2)
{
	var german=new GT_German33();
	if (this.isGermany33())
	{
		var coord = GT_Math.wgs84_to_utm(this.latitude, this.longitude, 33);
		german.setGridCoordinates(Math.round(coord[0]), Math.round(coord[1]));
	}
	else 
	{
		german.setError("Coordinate not within Germany (Zone 33)");
	}

	return german;
}

GT_WGS84.prototype.getGerman31 = function(uselevel2)
{
	var german=new GT_German31();
	if (this.isGermany31())
	{
		var coord = GT_Math.wgs84_to_utm(this.latitude, this.longitude, 31);
		german.setGridCoordinates(Math.round(coord[0]), Math.round(coord[1]));
	}
	else 
	{
		german.setError("Coordinate not within Germany (Zone 31)");
	}

	return german;
}

GT_WGS84.prototype.getOSGB = function(uselevel2)
{
	var osgb=new GT_OSGB();
	if (this.isGreatBritain())
	{
		var height = 0;
		
		var x1 = GT_Math.Lat_Long_H_to_X(this.latitude,this.longitude,height,6378137.00,6356752.313);
		var y1 = GT_Math.Lat_Long_H_to_Y(this.latitude,this.longitude,height,6378137.00,6356752.313);
		var z1 = GT_Math.Lat_H_to_Z     (this.latitude,height,6378137.00,6356752.313);

		var x2 = GT_Math.Helmert_X(x1,y1,z1,-446.448,-0.2470,-0.8421,20.4894);
		var y2 = GT_Math.Helmert_Y(x1,y1,z1, 125.157,-0.1502,-0.8421,20.4894);
		var z2 = GT_Math.Helmert_Z(x1,y1,z1,-542.060,-0.1502,-0.2470,20.4894);

		var latitude2  = GT_Math.XYZ_to_Lat (x2,y2,z2,6377563.396,6356256.910);
		var longitude2 = GT_Math.XYZ_to_Long(x2,y2);

		var e = GT_Math.Lat_Long_to_East (latitude2,longitude2,6377563.396,6356256.910,400000,0.999601272,49.00000,-2.00000);
		var n = GT_Math.Lat_Long_to_North(latitude2,longitude2,6377563.396,6356256.910,400000,-100000,0.999601272,49.00000,-2.00000);

		osgb.setGridCoordinates(Math.round(e), Math.round(n));
	}
	else
	{
		osgb.setError("Coordinate not within Great Britain");
	}

	return osgb;
}




/*****************************************************************************
*
* GT_Math is a collection of static methods doing all the nasty sums
*
*****************************************************************************/

//GT_Math is just namespace for all the nasty maths functions
function GT_Math()
{
}

GT_Math.pow2 = function(x) {
	return x*x;
}

GT_Math.pow3 = function(x) {
	return x*x*x;
}

GT_Math.deg2rad = function(x) {
	return x*Math.PI/180.;
}

GT_Math.rad2deg = function(x) {
	return x/Math.PI*180.;
}

GT_Math.sinh = function(x) {
	return (Math.exp(x)-Math.exp(-x))*0.5;
}

GT_Math.asinh = function(x) {
	return Math.log(Math.sqrt(x*x+1)+x);
}

GT_Math.utm_to_wgs84 = function(east,north,zone) {
	r1=6378137.;
	r2=6356752.31425;

	re=GT_Math.pow2(r1)/r2;
	e1=1-GT_Math.pow2(r2/r1);
	e2=1-r2/r1;
	e3=GT_Math.pow2(r1/r2)-1;

	m0=1 + 3./4*e1 + 45./64*GT_Math.pow2(e1) + 175./256*GT_Math.pow3(e1);
	//m1=    3./4*e1 + 15./16*GT_Math.pow2(e1) + 525./512*GT_Math.pow3(e1);
	//m2=               15./64*GT_Math.pow2(e1) + 105./256*GT_Math.pow3(e1);
	//m3=                                   35./512*GT_Math.pow3(e1);

	b0=r1*(1-e1)*m0;
	//b1=r1*(1-e1)*m1/2;
	//b2=r1*(1-e1)*m2/4;
	//b3=r1*(1-e1)*m3/6;

	at=e2/(2-e2);
	a1=3./2*(at-9./16*GT_Math.pow3(at));
	a2=21./16*GT_Math.pow2(at);
	a3=151./96*GT_Math.pow3(at);

	//our origin: 200, 5200
	//east=east+200000;
	//north=north+5200000;

	//trigger_error("E/N: " . east . "/" . north, E_USER_NOTICE);

	y=(east-500000)/0.9996;
	x=north/0.9996;

	phi=x/b0;
	bf=phi+a1*Math.sin(2*phi)+a2*Math.sin(4*phi)+a3*Math.sin(6*phi);
	lmbda=y/re;
	vf=Math.sqrt(1+e3*GT_Math.pow2(Math.cos(bf)));
	del=Math.atan(vf/(Math.cos(bf))*GT_Math.sinh(lmbda));
	b=Math.atan(Math.tan(bf)*Math.cos(del*vf));

	lng=zone*6-183+GT_Math.rad2deg(del);
	lat=GT_Math.rad2deg(b);

	//trigger_error("->L/L: " . lat . "/" . lng, E_USER_NOTICE);

	return new Array(lat,lng);
}

GT_Math.wgs84_to_utm = function(lat,lng,zone) {
	r1=6378137.;
	r2=6356752.31425;

	re=GT_Math.pow2(r1)/r2;
	e1=1-GT_Math.pow2(r2/r1);
	//e2=1-r2/r1;
	e3=GT_Math.pow2(r1/r2)-1;

	m0=1 + 3./4*e1 + 45./64*GT_Math.pow2(e1) + 175./256*GT_Math.pow3(e1);
	m1=    3./4*e1 + 15./16*GT_Math.pow2(e1) + 525./512*GT_Math.pow3(e1);
	m2=               15./64*GT_Math.pow2(e1) + 105./256*GT_Math.pow3(e1);
	m3=                                   35./512*GT_Math.pow3(e1);

	b0=r1*(1-e1)*m0;
	b1=r1*(1-e1)*m1/2;
	b2=r1*(1-e1)*m2/4;
	b3=r1*(1-e1)*m3/6;

	//at=e2/(2-e2);
	//a1=3./2*(at-9./16*GT_Math.pow3(at));
	//a2=21./16*GT_Math.pow2(at);
	//a3=151./96*GT_Math.pow3(at);

	//zone2=Math.floor(lng/6.)+31; //FIXME: Math.floor/truncate? FIXME use calculated zone?

	del=GT_Math.deg2rad(lng-zone*6+183);
	b=GT_Math.deg2rad(lat);
	v=Math.sqrt(1+e3*GT_Math.pow2(Math.cos(b)));
	bf=Math.atan(Math.tan(b)/(Math.cos(del*v)));
	vf=Math.sqrt(1+e3*GT_Math.pow2(Math.cos(bf)));
	
	y=re*GT_Math.asinh(Math.tan(del)/vf*Math.cos(bf));
	x=b0*bf-b1*Math.sin(2*bf)+b2*Math.sin(4*bf)-b3*Math.sin(6*bf);

	east=0.9996*y+500000;
	north=0.9996*x; // FIXME southern hem: +10000000

	//our origin: 200, 5200
	//east=east-200000;
	//north=north-5200000;

	return new Array(east,north);
}

GT_Math.E_N_to_Lat = function(East, North, a, b, e0, n0, f0, PHI0, LAM0)
{
	//Un-project Transverse Mercator eastings and northings back to latitude.
	//Input: - _
	//eastings (East) and northings (North) in meters; _
	//ellipsoid axis dimensions (a & b) in meters; _
	//eastings (e0) and northings (n0) of false origin in meters; _
	//central meridian scale factor (f0) and _
	//latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.

	//'REQUIRES THE "Marc" AND "InitialLat" FUNCTIONS

	//Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI0 = PHI0 * (Pi / 180);
    var RadLAM0 = LAM0 * (Pi / 180);

	//Compute af0, bf0, e squared (e2), n and Et
    var af0 = a * f0;
    var bf0 = b * f0;
    var e2 = (Math.pow(af0,2) - Math.pow(bf0,2)) / Math.pow(af0,2);
    var n = (af0 - bf0) / (af0 + bf0);
    var Et = East - e0;

	//Compute initial value for latitude (PHI) in radians
    var PHId = GT_Math.InitialLat(North, n0, af0, RadPHI0, n, bf0);
    
	//Compute nu, rho and eta2 using value for PHId
    var nu = af0 / (Math.sqrt(1 - (e2 * ( Math.pow(Math.sin(PHId),2)))));
    var rho = (nu * (1 - e2)) / (1 - (e2 * Math.pow(Math.sin(PHId),2)));
    var eta2 = (nu / rho) - 1;
    
	//Compute Latitude
    var VII = (Math.tan(PHId)) / (2 * rho * nu);
    var VIII = ((Math.tan(PHId)) / (24 * rho * Math.pow(nu,3))) * (5 + (3 * (Math.pow(Math.tan(PHId),2))) + eta2 - (9 * eta2 * (Math.pow(Math.tan(PHId),2))));
    var IX = ((Math.tan(PHId)) / (720 * rho * Math.pow(nu,5))) * (61 + (90 * ((Math.tan(PHId)) ^ 2)) + (45 * (Math.pow(Math.tan(PHId),4))));
    
    var E_N_to_Lat = (180 / Pi) * (PHId - (Math.pow(Et,2) * VII) + (Math.pow(Et,4) * VIII) - ((Et ^ 6) * IX));
	
	return (E_N_to_Lat);
}

GT_Math.E_N_to_Long = function(East, North, a, b, e0, n0, f0, PHI0, LAM0)
{
	//Un-project Transverse Mercator eastings and northings back to longitude.
	//Input: - _
	//eastings (East) and northings (North) in meters; _
	//ellipsoid axis dimensions (a & b) in meters; _
	//eastings (e0) and northings (n0) of false origin in meters; _
	//central meridian scale factor (f0) and _
	//latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.

	//REQUIRES THE "Marc" AND "InitialLat" FUNCTIONS

	//Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI0 = PHI0 * (Pi / 180);
    var RadLAM0 = LAM0 * (Pi / 180);

	//Compute af0, bf0, e squared (e2), n and Et
    var af0 = a * f0;
    var bf0 = b * f0;
    var e2 = (Math.pow(af0,2) - Math.pow(bf0,2)) / Math.pow(af0,2);
    var n = (af0 - bf0) / (af0 + bf0);
    var Et = East - e0;

	//Compute initial value for latitude (PHI) in radians
    var PHId = GT_Math.InitialLat(North, n0, af0, RadPHI0, n, bf0);
    
	//Compute nu, rho and eta2 using value for PHId
   	var nu = af0 / (Math.sqrt(1 - (e2 * (Math.pow(Math.sin(PHId),2)))));
    var rho = (nu * (1 - e2)) / (1 - (e2 * Math.pow(Math.sin(PHId),2)));
    var eta2 = (nu / rho) - 1;

	//Compute Longitude
    var X = (Math.pow(Math.cos(PHId),-1)) / nu;
    var XI = ((Math.pow(Math.cos(PHId),-1)) / (6 * Math.pow(nu,3))) * ((nu / rho) + (2 * (Math.pow(Math.tan(PHId),2))));
    var XII = ((Math.pow(Math.cos(PHId),-1)) / (120 * Math.pow(nu,5))) * (5 + (28 * (Math.pow(Math.tan(PHId),2))) + (24 * (Math.pow(Math.tan(PHId),4))));
    var XIIA = ((Math.pow(Math.cos(PHId),-1)) / (5040 * Math.pow(nu,7))) * (61 + (662 * (Math.pow(Math.tan(PHId),2))) + (1320 * (Math.pow(Math.tan(PHId),4))) + (720 * (Math.pow(Math.tan(PHId),6))));

    var E_N_to_Long = (180 / Pi) * (RadLAM0 + (Et * X) - (Math.pow(Et,3) * XI) + (Math.pow(Et,5) * XII) - (Math.pow(Et,7) * XIIA));
	
	return E_N_to_Long;
}

GT_Math.InitialLat = function(North, n0, afo, PHI0, n, bfo)
{
	//Compute initial value for Latitude (PHI) IN RADIANS.
	//Input: - _
	//northing of point (North) and northing of false origin (n0) in meters; _
	//semi major axis multiplied by central meridian scale factor (af0) in meters; _
	//latitude of false origin (PHI0) IN RADIANS; _
	//n (computed from a, b and f0) and _
	//ellipsoid semi major axis multiplied by central meridian scale factor (bf0) in meters.
 
	//REQUIRES THE "Marc" FUNCTION
	//THIS FUNCTION IS CALLED BY THE "E_N_to_Lat", "E_N_to_Long" and "E_N_to_C" FUNCTIONS
	//THIS FUNCTION IS ALSO USED ON IT'S OWN IN THE  "Projection and Transformation Calculations.xls" SPREADSHEET

	//First PHI value (PHI1)
   	var PHI1 = ((North - n0) / afo) + PHI0;
    
	//Calculate M
    var M = GT_Math.Marc(bfo, n, PHI0, PHI1);
    
	//Calculate new PHI value (PHI2)
    var PHI2 = ((North - n0 - M) / afo) + PHI1;
    
	//Iterate to get final value for InitialLat
	while (Math.abs(North - n0 - M) > 0.00001) 
	{
        PHI2 = ((North - n0 - M) / afo) + PHI1;
        M = GT_Math.Marc(bfo, n, PHI0, PHI2);
        PHI1 = PHI2;
	}    
    return PHI2;
}

GT_Math.Lat_Long_H_to_X = function(PHI, LAM, H, a, b)
{
	// Convert geodetic coords lat (PHI), long (LAM) and height (H) to cartesian X coordinate.
	// Input: - _
	//    Latitude (PHI)& Longitude (LAM) both in decimal degrees; _
	//  Ellipsoidal height (H) and ellipsoid axis dimensions (a & b) all in meters.

	// Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI = PHI * (Pi / 180);
    var RadLAM = LAM * (Pi / 180);

	// Compute eccentricity squared and nu
    var e2 = (Math.pow(a,2) - Math.pow(b,2)) / Math.pow(a,2);
    var V = a / (Math.sqrt(1 - (e2 * (  Math.pow(Math.sin(RadPHI),2)))));

	// Compute X
    return (V + H) * (Math.cos(RadPHI)) * (Math.cos(RadLAM));
}


GT_Math.Lat_Long_H_to_Y =function(PHI, LAM, H, a, b) 
{
	// Convert geodetic coords lat (PHI), long (LAM) and height (H) to cartesian Y coordinate.
	// Input: - _
	// Latitude (PHI)& Longitude (LAM) both in decimal degrees; _
	// Ellipsoidal height (H) and ellipsoid axis dimensions (a & b) all in meters.

	// Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI = PHI * (Pi / 180);
    var RadLAM = LAM * (Pi / 180);

	// Compute eccentricity squared and nu
    var e2 = (Math.pow(a,2) - Math.pow(b,2)) / Math.pow(a,2);
    var V = a / (Math.sqrt(1 - (e2 * (  Math.pow(Math.sin(RadPHI),2))) ));

	// Compute Y
    return (V + H) * (Math.cos(RadPHI)) * (Math.sin(RadLAM));
}


GT_Math.Lat_H_to_Z =function(PHI, H, a, b)
{
	// Convert geodetic coord components latitude (PHI) and height (H) to cartesian Z coordinate.
	// Input: - _
	//    Latitude (PHI) decimal degrees; _
	// Ellipsoidal height (H) and ellipsoid axis dimensions (a & b) all in meters.

	// Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI = PHI * (Pi / 180);

	// Compute eccentricity squared and nu
    var e2 = (Math.pow(a,2) - Math.pow(b,2)) / Math.pow(a,2);
    var V = a / (Math.sqrt(1 - (e2 * (  Math.pow(Math.sin(RadPHI),2)) )));

	// Compute X
    return ((V * (1 - e2)) + H) * (Math.sin(RadPHI));
}


GT_Math.Helmert_X =function(X,Y,Z,DX,Y_Rot,Z_Rot,s) 
{

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
    return  (X + (X * sfactor) - (Y * RadZ_Rot) + (Z * RadY_Rot) + DX);
}


GT_Math.Helmert_Y =function(X,Y,Z,DY,X_Rot,Z_Rot,s)
{
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

}



GT_Math.Helmert_Z =function(X, Y, Z, DZ, X_Rot, Y_Rot, s)
{
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
	return (-1 * X * RadY_Rot) + (Y * RadX_Rot) + Z + (Z * sfactor) + DZ;
} 






GT_Math.XYZ_to_Lat =function(X, Y, Z, a, b) 
{
	// Convert XYZ to Latitude (PHI) in Dec Degrees.
	// Input: - _
	// XYZ cartesian coords (X,Y,Z) and ellipsoid axis dimensions (a & b), all in meters.

	// THIS FUNCTION REQUIRES THE "Iterate_XYZ_to_Lat" FUNCTION
	// THIS FUNCTION IS CALLED BY THE "XYZ_to_H" FUNCTION

    var RootXYSqr = Math.sqrt(Math.pow(X,2) + Math.pow(Y,2));
    var e2 = (Math.pow(a,2) - Math.pow(b,2)) / Math.pow(a,2);
    var PHI1 = Math.atan2(Z , (RootXYSqr * (1 - e2)) );
    
    var PHI = GT_Math.Iterate_XYZ_to_Lat(a, e2, PHI1, Z, RootXYSqr);
    
    var Pi = 3.14159265358979;
    
    return PHI * (180 / Pi);
}


GT_Math.Iterate_XYZ_to_Lat =function(a, e2, PHI1, Z, RootXYSqr) 
{
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


    var V = a / (Math.sqrt(1 - (e2 * Math.pow(Math.sin(PHI1),2))));
    var PHI2 = Math.atan2((Z + (e2 * V * (Math.sin(PHI1)))) , RootXYSqr);
    
    while (Math.abs(PHI1 - PHI2) > 0.000000001) {
        PHI1 = PHI2;
        V = a / (Math.sqrt(1 - (e2 * Math.pow(Math.sin(PHI1),2))));
        PHI2 = Math.atan2((Z + (e2 * V * (Math.sin(PHI1)))) , RootXYSqr);
    }

    return PHI2;
}


GT_Math.XYZ_to_Long =function (X, Y) 
{
	// Convert XYZ to Longitude (LAM) in Dec Degrees.
	// Input: - _
	// X and Y cartesian coords in meters.

    var Pi = 3.14159265358979;
    return Math.atan2(Y , X) * (180 / Pi);
}

GT_Math.Marc =function (bf0, n, PHI0, PHI) 
{
	//Compute meridional arc.
	//Input: - _
	// ellipsoid semi major axis multiplied by central meridian scale factor (bf0) in meters; _
	// n (computed from a, b and f0); _
	// lat of false origin (PHI0) and initial or final latitude of point (PHI) IN RADIANS.

	//THIS FUNCTION IS CALLED BY THE - _
	// "Lat_Long_to_North" and "InitialLat" FUNCTIONS
	// THIS FUNCTION IS ALSO USED ON IT'S OWN IN THE "Projection and Transformation Calculations.xls" SPREADSHEET

		return bf0 * (((1 + n + ((5 / 4) * Math.pow(n,2)) + ((5 / 4) * Math.pow(n,3))) * (PHI - PHI0)) - (((3 * n) + (3 * Math.pow(n,2)) + ((21 / 8) * Math.pow(n,3))) * (Math.sin(PHI - PHI0)) * (Math.cos(PHI + PHI0))) + ((((15 / 8
	) * Math.pow(n,2)) + ((15 / 8) * Math.pow(n,3))) * (Math.sin(2 * (PHI - PHI0))) * (Math.cos(2 * (PHI + PHI0)))) - (((35 / 24) * Math.pow(n,3)) * (Math.sin(3 * (PHI - PHI0))) * (Math.cos(3 * (PHI + PHI0)))));
}




GT_Math.Lat_Long_to_East =function (PHI, LAM, a, b, e0, f0, PHI0, LAM0)
{
	//Project Latitude and longitude to Transverse Mercator eastings.
	//Input: - _
	//    Latitude (PHI) and Longitude (LAM) in decimal degrees; _
	//    ellipsoid axis dimensions (a & b) in meters; _
	//    eastings of false origin (e0) in meters; _
	//    central meridian scale factor (f0); _
	// latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.

	// Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI = PHI * (Pi / 180);
    var RadLAM = LAM * (Pi / 180);
    var RadPHI0 = PHI0 * (Pi / 180);
    var RadLAM0 = LAM0 * (Pi / 180);

    var af0 = a * f0;
    var bf0 = b * f0;
    var e2 = (Math.pow(af0,2) - Math.pow(bf0,2)) / Math.pow(af0,2);
    var n = (af0 - bf0) / (af0 + bf0);
    var nu = af0 / (Math.sqrt(1 - (e2 * Math.pow(Math.sin(RadPHI),2) )));
    var rho = (nu * (1 - e2)) / (1 - (e2 * Math.pow(Math.sin(RadPHI),2) ));
    var eta2 = (nu / rho) - 1;
    var p = RadLAM - RadLAM0;
    
    var IV = nu * (Math.cos(RadPHI));
    var V = (nu / 6) * ( Math.pow(Math.cos(RadPHI),3)) * ((nu / rho) - (Math.pow(Math.tan(RadPHI),2)));
    var VI = (nu / 120) * (Math.pow(Math.cos(RadPHI),5)) * (5 - (18 * (Math.pow(Math.tan(RadPHI),2))) + (Math.pow(Math.tan(RadPHI),4)) + (14 * eta2) - (58 * (Math.pow(Math.tan(RadPHI),2)) * eta2));
    
    return e0 + (p * IV) + (Math.pow(p,3) * V) + (Math.pow(p,5) * VI);
}


GT_Math.Lat_Long_to_North =function (PHI, LAM, a, b, e0, n0, f0, PHI0, LAM0) 
{
	// Project Latitude and longitude to Transverse Mercator northings
	// Input: - _
	// Latitude (PHI) and Longitude (LAM) in decimal degrees; _
	// ellipsoid axis dimensions (a & b) in meters; _
	// eastings (e0) and northings (n0) of false origin in meters; _
	// central meridian scale factor (f0); _
	// latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.

	// REQUIRES THE "Marc" FUNCTION

	// Convert angle measures to radians
    var Pi = 3.14159265358979;
    var RadPHI = PHI * (Pi / 180);
    var RadLAM = LAM * (Pi / 180);
    var RadPHI0 = PHI0 * (Pi / 180);
    var RadLAM0 = LAM0 * (Pi / 180);
    
    var af0 = a * f0;
    var bf0 = b * f0;
    var e2 = (Math.pow(af0,2) - Math.pow(bf0,2)) / Math.pow(af0,2);
    var n = (af0 - bf0) / (af0 + bf0);
    var nu = af0 / (Math.sqrt(1 - (e2 * Math.pow(Math.sin(RadPHI),2))));
    var rho = (nu * (1 - e2)) / (1 - (e2 * Math.pow(Math.sin(RadPHI),2)));
    var eta2 = (nu / rho) - 1;
    var p = RadLAM - RadLAM0;
    var M = GT_Math.Marc(bf0, n, RadPHI0, RadPHI);
    
    var I = M + n0;
    var II = (nu / 2) * (Math.sin(RadPHI)) * (Math.cos(RadPHI));
    var III = ((nu / 24) * (Math.sin(RadPHI)) * (Math.pow(Math.cos(RadPHI),3))) * (5 - (Math.pow(Math.tan(RadPHI),2)) + (9 * eta2));
    var IIIA = ((nu / 720) * (Math.sin(RadPHI)) * (Math.pow(Math.cos(RadPHI),5))) * (61 - (58 * (Math.pow(Math.tan(RadPHI),2))) + (Math.pow(Math.tan(RadPHI),4)));
    
    return I + (Math.pow(p,2) * II) + (Math.pow(p,4) * III) + (Math.pow(p,6) * IIIA);
}
