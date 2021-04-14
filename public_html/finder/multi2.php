<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
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

require_once('geograph/global.inc.php');
init_session();




$smarty = new GeographPage;
$template = 'finder_multi2.tpl';

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

	$fuzzy = !empty($_GET['f']);
	
	$q = str_replace("(anything) near",'',$q);
	$q = str_replace("near (anywhere)",'',$q);
	$q = preg_replace('/(\s+)\bnear\b\s+/','$1',$q);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q.'.'.$fuzzy;

	$sphinx->pageSize = $pgsize = 5;


	#$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$cacheid .=".".$pg;

	if (!$smarty->is_cached($template, $cacheid)) {

		$db = GeographDatabaseConnection(true);

		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$results = array();

		##########################################################

		$u2 = urlencode($sphinx->qclean);

		$others['search'] = array('title'=>'Full Image Search','url'=>"/search.php?q=$u2");
		$others['tags'] = array('title'=>'Image Tags','url'=>"/finder/bytag.php?q=$u2");
		$others['groups'] = array('title'=>'Automatic Clusters','url'=>"/finder/groups.php?q=$u2&group=group_ids");
		$others['content'] = array('title'=>'Collections','url'=>"/content/?q=$u2");
		$others['places'] = array('title'=>'Placenames','url'=>"/finder/places.php?q=$u2");
		$others['users'] = array('title'=>'Contributors','url'=>"/finder/contributors.php?q=$u2");
		$others['sqim'] = array('title'=>'Images by Square','url'=>"/finder/sqim.php?q=$u2");
		$others['text'] = array('title'=>'Simple Text Search','url'=>"/full-text.php?q=$u2");
		$others['snippet'] = array('title'=>'Shared Descriptions','url'=>"/snippets.php?q=$u2");
		$others['links'] = array('title'=>'Links Directory','url'=>"http://www.geograph.org/links/search.php?q=$u2");

		if ($CONF['forums']) {
			$others['discuss'] = array('title'=>'Discussions','url'=>"/finder/discussions.php?q=$u2");
		}

		$others['google'] = array('title'=>'Google Search','url'=>"https://www.google.co.uk/search?q=$u2&sitesearch=".$_SERVER['HTTP_HOST']);
		$others['gimages'] = array('title'=>'Google Images','url'=>"https://www.google.co.uk/search?q=$u2+site:".$_SERVER['HTTP_HOST']."&tbm=isch");

		$others['browser'] = array('title'=>'Geograph Browser','url'=>"/browser/#!/q=$u2");

		$old = $sphinx->q;
		$try_words = true;

		if (is_numeric($q)) {

			$where = "gridimage_id = $q";

			$sql = "SELECT gridimage_id,grid_reference,title,realname,user_id
			FROM gridimage_search
			WHERE $where
			LIMIT {$sphinx->pageSize}";

			$list = $db->getAll($sql);
			if ($list) {
				$result = array();
				$result['title'] = "Image ID $q";
				$result['results'] = array();

				foreach ($list as $row) {
					$row['link'] = '/photo/'.$row['gridimage_id'];
					$result['results'][] = $row;
				}
				$results[] = $result;
			}
		}


		if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\b/",$sphinx->qclean,$gr)) {
			$square=new GridSquare;
			$grid_ok=$square->setByFullGridRef($sphinx->qclean,true);

			if ($grid_ok) {
				$gr = $square->grid_reference;

				if ($square->imagecount) {
					$where = "grid_reference = '{$gr}'";

					$sql = "SELECT gridimage_id,title,realname,user_id
					FROM gridimage_search
					WHERE $where
					LIMIT {$sphinx->pageSize}";

					$result = array();
					$result['title'] = "Images in {$gr}";
					$result['results'] = array();

					$list = $db->getAll($sql);
					foreach ($list as $row) {
						$row['link'] = '/photo/'.$row['gridimage_id'];
						$result['results'][] = $row;
					}
					$result['count'] = $square->imagecount." images";
					$result['link'] = "/gridref/".urlencode($sphinx->qclean);
					$results[] = $result;

					$others['search2'] = array('title'=>'Images Near '.$gr,'url'=>"/search.php?gridref=$u2&do=1");

				} else {
					$sphinx->processQuery();
					$ids = $sphinx->returnIds($pg,'_images');
					if (!empty($ids) && count($ids)) {

						$where = "gridimage_id IN(".join(",",$ids).")";

						$sql = "SELECT gridimage_id,grid_reference,title,realname,user_id
						FROM gridimage_search
						WHERE $where
						LIMIT {$sphinx->pageSize}";

						$result = array();
						$result['title'] = "Images near {$gr}";
						$result['results'] = array();

						$list = $db->getAll($sql);
						foreach ($list as $row) {
							$row['link'] = '/photo/'.$row['gridimage_id'];
							$result['results'][] = $row;
						}
						$result['count'] = $sphinx->resultCount." images within 2km";
						$result['link'] = "/search.php?gridref=$gr&do=1";
						$results[] = $result;

						unset($others['search']);
					}

					$sphinx->q = $old;
				}

				if ($square->natgrlen == 6) { //centisquare

					//todo - sphinx can do this...

					require_once('geograph/conversions.class.php');
					$conv = new Conversions;
					list($centi,$len) = $conv->national_to_gridref(
					$square->getNatEastings()-$correction,
					$square->getNatNorthings()-$correction,
					6,
					$square->reference_index,false);

					$others['search3'] = array('title'=>'Images in centisquare '.$sphinx->qclean,'url'=>"/gridref/$gr?centi=$centi");
				}
			}
		}

		if ($try_words) {
			##########################################################

			//specifically exclude contributor column!

			if (!preg_match('/@\w+/',$old)) {
				//todo, maybe extend this to myriad etc?
				$sphinx->q = "@(title,comment,imageclass,snippet,snippet_title) ".$sphinx->q;
			}

			$ids = $sphinx->returnIds($pg,'_images');
			if (!empty($ids) && count($ids)) {

				$where = "gridimage_id IN(".join(",",$ids).")";

				$sql = "SELECT gridimage_id,grid_reference,title,realname,user_id
				FROM gridimage_search
				WHERE $where
				LIMIT {$sphinx->pageSize}";

				$result = array();
				$result['title'] = "Image keyword matches";
				if ($q != trim($_GET['q'])) {
					$result['title'] .= " for [ $q ]";
				}
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = '/photo/'.$row['gridimage_id'];
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." images";
				$result['link'] = "/search.php?searchtext=$u2&do=1";
				$results[] = $result;

				unset($others['search']);
			}

			$sphinx->q = $old;

			##########################################################

			if (!empty($CONF['sphinxql_dsn']) && !preg_match('/[@:]/',$sphinx->q)) {

		                $prev_fetch_mode = $ADODB_FETCH_MODE;
		                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		                $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to search server");

				$attribute = "group";
		                $where = "match(".$sph->Quote("@groups ".$sphinx->q).")";


	                        $rows = $sph->getAll($sql = "
	                        select id,{$attribute}s,{$attribute}_ids,COUNT(*) as images,GROUPBY() as group,grid_reference
	                        from sample8
	                        where $where
	                        group by {$attribute}_ids
	                        order by images desc
	                        limit 50"); //we deliberately overfetch, because some may be filtered!

				if (!empty($rows)) {
	                                $result = array();
                                	$result['title'] = "Automatic Clusters";
                        	        if ($q != trim($_GET['q'])) {
                	                        $result['title'] .= " for [ $q ]";
        	                        }
	                                $result['results'] = array();

					$words = explode(' ',trim(preg_replace('/[^\w]+/',' ',$sphinx->q)));
					$crit = "/(".implode('|',$words).")/i";
		                        foreach ($rows as $idx => $row) {
		                                $ids = explode(',',$row[$attribute.'_ids']);
	        	                        $names = explode('_SEP_',$row[$attribute.'s']);array_shift($names); //the first is always blank!
                        	       		$row['title'] = trim($names[array_search($row['group'],$ids)]);

						if (!empty($words) && !preg_match($crit,$row['title']))
							continue;

	                                        $row['link'] = "/browser/#!/q=$u2+groups%3A".urlencode($row['title']);
        	                                $result['results'][] = $row;

						if (count($result['results']) == $sphinx->pageSize)
							break;
               			        }
					if (!empty($result['results'])) {
						$data = $sph->getAssoc("SHOW META");

        	        	                $result['count'] = $data['total_found']." clusters";
        		                        $result['link'] = "/finder/groups.php?q=$u2&group=group_ids";
	                	                $results[] = $result;
					}
                                }

				unset($others['groups']);
			}


			##########################################################
			//alternate: $places = $gaz->findPlacename($placename);
			$ids = $sphinx->returnIds($pg,'gaz');
			unset($others['places']); //now we checked this no point showing it! (we know it wont work!)
			if (!empty($ids) && count($ids)) {

				$where = "id IN(".join(",",$ids).")";

				$sql = "SELECT name as title,gr as grid_reference,id,localities as type
				FROM placename_index
				WHERE $where
				LIMIT {$sphinx->pageSize}";

				$result = array();
				$result['title'] = "Placename matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = '/search.php?placename='.$row['id']."&do=1";
					$bits = explode(', ',$row['type']);
					if (count($bits) > 2) {
						$row['type'] = ", ".implode(", ",array_slice($bits,0,2));
					}
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." places";
				$result['link'] = "/finder/places.php?q=$u2";
				$results[] = $result;

			} elseif (strpos($sphinx->q,' ') !== FALSE) {
				$sphinx->q = "@name (^".implode('$|^',preg_split('/ +/',$sphinx->q)).'$)';
				$sphinx->pageSize *= 3;
				$ids = $sphinx->returnIds($pg,'gaz_stemmed'); //used stemmed because it has enable_star
				$sphinx->pageSize /= 3;
				if (!empty($ids) && count($ids)) {

					$where = "id IN(".join(",",$ids).")";

					$sql = "SELECT name,gr as grid_reference,id,localities as type
					FROM placename_index
					WHERE $where
					LIMIT {$sphinx->pageSize}";

					$result = array();
					$result['title'] = "Placename partial matches";
					$result['results'] = array();

					$list = $db->getAll($sql);
					foreach ($list as $row) {
						$left = trim(preg_replace('/ +/',' ',str_ireplace($row['name'],'',$old)));

						$row['link'] = '/search.php?searchtext='.urlencode($left).'&placename='.$row['id']."&do=1";
						$row['title'] = "Search for '$left' near ".$row['name'];

						$bits = explode(', ',$row['type']);
						if (count($bits) > 2) {
							$row['type'] = ", ".implode(", ",array_slice($bits,0,2));
						}
						$result['results'][] = $row;
					}
					$result['count'] = $sphinx->resultCount." places";
					$result['link'] = "/finder/places.php?q=".urlencode($sphinx->q);
					$results[] = $result;
				}

				$sphinx->q = $old;

			} else {
				//last ditch attempt incase we have a single match (farms etc not in placename index)

				//todo - see multi.php
			}

			##########################################################

			//todo - change the query to promote short matches (eg | "^bridge$") - field end doesnt work right now :(

			$ids = $sphinx->returnIds($pg,'tags');
			if (!empty($ids) && count($ids)) {

				$where = "tag_id IN(".join(",",$ids).")";

				$sql = "SELECT if (tag.prefix != '' and not (tag.prefix='term' or tag.prefix='category' or tag.prefix='cluster' or tag.prefix='wiki'),concat(tag.prefix,':',tag.tag),tag.tag) as title, count(*) as images
				FROM tag_public tag
				WHERE $where
				GROUP BY tag_id
				LIMIT {$sphinx->pageSize}";

				$result = array();
				$result['title'] = "Tag matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = '/tagged/'.urlencode2($row['title']);
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." tags";
				$result['link'] = "/finder/tags.php?q=$u2";
				$results[] = $result;
			}

			##########################################################

			$ids = $sphinx->returnIds($pg,'user');
			if (!empty($ids) && count($ids)) {

				$where = "user_id IN(".join(",",$ids).")";

				$sql = "SELECT realname as title,nickname as type,user.user_id,images
				FROM user
				INNER JOIN user_stat USING (user_id)
				WHERE $where
				LIMIT {$sphinx->pageSize}";

				$result = array();
				$result['title'] = "Contributor Name matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = '/profile/'.$row['user_id'];
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." contributors";
				$result['link'] = "/finder/contributors.php?q=$u2";
				$results[] = $result;

				unset($others['users']);
			}

			##########################################################

			$data = file_get_contents("http://www.geograph.org/links/search.json.php?q=".urlencode($old));
			$decode = json_decode($data,true);
			if (!empty($decode['rows'])) {

				$result = array();
				$result['title'] = "Links Directory matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($decode['rows'] as $row1) {
					$row = array();
					$row['title'] = $row1['title'];
					$row['link'] = $row1['url'];
					$row['type'] = 'link';
					$result['results'][] = $row;
				}
				if (preg_match('/of (\d+)/',$decode['info'],$m))
					$result['count'] = $m[1]." links";
				$result['link'] = "http://www.geograph.org/links/search.php?q=$u2";
				$results[] = $result;

				unset($others['links']);
			}

			##########################################################

//todo - worth striping out any of the other collections to dedicated searchs (or maybe if collections count is over 20 say?)

			$sphinx->q = "@title ".$sphinx->q." @source -themed -user -category -portal"; //we look at user above, and category below

			$ids = $sphinx->returnIds($pg,'content_stemmed');
			if (!empty($ids) && count($ids)) {

				$where = "content_id IN(".join(",",$ids).")";

				$sql = "SELECT title,url,images,source,content.user_id,realname
				FROM content
				LEFT JOIN user USING (user_id)
				WHERE $where
				LIMIT {$sphinx->pageSize}";

				$result = array();
				$result['title'] = "Collection title matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = $row['url'];
					$row['type'] = $CONF['content_sources'][$row['source']];
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." collections";
				$result['link'] = "/content/?q=$u2&scope=all&in=title";
				$results[] = $result;

				//actully we only done a content TITLE search,  whole document matches could be different
				$others['content'] = array('title'=>'Collections (non title)','url'=>"/content/?q=$u2&scope=all&in=nottitle");

			} else {
				$sphinx->q = $old." @source -themed -user -category";

				$ids = $sphinx->returnIds($pg,'content_stemmed');
				if (!empty($ids) && count($ids)) {

					$where = "content_id IN(".join(",",$ids).")";

					$sql = "SELECT title,url,images,source,content.user_id,realname
					FROM content
					LEFT JOIN user USING (user_id)
					WHERE $where
					LIMIT {$sphinx->pageSize}";

					$result = array();
					$result['title'] = "Collection keyword matches";
					$result['results'] = array();

					$list = $db->getAll($sql);
					foreach ($list as $row) {
						$row['link'] = $row['url'];
						$row['type'] = $CONF['content_sources'][$row['source']];
						$result['results'][] = $row;
					}
					$result['count'] = $sphinx->resultCount." collections";
					$result['link'] = "/content/?q=$u2&scope=all";
					$results[] = $result;

					unset($others['content']);
				}
			}

			$sphinx->q = $old;

			##########################################################

			$sphinx->q = $old;

			$ids = $sphinx->returnIds($pg,'document_stemmed');
			if (!empty($ids) && count($ids)) {

				$where = "content_id IN(".join(",",$ids).")";

				$sql = "SELECT title,url,images,source,content.user_id,realname
				FROM content
				LEFT JOIN user USING (user_id)
				WHERE $where
				LIMIT {$sphinx->pageSize}";

				$result = array();
				$result['title'] = "Project Info Page matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = $row['url'];
					$row['type'] = $CONF['content_sources'][$row['source']];
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." pages";
				$result['link'] = "/content/documentation.php?q=$u2";
				$results[] = $result;
			}

			##########################################


			$ids = $sphinx->returnIds($pg,'category');
			if (!empty($ids) && count($ids)) {

				$where = "category_id IN(".join(",",$ids).")";

				$sql = "SELECT imageclass as title,c as images
				FROM category_stat
				WHERE $where
				LIMIT {$sphinx->pageSize}";

				$result = array();
				$result['title'] = "Category matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = '/search.php?imageclass='.urlencode($row['title'])."&do=1";
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." categories";
				$result['link'] = "/content/?q=$u2&scope=category";
				$results[] = $result;
			}

			##########################################################
		}

/*
todo...?

if (count($results) == 1 && strpos($results[0]['link'],'/search.php') === 0)
	header("Location: {$results[0]['link']}");
	exit;
}

*/

		$smarty->assign_by_ref("results",$results);
		$smarty->assign_by_ref("others",$others);
	}

	$smarty->assign("q",$sphinx->qclean);
	$smarty->assign("fuzzy",$fuzzy);
}

$smarty->display($template,$cacheid);

