<?php
/**
 * $Project: GeoGraph $
 * $Id$
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

function FractionToDecimal($fraction) {
	$result = 0;
	eval ("\$result = 1.0*$fraction;");
	return $result;
}

function ExifConvertDegMinSecToDD($deg, $min, $sec) {
	$dec_min = ($min*60.0 + $sec)/60.0;
	return ($deg*60.0 + $dec_min)/60.0;
}


/**
* Perform coordinate conversion for use within Geograph
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/

	
class Conversions
{		
	var $db = null;
	
//use:	list($x,$y,$reference_index) = wgs84_to_internal($lat,$long);
		//with reference_index deduced from the location and the approraite conversion used

function wgs84_to_internal($lat,$long) {
	list($e,$n,$reference_index) = $this->wgs84_to_national($lat,$long);
	return $this->national_to_internal($e,$n,$reference_index);
}


// see solution 1 at http://astronomy.swin.edu.au/~pbourke/geometry/insidepoly/
function pointInside($p,&$points) {
	$c = 0;
	$p1 = $points[0];
	$n = count($points);
	for ($i=1; $i<=$n; $i++) {
		$p2 = $points[$i % $n];
		if ($p[1] > min($p1[1], $p2[1]) 
				&& $p[1] <= max($p1[1], $p2[1]) 
				&& $p[0] <= max($p1[0], $p2[0]) 
				&& $p1[1] != $p2[1]) {
			$xinters = ($p[1] - $p1[1]) * ($p2[0] - $p1[0]) / ($p2[1] - $p1[1]) + $p1[0];
			if ($p1[0] == $p2[0] || $p[0] <= $xinters)
				$c++;
		}
		$p1 = $p2;
	}
	// if the number of edges we passed through is even, then it’s not in the poly.
	return $c%2!=0;
}
		

//use:	list($e,$n,$reference_index) = wgs84_to_national($lat,$long);
		//with reference_index deduced from the location and the approraite conversion used
function wgs84_to_national($lat,$long,$usehermert = true) {
include_once("proj4php/src/proj4php/proj4php.php");

$proj4 = new Proj4php();
$projWGS84 = new Proj4phpProj('EPSG:4326',$proj4);
$projSwiss = new Proj4phpProj('EPSG:21781',$proj4);

$pointSrc = new proj4phpPoint($long,$lat);
$pointDest = $proj4->transform($projWGS84,$projSwiss,$pointSrc);

return array($pointDest->x,$pointDest->y,10);

}


//use:	list($lat,$long) = internal_to_wgs84($x,$y,$reference_index = 0);
		//reference_index is optional as we can duduce this (but if known then can pass it in to save having to recaluate)
			//will probably just call national_to_wgs84 once converted

function internal_to_wgs84($x,$y,$reference_index = 0) {
	list ($e,$n,$reference_index) = $this->internal_to_national($x,$y,$reference_index);
	return $this->national_to_wgs84($e,$n,$reference_index);
}


//use:	list($lat,$long) = national_to_wgs84($e,$n,$reference_index);

function national_to_wgs84($e,$n,$reference_index,$usehermert = true) {

include_once("proj4php/src/proj4php/proj4php.php");

$proj4 = new Proj4php();
$projWGS84 = new Proj4phpProj('EPSG:4326',$proj4);
$projSwiss = new Proj4phpProj('EPSG:21781',$proj4);

$pointSrc = new proj4phpPoint($e,$n);
$pointDest = $proj4->transform($projSwiss,$projWGS84,$pointSrc);

return array($pointDest->y,$pointDest->x,10);

}


//use:	list($lat,$long) = gridsquare_to_wgs84(&$gridsquare);
			//will contain nateastings/natnorthings  or can call getNationalEastings to get them

function gridsquare_to_wgs84(&$gridsquare) {
	if (!$gridsquare->nateastings)
		$gridsquare->getNatEastings();
	return $this->national_to_wgs84($gridsquare->nateastings,$gridsquare->natnorthings,$gridsquare->reference_index);
}

//--------------------------------------------------------------------------------
// convenence functions

//use:    $gr = internal_to_gridref($x,$y,$gr_length,$reference_index = 0);
         //reference_index is optional as we can duduce this

function internal_to_gridref($x,$y,$gr_length,$reference_index = 0) {
	list($e,$n,$reference_index) = $this->internal_to_national($x,$y,$reference_index);

	return $this->national_to_gridref($e-500,$n-500,$gr_length,$reference_index);
}


//use:    list($gr,$len) = national_to_gridref($e,$n,$gr_length,$reference_index);

function national_to_gridref($e,$n,$gr_length,$reference_index,$spaced = false) {
	list($x,$y) = $this->national_to_internal($e,$n,$reference_index );

	$eastings = sprintf("%06d",$e);
	$northings = sprintf("%06d",$n);

	if ($gr_length) {
		$len = intval($gr_length/2);
	} else {
		//try to work out the shortest grid ref length
		$east = preg_replace("/^(\d+?)0*$/",'$1',$eastings);
		$north = preg_replace("/^(\d+?)0*$/",'$1',$northings);
		$len = max(strlen($east),strlen($north),2);
	}

	$eastings = substr($eastings,0,$len+1);
	$northings = substr($northings,0,$len+1);
	if ($spaced) {
		return array("$eastings $northings",$len);
	} else {
		return array($eastings.$northings,$len);
	}
}

//use:    list($x,$y) = national_to_internal($e,$n,$reference_index );

function national_to_internal($e,$n,$reference_index ) {
	global $CONF;
	$x = intval($e / 1000);
	$y = intval($n / 1000);
	
	//add the internal origin
	$x += $CONF['origins'][$reference_index][0];
	$y += $CONF['origins'][$reference_index][1];
	return array($x,$y);
}


//use:    list($e,$n,$reference_index) = internal_to_national($x,$y,$reference_index = 0);
// note gridsquare has its own version that takes into account the userspecified easting/northing
function internal_to_national($x,$y,$reference_index = 0) {
	global $CONF;

$reference_index = 10;

	if ($reference_index) {
		//remove the internal origin
		$x -= $CONF['origins'][$reference_index][0];
		$y -= $CONF['origins'][$reference_index][1];

		//lets position the national coords in the center of the square!
		$e = intval($x * 1000 + 500);
		$n = intval($y * 1000 + 500);
		return array($e,$n,$reference_index);
	} else {
		return array();
	}
}


//use:    list($x,$y,$reference_index) = gridref_to_internal($gr);

//use:    list($e,$n,$reference_index) = gridref_to_national($gr);

//use:	list($x,$y) = alignInternalToNationalLines($x,$y,$reference_index = 0);
	 //reference_index is optional as we can duduce this
	 // for mosaic->setAlignedOrigin to handle the hardcoded alignments

//use:	list($e,$n) = osgb36_to_irish($e,$n);
			// this is used when we have a dataset in osgb and need to convert it to irish national (eg loc_placenames etc)

function wgs84_to_friendly($lat,$long) {
	$el = ($long > 0)?'E':'W';
	$nl = ($lat > 0)?'N':'S';
	
	$xd = intval(abs($long));
	$xm = intval((abs($long)-$xd)*60);
	$xs = (abs($long)*3600)-($xm*60)-($xd*3600);

	$yd = intval(abs($lat));
	$ym = intval((abs($lat)-$yd)*60);
	$ys = (abs($lat)*3600)-($ym*60)-($yd*3600);

	$ymd = sprintf("%.4f",$ym+($ys/60));
	$xmd = sprintf("%.4f",$xm+($xs/60));
	
	return array("$yd:$ymd$nl","$xd:$xmd$el");
}

function wgs84_to_friendly_smarty_parts($lat,$long,&$smarty) {
	$el = ($long > 0)?'E':'W';
	$nl = ($lat > 0)?'N':'S';
	
	$along = abs($long);
	$alat = abs($lat);
	
	$xd = intval($along);
	$xm = intval(($along-$xd)*60);
	$xs = ($along*3600)-($xm*60)-($xd*3600);

	$yd = intval($alat);
	$ym = intval(($alat-$yd)*60);
	$ys = ($alat*3600)-($ym*60)-($yd*3600);

	$ymd = sprintf("%.4f",$ym+($ys/60));
	$xmd = sprintf("%.4f",$xm+($xs/60));
	
	$xs = sprintf("%.5f",$xs);
	$ys = sprintf("%.5f",$ys);
	
	foreach (array('el','nl','along','alat','xd','xm','xs','yd','ym','ys','ymd','xmd') as $name) {
		$smarty->assign($name, $$name);
	}
	$smarty->assign('latdm', "$yd:$ymd$nl");
	$smarty->assign('longdm', "$xd:$xmd$el");
}

//----------------------------------------------------------------

	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!is_object($this->db))
			$this->db=NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');
		return $this->db;
	}

	/**
	 * set stored db object
	 * @access private
	 */
	function _setDB(&$db)
	{
		$this->db=$db;
	}

}
