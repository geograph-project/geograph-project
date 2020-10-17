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
		list($gridimage_id,$user_id) = explode(',',$event['event_param']);

		$image=new GridImage();
		$image->gridimage_id = $gridimage_id;
		$image->user_id = $user_id;

		//beware image is not a full image object.
		//this wont be a full normal valid thumbnail, html, but it will have at least created the thumbnail.jpg which is the object of this exercise. 

		$image->getThumbnail(213,160, 2); //urlonly avoids the html tag!

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}

