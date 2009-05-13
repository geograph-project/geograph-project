<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
 *   adapted from OSGB spreadsheet (www.gps.gov.uk)
 *    by Ian Harris 2004 (ian@teasel.org)
 *     added Irish Grid References by Barry Hunter (2005)
 *     added ITM by Barry Hunter (May 2009)
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

/**
* Perform WGS84 Lat/Long <=> OSGB36 Grid Reference Conversion
*  and WGS84 Lat/Long <=> "Irish Grid" (IG) Conversion
*  and WGS84 Lat/Long <=> "Irish Transverse Mercator" (ITM) Conversion
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/
	
class ConversionsLatLong extends Conversions
{		

# great circle distance in m
function distance ($lat1, $lon1, $lat2, $lon2) {
	return (6371000*3.1415926*sqrt(($lat2-$lat1)*($lat2-$lat1) + cos($lat2/57.29578)*cos($lat1/57.29578)*($lon2-$lon1)*($lon2-$lon1))/180);
}

/**************************
* ITM Functions
***************************/

# www.osi.ie/GetAttachment.aspx?id=8ec9f05d-d698-488e-a4f5-07227854d10b

#ITM uses GRS80 which is equivilent to WGS84 so no need for datum transformations!

function wgs84_to_itm($lat,$long) {
    
    $e = $this->Lat_Long_to_East ($lat,$long,6378137,6356752.314, 600000,       0.999820,53.50000,-8.00000);
    $n = $this->Lat_Long_to_North($lat,$long,6378137,6356752.314, 600000,750000,0.999820,53.50000,-8.00000);

    return array($e,$n);
}

function itm_to_wgs84($e,$n) {
 
    $lat = $this->E_N_to_Lat ($e,$n,6378137,6356752.314,600000,750000,0.999820,53.50000,-8.00000);
    $lon = $this->E_N_to_Long($e,$n,6378137,6356752.314,600000,750000,0.999820,53.50000,-8.00000);

    return array($lat,$lon);
}

/**************************
* Irish Functions
***************************/

#source http://www.osni.gov.uk/downloads/Making%20maps%20GPS%20compatible.pdf 
#Translations Rotations
#?X (m) +482.530 ?x (”) +1.042
#?Y (m) -130.596 ?y (”) +0.214
#?Z (m) +564.557 ?z (”) +0.631
#Scale (ppm) +8.150

#source of ellipsoid axis dimensions a,b : http://www.osni.gov.uk/technical/grid.doc


function wgs84_to_irish($lat,$long,$uselevel2 = true) {
    $height = 0;

	if ($uselevel2) {
		//Level 2 Transformation - 95% of points should fall within 40 cm
		$x1 = $this->Lat_Long_H_to_X($lat,$long,$height,6378137.00,6356752.313);
		$y1 = $this->Lat_Long_H_to_Y($lat,$long,$height,6378137.00,6356752.313);
		$z1 = $this->Lat_H_to_Z     ($lat,      $height,6378137.00,6356752.313);

		$x2 = $this->Helmert_X($x1,$y1,$z1,-482.53 ,-0.214,-0.631,-8.15);
		$y2 = $this->Helmert_Y($x1,$y1,$z1, 130.596,-1.042,-0.631,-8.15);
		$z2 = $this->Helmert_Z($x1,$y1,$z1,-564.557,-1.042,-0.214,-8.15);

		$lat  = $this->XYZ_to_Lat ($x2,$y2,$z2,6377340.189,6356034.447);
		$long = $this->XYZ_to_Long($x2,$y2);
	} 

    $e = $this->Lat_Long_to_East ($lat,$long,6377340.189,6356034.447, 200000,1.000035,53.50000,-8.00000);
    $n = $this->Lat_Long_to_North($lat,$long,6377340.189,6356034.447, 200000,250000,1.000035,53.50000,-8.00000);

	if (!$uselevel2) {
		//Level 1 Transformation - 95% of points within 2 metres
		#fixed datum shift correction (instead of fancy hermert translation above!)
		##source http://www.osni.gov.uk/downloads/Making%20maps%20GPS%20compatible.pdf
		$e=$e+49;
		$n=$n-23.4;
	}

    return array($e,$n);
}




function irish_to_wgs84($e,$n,$uselevel2 = true) {
    $height = 0;

	if (!$uselevel2) {
		#fixed datum shift correction (instead of fancy hermert translation below!)
		$e = $e-49;
		$n = $n+23.4;
	}

    $lat = $this->E_N_to_Lat ($e,$n,6377340.189,6356034.447,200000,250000,1.000035,53.50000,-8.00000);
    $lon = $this->E_N_to_Long($e,$n,6377340.189,6356034.447,200000,250000,1.000035,53.50000,-8.00000);

	if ($uselevel2) {
		$x1 = $this->Lat_Long_H_to_X($lat,$lon,$height,6377340.189,6356034.447);
		$y1 = $this->Lat_Long_H_to_Y($lat,$lon,$height,6377340.189,6356034.447);
		$z1 = $this->Lat_H_to_Z     ($lat,     $height,6377340.189,6356034.447);

		$x2 = $this->Helmert_X($x1,$y1,$z1, 482.53 ,0.214,0.631,8.15);
		$y2 = $this->Helmert_Y($x1,$y1,$z1,-130.596,1.042,0.631,8.15);
		$z2 = $this->Helmert_Z($x1,$y1,$z1, 564.557,1.042,0.214,8.15);

		$lat  = $this->XYZ_to_Lat ($x2,$y2,$z2,6378137.000,6356752.313);
		$lon  = $this->XYZ_to_Long($x2,$y2);
	} 

    return array($lat,$lon);
}

/**************************
* OSGB Functions
***************************/

		#-===-
		#ETRS89 (WGS84) to OSGB36/ODN Helmert transformation  
		#  X(m)     Y(m)     Z(m)     s(PPM)  X(sec)  Y(sec)  X(sec)  
		#-446.448 +125.157 -542.060 +20.4894 -0.1502 -0.2470 -0.8421 

function wgs84_to_osgb36($lat,$long) {
    $height = 0;

    $x1 = $this->Lat_Long_H_to_X($lat,$long,$height,6378137.00,6356752.313);
    $y1 = $this->Lat_Long_H_to_Y($lat,$long,$height,6378137.00,6356752.313);
    $z1 = $this->Lat_H_to_Z     ($lat,      $height,6378137.00,6356752.313);
    
    $x2 = $this->Helmert_X($x1,$y1,$z1,-446.448,-0.2470,-0.8421,20.4894);
    $y2 = $this->Helmert_Y($x1,$y1,$z1, 125.157,-0.1502,-0.8421,20.4894);
    $z2 = $this->Helmert_Z($x1,$y1,$z1,-542.060,-0.1502,-0.2470,20.4894);
    
    $lat2  = $this->XYZ_to_Lat ($x2,$y2,$z2,6377563.396,6356256.910);
    $long2 = $this->XYZ_to_Long($x2,$y2); 
    
    $e = $this->Lat_Long_to_East ($lat2,$long2,6377563.396,6356256.910,400000,0.999601272,49.00000,-2.00000);
    $n = $this->Lat_Long_to_North($lat2,$long2,6377563.396,6356256.910,400000,-100000,0.999601272,49.00000,-2.00000);
    
    return array($e,$n);
}

function osgb36_to_wgs84($e,$n) {
    $height = 0;

    $lat1 = $this->E_N_to_Lat ($e,$n,6377563.396,6356256.910,400000,-100000,0.999601272,49.00000,-2.00000);
    $lon1 = $this->E_N_to_Long($e,$n,6377563.396,6356256.910,400000,-100000,0.999601272,49.00000,-2.00000);
	
	$x1 = $this->Lat_Long_H_to_X($lat1,$lon1,$height,6377563.396,6356256.910);
	$y1 = $this->Lat_Long_H_to_Y($lat1,$lon1,$height,6377563.396,6356256.910);
	$z1 = $this->Lat_H_to_Z     ($lat1,      $height,6377563.396,6356256.910);

	$x2 = $this->Helmert_X($x1,$y1,$z1,446.448 ,0.2470,0.8421,-20.4894);
	$y2 = $this->Helmert_Y($x1,$y1,$z1,-125.157,0.1502,0.8421,-20.4894);
	$z2 = $this->Helmert_Z($x1,$y1,$z1,542.060 ,0.1502,0.2470,-20.4894);

	$lat = $this->XYZ_to_Lat($x2,$y2,$z2,6378137.000,6356752.313);
	$lon = $this->XYZ_to_Long($x2,$y2);

    return array($lat,$lon);
}


function osgb36_to_gridref($e,$n) {
    $codes = array(
		array ('SV','SW','SX','SY','SZ','TV','TW'), 
        array ('SQ','SR','SS','ST','SU','TQ','TR'),
        array ('SL','SM','SN','SO','SP','TL','TM'),
        array ('SF','SG','SH','SJ','SK','TF','TG'),
        array ('SA','SB','SC','SD','SE','TA','TB'),
        array ('NV','NW','NX','NY','NZ','OV','OW'),
        array ('NQ','NR','NS','NT','NU','OQ','OR'),
        array ('NL','NM','NN','NO','NP','OL','OM'),
        array ('NF','NG','NH','NJ','NK','OF','OG'),
        array ('NA','NB','NC','ND','NE','OA','OB'),
        array ('HV','HW','HX','HY','HZ','JV','JW'),
        array ('HQ','HR','HS','HT','HU','JQ','JR'),
        array ('HL','HM','HN','HO','HP','JL','JM'),
               );

    $ref = sprintf ("%s %05d %05d", $codes[intval($n/100000)][intval($e/100000)],fmod($e,100000), fmod($n,100000)) ;
	return $ref;
}

/**************************
* General Functions
***************************/

function E_N_to_Lat($East, $North, $a, $b, $e0, $n0, $f0, $PHI0, $LAM0) {
	#Un-project Transverse Mercator eastings and northings back to latitude.
	#Input: - _
	#eastings (East) and northings (North) in meters; _
	#ellipsoid axis dimensions (a & b) in meters; _
	#eastings (e0) and northings (n0) of false origin in meters; _
	#central meridian scale factor (f0) and _
	#latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.

	#'REQUIRES THE "Marc" AND "InitialLat" FUNCTIONS

	#Convert angle measures to radians
    $Pi = 3.14159265358979;
    $RadPHI0 = $PHI0 * ($Pi / 180);
    $RadLAM0 = $LAM0 * ($Pi / 180);

	#Compute af0, bf0, e squared (e2), n and Et
    $af0 = $a * $f0;
    $bf0 = $b * $f0;
    $e2 = (pow($af0,2) - pow($bf0,2)) / pow($af0,2);
    $n = ($af0 - $bf0) / ($af0 + $bf0);
    $Et = $East - $e0;

	#Compute initial value for latitude (PHI) in radians
    $PHId = $this->InitialLat($North, $n0, $af0, $RadPHI0, $n, $bf0);
    
	#Compute nu, rho and eta2 using value for PHId
    $nu = $af0 / (sqrt(1 - ($e2 * ( pow(Sin($PHId),2)))));
    $rho = ($nu * (1 - $e2)) / (1 - ($e2 * pow(Sin($PHId),2)));
    $eta2 = ($nu / $rho) - 1;
    
	#Compute Latitude
    $VII = (tan($PHId)) / (2 * $rho * $nu);
    $VIII = ((tan($PHId)) / (24 * $rho * pow($nu,3))) * (5 + (3 * (pow(tan($PHId),2))) + $eta2 - (9 * $eta2 * (pow(tan($PHId),2))));
    $IX = ((tan($PHId)) / (720 * $rho * pow($nu,5))) * (61 + (90 * ((tan($PHId)) ^ 2)) + (45 * (pow(tan($PHId),4))));
    
    $E_N_to_Lat = (180 / $Pi) * ($PHId - (pow($Et,2) * $VII) + (pow($Et,4) * $VIII) - (($Et ^ 6) * $IX));
	return ($E_N_to_Lat);
}

function E_N_to_Long($East, $North, $a, $b, $e0, $n0, $f0, $PHI0, $LAM0) {
	#Un-project Transverse Mercator eastings and northings back to longitude.
	#Input: - _
	#eastings (East) and northings (North) in meters; _
	#ellipsoid axis dimensions (a & b) in meters; _
	#eastings (e0) and northings (n0) of false origin in meters; _
	#central meridian scale factor (f0) and _
	#latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.

	#REQUIRES THE "Marc" AND "InitialLat" FUNCTIONS

	#Convert angle measures to radians
    $Pi = 3.14159265358979;
    $RadPHI0 = $PHI0 * ($Pi / 180);
    $RadLAM0 = $LAM0 * ($Pi / 180);

	#Compute af0, bf0, e squared (e2), n and Et
    $af0 = $a * $f0;
    $bf0 = $b * $f0;
    $e2 = (pow($af0,2) - pow($bf0,2)) / pow($af0,2);
    $n = ($af0 - $bf0) / ($af0 + $bf0);
    $Et = $East - $e0;

	#Compute initial value for latitude (PHI) in radians
    $PHId = $this->InitialLat($North, $n0, $af0, $RadPHI0, $n, $bf0);
    
	#Compute nu, rho and eta2 using value for PHId
    $nu = $af0 / (sqrt(1 - ($e2 * (pow(sin($PHId),2)))));
    $rho = ($nu * (1 - $e2)) / (1 - ($e2 * pow(Sin($PHId),2)));
    $eta2 = ($nu / $rho) - 1;

	#Compute Longitude
    $X = (pow(cos($PHId),-1)) / $nu;
    $XI = ((pow(cos($PHId),-1)) / (6 * pow($nu,3))) * (($nu / $rho) + (2 * (pow(tan($PHId),2))));
    $XII = ((pow(cos($PHId),-1)) / (120 * pow($nu,5))) * (5 + (28 * (pow(tan($PHId),2))) + (24 * (pow(tan($PHId),4))));
    $XIIA = ((pow(Cos($PHId),-1)) / (5040 * pow($nu,7))) * (61 + (662 * (pow(tan($PHId),2))) + (1320 * (pow(Tan($PHId),4))) + (720 * (pow(tan($PHId),6))));

    $E_N_to_Long = (180 / $Pi) * ($RadLAM0 + ($Et * $X) - (pow($Et,3) * $XI) + (pow($Et,5) * $XII) - (pow($Et,7) * $XIIA));
	return $E_N_to_Long;
}

function InitialLat($North, $n0, $afo, $PHI0, $n, $bfo) {
	#Compute initial value for Latitude (PHI) IN RADIANS.
	#Input: - _
	#northing of point (North) and northing of false origin (n0) in meters; _
	#semi major axis multiplied by central meridian scale factor (af0) in meters; _
	#latitude of false origin (PHI0) IN RADIANS; _
	#n (computed from a, b and f0) and _
	#ellipsoid semi major axis multiplied by central meridian scale factor (bf0) in meters.
 
	#REQUIRES THE "Marc" FUNCTION
	#THIS FUNCTION IS CALLED BY THE "E_N_to_Lat", "E_N_to_Long" and "E_N_to_C" FUNCTIONS
	#THIS FUNCTION IS ALSO USED ON IT'S OWN IN THE  "Projection and Transformation Calculations.xls" SPREADSHEET

	#First PHI value (PHI1)
    $PHI1 = (($North - $n0) / $afo) + $PHI0;
    
	#Calculate M
    $M = $this->Marc($bfo, $n, $PHI0, $PHI1);
    
	#Calculate new PHI value (PHI2)
    $PHI2 = (($North - $n0 - $M) / $afo) + $PHI1;
    
	#Iterate to get final value for InitialLat
	While (abs($North - $n0 - $M) > 0.00001) {
        $PHI2 = (($North - $n0 - $M) / $afo) + $PHI1;
        $M = $this->Marc($bfo, $n, $PHI0, $PHI2);
        $PHI1 = $PHI2;
	}    
    return $PHI2;
}



function Lat_Long_H_to_X ($PHI, $LAM, $H, $a, $b) {
# Convert geodetic coords lat (PHI), long (LAM) and height (H) to cartesian X coordinate.
# Input: - _
#    Latitude (PHI)& Longitude (LAM) both in decimal degrees; _
#  Ellipsoidal height (H) and ellipsoid axis dimensions (a & b) all in meters.
    
# Convert angle measures to radians
    $Pi = 3.14159265358979;
    $RadPHI = $PHI * ($Pi / 180);
    $RadLAM = $LAM * ($Pi / 180);

# Compute eccentricity squared and nu
    $e2 = (pow($a,2) - pow($b,2)) / pow($a,2);
    $V = $a / (sqrt(1 - ($e2 * (  pow(sin($RadPHI),2)))));

# Compute X
    return ($V + $H) * (cos($RadPHI)) * (cos($RadLAM));
}


function Lat_Long_H_to_Y ($PHI, $LAM, $H, $a, $b) {
# Convert geodetic coords lat (PHI), long (LAM) and height (H) to cartesian Y coordinate.
# Input: - _
# Latitude (PHI)& Longitude (LAM) both in decimal degrees; _
# Ellipsoidal height (H) and ellipsoid axis dimensions (a & b) all in meters.

# Convert angle measures to radians
    $Pi = 3.14159265358979;
    $RadPHI = $PHI * ($Pi / 180);
    $RadLAM = $LAM * ($Pi / 180);

# Compute eccentricity squared and nu
    $e2 = (pow($a,2) - pow($b,2)) / pow($a,2);
    $V = $a / (sqrt(1 - ($e2 * (  pow(sin($RadPHI),2))) ));

# Compute Y
    return ($V + $H) * (cos($RadPHI)) * (sin($RadLAM));
}


function Lat_H_to_Z ($PHI, $H, $a, $b) {
# Convert geodetic coord components latitude (PHI) and height (H) to cartesian Z coordinate.
# Input: - _
#    Latitude (PHI) decimal degrees; _
# Ellipsoidal height (H) and ellipsoid axis dimensions (a & b) all in meters.

# Convert angle measures to radians
    $Pi = 3.14159265358979;
    $RadPHI = $PHI * ($Pi / 180);

# Compute eccentricity squared and nu
    $e2 = (pow($a,2) - pow($b,2)) / pow($a,2);
    $V = $a / (sqrt(1 - ($e2 * (  pow(sin($RadPHI),2)) )));

# Compute X
    return (($V * (1 - $e2)) + $H) * (sin($RadPHI));
}


function Helmert_X ($X,$Y,$Z,$DX,$Y_Rot,$Z_Rot,$s) {

# (X, Y, Z, DX, Y_Rot, Z_Rot, s)
# Computed Helmert transformed X coordinate.
# Input: - _
#    cartesian XYZ coords (X,Y,Z), X translation (DX) all in meters ; _
# Y and Z rotations in seconds of arc (Y_Rot, Z_Rot) and scale in ppm (s).

# Convert rotations to radians and ppm scale to a factor
$Pi = 3.14159265358979;
$sfactor = $s * 0.000001;

$RadY_Rot = ($Y_Rot / 3600) * ($Pi / 180);

$RadZ_Rot = ($Z_Rot / 3600) * ($Pi / 180);
    
#Compute transformed X coord
    return  ($X + ($X * $sfactor) - ($Y * $RadZ_Rot) + ($Z * $RadY_Rot) + $DX);
}


function Helmert_Y ($X,$Y,$Z,$DY,$X_Rot,$Z_Rot,$s) {
# (X, Y, Z, DY, X_Rot, Z_Rot, s)
# Computed Helmert transformed Y coordinate.
# Input: - _
#    cartesian XYZ coords (X,Y,Z), Y translation (DY) all in meters ; _
#  X and Z rotations in seconds of arc (X_Rot, Z_Rot) and scale in ppm (s).
 
# Convert rotations to radians and ppm scale to a factor
$Pi = 3.14159265358979;
$sfactor = $s * 0.000001;
$RadX_Rot = ($X_Rot / 3600) * ($Pi / 180);
$RadZ_Rot = ($Z_Rot / 3600) * ($Pi / 180);
    
# Compute transformed Y coord
return ($X * $RadZ_Rot) + $Y + ($Y * $sfactor) - ($Z * $RadX_Rot) + $DY;

}


function Helmert_Z ($X, $Y, $Z, $DZ, $X_Rot, $Y_Rot, $s) {

# (X, Y, Z, DZ, X_Rot, Y_Rot, s)
# Computed Helmert transformed Z coordinate.
# Input: - _
#    cartesian XYZ coords (X,Y,Z), Z translation (DZ) all in meters ; _
# X and Y rotations in seconds of arc (X_Rot, Y_Rot) and scale in ppm (s).
# 
# Convert rotations to radians and ppm scale to a factor
$Pi = 3.14159265358979;
$sfactor = $s * 0.000001;
$RadX_Rot = ($X_Rot / 3600) * ($Pi / 180);
$RadY_Rot = ($Y_Rot / 3600) * ($Pi / 180);
    
# Compute transformed Z coord
return (-1 * $X * $RadY_Rot) + ($Y * $RadX_Rot) + $Z + ($Z * $sfactor) + $DZ;
} 





function XYZ_to_Lat ($X, $Y, $Z, $a, $b) {
# Convert XYZ to Latitude (PHI) in Dec Degrees.
# Input: - _
# XYZ cartesian coords (X,Y,Z) and ellipsoid axis dimensions (a & b), all in meters.

# THIS FUNCTION REQUIRES THE "Iterate_XYZ_to_Lat" FUNCTION
# THIS FUNCTION IS CALLED BY THE "XYZ_to_H" FUNCTION

    $RootXYSqr = sqrt(pow($X,2) + pow($Y,2));
    $e2 = (pow($a,2) - pow($b,2)) / pow($a,2);
    $PHI1 = atan2 ($Z , ($RootXYSqr * (1 - $e2)) );
    
    $PHI = $this->Iterate_XYZ_to_Lat($a, $e2, $PHI1, $Z, $RootXYSqr);
    
    $Pi = 3.14159265358979;
    
    return $PHI * (180 / $Pi);
    }


function Iterate_XYZ_to_Lat ($a, $e2, $PHI1, $Z, $RootXYSqr) {
# Iteratively computes Latitude (PHI).
# Input: - _
#    ellipsoid semi major axis (a) in meters; _
#    eta squared (e2); _
#    estimated value for latitude (PHI1) in radians; _
#    cartesian Z coordinate (Z) in meters; _
# RootXYSqr computed from X & Y in meters.

# THIS FUNCTION IS CALLED BY THE "XYZ_to_PHI" FUNCTION
# THIS FUNCTION IS ALSO USED ON IT'S OWN IN THE _
# "Projection and Transformation Calculations.xls" SPREADSHEET


    $V = $a / (sqrt(1 - ($e2 * pow(sin($PHI1),2))));
    $PHI2 = atan2(($Z + ($e2 * $V * (sin($PHI1)))) , $RootXYSqr);
    
    while (abs($PHI1 - $PHI2) > 0.000000001) {
        $PHI1 = $PHI2;
        $V = $a / (sqrt(1 - ($e2 * pow(sin($PHI1),2))));
        $PHI2 = atan2(($Z + ($e2 * $V * (sin($PHI1)))) , $RootXYSqr);
    }

    return $PHI2;
}


function XYZ_to_Long ($X, $Y) {
# Convert XYZ to Longitude (LAM) in Dec Degrees.
# Input: - _
# X and Y cartesian coords in meters.

    $Pi = 3.14159265358979;
    return atan2($Y , $X) * (180 / $Pi);
}


function XYZ_to_H ($X, $Y, $Z, $a, $b) {
# Convert XYZ to Ellipsoidal Height.
# Input: - _
# XYZ cartesian coords (X,Y,Z) and ellipsoid axis dimensions (a & b), all in meters.

# REQUIRES THE "XYZ_to_Lat" FUNCTION

# Compute PHI (Dec Degrees) first
    $PHI = $this->XYZ_to_Lat($X, $Y, $Z, $a, $b);

#Convert PHI radians
    $Pi = 3.14159265358979;
    $RadPHI = $PHI * ($Pi / 180);
    
# Compute H
    $RootXYSqr = sqrt(pow($X,2) + pow($Y,2));
    $e2 = (pow($a,2) - pow($b,2)) / pow($a,2);
    $V = $a / (sqrt(1 - ($e2 * pow(sin($RadPHI),2))));
    $H = ($RootXYSqr / cos($RadPHI)) - $V;
    
    return $H;
}



function Lat_Long_to_East ($PHI, $LAM, $a, $b, $e0, $f0, $PHI0, $LAM0) {
#Project Latitude and longitude to Transverse Mercator eastings.
#Input: - _
#    Latitude (PHI) and Longitude (LAM) in decimal degrees; _
#    ellipsoid axis dimensions (a & b) in meters; _
#    eastings of false origin (e0) in meters; _
#    central meridian scale factor (f0); _
# latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.

# Convert angle measures to radians
    $Pi = 3.14159265358979;
    $RadPHI = $PHI * ($Pi / 180);
    $RadLAM = $LAM * ($Pi / 180);
    $RadPHI0 = $PHI0 * ($Pi / 180);
    $RadLAM0 = $LAM0 * ($Pi / 180);

    $af0 = $a * $f0;
    $bf0 = $b * $f0;
    $e2 = (pow($af0,2) - pow($bf0,2)) / pow($af0,2);
    $n = ($af0 - $bf0) / ($af0 + $bf0);
    $nu = $af0 / (sqrt(1 - ($e2 * pow(sin($RadPHI),2) )));
    $rho = ($nu * (1 - $e2)) / (1 - ($e2 * pow(sin($RadPHI),2) ));
    $eta2 = ($nu / $rho) - 1;
    $p = $RadLAM - $RadLAM0;
    
    $IV = $nu * (cos($RadPHI));
    $V = ($nu / 6) * ( pow(cos($RadPHI),3)) * (($nu / $rho) - (pow(tan($RadPHI),2)));
    $VI = ($nu / 120) * (pow(cos($RadPHI),5)) * (5 - (18 * (pow(tan($RadPHI),2))) + (pow(tan($RadPHI),4)) + (14 * $eta2) - (58 * (pow(tan($RadPHI),2)) * $eta2));
    
    return $e0 + ($p * $IV) + (pow($p,3) * $V) + (pow($p,5) * $VI);
}


function Lat_Long_to_North ($PHI, $LAM, $a, $b, $e0, $n0, $f0, $PHI0, $LAM0) {
# Project Latitude and longitude to Transverse Mercator northings
# Input: - _
# Latitude (PHI) and Longitude (LAM) in decimal degrees; _
# ellipsoid axis dimensions (a & b) in meters; _
# eastings (e0) and northings (n0) of false origin in meters; _
# central meridian scale factor (f0); _
# latitude (PHI0) and longitude (LAM0) of false origin in decimal degrees.

# REQUIRES THE "Marc" FUNCTION

# Convert angle measures to radians
    $Pi = 3.14159265358979;
    $RadPHI = $PHI * ($Pi / 180);
    $RadLAM = $LAM * ($Pi / 180);
    $RadPHI0 = $PHI0 * ($Pi / 180);
    $RadLAM0 = $LAM0 * ($Pi / 180);
    
    $af0 = $a * $f0;
    $bf0 = $b * $f0;
    $e2 = (pow($af0,2) - pow($bf0,2)) / pow($af0,2);
    $n = ($af0 - $bf0) / ($af0 + $bf0);
    $nu = $af0 / (sqrt(1 - ($e2 * pow(sin($RadPHI),2))));
    $rho = ($nu * (1 - $e2)) / (1 - ($e2 * pow(sin($RadPHI),2)));
    $eta2 = ($nu / $rho) - 1;
    $p = $RadLAM - $RadLAM0;
    $M = $this->Marc($bf0, $n, $RadPHI0, $RadPHI);
    
    $I = $M + $n0;
    $II = ($nu / 2) * (sin($RadPHI)) * (cos($RadPHI));
    $III = (($nu / 24) * (sin($RadPHI)) * (pow(cos($RadPHI),3))) * (5 - (pow(tan($RadPHI),2)) + (9 * $eta2));
    $IIIA = (($nu / 720) * (sin($RadPHI)) * (pow(cos($RadPHI),5))) * (61 - (58 * (pow(tan($RadPHI),2))) + (pow(tan($RadPHI),4)));
    
    return $I + (pow($p,2) * $II) + (pow($p,4) * $III) + (pow($p,6) * $IIIA);
}
   




function Marc ($bf0, $n, $PHI0, $PHI) {
#Compute meridional arc.
#Input: - _
# ellipsoid semi major axis multiplied by central meridian scale factor (bf0) in meters; _
# n (computed from a, b and f0); _
# lat of false origin (PHI0) and initial or final latitude of point (PHI) IN RADIANS.

#THIS FUNCTION IS CALLED BY THE - _
# "Lat_Long_to_North" and "InitialLat" FUNCTIONS
# THIS FUNCTION IS ALSO USED ON IT'S OWN IN THE "Projection and Transformation Calculations.xls" SPREADSHEET

    return $bf0 * (((1 + $n + ((5 / 4) * pow($n,2)) + ((5 / 4) * pow($n,3))) * ($PHI - $PHI0)) - (((3 * $n) + (3 * pow($n,2)) + ((21 / 8) * pow($n,3))) * (sin($PHI - $PHI0)) * (cos($PHI + $PHI0))) + ((((15 / 8
) * pow($n,2)) + ((15 / 8) * pow($n,3))) * (sin(2 * ($PHI - $PHI0))) * (cos(2 * ($PHI + $PHI0)))) - (((35 / 24) * pow($n,3)) * (sin(3 * ($PHI - $PHI0))) * (cos(3 * ($PHI + $PHI0)))));
}

}
