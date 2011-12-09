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

if (empty($_GET))
	$template = 'tags_homepage.tpl';

$db = GeographDatabaseConnection(true);

$where = '';
$andwhere = '';

if (isset($_GET['prefix'])) {

	$andwhere = " AND prefix = ".$db->Quote($_GET['prefix']);
	$smarty->assign('theprefix', $prefix = $_GET['prefix']);
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
	$smarty->assign('thetag', $_GET['tag']);
	
	if (empty($prefix) && isset($_GET['exact'])) {
		$andwhere = " AND prefix = ''";
	}
	
}

if (!$smarty->is_cached($template, $cacheid))
{
	if ($template == 'tags_homepage.tpl') {
	
		$taglist = array();
		if (1) {
		$taglist[] = array(
			'title' => 'Recent Descriptions',
			'tags' => $db->CacheGetAll(3600,"SELECT prefix,tag,description,COUNT(*) `count` FROM tag INNER JOIN gridimage_tag USING (tag_id) WHERE description != '' GROUP BY tag_id ORDER BY tag.updated DESC LIMIT 50")
		);
		} else {
		$taglist[] = array(
			'title' => 'Recent Tags',
			'tags' => $db->CacheGetAll(3600,"SELECT prefix,tag,description,COUNT(*) `count` FROM tag_public GROUP BY tag_id ORDER BY created DESC LIMIT 50")
		);
		}
		$taglist[] = array(
			'title' => 'Popular Tags',
			'tags' => $db->CacheGetAll(3600*6,"SELECT prefix,tag,description,COUNT(*) `count` FROM tag_public WHERE prefix != 'top' GROUP BY tag_id ORDER BY count DESC LIMIT 50")
		);
		$taglist[] = array(
			'title' => 'Geographical Context',
			'tags' => $db->CacheGetAll(3600*6,"SELECT prefix,tag,t.description,COUNT(*) `count` FROM category_primary INNER JOIN tag_public t ON (top = tag AND prefix = 'top')  WHERE prefix = 'top' GROUP BY tag_id ORDER BY tag")
		);
		$taglist[] = array(
			'title' => 'Image Buckets',
			'tags' => $db->CacheGetAll(3600*6,"SELECT prefix,tag,description,COUNT(*) `count` FROM tag INNER JOIN gridimage_tag USING (tag_id) WHERE tag.status = 1 AND prefix = 'bucket' GROUP BY tag_id ORDER BY tag LIMIT 30")
		);
		$taglist[] = array(
			'title' => 'Random Tags',
			'tags' => $db->getAll("SELECT prefix,tag,description,COUNT(*) `count` FROM tag_public WHERE prefix != 'top' GROUP BY tag_id ORDER BY RAND() LIMIT 30")
		);
		$smarty->assign_by_ref('taglist', $taglist);
	
	} else {
		
		if (!empty($_GET['tag'])) {
		
			$tags= $db->getAssoc("SELECT tag_id,prefix,tag,canonical,description FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['tag']).$andwhere);
		
			if (!empty($tags)) {
				if (!isset($_GET['exact'])) {
					$bits = array();

					foreach ($tags as $tag_id => $row) {
						if (!empty($row['canonical'])) {
							$bits[] = "tag_id = {$row['canonical']}";
							$bits[] = "canonical = {$row['canonical']}";
						} else {
							$bits[] = "canonical = $tag_id";
						}
					}
					if (!empty($bits)) {
						$more = $db->getAll("SELECT tag_id,prefix,tag FROM tag WHERE status = 1 AND (".implode(" OR ",$bits).")");
						if ($more) {
							$sphinxq = array($sphinxq);
							foreach($more as $tag_id => $row) {
								$sphinxq[]= "tags:".($row['prefix']?"{$row['prefix']} ":'').preg_replace('/[^\w]+/',' ',$row['tag']);
								$tags[$tag_id] = 1;
							}
							if (count($sphinxq) > 1) {
								$sphinxq = '('.implode(' ) | (',$sphinxq).' )';
							} else {
								$sphinxq = implode('',$sphinxq);
							}
						}
					}
				}
				
				if (count($tags) == 1) {
					reset($tags);
					$smarty->assign('onetag',1);
					$smarty->assign('description',$tags[key($tags)]['description']);
				} elseif (empty($prefix)) {
					foreach ($tags as $tag_id => $row) {
						if (!empty($row['tag']) && empty($row['prefix']) && strcasecmp($row['tag'],$_GET['tag']) == 0) {
							$smarty->assign('onetag',1);
							$smarty->assign('needprefix',1);
							$smarty->assign('description',$row['description']);
						}
					}										
				}

	
				if (!empty($_GET['photo']) && !empty($db)) {
					$imagerow = $db->getRow("SELECT grid_reference,x,y,wgs84_lat,wgs84_long FROM gridimage_search WHERE gridimage_id = ".intval($_GET['photo']));
					$smarty->assign('gridref',$imagerow['grid_reference']);
				}
		
				if (!empty($_GET['exclude'])) {
					$exclude= $db->getRow("SELECT * FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['exclude']));
					if (!empty($exclude)) {
						$sphinxq .= " -\"{$exclude['tag']}\"";
						$smarty->assign('exclude',$exclude['tag']);
					}
				}
			
				$imagelist = new ImageList();

				if ($sphinxq && !empty($CONF['sphinx_host']) && (empty($_GET['legacy']) || !empty($imagerow)) ) {
				
					$sphinx = new sphinxwrapper($sphinxq); 

					$sphinx->pageSize = $pgsize = 50; 
					$pg = 1;
				
					if (!empty($imagerow)) {
						$cl = $sphinx->_getClient();

						$cl->SetGeoAnchor('wgs84_lat', 'wgs84_long',  deg2rad($imagerow['wgs84_lat']), deg2rad($imagerow['wgs84_long']) );

						#$cl->SetFilterFloatRange('@geodist', 0.0, floatval($data['d']*1000));

						$sphinx->sort = "@geodist ASC, @relevance DESC, @id DESC";
					} elseif ($_SERVER['HTTP_HOST'] == 'www.geograph.ie') {
						$cl = $sphinx->_getClient();
						$cl->SetFilterRange('scenti',20000000,30000000);
					}
				
					$ids = $sphinx->returnIds($pg,'_images');
				
					if (!empty($ids)) {
						$imagelist->getImagesByIdList($ids);
						$smarty->assign('images',$sphinx->resultCount);
					}
				
				} else {
					$ids = implode(',',array_keys($tags));
			
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
			
				

			} else {
				$smarty->assign('thetag', '');
			}
		
		}
	
		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		if (isset($_GET['prefixes'])) {	
			$prefixes = $db->cacheGetAll(6000,"SELECT LOWER(prefix) AS prefix,COUNT(DISTINCT tag_id) AS tags FROM tag INNER JOIN gridimage_tag gt USING (tag_id) WHERE gt.status = 2 GROUP BY prefix");
			$smarty->assign_by_ref('prefixes', $prefixes);

		} elseif (empty($_GET['tag'])) {
			$tags = $db->cacheGetAll(3600,"SELECT LOWER(tag) AS tag,COUNT(*) AS images FROM tag INNER JOIN gridimage_tag gt USING(tag_id) WHERE gt.status = 2 $andwhere GROUP BY tag ORDER BY tag LIMIT 1000");
	
			$smarty->assign_by_ref('tags', $tags);
		}
	}
} elseif (!empty($_GET['tag'])) {
	$tags= $db->getAssoc("SELECT tag_id,prefix,tag,canonical,description FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['tag']).$andwhere);
	
	if (!isset($_GET['exact'])) {
		$bits = array();

		foreach ($tags as $tag_id => $row) {
			if (!empty($row['canonical'])) {
				$bits[] = "tag_id = {$row['canonical']} OR canonical = {$row['canonical']}";
			} else {
				$bits[] = "canonical = $tag_id";
			}
		}
		if (!empty($bits)) {
			$more = $db->getAll("SELECT tag_id,prefix,tag FROM tag WHERE (".implode(") OR (",$bits).")");
			if ($more) {
				foreach($more as $tag_id => $row) {
					$tags[$tag_id] = 1;
				}
			}
		}
	}

	if (count($tags) == 1) {
		reset($tags);
		$smarty->assign('onetag',1);
		$smarty->assign('description',$tags[key($tags)]['description']);
	} elseif (empty($prefix)) {
                foreach ($tags as $tag_id => $row) {
                        if (!empty($row['tag']) && empty($row['prefix']) && strcasecmp($row['tag'],$_GET['tag']) == 0) {
                                $smarty->assign('onetag',1);
                                $smarty->assign('needprefix',1);
                                $smarty->assign('description',$row['description']);
                        }
                }
        }

}

$smarty->display($template, $cacheid);


