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
* Provides the GridBrowser class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

/**
* Grid browser class
* Provides facilities for building browsing facilities
*/
class GridBrowser
{
	var $db=null;
	var $gridref="";
	var $gridsquare_id=0;
	var $imgcount=0;
	var $errormsg="";
	var $nearest_gridref='';
	var $nearest_distance=0;
	
	
	/**
	* Constructor
	*/
	function GridBrowser()
	{
		$this->db = NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');   
	}
	
	/**
	* store error message
	*/
	function error($msg)
	{
		$this->errormsg=$msg;
	}
	
	/**
	* Get an array of valid grid prefixes
	*/
	function getGridPrefixes()
	{
		return $this->db->GetAssoc("select prefix,prefix from gridprefix order by reference_index,prefix");

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
	
	function validGridRef($gridsquare, $eastings, $northings)
	{
		$ok=true;
		$ok=$ok && preg_match('/^[A-Z]{1,2}$/',$gridsquare);
		$ok=$ok && preg_match('/^[0-9]{1,2}$/',$eastings);
		$ok=$ok && preg_match('/^[0-9]{1,2}$/',$northings);
		return $ok;
	}
	
	/**
	* set up and validate grid square selection
	*/
	function setGridRef($gridsquare, $eastings, $northings)
	{
		//assume the inputs are tainted..
		$ok=$this->validGridRef($gridsquare, $eastings, $northings);
		if ($ok)
		{
			//inputs are good
			$gridref=sprintf("%s%02d%02d", $gridsquare, $eastings, $northings);

			//check the square exists in database
			$count=0;
			$square = $this->db->GetRow("select * from gridsquare where ".$this->db->Quote($gridref)."in (grid_reference1,grid_reference2)");	
			if (count($square))
			{		
				//square is good, how many pictures?
				$this->gridref=$gridref;
				$this->gridsquare_id=$square['gridsquare_id'];
				$this->imgcount = $this->db->GetOne("select count(*) from gridimage where gridsquare_id={$this->gridsquare_id}");
				
				if ($this->imgcount==0)
				{
					//find nearest square for 100km
					$nearest=$this->findNearby($square['x'], $square['y'], 100);
					if ($nearest)
					{
						//get grid reference
						$this->nearest_gridref=$nearest['grid_reference1'];
						if (substr($nearest['grid_reference1'],0,1)=='#')
							$this->nearest_gridref=$nearest['grid_reference2'];
							
						$this->nearest_distance=round($nearest['distance']);	
						
					}
				}
				
			}
			else
			{
				//is it sea? what's the closes square with land? more than 5km away? disallow
				$ok=false;
				$this->error("$gridref seems to be all at sea! Please contact us if you think this is in error");

			}

		}	
		else
		{
			$this->error("Bad grid reference");
		}

		return $ok;
	}
	
	/**
	* set up and validate grid square selection
	*/
	function findNearby($x, $y, $radius)
	{
		//to optimise the query, we scan a square centred on the
		//the required point
		$left=$x-$radius;
		$right=$x+$radius;
		$top=$y-$radius;
		$bottom=$y+$radius;
		
		$sql="select *, ".
			"sqrt(power(x-$x,2)+power(y-$y,2)) as distance ".
			"from gridsquare where ".
			"x between $left and $right and ".
			"y between $top and $bottom and ".
			"imagecount>0 ".
			"order by distance desc";
		
		$square = $this->db->GetRow($sql);	
		if (count($square) && ($square['distance'] <= $radius))
		{
			return $square;
		}
		else
		{
			return false;
		}
			
	}
}


?>