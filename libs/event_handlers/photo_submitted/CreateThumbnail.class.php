<?php
/**
 * $Project: GeoGraph $
 * $Id: MaintainRecentlyModeratedList.class.php 3753 2007-09-05 19:34:21Z barry $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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


require_once("geograph/eventhandler.class.php");
require_once("geograph/gridsquare.class.php");


//filename of class file should correspond to class name, e.g.  myhandler.class.php
class CreateThumbnail extends EventHandler
{
	function processEvent(&$event)
	{
		list($gridimage_id,$user_id,$original) = explode(',',$event['event_param']);

		$db = $this->_getDB();

		$image=new GridImage();
		if (empty($db->readonly))
			$image->_setDB($db);

		$image->gridimage_id = $gridimage_id;
		$image->user_id = $user_id;
		$image->title = 'fake'; //_getResized wont save to memcache if title empty!
		$image->grid_reference = 'fake'; //most code expect it always defined! (just avoids notices)

		//beware image is not a full image object
		//this wont be a full normal valid thumbnail, html, but it will have at least created the thumbnail.jpg which is the object of this exercise

		$image->getThumbnail(120,120, 2); //urlonly avoids the html tag!
		$image->getThumbnail(213,160, 2);

		if ($original) {
			//will call getSize - and the fullsize image should generall already be downloaded. (the origianl may download too to get size)
			$image->getFull(true,true,true); //should generate the 800/1024 versions - as needed (all three params needed for responsive to work!)
		}

		//might as as well create the vision thumbnail too (we probably already downloaded the fullsize)
		if (isset($image->cached_size[0]) && ($image->cached_size[0] < 224 || $image->cached_size[1] < 224) && $image->original_width > 224) {
			$image->getSquareThumbnail(224,224,'path', true, '_original');
                } else {
			$image->getSquareThumbnail(224,224,'path');
		}

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}

