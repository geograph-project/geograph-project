<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2010 Barry Hunter (geo@barryhunter.co.uk)
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

session_cache_limiter('none');
init_session();

$smarty = new GeographPage;
$template='related.tpl';	

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

$cacheid = empty($_GET['id'])?0:intval($_GET['id']);

if (!$smarty->is_cached($template, $cacheid)) {

	if (!empty($_GET['id'])) {
	
		$image=new GridImage();
		$ok = $image->loadFromId($_REQUEST['id']);

		if (!$ok || $image->moderation_status=='rejected') {
			//clear the image
			$image=new GridImage;
			header("HTTP/1.0 410 Gone");
			header("Status: 410 Gone");
			$template = "static_404.tpl";
		} else {

			$results = array();

			###########################

			$res = check_images(
				"Similar Title",
				$image->grid_reference,
				"title:".preg_replace('/\b\d+\b/',' ',$image->title)
			);
			if (!empty($res)) $results[] = $res;

			###########################

			if (!preg_match('/(0000|-00)/',$image->imagetaken)) {
				$res = check_images(
					"Taken by same Contributor on the same Day near {$image->grid_reference}",
					$image->grid_reference,
					"user_id:{$image->user_id} takenday:".str_replace('-','',$image->imagetaken)
				);
				if (!empty($res)) $results[] = $res;

				###########################

				$res = check_images(
					"Taken the same Day near {$image->grid_reference}",
					$image->grid_reference,
					"takenday:".str_replace('-','',$image->imagetaken)
				);
				if (!empty($res)) $results[] = $res;
			}

			###########################

			$res = check_images(
				"In similar category near {$image->grid_reference}",
				$image->grid_reference,
				"category:{$image->imageclass}"
			);
			if (!empty($res)) $results[] = $res;

			###########################

			$db = GeographDatabaseConnection(true);

			$ids = $db->CacheGetCol(3600*6,"SELECT from_gridimage_id
						FROM gridimage_backlink ba
						WHERE ba.gridimage_id = {$image->gridimage_id}");
			if (!empty($ids)) {
				$res = check_images(
					"Linking to this image",
					$ids,
					"comment:{$image->gridimage_id}"
				);
				if (!empty($res)) $results[] = $res;
			}

			###########################

			$image->loadSnippets();
			if ($image->snippet_count) {
				foreach ($image->snippets as $idx => $row) {
					$ids = $db->GetCol("SELECT gridimage_id
									FROM gridimage_snippet ba
									WHERE snippet_id = {$row['snippet_id']} AND gridimage_id < 4294967296");

					if (!empty($ids)) {
						$res = check_images(
							"Using '{$row['title']}' shared description",
							$ids,
							"snippet_id:{$row['snippet_id']}"
						);
						if (!empty($res)) {
							$res['link'] = "/snippet/{$row['snippet_id']}";
							$results[] = $res;
						}
					}
				}
			}

			###########################

			$labels = $db->getCol("SELECT label 
						FROM gridimage_group 
						WHERE gridimage_id = {$image->gridimage_id} AND label != '(Other)'
						ORDER BY score DESC LIMIT 5");
			if (!empty($labels)) {
				srand($image->gridimage_id);
				foreach ($labels as $idx => $label) {
					if (rand(1,10) > 7) {
						$l2 = $db->Quote($label);
						$ids = $db->GetCol("SELECT gridimage_id
									FROM gridimage_group gg
									INNER JOIN gridimage_search USING (gridimage_id)
									WHERE label = $l2 AND grid_reference = '{$image->grid_reference}'");

						if (!empty($ids)) {
							$res = check_images(
								"Marked with '{$label}'",
								$ids,
								"text:$label"
							);
							if (!empty($res)) {
								$res['link'] = "/search.php?gridref={$image->grid_reference}&amp;distance=1&amp;orderby=score+desc&amp;displayclass=full&amp;cluster2=1&amp;label=".urlencode($label)."&amp;do=1";
								$results[] = $res;
							}
						}
					} else {
						$res = check_images(
							"Matching '{$label}'",
							'',
							"text:{$label} grid_reference:{$image->grid_reference}"
						);
						if (!empty($res)) $results[] = $res;
					}
				}
			}

			###########################

			$res = check_images(
				"By same Contributor near {$image->grid_reference}",
				$image->grid_reference,
				"user_id:{$image->user_id}"
			);
			if (!empty($res)) $results[] = $res;

			###########################


			$smarty->assign_by_ref('results', $results);

		}
		$image->image_taken=$image->getFormattedTakenDate();
		$smarty->assign_by_ref('image', $image);
	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		$template = "static_404.tpl";
	}
}

$smarty->display($template,$cacheid);


####################

	function check_images($title,$gridref,$q) {
		static $used;
		global $image;

		if (is_array($gridref)) {
			$ids = $gridref;
			$count = count($ids);
		} else {
			$sphinx = new sphinxwrapper($gridref.' '.$q);
			$sphinx->pageSize = 25;
			$sphinx->processQuery();

			$ids = $sphinx->returnIds(1,'_images');
			$count = $sphinx->resultCount;
		}

		if (!empty($ids)) {
			$zap = array();
			foreach ($ids as $c => $id) {
				if ($id == $image->gridimage_id) {
					unset($ids[$c]);
					$count--;
				}
				if (!empty($used[$id])) {
					$zap[$id] = $c;
				}
				$used[$id]++;
			}
			foreach ($zap as $id => $c) {
				if (count($ids) > 4) 
					unset($ids[$c]);
			}

			if ($ids) $ids = array_slice($ids,0,4);
			if ($ids) {
				$row = array();
				$images=new ImageList();
				$images->getImagesByIdList($ids);

				$row['title'] = $title;
				$row['images'] = $images->images;
				$row['resultCount'] = $count;
				$row['query'] = $q;
				return $row;
			}
		} else
			return 0;
	}