<?php /**
 * $Project: GeoGraph $
 * $Id$
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


$template = 'tags.tpl';
$cacheid = md5(serialize($_GET));



if (!$smarty->is_cached($template, $cacheid))
{
	
	$db = GeographDatabaseConnection(true);
	
	$where = '';
	$andwhere = '';

	if (isset($_GET['prefix'])) {
	
		$andwhere = " AND prefix = ".$db->Quote($_GET['prefix']);
		$smarty->assign('theprefix', $_GET['prefix']);
	}

	if (!empty($_GET['tag'])) {
		
		if (strpos($_GET['tag'],':') !== FALSE) {
			list($prefix,$_GET['tag']) = explode(':',$_GET['tag'],2);
			
			$andwhere = " AND prefix = ".$db->Quote($prefix);
			$smarty->assign('theprefix', $prefix);
			$sphinxq = "tags:\"$prefix {$_GET['tag']}\"";
		} elseif (isset($_GET['prefix'])) {
			$sphinxq = "tags:\"{$_GET['prefix']} {$_GET['tag']}\"";
		} else {
			$sphinxq = "tags:\"{$_GET['tag']}\"";
		}
		
		$col= $db->getCol("SELECT tag_id FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['tag']).$andwhere);
		
		if (!empty($col)) {

	
			if (!empty($_GET['photo']) && !empty($db)) {
				$imagerow = $db->getRow("SELECT grid_reference,x,y,wgs84_lat,wgs84_long FROM gridimage_search WHERE gridimage_id = ".intval($_GET['photo']));
				$smarty->assign('gridref',$imagerow['grid_reference']);
			}
		
			$ids = implode(',',$col);
			
			if (!empty($_GET['exclude'])) {
				$exclude= $db->getRow("SELECT * FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['exclude']));
				if (!empty($exclude))
					$sphinxq .= " -\"{$exclude['tag']}\"";
			}
			
			$imagelist = new ImageList();

			if ($sphinxq && !empty($CONF['sphinx_host'])) {
				
				$sphinx = new sphinxwrapper($sphinxq); 

				$sphinx->pageSize = $pgsize = 50; 
				$pg = 1;
				
				if (!empty($imagerow)) {
					$cl = $sphinx->_getClient();

					$cl->SetGeoAnchor('wgs84_lat', 'wgs84_long',  deg2rad($imagerow['wgs84_lat']), deg2rad($imagerow['wgs84_long']) );

					#$cl->SetFilterFloatRange('@geodist', 0.0, floatval($data['d']*1000));

					$sphinx->sort = "@geodist ASC, @relevance DESC, @id DESC";
				}
				
				$ids = $sphinx->returnIds($pg,'_images');
				
				if (!empty($ids))
					$imagelist->getImagesByIdList($ids);
				
			} else {
				if (!empty($exclude)) {
					$sql = "select gi.*
						from gridimage_tag gt
							inner join gridimage_search gi using(gridimage_id)
						where status =2
						and gt.tag_id IN ($ids)
						and gt.gridimage_id NOT IN (SELECT gridimage_id FROM gridimage_tag gt2 WHERE gt2.tag_id = {$exclude['tag_id']})
						group by gt.gridimage_id
						order by created desc 
						limit 50";
				} else {
					$sql = "select gi.*
						from gridimage_tag gt
							inner join gridimage_search gi using(gridimage_id)
						where status =2
						and tag_id IN ($ids)
						group by gt.gridimage_id
						order by created desc 
						limit 50";
				}

				$imagelist->_getImagesBySql($sql);
			}
	
			if (!empty($imagelist->images)) {
				$ids = array();
				foreach ($imagelist->images as $idx => $image) {
					$ids[$image->gridimage_id]=$idx;
					$imagelist->images[$idx]->tags = array();
				}


				$db = $imagelist->_getDB(true); //to reuse the same connection

				//TODO, gridimage_search now has tags row. But cant just blindly explode(',' as context have comma in too doh!
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
			}
			
			$smarty->assign('thetag', $_GET['tag']);

		}
		
	}
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	$prefixes = $db->getAll("SELECT LOWER(prefix) AS prefix,COUNT(*) AS tags FROM tag GROUP BY prefix");
	$smarty->assign_by_ref('prefixes', $prefixes);


	if (empty($_GET['tag'])) {
		$tags = $db->getAll("SELECT LOWER(tag) AS tag,COUNT(*) AS images FROM tag INNER JOIN gridimage_tag gt USING(tag_id) WHERE gt.status = 2 $andwhere GROUP BY tag ORDER BY tag LIMIT 1000");
	
		$smarty->assign_by_ref('tags', $tags);
	}
}

$smarty->display($template, $cacheid);

