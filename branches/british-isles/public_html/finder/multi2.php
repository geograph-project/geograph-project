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
		$others['content'] = array('title'=>'Collections','url'=>"/content/?q=$u2");
		$others['places'] = array('title'=>'Placenames','url'=>"/finder/places.php?q=$u2");
		$others['users'] = array('title'=>'Contributors','url'=>"/finder/contributors.php?q=$u2");
		$others['sqim'] = array('title'=>'Images by Square','url'=>"/finder/sqim.php?q=$u2");
		$others['text'] = array('title'=>'Simple Text Search','url'=>"/fulltext.php?q=$u2");
		if ($CONF['forums']) {
			$others['discuss'] = array('title'=>'Discussions','url'=>"/finder/discussions.php?q=$u2");
		}

		$others['google'] = array('title'=>'Google Search','url'=>"http://www.google.co.uk/search?q=$u2&sitesearch=".$_SERVER['HTTP_HOST']);
		$others['gimages'] = array('title'=>'Google Images','url'=>"http://images.google.com/images?q=$u2+site:".$_SERVER['HTTP_HOST']);

		$try_words = true;

		if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\b/",$sphinx->qclean,$gr)) {
			//todo - see multi.php
		}

		if ($try_words) {
			##########################################################

			//specifically exclude contributor column!
			$old = $sphinx->q;
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
				LIMIT 60";
				
				$result = array();
				$result['title'] = "Image keyword matches";
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
			//alternate: $places = $gaz->findPlacename($placename);
			$ids = $sphinx->returnIds($pg,'gaz');
			if (!empty($ids) && count($ids)) {
			
				$where = "id IN(".join(",",$ids).")";
										
				$sql = "SELECT name as title,gr as grid_reference,id,localities as type
				FROM placename_index
				WHERE $where
				LIMIT 60";

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
			
				unset($others['places']);
			} else {
				//last ditch attempt incase we have a single match (farms etc not in placename index) 
				
				//todo - see multi.php
			}

			##########################################################

			$ids = $sphinx->returnIds($pg,'user');
			if (!empty($ids) && count($ids)) {
				
				$where = "user_id IN(".join(",",$ids).")";
														
				$sql = "SELECT realname as title,user_id
				FROM user
				WHERE $where
				LIMIT 60";

				$result = array();
				$result['title'] = "Contributor Name matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = '/profile/'.$row['id'];
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." contributors";
				$result['link'] = "/finder/contributors.php?q=$u2";
				$results[] = $result;
				
				unset($others['users']);
			}

			##########################################################

			$sphinx->q = "@title ".$sphinx->q." @source -themed";

			$ids = $sphinx->returnIds($pg,'content_stemmed');
			if (!empty($ids) && count($ids)) {
				
				$sources = array('portal'=>'Portal', 'article'=>'Article', 'blog'=>'Blog Entry', 'trip'=>'Geo-trip', 'gallery'=>'Gallery', 'themed'=>'Themed Topic', 'help'=>'Help Article', 'gsd'=>'Grid Square Discussion', 'snippet'=>'Shared Description', 'user'=>'User Profile', 'category'=>'Category', 'other'=>'Other');

				
				$where = "content_id IN(".join(",",$ids).")";
														
				$sql = "SELECT title,url,images,source,content.user_id,realname
				FROM content
				LEFT JOIN user USING (user_id)
				WHERE $where
				LIMIT 60";

				$result = array();
				$result['title'] = "Collection title matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = $row['url'];
					$row['type'] = $sources[$row['source']];
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." collections";
				$result['link'] = "/content/?q=$u2&scope=all&in=title";
				$results[] = $result;
				
				unset($others['content']);
			} else {
				$sphinx->q = $old." @source -themed";
				
				$ids = $sphinx->returnIds($pg,'content_stemmed');
				if (!empty($ids) && count($ids)) {

					$sources = array('portal'=>'Portal', 'article'=>'Article', 'blog'=>'Blog Entry', 'trip'=>'Geo-trip', 'gallery'=>'Gallery', 'themed'=>'Themed Topic', 'help'=>'Help Article', 'gsd'=>'Grid Square Discussion', 'snippet'=>'Shared Description', 'user'=>'User Profile', 'category'=>'Category', 'other'=>'Other');


					$where = "content_id IN(".join(",",$ids).")";

					$sql = "SELECT title,url,images,source,content.user_id,realname
					FROM content
					LEFT JOIN user USING (user_id)
					WHERE $where
					LIMIT 60";

					$result = array();
					$result['title'] = "Collection keyword matches";
					$result['results'] = array();

					$list = $db->getAll($sql);
					foreach ($list as $row) {
						$row['link'] = $row['url'];
						$row['type'] = $sources[$row['source']];
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

			$ids = $sphinx->returnIds($pg,'category');
			if (!empty($ids) && count($ids)) {
			
				$where = "category_id IN(".join(",",$ids).")";
																		
				$sql = "SELECT imageclass as title,c as images
				FROM category_stat
				WHERE $where
				LIMIT 60";

				$result = array();
				$result['title'] = "Category matches";
				$result['results'] = array();

				$list = $db->getAll($sql);
				foreach ($list as $row) {
					$row['link'] = '/search.php?imageclass='.urlencode($row['title'])."&do=1";
					$result['results'][] = $row;
				}
				$result['count'] = $sphinx->resultCount." categories";
				$result['link'] = "/content/?q=$q2&scope=category";
				$results[] = $result;
			
			}

			##########################################################
		}
		

		
		$smarty->assign_by_ref("results",$results);
		$smarty->assign_by_ref("others",$others);
	}

	$smarty->assign("q",$sphinx->qclean);
	$smarty->assign("fuzzy",$fuzzy);
}

$smarty->display($template,$cacheid);
