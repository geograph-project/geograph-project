<?php
/**
 * $Project: GeoGraph $
 * $Id: related.php 7025 2011-01-22 13:13:08Z geograph $
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

rate_limiting('related.php');

$smarty = new GeographPage;

#$smarty->display("sample8_unavailable.tpl");
#exit;

$template='related.tpl';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

$cacheid = empty($_GET['id'])?0:intval($_GET['id']);
if (!empty($_GET['method']) && preg_match('/^\w+$/',$_GET['method'])) {
	$cacheid .= ".".$_GET['method'];
} else {
	$_GET['method'] = '';//cheap hack so can do comparisions below without undefined warnigns
}

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

/////////////////////////////////////
//sample/quick

		} elseif ($_GET['method'] == 'sample' || $_GET['method'] == 'quick') {
			$results = array();

			require_once ( "3rdparty/sphinxapi.php" );

			$cl = new SphinxClient ();
			$cl->SetServer ( $CONF['sphinx_host'], $CONF['sphinx_port'] );

			$cl->SetIDRange($image->gridimage_id,$image->gridimage_id);
			$cl->SetLimits(0,1);
			$res = $cl->Query('','sample8');

		if (!empty($_GET['debug'])) {
			print_r($res);
			print_r($cl);
			print "\n\n<hr>\n\n";
		}

			$data = array();
			$data[''] = array(); //just to forse it to be first

			if (empty($res) || empty($res['matches'])) {

				$data[''][] = $image->title;
				$data[''][] = $image->realname;
				$data[''][] = str_replace('-','',$image->imagetaken);
				$data[''][] = str_replace('-','',substr($image->imagetaken,0,7));
				$data[''][] = substr($image->imagetaken,0,4);
				$data[''][] = $image->grid_reference;
				if (preg_match('/([A-Z]+)(\d)\d(\d)\d/',$image->grid_reference,$m)) {
					$data[''][] = $m[1].$m[2].$m[3];
					$data[''][] = $m[1];
				}

			} else {
				$columns = array();
				foreach ($res['fields'] as $idx => $attr) {
					if (isset($res['attrs'][$attr]) && $res['attrs'][$attr] == 7 && $attr != 'hash' && $attr != 'status' && $attr != 'takenyear') {
						$columns[] = $attr;
					}
				}

				foreach ($res['matches'] as $idx => $row) {
					foreach ($columns as $column) {
						if (!empty($row['attrs'][$column])) {
							if (is_numeric($row['attrs'][$column])) {
								if ($row['attrs'][$column] > 1000)
									$data[$column] = array($row['attrs'][$column]);
							} else {
								$data[''][] = $row['attrs'][$column];
							}
						}
					}
				}
			}

			$q = array();
			foreach ($data as $column => $row) {
				$words = explode(' ',trim(strtolower(preg_replace('/[^\w]+/',' ',str_replace('_SEP_',' ',implode(' ',$row))))));
				$words = array_unique($words);

				if (empty($_GET['all'])) {
					$keywords = $cl->BuildKeywords(implode(' ',$words),'sample8C', true);
					$inverted = array_flip($words); // hay look, really nice quick lookup table, to delete by value.
					foreach ($keywords as $idx => $keyword) {
						if ($keyword['docs'] > 100000) {
							unset($words[$inverted[$keyword['tokenized']]]);
						}
					}
				}
				if (empty($words))
					continue;

				if ($column) {
					if (count($words) > 1 && count($words) < 32) {
						$q[] = '| (@'.$column.' "'.implode(' ',$words).'"/1 )';
					} else {
                                                $q[] = '| (@'.$column.' '.implode(' | ',$words).' )';
                                        }
				} else {
					if (count($words) < 32) { //quorym can only cope with 32 words!
						$q[] = '"'.implode(' ',$words).'"/'.max(4,intval(count($words)/10));
					} else {
						$q[] = '( '.implode(' | ',$words).' )';
					}
				}
			}
			$q = implode(' ',$q);

			if ($_GET['method'] == 'quick') {
				$cl->setLimits(0,25,500,30000);
				$cl->setMaxQueryTime(150);
			} elseif (empty($_GET['exact'])) {
				$cl->setLimits(0,25,1000,300000);
				$cl->setMaxQueryTime(600); //setting this gives more approximate, but quicker results. Without this can easily take 20seconds+ !!?
			} else {
				$cl->setLimits(0,25);
			}

			$cl->_min_id = 0; $cl->_max_id = 0; //there is no ResetIDRange
			$cl->setMatchMode(SPH_MATCH_EXTENDED);
///			$cl->setRankingMode(SPH_RANK_WORDCOUNT);
			$cl->setSelect("user_id,realname,title,grid_reference");
			$cl->setFieldWeights(array('title'=>5,'myriad'=>4,'hectad'=>3,'grid_reference'=>2,'takenday'=>2));

			$res = $cl->Query($q, 'sample8');

		if (!empty($_GET['debug'])) {
			print_r($q);
			print_r($res);
			print_r($cl);
			exit;
		}

			if ($res && !empty($res['matches'])) {
				$images = array();
				foreach ($res['matches'] as $idx=>$row) {
					if ($idx == $_REQUEST['id'])
						continue;
					 $gridimage = new GridImage;
                                         $row['attrs']['gridimage_id'] = $idx;
                                         $gridimage->fastInit($row['attrs']);

                                         $images[] = $gridimage;
				}

                                $row = array();
                                $row['title'] = "Images similar/related to this image";
                                $row['images'] = $images;
                                $row['resultCount'] = $res['total']; //using this rather than total_found, because there can be many thousends of results.
                                $row['query'] = $q;

				$results[] = $row;
			}

                        $smarty->assign_by_ref('results', $results);
                        $smarty->assign('method',$_GET['method']);

//////////////////////////////////////
// combined

		} elseif (!empty($_GET['method']) && $_GET['method'] == 'combined') {
			$results = array();

			$t2 = preg_replace('/\b\d+\b/',' ',$image->title);

                        $db = GeographDatabaseConnection(true);

			$bit = array();

			$bits[] = "(\"^$t2\")";

			if (preg_match('/(^|[\n\r\s]+)Keywords?[\s:]([^\n\r>]+)$/i',$image->comment,$m)) {
				$p = preg_split('/[\n;,:\.]+/',$m[2]);
				if (count($p) == 1) {
					$p = preg_split('/\s+/',$m[2]);
				}
				foreach ($p as $line) {
					$bits[] = "( $line )";
				}
			}

			$p = preg_split('/[\s;,\.]+/',$t2);
			foreach ($p as $line) {
				if (preg_match('/^[A-Z]/',trim($line))) {
					$bits[] = "( $line )";
				}
			}

			$labels = $db->getCol("SELECT label 
				FROM gridimage_group 
				WHERE gridimage_id = {$image->gridimage_id} AND label != '(Other)'
				ORDER BY score DESC LIMIT 5");
			if (!empty($labels)) {
				foreach ($labels as $idx => $label) {
					$bits[] = "( {$label} )";
				}
			}

			if (!empty($image->imageclass))
				$bits[] = "( imageclass:{$image->imageclass} )";

	//TODO - tags/context etc?

			$bits[] = "( comment:{$image->gridimage_id} )";

			$image->loadSnippets();
			if ($image->snippet_count) {
				foreach ($image->snippets as $idx => $row) {
					$bits[] = "( snippet_id:{$row['snippet_id']} )";
				}
			}

			if (!empty($t2))
				$bits[] = "( title:$t2 )";

			if (!preg_match('/(0000|-00)/',$image->imagetaken)) {
				$bits[] = "( takenday:".str_replace('-','',$image->imagetaken)." )";

				$bits[] = "( takenyear:".substr($image->imagetaken,0,4)." )";
			}

#			$bits[] = "( user_id:{$image->user_id} )";

#			$bits[] = "( grid_reference:{$image->grid_reference} )";


                        $res = check_images(
                                "Combined results near {$image->grid_reference}",
                                $image->grid_reference,
                                "( ".implode(' | ',$bits).") ",
				25
                        );
                        if (!empty($res)) $results[] = $res;

			$smarty->assign_by_ref('results', $results);
			$smarty->assign('method','combined');

///////////////////////////////////////////////////
// split

		} else {

			$results = array();


/* TODO. could add some more..

* Tags! + Subject
* Context?
* Automatic Clusters - DONE!
* Currated labels
* Same title (via gridimage_duplicate)
* Photoset (via duplication_stat)
* Manual Photosets (if implemeneted)
* others from duplication_stat
* terms?
* vision groups? (bother hold google AI, plus our own experimental models)
* wikipedia groups
* collections (mainly articles)
* gridimage_post_highlight
* recreates: tag (although done with backlink for now!)
* computer vision
* taken in same location - eg centisquare+direction, but probably same addtional verificaton that same view - computer vision?

*/

			###########################
			//similar title

			$t2 = preg_replace('/\b(\d+|\w{1,2})\b/',' ',$image->title);
			$t2 = str_replace("'",'',$t2);
			$t2 = trim(preg_replace('/\s+/',' ',$t2));

			$res = check_images(
				"Similar Title near {$image->grid_reference}",
				$image->grid_reference,
				"title:($t2) | (\"^$t2\") | \"$t2\"/2"
			);
			if (!empty($res)) $results[] = $res;

			###########################
			//taken same day

			if (!preg_match('/(0000|-00)/',$image->imagetaken)) {
/*				$res = check_images(
					"Taken by same Contributor on the same Day near {$image->grid_reference}",
					$image->grid_reference,
					"user_id:{$image->user_id} takenday:".str_replace('-','',$image->imagetaken)
				);
				if (!empty($res)) $results[] = $res;
*/
				###########################

				$res = check_images(
					"Taken the same Day near {$image->grid_reference}",
					$image->grid_reference,
					"takenday:".str_replace('-','',$image->imagetaken)
				);
				if (!empty($res)) $results[] = $res;
			}

			###########################
			//same category

			$res = check_images(
				"In similar category near {$image->grid_reference}",
				$image->grid_reference,
				"\"{$image->title} {$image->imageclass} {$image->grid_reference}\"/1 imageclass:(\"^{$image->imageclass}\" | ({$image->imageclass}))" //(the quorum is just to push more similar images to the top)
			);
			if (!empty($res)) $results[] = $res;

			###########################
			//backlinks

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
			//shared description

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
			//automatic clusters

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
								"Marked with '{$label}' (automatic label) in {$image->grid_reference}",
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
							"Matching '{$label}' (automatic label) in {$image->grid_reference}",
							'',
							"text:{$label} grid_reference:{$image->grid_reference}"
						);
						if (!empty($res)) $results[] = $res;
					}
				}
			}

			###########################
			//contributor

			$res = check_images(
				"By same Contributor near {$image->grid_reference}",
				$image->grid_reference,
				"user_id:{$image->user_id}"
			);
			if (!empty($res)) $results[] = $res;

			###########################


			$smarty->assign_by_ref('results', $results);
			$smarty->assign('method','split');
		}
		$image->image_taken=$image->getFormattedTakenDate();
		$smarty->assign_by_ref('image', $image);

		$methods = array('split'=>'Breakdown','combined'=>'Combined Results','quick'=>'Quick Results');
		$smarty->assign_by_ref('methods', $methods);

		                       $smarty->assign('thumbw',120);
                                        $smarty->assign('thumbh',120);

/////////////////////////////////////////////

	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		$template = "static_404.tpl";
	}
}

$smarty->display($template,$cacheid);


####################

	function check_images($title,$gridref,$q,$number = 4) {
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
				//$used[$id]++; //moved below - so that it only marks the four shown as used. 
			}
			foreach ($zap as $id => $c) {
				if (count($ids) > $number) 
					unset($ids[$c]);
			}
			if ($ids) {
				$ids = array_slice($ids,0,$number);
				foreach ($ids as $c => $id) {
					$used[$id]++;
				}
			}
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
