<?php
/**
 * $Project: GeoGraph $
 * $Id: token.class.php 3183 2007-03-20 21:50:37Z barry $
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


require_once("geograph/token.class.php");
require_once("geograph/gridimage.class.php");
/**
* Provides a class for managing picture of the day
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision: 3183 $
*/
class PictureOfTheDay
{
	/**
	 * today's image, as selected by initToday
	 */
	var $gridimage_id;
	var $width=381; #FIXME width/height not used?
	var $height=255;
	
	public function __construct($w=381,$h=255) #FIXME width/height not used?
	{
		$this->width=$w;
		$this->height=$h;
	}
	function PictureOfTheDay($w=381,$h=255)
	{
		self::__construct($w, $h);
	}
	
	function initToday()
	{
		global $CONF;
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  
		$upper=$CONF['potd_daysperimage']-1;
	
		#$gridimage_id=$db->GetOne("select gridimage_id from gridimage_daily where to_days(showday)=to_days(now())");
		$gridimage_id=$db->GetOne("select gridimage_id from gridimage_daily where (to_days(now())-to_days(showday)) between 0 and $upper order by (to_days(now())-to_days(showday))");
		if (empty($gridimage_id))
		{
			//get timestamp from db server
			$now=$db->GetOne("select now()");
			
			//lock the table to avoid a midnight race
			$db->Execute("lock tables gridimage_daily write,gridimage_search write");
			
			//we've got our lock, so lets check we weren't beaten to the punch
			#$gridimage_id=$db->GetOne("select gridimage_id from gridimage_daily where to_days(showday)=to_days(now())");
			$gridimage_id=$db->GetOne("select gridimage_id from gridimage_daily where (to_days(now())-to_days(showday)) between 0 and $upper order by (to_days(now())-to_days(showday))");
			if (empty($gridimage_id))
			{
				//ok, there is still no image for today, and we have a
				//lock on the table - assign the first available image
				//ordered by number - giving preference to geograph and highly voted images 
				$gridimage_id=$db->GetOne("select gridimage_id from gridimage_daily inner join gridimage_search using (gridimage_id) where showday is null order by moderation_status desc,(abs(datediff(now(),imagetaken)) mod 365 div 14) asc,(vote_baysian > 3) desc,crc32(gridimage_id)");

				if (!empty($gridimage_id)) {
					$db->Execute("update gridimage_daily set showday='$now' where gridimage_id = $gridimage_id");

					//refetch
					$gridimage_id=$db->GetOne("select gridimage_id from gridimage_daily where to_days(showday)=to_days(now())");
				}
			}
				
			//release our stranglehold
			$db->Execute("unlock tables");
		}
		
		if (empty($gridimage_id))
		{
			//select the most recent old one
			$gridimage_id=$db->GetOne("select gridimage_id from gridimage_daily " .
					"where to_days(showday)<to_days(now()) " .
					"order by (to_days(now())-to_days(showday))");
		}
		
		$this->gridimage_id=$gridimage_id;
	}
	
	function assignToSmarty(&$smarty,$gridimage_id = 0)
	{
		global $CONF;
		if (empty($gridimage_id)) {
			$this->initToday();
		} else {
			$this->gridimage_id = $gridimage_id;
		}
		
		$pictureoftheday=array();
		$pictureoftheday['gridimage_id']=$this->gridimage_id;
		$pictureoftheday['width']=$this->width;
		$pictureoftheday['height']=$this->height;
		$pictureoftheday['image']=new GridImage($this->gridimage_id);
		$pictureoftheday['image']->compact();
		$pictureoftheday['search']=$CONF['searchid_potd'];
		
		$smarty->assign('pictureoftheday', $pictureoftheday);
	
		$this->image =& $pictureoftheday['image'];
	}

	function serveImageFromToken($tokenstr)
	{
		$token=new Token;
		if ($token->parse($tokenstr))
		{
		    $this->width=$token->getValue("w");
		    $this->height=$token->getValue("h");
		    $this->gridimage_id=$token->getValue("i");
		    
		    $image=new GridImage($this->gridimage_id);
		}
		else
		{
			header("HTTP/1.0 403 Bad Token");
		}
	}	
}
?>
