<?php
/**
 * $Project: GeoGraph $
 * $Id: discussions.php 8497 2017-05-20 14:09:31Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
$template = 'finder_discussions.tpl';

if (!empty($_GET['q'])) {

	 $USER->mustHavePerm("basic");

	$q=trim($_GET['q']);
	$q = preg_replace('/(-?)\b(by):/','@name $1',$q);
	$q = str_replace("'",' ',$q);
	$sphinx = new sphinxwrapper($q);

$orders = array(
	1=>'Keyword Match - Grouped by Age',
	2=>'Keyword Match Only',
	3=>'Most Recent Post First',
	4=>'Oldest Post First',
	5=>'Most Recent Topic First',
	6=>'Oldest Topic First'
);

	$grouped = !empty($_GET['t']);
	if ($titleonly = !empty($_GET['titleonly'])) {
		$grouped = 1;
	}

	$forum = (!empty($_GET['forum']))?intval($_GET['forum']):0;
	$expand = (!empty($_GET['expand']))?intval($_GET['expand']):0;
	$order = (!empty($_GET['order']) && isset($orders[$_GET['order']]))?intval($_GET['order']):1;
	if (!empty($_GET['relevance'])) { $order = 2; }

	$extra = "q=".urlencode($q).($titleonly?"&amp;titleonly=on":'').($grouped?"&amp;t=on":'').($order?"&amp;order=$order":'').($forum?"&amp;forum=$forum":'').($expand?"&amp;expand=on":'');

	//gets a cleaned up verion of the query (suitable for filename etc)
	$cacheid = implode('.',array($sphinx->q,$grouped,$titleonly,$forum,$expand,0,$order));


	$sphinx->pageSize = $pgsize = 15;

	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$cacheid .=".".$pg;

	if (!$smarty->is_cached($template, $cacheid)) {
		$db = GeographDatabaseConnection(false);

		$offset = (($pg -1)* $sphinx->pageSize)+1;
		if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/",$sphinx->q,$m)) {
			$smarty->assign('gridref',$m[1].$m[2].$m[3]);
			$sphinx->q = " ".$sphinx->q;
		}

		if ($offset < (1000-$pgsize) ) {
			$sphinx->processQuery();

			if ($titleonly && strpos($sphinx->q,'@') !== 0) {
				$sphinx->q = "@title ".$sphinx->q;
			}
			if (!empty($forum))
				$sphinx->q .= " @forum $forum";
			else
				$sphinx->q .= " @forum -11";

			$cl = $sphinx->_getClient();
			$cl->SetFieldWeights(array('title'=>2));

			if ($grouped) {
				//require_once ( "3rdparty/sphinxapi.php" ); //toload the sphinx constants

                                switch ($order) {
                                        case 1:
						//from http://sphinxsearch.com/blog/2010/06/27/doing-time-segments-geodistance-searches-and-overrides-in-sphinxql/
						$col = "INTERVAL(post_time, NOW()-90*86400, NOW()-30*86400, NOW()-7*86400, NOW()-86400, NOW()-3600) AS time_seg";
						$cl->setSelect("*, $col");
						$sphinx->sort = 'time_seg DESC, @weight DESC'; break;
                                        case 2: $sphinx->sort = '@weight DESC'; break;
                                        case 3: $sphinx->sort = '@id DESC'; break;
                                        case 4: $sphinx->sort = '@id ASC'; break;
                                        case 5: $sphinx->sort = 'topic_id DESC'; break;
                                        case 6: $sphinx->sort = 'topic_id ASC'; break;
                                }

				$sphinx->setGroupBy('topic_id',SPH_GROUPBY_ATTR, $sphinx->sort); //use for both INNER and OUTER sort

				$ids = $sphinx->returnIds($pg,'_posts');

			} elseif ($order > 1) { //1 falls back to timesegments!

				switch ($order) {
					case 2: break; //sphinx default is relevence!
					case 3: $sphinx->sort = 'post_time DESC'; break;
					case 4: $sphinx->sort = 'post_time ASC'; break;
                                        case 5: $sphinx->sort = 'topic_id DESC'; break;
                                        case 6: $sphinx->sort = 'topic_id ASC'; break;
				}

				$ids = $sphinx->returnIds($pg,'_posts');
			} else {
				//sort in time segments then relevence
				$ids = $sphinx->returnIds($pg,'_posts','post_time');
			}

			if (!empty($ids) && count($ids)) {
				$where = "post_id IN(".join(",",$ids).")";

				$limit = 25;

				$prev_fetch_mode = $ADODB_FETCH_MODE;
				$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
				$rows = $db->getAssoc($sql = "
				select post_id,post_text,poster_name,poster_id,
					geobb_posts.topic_id,geobb_topics.forum_id,topic_title,topic_poster,topic_poster_name
				from geobb_posts
					inner join geobb_topics using (topic_id)
				where $where
				limit $limit");

				$docs = array();
				foreach ($ids as $c => $id) {
					$row = $rows[$id];
					$docs[$c] = strip_tags(preg_replace('/<i>.*?<\/i>/',' ',$row['post_text']));
				}
				if ($expand) {
					$reply = $sphinx->BuildExcerpts($docs, 'post_stemmed', $sphinx->q,array('limit' => 512,'around' => 12, 'query_mode' => 1));
				} else {
					$reply = $sphinx->BuildExcerpts($docs, 'post_stemmed', $sphinx->q);
				}

				$times = array(
				'hour' => time() - 3600,
				'day' => time() - 3600*24,
				'week' => time() - 3600*24*7,
				'month' => time() - 3600*24*30,
				'three months' => time() - 3600*24*90);


				$results = array(); $i =0;
				$lookup = array();
				foreach ($ids as $c => $id) {
					$row = $rows[$id];
					$row['id'] = $id;
					$row['post_time'] = $sphinx->res['matches'][$id]['attrs']['post_time'];
					$row['era'] = '';
					foreach ($times as $lab => $tim) {
						if ($row['post_time'] > $tim) {
							$row['era'] = $lab;
							break;
						}
					}
					$row['excerpt'] = $reply[$c];

					if (isset($lookup[$row['topic_id']])) {
						$results[$lookup[$row['topic_id']]]['results'][] = $row;
						$results[$lookup[$row['topic_id']]]['result_count']++;
					} else {
						$row['results'] = array();
						$row['result_count'] = 0;
						$lookup[$row['topic_id']] = $i++;
						$results[] = $row;
					}
				}
				$smarty->assign_by_ref('results', $results);
				$smarty->assign("query_info",$sphinx->query_info);

				if ($sphinx->numberOfPages > 1) {
					$smarty->assign('pagesString', pagesString($pg,$sphinx->numberOfPages,$_SERVER['PHP_SELF']."?".$extra."&amp;page=",'','',$sphinx->resultCount <= $sphinx->maxResults) );
					$smarty->assign("offset",$offset);
				}
				$ADODB_FETCH_MODE = $prev_fetch_mode;
			}
		} else {
			$smarty->assign("query_info","Search will only return 1000 results - please refine your search");
			$smarty->assign('pagesString', pagesString($pg,1,$_SERVER['PHP_SELF']."?".$extra."&amp;page=") );

		}
		$forums = $db->getAssoc("SELECT forum_id,forum_name FROM geobb_forums");
		$forums = array(0=>'Any/All Discussion Forums')+$forums;
		$smarty->assign_by_ref("forums",$forums);
	}

	$smarty->assign("q",$sphinx->qclean);
	$smarty->assign("grouped",$grouped);
	$smarty->assign("titleonly",$titleonly);
	$smarty->assign("forum",$forum);
	$smarty->assign("order",$order);
	$smarty->assign("orders",$orders);
}

$smarty->display($template,$cacheid);



