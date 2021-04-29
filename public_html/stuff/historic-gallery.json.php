<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6417 2010-03-04 22:14:53Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');
require_once('geograph/imagelist.class.php');


	$imagelist=new ImageList;

	$sql = " select gridimage_id,showday,title,realname,user_id,original_width,original_height,grid_reference,imagetaken
	 from gallery_ids inner join gridimage_size on (id = gridimage_id) inner join gridimage_search using (gridimage_id)
	 where original_width > 800 and imagetaken < date_sub(now(),interval 10 year) order by baysian desc limit 30";

$sql = str_replace('order by'," and tags rlike '(City|Village|urban)' order by ",$sql);


	$imagelist->_getImagesBySql($sql);

	if (count($imagelist->images)) {
		foreach ($imagelist->images as $i => $image) {
			$imagelist->images[$i]->imagetakenString = getFormattedDate($image->imagetaken);
			$imagelist->images[$i]->fullpath = $imagelist->images[$i]->_getFullpath();
			if ($imagelist->images[$i]->original_width) {
				$imagelist->images[$i]->original = $imagelist->images[$i]->_getOriginalpath();
			}
			foreach (get_object_vars($image) as $key => $value) {
				if (empty($value))
					unset($imagelist->images[$i]->{$key});
			}
		}
		outputJSON($imagelist->images);
	}
