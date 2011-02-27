<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
	$sphinx = new sphinxwrapper($q);

	$grouped = !empty($_GET['t']);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q.'.'.$grouped;

	$sphinx->pageSize = $pgsize = 15;

	
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}
	
	$cacheid .=".".$pg;
	
	if (!$smarty->is_cached($template, $cacheid)) {
		
		$offset = (($pg -1)* $sphinx->pageSize)+1;
		if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/",$sphinx->q,$m)) {
			$smarty->assign('gridref',$m[1].$m[2].$m[3]);
			$sphinx->q = " ".$sphinx->q;
		}
		
		if ($offset < (1000-$pgsize) ) { 
			$sphinx->processQuery();
			
			if ($grouped) {
				require_once ( "3rdparty/sphinxapi.php" ); //toload the sphinx constants
			
				//sorts by relevence within the groups (sphinxclient default) - timesegment sorting doesn't work
				//...then the groups are orded in date descending
				
				$sphinx->setGroupBy('topic_id',SPH_GROUPBY_ATTR,"@id DESC");
				
				$ids = $sphinx->returnIds($pg,'_posts');	
			} else {
				//sort in time segments then relevence
				$ids = $sphinx->returnIds($pg,'_posts','post_time');	
			}

			
			if (!empty($ids) && count($ids)) {
				$where = "post_id IN(".join(",",$ids).")";

				$db = GeographDatabaseConnection(false);

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
				$reply = $sphinx->BuildExcerpts($docs, 'post_stemmed', $sphinx->q);
				
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
					$smarty->assign('pagesString', pagesString($pg,$sphinx->numberOfPages,$_SERVER['PHP_SELF']."?q=".urlencode($q).($grouped?"&amp;t=on":'')."&amp;page=",'','',$sphinx->resultCount <= $sphinx->maxResults) );
					$smarty->assign("offset",$offset);
				}
				$ADODB_FETCH_MODE = $prev_fetch_mode;
			}
		} else {
			$smarty->assign("query_info","Search will only return 1000 results - please refine your search");
			$smarty->assign('pagesString', pagesString($pg,1,$_SERVER['PHP_SELF']."?q=".urlencode($q).($grouped?"&amp;t=on":'')."&amp;page=") );

		}
	}
	
	$smarty->assign("q",$sphinx->qclean);
	$smarty->assign("grouped",$grouped);
}

$smarty->display($template,$cacheid);

?>
