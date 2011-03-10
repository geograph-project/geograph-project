<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 7069 2011-02-04 00:06:46Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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



init_session();

$smarty = new GeographPage;


$template = 'tags2.tpl';
$cacheid = "example".md5(serialize($_GET));



if (!$smarty->is_cached($template, $cacheid))
{
	
	$db = GeographDatabaseConnection(true);
	
	$ids = $db->getCol("SELECT gridimage_id,count(*) c FROM gridimage_tag WHERE status = 2 GROUP BY gridimage_id HAVING c BETWEEN 2 AND 7 ORDER BY NULL LIMIT 50");

	$ids = implode(',',$ids);

				$sql = "select gi.*
					from gridimage_search gi 
					where gridimage_id in ($ids)
					limit 50";

			$imagelist = new ImageList();

			$imagelist->_getImagesBySql($sql);

			$ids = array();
			foreach ($imagelist->images as $idx => $image) {
				$ids[$image->gridimage_id]=$idx;
				$imagelist->images[$idx]->tags = array();
			}
			$db = $imagelist->_getDB(true); //to reuse the same connection

			if ($idlist = implode(',',array_keys($ids))) {
				$sql = "SELECT gridimage_id,tag,prefix FROM tag INNER JOIN gridimage_tag gt USING (tag_id) WHERE gt.status = 2 AND gridimage_id IN ($idlist) ORDER BY tag";			

				$tags = $db->getAll($sql);
				if ($tags) {
					foreach ($tags as $row) {
						$idx = $ids[$row['gridimage_id']];
						$imagelist->images[$idx]->tags[] = $row;
					}
				}
			}

			$smarty->assign_by_ref('results', $imagelist->images);
			
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$smarty->assign('example',1);
}

$smarty->display($template, $cacheid);

