<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 4028 2008-01-03 21:54:06Z barry $
 * 
 * GeoGraph geographic photo archive project
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

require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
init_session();

$smarty = new GeographPage;

$post_id = intval($_GET['id']);

$template='stuff_post.tpl';
$cacheid=$post_id;



//regenerate?
if (!$smarty->is_cached($template, $cacheid)) {

	if ($post_id) {
		$db = GeographDatabaseConnection(true);
		
		$row = $db->getRow("
			select topic_title
			from geobb_topics
			inner join geobb_posts using (topic_id)
			inner join gridimage_post_highlight using (post_id)
			where post_id = $post_id
			");

		$smarty->assign($row);
		
		if (!empty($row)) {
			$sql = "select gi.*
				from gridimage_post
					inner join gridimage_search gi using(gridimage_id)
				where post_id = $post_id
				limit 50";

			$imagelist = new ImageList();

			$imagelist->_getImagesBySql($sql);
			
			foreach ($imagelist->images as $idx => $image) {
				$imagelist->images[$idx]->imagetakenString = getFormattedDate($image->imagetaken);
			}
			
			$smarty->assign_by_ref('results', $imagelist->images);

		}
	}
}


$smarty->display($template, $cacheid);

