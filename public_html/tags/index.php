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


$redirect = array(
'Lowland landscapes'=>'Lowlands',
'Upland landscapes'=>'Uplands',
'moorland'=>'Moorland',
'Heath, heather moor'=>'Heath, Scrub',
'Wildlife'=>'Wild Animals, Plants and Mushrooms',
'Farming, Market gardening'=>'Farm, Fishery, Market Gardening',
'Park, Open space, Garden'=>'Park and Public Gardens',
'Telecommunications'=>'Communications');

if (!empty($_GET['tag']) && $redirect[$_GET['tag']]) {
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");
	header("Location: ".($url = smarty_function_linktoself(array('name'=>'tag','value'=>$redirect[$_GET['tag']]))) );
	print "<a href=\"".htmlentities($url)."\">moved</a>";
	exit;
}

if ($_SERVER['HTTP_HOST'] == 'www.geograph.ie' &&
               ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
               (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) ) {
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");
	if (!empty($_GET['tag']) && count($_GET) == 1) {
		$url = "http://www.geograph.org.uk/tagged/".str_replace('%2F','/',str_replace('%3A',':',urlencode($_GET['tag'])));
	} else {
		$url = "http://www.geograph.org.uk/tags/?".$_SERVER['QUERY_STRING'];
	}
	header("Location: ".$url);
	print "<a href=\"".htmlentities($url)."\">moved</a>";
	exit;
}

if ((!empty($_GET['photo']) || !empty($_GET['exclude'])) && stripos($_SERVER['HTTP_USER_AGENT'], 'CCBot') === 0) {
	$url = "http://www.geograph.org.uk/tagged/".str_replace('%2F','/',str_replace('%3A',':',urlencode($_GET['tag'])));
// we dont add this for CCBot, as it will immidiately just follow the redirect - even if it done so already.
//        header("Location: ".$url);
	print "<html><head><link rel=\"canonical\" href=\"".htmlentities($url)."\"/></head>";
        print "<body><a href=\"".htmlentities($url)."\">moved</a></body></html>";
	exit;
}

if (strpos($_SERVER['REQUEST_URI'],'/tags/index.php') === 0
	||
	(strpos($_SERVER['REQUEST_URI'],'/tags/') === 0 && !empty($_GET['tag']) && count($_GET) == 1)
   ) {
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");

        if (!empty($_GET['tag']) && count($_GET) == 1) {
                $url = "/tagged/".str_replace('%2F','/',str_replace('%3A',':',urlencode($_GET['tag'])));
        } else {
                $url = "/tags/?".$_SERVER['QUERY_STRING'];
        }
        header("Location: ".$url);
        print "<a href=\"".htmlentities($url)."\">moved</a>";

	exit;
}


init_session();

$smarty = new GeographPage;

if (isset($_GET['tag']) && empty($_GET['tag'])) {
	unset($_GET['tag']);
}

$template = 'tags.tpl';
$cacheid = md5(serialize($_GET));

if (empty($_GET) || !empty($_GET['homepage'])) {
	$template = 'tags_homepage.tpl';
	customExpiresHeader(3600,false,true);
	if ($smarty->caching) {
		 $smarty->caching = 2; // lifetime is per cache
		 $smarty->cache_lifetime = 3600*3;
	}
}

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
		$sphinxq = "tags:\"__TAG__ $prefix {$_GET['tag']} __TAG__\"";
	} elseif (isset($_GET['prefix'])) {
		$sphinxq = "tags:\"__TAG__ {$_GET['prefix']} {$_GET['tag']} __TAG__\"";
	} else {
		$sphinxq = "tags:\"__TAG__ {$_GET['tag']} __TAG__\"";
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
		$taglist[] = array(
			'title' => 'Geographical Context',
			'tags' => $db->CacheGetAll(3600*rand(10,30),"SELECT prefix,tag,tag.description,`count` FROM category_primary INNER JOIN tag ON (top = tag AND prefix = 'top') INNER JOIN tag_stat USING (tag_id) WHERE prefix = 'top' ORDER BY tag")
		);
		$taglist[] = array(
			'title' => 'Popular Tags',
			'tags' => $db->CacheGetAll(3600*rand(10,30),"SELECT prefix,tag,description,`count` FROM tag INNER JOIN tag_stat USING (tag_id) WHERE prefix != 'top' ORDER BY count DESC LIMIT 50")
		);
		if (rand(1,10)>5) {
		$taglist[] = array(
			'title' => 'Recent Descriptions',
			'tags' => $db->CacheGetAll(3600*rand(3,5),"SELECT prefix,tag,description,`count` FROM tag INNER JOIN tag_stat USING (tag_id) WHERE description != '' ORDER BY tag.updated DESC LIMIT 50")
		);
		} else {
		$taglist[] = array(
			'title' => 'Recent Tags',
			'tags' => $db->CacheGetAll(3600*rand(1,5),"SELECT prefix,tag,description,`count` FROM tag INNER JOIN tag_stat USING (tag_id) WHERE prefix != 'top' ORDER BY last_used DESC LIMIT 50")
		);
		}
		$taglist[] = array(
			'title' => 'Image Buckets',
			'tags' => $db->CacheGetAll(3600*rand(30,50),"SELECT prefix,tag,description,COUNT(*) `count` FROM tag INNER JOIN gridimage_tag USING (tag_id) WHERE tag.status = 1 AND prefix = 'bucket' GROUP BY tag_id ORDER BY tag LIMIT 30")
		);

		$rnd = rand(1,1000)/1000;

		$taglist[] = array(
			'title' => 'Random Tags',
			'tags' => $db->getAll("SELECT prefix,tag,`count`,description FROM tag INNER JOIN tag_stat USING (tag_id) WHERE `rnd` > $rnd AND `count` >= 50 ORDER BY rnd LIMIT 30")
		);
		$smarty->assign_by_ref('taglist', $taglist);

	} else {

		if (!empty($_GET['tag'])) {

			$tags= $db->getAssoc("SELECT tag_id,prefix,tag,canonical,description FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['tag']).$andwhere);

			if (!empty($tags)) {

				$others = $db->getAssoc("SELECT tag_id,prefix,tag FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['tag'])." AND prefix != ".$db->Quote($tags['prefix']));
				$smarty->assign_by_ref('others', $others);

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
								$sphinxq[]= "tags:\"__TAG__ ".($row['prefix']?"{$row['prefix']} ":'').preg_replace('/[^\w]+/',' ',$row['tag'])." __TAG__\"";
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

				if (!empty($_SERVER['HTTP_REFERER']) && preg_match('/photo\/(\d+)/',$_SERVER['HTTP_REFERER'],$m)) {
					$_GET['photo'] = intval($m[1]);
				}
				if (!empty($_GET['photo']) && !empty($db)) {
					$imagerow = $db->getRow("SELECT grid_reference,x,y,wgs84_lat,wgs84_long FROM gridimage_search WHERE gridimage_id = ".intval($_GET['photo']));
					$smarty->assign('gridref',$imagerow['grid_reference']);
				}

//temp fix as sphinxwrapper expands hyphenated words - with breaks the the __tag__ trick above.
$sphinxq = str_replace('-',' ',$sphinxq);

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
				$smarty->assign('q', $_GET['tag']);
				$smarty->assign('thetag', '');
			}
		}

		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		if (isset($_GET['prefixes'])) {
			$prefixes = $db->cacheGetAll(6000,"SELECT LOWER(prefix) AS prefix,COUNT(DISTINCT tag_id) AS tags FROM tag INNER JOIN tag_stat USING (tag_id) GROUP BY prefix");
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


