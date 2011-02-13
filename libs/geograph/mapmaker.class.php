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
	*
	* $usr:  > 0 => number of images by user $usr
	*          0 => land percentage + image count
	*         -1 => land percentage
	*         -2 => image count
	*        <-2 => reserved, defaults to 0
	* $bw:     colour / grey scale (colour only for  $usr == 0)
	* $limit:  count == 0      => colour 0
	*          count == $limit => colour 255
	*          $limit <= 0 => defaults to 100 for land percentages and to 1 for image counts
	* $geo:    number of geographs instead of images
	* $cid/$level1/$level2:
	*          $level < -1 => ignore
	*          $cid  < 0   => ignore
	*
	*          valid $cid and $level1    => show corresponding percentage (gridsquare_percentage)
	*          valid $level1 and $level2 => show (sum($level1) - sum($level2)) * 10 + 50  [ compare hierarchie levels ]
	*          valid $level1             => show sum($level1)
	*/
	function build($x1, $y1, $x2, $y2, $showgrid=true,$scale = 1,$force = false,$reference_index = 0,$usr = 0,$bw = false,$limit = 0,$geo = false, $level1 = -2, $level2 = -2, $cid = -1)
	{
		$this->db = NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');   
	
	
		//ensure coords are in right order
		$left=min($x1, $x2);
		$right=max($x1, $x2);
		$top=max($y1, $y2);
		$bottom=min($y1, $y2);

		if ($usr < -2)
			$usr = 0;
		//if ($usr == 0)
		//	$colour = true;
		if ($limit == 0) {
			if ($usr == 0 || $usr == -1)
				$limit = 100;
			else
				$limit = 1;
		}
		if ($geo||$usr > 0||$level1 < -1) {
			$level1 = -2;
			$level2 = -2;
			$cid = -1;
		} elseif ($cid >= 0) {
			$level2 = -2;
		} else {
			$cid = -1;
			if ($level2 < -1 || $level1 == $level2)
				$level2 = -2;
		}
	
		//figure out filename
		$filename="map_{$left}_{$top}_{$right}_{$bottom}_{$scale}_{$reference_index}_{$showgrid}_{$usr}_{$bw}_{$limit}_{$geo}_{$level1}_{$level2}_{$cid}.png";
	
		#elementry caching!
		if (!$force && file_exists($_SERVER['DOCUMENT_ROOT']."/maps/$filename")) {
			return "/maps/$filename";
 		}
	
		//figure out dimensions
		$width=$right-$left+1;
		$height=$top-$bottom+1;
		
		//create new image of appropriate size
		$img=imagecreate($width,$height);
		
		$gridcol=imagecolorallocate ($img, 0,0,0);

		if ($bw) {
			$bg=imagecolorallocate ($img, 255,255,255);
			$dotcolor = imagecolorallocate($img,255,255,255);
		} else {
			$bg=imagecolorallocate ($img, 0,0,200);
			$dotcolor = imagecolorallocate($img,255,0,0);
		}
		imagefill($img,0,0,$bg);
		
		#get greens to use for percentages
		$land=array();
		for ($p=0; $p<=100; $p++)
		{
			if ($bw) {
				// 0   -> 255
				// 100 -> 0
				// grid shader: round(((255-$col)*100)/255);
				$g = round((100-$p)/100*255);//FIXME rounding
				$land[$p]=imagecolorallocate($img, $g,$g,$g);
			} else {
				//how much green?
				$g=(200*$p)/100;
				
				//how much blue?
				$b=200-$g;
				
				$land[$p]=imagecolorallocate($img, 0,$g,$b);
			}
		
		}
		$otherlandcolor = $land[20];

		if ($geo||$usr > 0) {
			$sql_cond = '(gi.moderation_status is not NULL)';
			if ($geo)
				$sql_cond .= " and (gi.moderation_status='geograph')";
			if ($usr > 0)
				$sql_cond .= " and (gi.user_id='$usr')";
			$sql_imagecount = "sum($sql_cond) as imagecount";
			$sql_table = 'gridsquare gs left join gridimage gi using(gridsquare_id)';
			$sql_group = ' group by gs.gridsquare_id';
		} else {
			$sql_imagecount = 'imagecount';
			$sql_table = 'gridsquare gs';
			$sql_group = '';
		}
		if ($usr == 0) {
			$sql_order = ' order by imagecount';
		} else {
			$sql_order = '';
		}
		$sql_percent = '';
		$sql_where = '';
		if ($level1 >= -1) {
			if ($cid >= 0) {
				$sql_table .= ' inner join gridsquare_percentage gp using(gridsquare_id)';
				$sql_where = " and gp.level='$level1' and gp.community_id='$cid'";
				$sql_percent = 'gp.percent as ';
			} elseif ($level2 >= -1) {
				$sql_table .= ' inner join gridsquare_percentage gp using(gridsquare_id)';
				$sql_where = " and gp.level in ('$level1','$level2')";
				$sql_percent = "10*sum(if(gp.level='$level1',gp.percent,-gp.percent))+50 as ";
				$sql_group = ' group by gs.gridsquare_id';
			} else {
				$sql_table .= ' inner join gridsquare_percentage gp using(gridsquare_id)';
				$sql_where = " and gp.level='$level1'";
				$sql_percent = 'sum(gp.percent) as ';
				$sql_group = ' group by gs.gridsquare_id';
			}
		}

		//now plot all squares in the desired area
		$sql="select x,y,$sql_percent percent_land,$sql_imagecount,gs.reference_index,gs.gridsquare_id from $sql_table where ".
			"(x between $left and $right) and ".
			"(y between $bottom and $top)$sql_where$sql_group$sql_order";
		trigger_error("map file: $filename", E_USER_NOTICE);
		trigger_error("     sql: $sql", E_USER_NOTICE);
			
		$recordSet = &$this->db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$gridx=$recordSet->fields[0];
			$gridy=$recordSet->fields[1];
			$pland=min(100,max(0,$recordSet->fields[2]));
			$imag =$recordSet->fields[3];
			$ri =  $recordSet->fields[4];
			
			$imgx=$gridx-$left;
			$imgy=$height-($gridy-$bottom)-1;
			
			if ($reference_index && $ri != $reference_index) {
				imagesetpixel($img, $imgx, $imgy, $otherlandcolor);
			} else if ($usr == 0 && $imag > 0) {
				if ($scale > 5) 
					imagesetpixel($img, $imgx, $imgy, $dotcolor);
				else 
					imagefilledrectangle ( $img, $imgx-2, $imgy-2, $imgx+2, $imgy+2, $dotcolor);
			} else {
				if ($usr == -2 || $usr > 0)
					$val = $imag;
				else
					$val = $pland;
				$val = round ($val * 100 / $limit);
				if ($val > 100)
					$val = 100;
				else if ($val < 0)
					$val = 0;
				imagesetpixel($img, $imgx, $imgy, $land[$val]);
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
