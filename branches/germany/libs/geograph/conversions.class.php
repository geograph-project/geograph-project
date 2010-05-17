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
function wgs84_to_national($lat,$long,$usehermert = true,$ri=-1) {
	require_once('geograph/conversionslatlong.class.php');
	$conv = new ConversionsLatLong;
	$ire = ($ri == 2 || $ri == -1 && $lat > 51.2 && $lat < 55.73 && $long > -12.2 && $long < -4.8);
	$uk = ($ri == 1 || $ri == -1 && $lat > 49 && $lat < 62 && $long > -9.5 && $long < 2.3);
	$ger32 = ($ri == 3 || $ri == -1 && $lat > 47 && $lat < 56 && $long >= 6 && $long <= 12); #FIXME
	$ger33 = ($ri == 4 || $ri == -1 && $lat > 47 && $lat < 56 && $long > 12 && $long < 16); #FIXME
	$ger31 = ($ri == 5 || $ri == -1 && $lat > 47 && $lat < 56 && $long > 4 && $long < 6); #FIXME
	
	if ($uk && $ire) {
		//rough border for ireland
		$ireland = array(
			array(-12.19,50.38),
			array( -6.39,50.94),
			array( -5.07,53.71),
			array( -5.25,54.71),
			array( -6.13,55.42),
			array(-10.65,56.15),
			array(-12.19,50.38) );
		$ire = $this->pointInside(array($long,$lat),$ireland);
		$uk = 1 - $ire;
	} 
	
	if ($ire) {
		return array_merge($conv->wgs84_to_irish($lat,$long,$usehermert),array(2));
	} else if ($uk) {
		return array_merge($conv->wgs84_to_osgb36($lat,$long),array(1));
	} else if($ger32) {
		return array_merge($conv->wgs84_to_utm($lat,$long,32),array(3));
	} else if($ger33) {
		return array_merge($conv->wgs84_to_utm($lat,$long,33),array(4));
	} else if($ger31) {
		return array_merge($conv->wgs84_to_utm($lat,$long,31),array(5));
	}
	return array();
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
	require_once('geograph/conversionslatlong.class.php');
	$conv = new ConversionsLatLong;
	$latlong = array();
	if ($reference_index == 1) {
		$latlong = $conv->osgb36_to_wgs84($e,$n);
	} else if ($reference_index == 2) {
		$latlong = $conv->irish_to_wgs84($e,$n,$usehermert);
	} else if ($reference_index == 3) {
		$latlong = $conv->utm_to_wgs84($e,$n,32);
	} else if ($reference_index == 4) {
		$latlong = $conv->utm_to_wgs84($e,$n,33);
	} else if ($reference_index == 5) {
		$latlong = $conv->utm_to_wgs84($e,$n,31);
	}
	return $latlong;
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
	if (!$reference_index) {
		return array("",0);
	}
	list($x,$y) = $this->national_to_internal($e,$n,$reference_index );

	$db = $this->_getDB();

	$x_lim=$x-100;
	$y_lim=$y-100;
	$sql="select prefix from gridprefix ".
		"where CONTAINS(geometry_boundary, GeomFromText('POINT($x $y)')) ".
		"and (origin_x > $x_lim) and (origin_y > $y_lim) ".
		"and reference_index=$reference_index";
	$prefix=$db->GetOne($sql);
	#$sql="select prefix from gridprefix ".
	#	"where $x between origin_x and (origin_x+width-1) and ".
	#	"$y between origin_y and (origin_y+height-1) and reference_index=$reference_index";
	#$prefix=$db->GetOne($sql);

	$eastings = sprintf("%05d",($e+ 500000) % 100000); //cope with negative! (for Rockall...)
	$northings = sprintf("%05d",$n % 100000);
	

	if ($gr_length) {
		$len = intval($gr_length/2);
	} else {
		//try to work out the shortest grid ref length
		$east = preg_replace("/^(\d+?)0*$/",'$1',$eastings);
		$north = preg_replace("/^(\d+?)0*$/",'$1',$northings);
		$len = max(strlen($east),strlen($north),2);
	}
	
	$eastings = substr($eastings,0,$len);
	$northings = substr($northings,0,$len);
	if ($spaced) {
		return array("$prefix $eastings $northings",$len);
	} else {
		return array($prefix.$eastings.$northings,$len);
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
	if (!$reference_index) {
		$db = $this->_getDB();
		
		$reference_index=$db->GetOne("select reference_index from gridsquare where CONTAINS( GeomFromText('POINT($x $y)'),point_xy )");
		
		//But what to do when the square is not on land??
		
		if (!$reference_index) {
			//when not on land just try any square!
			// but favour the _smaller_ grid - works better, but still not quite right where the two grids almost overlap
			$where_crit =  "order by reference_index desc";
			$x_lim=$x-100;
			$y_lim=$y-100;
		
			#$sql="select reference_index from gridprefix ".
			#	"where $x between origin_x and (origin_x+width-1) and ".
			#	"$y between origin_y and (origin_y+height-1) $where_crit";
			$sql="select reference_index from gridprefix ".
				"where CONTAINS(geometry_boundary, GeomFromText('POINT($x $y)')) and (origin_x > $x_lim) and (origin_y > $y_lim) ".
				$where_crit;
			$reference_index=$db->GetOne($sql);
		}
	}

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
	global $CONF;

	$xd = intval(abs($long));
	$xm = intval((abs($long)-$xd)*60);
	$xs = (abs($long)*3600)-($xm*60)-($xd*3600);

	$yd = intval(abs($lat));
	$ym = intval((abs($lat)-$yd)*60);
	$ys = (abs($lat)*3600)-($ym*60)-($yd*3600);

	if ($CONF['lang'] == 'de') {
		$el = ($long > 0)?'O':'W';
		$nl = ($lat > 0)?'N':'S';
	
		$xss=sprintf("%.2f",$xs); //FIXME needs locale de_DE
		$yss=sprintf("%.2f",$ys); //FIXME needs locale de_DE
		
		return array("{$yd}°$ym'$yss\"$nl","{$xd}°$xm'$xss\"$el");
	} else {
		$el = ($long > 0)?'E':'W';
		$nl = ($lat > 0)?'N':'S';

		$ymd = sprintf("%.4f",$ym+($ys/60));
		$xmd = sprintf("%.4f",$xm+($xs/60));

		return array("$yd:$ymd$nl","$xd:$xmd$el");
	}
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
