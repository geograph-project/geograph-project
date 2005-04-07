<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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
* Provides the GridSquare class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

/**
* GridSquare class
* Provides an abstraction of a grid square, providing all the
* obvious functions you'd expect
*/
class GridSquare
{
	/**
	* internal database handle
	*/
	var $db=null;
	
   /**
   	* gridsquare.gridsquare_id primary key
   	*/
	var $gridsquare_id=0;

   /**
   	* gridsquare.grid_reference 
   	*/
	var $grid_reference='';
 
 	/**
	* gridsquare.reference_index type of grid reference
	*/
 	var $reference_index=0;
 
	/**
	* gridsquare.x,y internal grid position
	*/
 	var $x=0;
 	var $y=0;
 
 	/**
 	* gridsquare.percent_land how much land?
 	*/
  	var $percent_land=0;
  	
  	/**
	* gridsquare.percent_land how much land?
	*/
	var $imagecount=0;
  	
  	/**
	* exploded gridsquare element of $this->grid_reference
	*/
	var $gridsquare="";
  	
  	/**
	* exploded eastings element of $this->grid_reference
	*/
	var $eastings=0;
  	
  	/**
	* exploded northings element of $this->grid_reference
	*/
	var $northings=0;
  	
  	/**
	* national easting/northing (ie not internal)
	*/
	var $nateastings;
  	var $natnorthings;
  	
  	/**
	* GridSquare instance of nearest square to this one with an image
	*/
	var $nearest=null;
  	
  	
  	/**
	* nearest member will have this set to show distance of nearest square from this one
	*/
	var $distance=0;
  	
	
	
	/**
	* Constructor
	*/
	function GridSquare()
	{
	}
	
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
	
	/**
	* store error message
	*/
	function _error($msg)
	{
		$this->errormsg=$msg;
	}
	
	/**
	* Conveience function to get six figure GridRef
	*/
	function get6FigGridRef()
	{
		return sprintf("%s%03d%03d", $this->gridsquare, $this->eastings*10 + 5, $this->northings*10 + 5);
	}

	/**
	* Conveience function to get national easting (not internal)
	*/
	function getNatEastings()
	{
		if (!isset($this->nateastings)) {
			$db=&$this->_getDB();
			
			$square = $db->GetRow("select origin_x,origin_y from gridprefix where prefix=".$db->Quote($this->gridsquare));	
			
			//get the first gridprefix with the required reference_index
			//after ordering by x,y - you'll get the bottom
			//left gridprefix, and hence the origin
			
			$origin = $db->CacheGetRow(100*24*3600,"select * from gridprefix where reference_index={$this->reference_index} order by origin_x,origin_y limit 1");	
			
			$square['origin_x'] -= $origin['origin_x'];
			$square['origin_y'] -= $origin['origin_y'];
			
			$this->nateastings = sprintf("%d%05d",intval($square['origin_x']/100),$this->eastings * 1000 + 500);
			$this->natnorthings = sprintf("%d%05d",intval($square['origin_y']/100),$this->northings * 1000 +500);
			
		} 
		return $this->nateastings;
	}
	
	/**
	* Conveience function to get national northing (not internal)
	*/
	function getNatNorthings()
	{
		if (!isset($this->natnorthings)) {
			$this->getNatEastings();
		} 
		return $this->natnorthings;
	}
	
	/**
	* Get an array of valid grid prefixes
	*/
	function getGridPrefixes()
	{
		//only show gb grid if we have land there
		//show all irish grid squares...
		$db=&$this->_getDB();
		return $db->GetAssoc("select prefix,prefix from gridprefix ".
			"where landcount>0 ".
			"order by reference_index,prefix");

	}
	
	/**
	* Get an array of valid kilometer indexes
	*/
	function getKMList()
	{
		$kmlist=array();
		for ($k=0; $k<100;$k++)
		{
			$kmlist[$k]=sprintf("%02d", $k);
		}
		return $kmlist;
	}
	
	/**
	* Store grid reference in session
	*/
	function rememberInSession()
	{
		if (strlen($this->grid_reference))
		{
			$_SESSION['gridref']=$this->grid_reference;
			$_SESSION['gridsquare']=$this->gridsquare;
			$_SESSION['eastings']=$this->eastings;
			$_SESSION['northings']= $this->northings;
			
		}
	}
	
	/**
	*
	*/
	function setByFullGridRef($gridreference)
	{
		$matches=array();
		$isfour=false;
		
		if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{5})[ \.]?(\d{5})\b/",$gridreference,$matches)) {
			list ($prefix,$e,$n) = array($matches[1],$matches[2],$matches[3]);
		} else if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{4})[ \.]?(\d{4})\b/",$gridreference,$matches)) {
			list ($prefix,$e,$n) = array($matches[1],"$matches[2]0","$matches[3]0");
		} else if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{3})[ \.]*(\d{3})\b/",$gridreference,$matches)) {
			list ($prefix,$e,$n) = array($matches[1],"$matches[2]00","$matches[3]00");
		} else if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{2})[ \.]?(\d{2})\b/",$gridreference,$matches)) {
			list ($prefix,$e,$n) = array($matches[1],"$matches[2]000","$matches[3]000");
			$isfour = true;
		}		
		if (!empty($prefix))
		{
			$gridref=sprintf("%s%02d%02d", strtoupper($prefix), intval($e/1000), intval($n/1000));
			$ok=$this->_setGridRef($gridref);
			if ($ok && !$isfour)
			{
				//use this function to work out the major easting/northing then convert to our exact values
				$eastings=$this->getNatEastings();
				$northings=$this->getNatNorthings();
				
				$emajor = floor($eastings / 100000);
				$nmajor = floor($northings / 100000);
	
				$this->nateastings = $emajor.sprintf("%05d",$e);
				$this->natnorthings = $nmajor.sprintf("%05d",$n);
				
			}
		} else {
			$ok=false;
			$this->_error(htmlentities($gridreference).' is not a valid grid reference');

		}
				
		return $ok;
	}
	
	/**
	* Stores the grid reference along with handy exploded elements 
	*/
	function _storeGridRef($gridref)
	{
		$this->grid_reference=$gridref;
		if (preg_match('/^([A-Z]{1,2})(\d\d)(\d\d)$/',$this->grid_reference, $matches))
		{
			$this->gridsquare=$matches[1];
			$this->eastings=$matches[2];
			$this->northings=$matches[3];
		}
		
	}
	
	
	/**
	* Just checks that a grid position is syntactically valid
	* No attempt is made to see if its a real grid position, just to ensure
	* that the input isn't anything nasty from the client side
	*/
	function validGridPos($gridsquare, $eastings, $northings)
	{
		$ok=true;
		$ok=$ok && preg_match('/^[A-Z]{1,2}$/',$gridsquare);
		$ok=$ok && preg_match('/^[0-9]{1,2}$/',$eastings);
		$ok=$ok && preg_match('/^[0-9]{1,2}$/',$northings);
		return $ok;
	}

	/**
	* set up and validate grid square selection using seperate reference components
	*/
	function setGridPos($gridsquare, $eastings, $northings)
	{
		//assume the inputs are tainted..
		$ok=$this->validGridPos($gridsquare, $eastings, $northings);
		if ($ok)
		{
			$gridref=sprintf("%s%02d%02d", $gridsquare, $eastings, $northings);
			$ok=$this->_setGridRef($gridref);
		}
		
		return $ok;
	}

	/**
	* Just checks that a grid position is syntactically valid
	* No attempt is made to see if its a real grid position, just to ensure
	* that the input isn't anything nasty from the client side
	*/
	function validGridRef($gridref, $figures=4)
	{
		return preg_match('/^[A-Z]{1,2}[0-9]{'.$figures.'}$/',$gridref);
	}


	/**
	* set up and validate grid square selection using grid reference
	*/
	function setGridRef($gridref)
	{
		$gridref = preg_replace('/[^\w]+/','',strtoupper($gridref)); #assume the worse and remove everything, also not everyone uses the shift key
		//assume the inputs are tainted..
		$ok=$this->validGridRef($gridref);
		if ($ok)
		{
			$ok=$this->_setGridRef($gridref);
		}
		else
		{
			//six figures?
			$matches=array();
			if (preg_match('/^([A-Z]{1,2})(\d\d)\d(\d\d)\d$/',$gridref,$matches))
			{
				$fixed=$matches[1].$matches[2].$matches[3];
				$this->_error('Please enter a 4 figure reference, i.e. '.$fixed.' instead of '.$gridref);
			}
			else
			{
				$this->_error(htmlentities($gridref).' is not a valid grid reference');
			}
		}
		
		return $ok;
	}
	
	/**
	* load square from database
	*/
	function loadFromId($gridsquare_id)
	{
		$db=&$this->_getDB();
		$square = $db->GetRow("select * from gridsquare where gridsquare_id=".$db->Quote($gridsquare_id));	
		if (count($square))
		{		
			//store cols as members
			foreach($square as $name=>$value)
			{
				if (!is_numeric($name))
					$this->$name=$value;
								
			}
			
			//ensure we get exploded reference members too
			$this->_storeGridRef($this->grid_reference);
			
			
		}
	}
	
	/**
	* load square from internal coordinates
	*/
	function loadFromPosition($internalx, $internaly)
	{
		$ok=false;
		$db=&$this->_getDB();
		$square = $db->GetRow("select * from gridsquare where ".
			"x=".$db->Quote($internalx).
			" and y=".$db->Quote($internaly));	
		if (count($square))
		{		
			$ok=true;
			
			//store cols as members
			foreach($square as $name=>$value)
			{
				if (!is_numeric($name))
					$this->$name=$value;
								
			}
			
			//ensure we get exploded reference members too
			$this->_storeGridRef($this->grid_reference);
		}
		return $ok;
	}

	/**
	* set up and validate grid square selection
	*/
	function _setGridRef($gridref)
	{
		$ok=true;

		$db=&$this->_getDB();
		
		//store the reference 
		$this->_storeGridRef($gridref);
			
		//check the square exists in database
		$count=0;
		$square = $db->GetRow("select * from gridsquare where grid_reference=".$db->Quote($gridref));	
		if (count($square))
		{		
			//store cols as members
			foreach($square as $name=>$value)
			{
				if (!is_numeric($name))
					$this->$name=$value;
						
			}
			
			
			//square is good, how many pictures?
			if ($this->imagecount==0)
			{
				//find nearest square for 100km
				$this->findNearby($square['x'], $square['y'], 100);
			}

		}
		else
		{
			$ok=false;
			
			//we don't have a square for given gridref, so first we
			//must figure out what the internal coords are for it
			
			$sql="select * from gridprefix where prefix='{$this->gridsquare}'";
			$prefix=$db->GetRow($sql);
			if (count($prefix))
			{
				$x=$prefix['origin_x'] + $this->eastings;
				$y=$prefix['origin_y'] + $this->northings;
			
				//what's the closes square with land? more than 5km away? disallow
				$ok=$this->findNearby($x,$y, 2, false);
			
				//we only need to know we found one...
				unset($this->nearest);
				
				if ($ok)
				{
					//square is close to land, so we're letting it slide, but we
					//need to create the square - we give it a land_percent of -1
					//to indicate it needs review, and also to prevent it being
					//used in further findNearby calls
					$sql="insert into gridsquare(x,y,percent_land,grid_reference,reference_index) ".
						"values($x,$y,-1,'$gridref',{$prefix['reference_index']})";
					$db->Execute($sql);
					$gridimage_id=$db->Insert_ID();
					
					//ensure we initialise ourselves properly
					$this->loadFromId($gridimage_id);
				}
			
				//we know there are no images, so lets find some nearby squares...
				$this->findNearby($x, $y, 100);
			}
			
			
			if (!$ok)
				$this->_error("$gridref seems to be all at sea! Please contact us if you think this is in error");

		}

		
		return $ok;
	}
	
	/**
	* find a nearby occupied square and store it in $this->nearby
	* returns true if an occupied square was found
	* if occupied is false, finds the nearest land square
	*/
	function findNearby($x, $y, $radius, $occupied=true)
	{
		$db=&$this->_getDB();
		
		//to optimise the query, we scan a square centred on the
		//the required point
		$left=$x-$radius;
		$right=$x+$radius;
		$top=$y-$radius;
		$bottom=$y+$radius;
		
		if ($occupied)
			$ofilter=" and imagecount>0 ";
		else
			$ofilter=" and percent_land>0 ";
			
		$sql="select *, ".
			"power(x-$x,2)+power(y-$y,2) as distance ".
			"from gridsquare where ".
			"x between $left and $right and ".
			"y between $top and $bottom ".
			$ofilter.
			"order by distance asc limit 1";
		
		$square = $db->GetRow($sql);	
		$distance = sqrt($square['distance']);
		if (count($square) && ($distance <= $radius))
		{
			//round off distance
			$square['distance']=round($distance);
			
			//create new grid square and store members
			$this->nearest=new GridSquare;
			foreach($square as $name=>$value)
			{
				if (!is_numeric($name))
					$this->nearest->$name=$value;
									
			}
			
			return true;
		}
		else
		{
			return false;
		}
			
	}
	
	function &getImages()
	{
		$db=&$this->_getDB();
		$images=array();
		
		$i=0;
		$recordSet = &$db->Execute("select gridimage.*,user.realname,user.email,user.website ".
			"from gridimage ".
			"inner join user using(user_id) ".
			"where gridsquare_id={$this->gridsquare_id} ".
			"and moderation_status in ('pending', 'accepted', 'geograph')".
			"order by moderation_status+0 desc,seq_no");
		while (!$recordSet->EOF) 
		{
			$images[$i]=new GridImage;
			$images[$i]->loadFromRecordset($recordSet);
			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close(); 
		
		return $images;
	}
	
	/**
	* Updates the imagecount and has_geographs columns for a square - use this after making changes
	*/
	function updateCounts()
	{
		$db=&$this->_getDB();
		
		$geographs= $db->GetOne("select count(*) from gridimage ".
					"where gridsquare_id={$this->gridsquare_id} and moderation_status='geograph'");

		$has_geographs=$geographs?1:0;

		//count how many images in the square
		$imagecount= $db->GetOne("select count(*) from gridimage ".
			"where gridsquare_id={$this->gridsquare_id} and moderation_status<>'rejected'");

		//update the has_geographs flag
		$db->Query("update gridsquare set has_geographs=$has_geographs,imagecount=$imagecount ".
			"where gridsquare_id={$this->gridsquare_id}");
	}
}


?>