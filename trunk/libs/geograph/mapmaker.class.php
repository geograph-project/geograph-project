<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
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
* Provides the MapMaker class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/


/**
* Map Maker
*
* Makes basic maps from grid square data
* @package Geograph
*/
class MapMaker
{
	var $db=null;
	
	/**
	* adds or updates squares
	*/
	function build($x1, $y1, $x2, $y2, $showgrid=true,$scale = 1,$force = false,$reference_index = 0)
	{
		$this->db = NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');   
	
	
		//ensure coords are in right order
		$left=min($x1, $x2);
		$right=max($x1, $x2);
		$top=max($y1, $y2);
		$bottom=min($y1, $y2);
	
		//figure out filename
		$filename="map_{$left}_{$top}_{$right}_{$bottom}_{$scale}.png";
	
		#elementry caching!
		if (!$force && file_exists($_SERVER['DOCUMENT_ROOT']."/maps/$filename")) {
			return "/maps/$filename";
 		}
	
		//figure out dimensions
		$width=$right-$left;
		$height=$top-$bottom;
		
		//create new image of appropriate size
		$img=imagecreate($width,$height);
		
		$gridcol=imagecolorallocate ($img, 0,0,0);
		
		$blue=imagecolorallocate ($img, 0,0,200);
		imagefill($img,0,0,$blue);
		
		#get greens to use for percentages
		$land=array();
		for ($p=0; $p<=100; $p++)
		{
			//how much green?
			$g=(200*$p)/100;
			
			//how much blue?
			$b=200-$g;
			
			$land[$p]=imagecolorallocate($img, 0,$g,$b);
		
		}
		$dotcolor = imagecolorallocate($img,255,0,0);
		$otherlandcolor = $land[20];
		
		//now plot all squares in the desired area
		$sql="select x,y,percent_land,imagecount,reference_index from gridsquare where ".
			"(x between $left and $right) and ".
			"(y between $bottom and $top) order by imagecount";
			
		$recordSet = &$this->db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields[0];
			$gridy=$recordSet->fields[1];
			
			$imgx=$gridx-$left;
			$imgy=$height-($gridy-$bottom);
			
			if ($reference_index && $recordSet->fields[4] != $reference_index) {
				imagesetpixel($img, $imgx, $imgy, $otherlandcolor);
			} else if ($recordSet->fields[3] > 0) {
				if ($scale > 5) 
					imagesetpixel($img, $imgx, $imgy, $dotcolor);
				else 
					imagefilledrectangle ( $img, $imgx-2, $imgy-2, $imgx+2, $imgy+2, $dotcolor);
			} else {
				imagesetpixel($img, $imgx, $imgy, $land[$recordSet->fields[2]]);
			}
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 
		
		//plot all gridprefixes
		if ($showgrid)
		{
			$sql="select * from gridprefix where ".
				"origin_x between $left-width and $right and ".
				"origin_y between $bottom-height and $top ".
				"and landcount>0";
				if ($reference_index)
					$sql .= " and reference_index = $reference_index";
				//"and reference_index=1 and landcount>0";


			$recordSet = &$this->db->Execute($sql);
			while (!$recordSet->EOF) 
			{
				$origin_x=$recordSet->fields['origin_x'];
				$origin_y=$recordSet->fields['origin_y'];
				$w=$recordSet->fields['width'];
				$h=$recordSet->fields['height'];

				$gleft=$origin_x-$left;
				$gbottom=$height-($origin_y-$bottom);

				$gright=$gleft + $w;
				$gtop=$gbottom - $h;

				//echo "{$recordSet->fields['prefix']} $imgx,$imgy<br>";
				//left
				imageline($img, $gleft, $gtop, $gleft, $gbottom, $gridcol);

				//right
				imageline($img, $gright, $gtop, $gright, $gbottom, $gridcol);

				//top
				imageline($img, $gleft, $gtop, $gright, $gtop, $gridcol);

				//bottom
				imageline($img, $gleft, $gbottom, $gright, $gbottom, $gridcol);

				imagestring ($img, 5, ($gleft+$gright)/2, ($gtop+$gbottom)/2, $recordSet->fields['prefix'], $gridcol);


				$recordSet->MoveNext();
			}
			$recordSet->Close(); 		
		}
		
		//resize to half size
		
		$resized = imagecreatetruecolor($width*$scale,$height*$scale);
		imagecopyresampled($resized, $img, 0, 0, 0, 0, 
			$width*$scale,$height*$scale, $width, $height);

		
		
		imagepng($resized, $_SERVER['DOCUMENT_ROOT'].'/maps/'.$filename);
		imagedestroy($img);
		imagedestroy($resized);
	
		return "/maps/$filename";
	}

	function _trace($msg)
	{
		echo "$msg<br/>";
		flush();
	}	
	function _err($msg)
	{
		echo "<p><b>Error:</b> $msg</p>";
		flush();
	}
	
}

?>