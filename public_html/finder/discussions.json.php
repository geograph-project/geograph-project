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


$USER->mustHavePerm("basic");

$results = array();
###################################################

if (!empty($_GET['vector'])) {

	//note this currently assumes $_GET['titleonly']=1 - as the vector index is titles only!

	if (!empty($_GET['q'])) {
		require_once("geograph/vectors.inc.php");

        	if (empty($filesystem))
                	$filesystem = new FileSystem(); //sets up S3 configuation automagically - needed for s3Vectors!

		##########################

		$vector = getTextEmbedding($_GET['q'], 'mpnet');

			if (!empty($vector) && count($vector) == 768) { //for mpnet!

				$limit = min(intval($_GET['limit'] ?? 20), 30);

			        $queryPayload = [
			            'vectorBucketName' => $CONF['s3_vector_bucket'],
			            'indexName' => 'thread-mpnet', // Hardcoded for now
			            'queryVector' => ['float32' => $vector],
			            'topK' => $limit,
			            'returnDistance' => !empty($_GET['rerank']),
			            'returnMetadata' => true,
			        ];

				$start  = microtime(true);
			        $result = queryS3Vectors($queryPayload);
				$end    = microtime(true);

				foreach ($result['vectors'] as $idx => $r) {
					$row = $r['metadata'];
					$row['topic_id'] = intval($r['key']);
					if (!empty($r['distance']))
                                        	$row['distance'] = floatval($r['distance']);

					$results[] = $row;
				}

				if (!empty($_GET['rerank'])) {
					rerank_items($results, 'title', $_GET['q']); //results passed by reference
				}

				$results[] = array('total_found' => count($result['vectors'])); //can't providle a real total for vector search
				$time = sprintf('%.3f',$end-$start);
				$results[] = array('query_info' => "Query '".preg_replace('/[^\w ]+/',' ',$_GET['q'])."' retrieved {$results['total_found']} of ? matches in $time sec.\n");
			} else {
				$results[] = array('error'=>'unable to lookup vector');
			}
	}

###################################################

} else {

	$q=trim($_GET['q']);
	$q = preg_replace('/(-?)\b(by):/','@name $1',$q);
	$q = str_replace("'",' ',$q);
	$sphinx = new sphinxwrapper($q);


	$grouped = !empty($_GET['t']);
	if ($titleonly = !empty($_GET['titleonly'])) {
		$grouped = 1;
	}

	$forum = (!empty($_GET['forum']))?intval($_GET['forum']):0;
	$order = (!empty($_GET['order']) && isset($orders[$_GET['order']]))?intval($_GET['order']):1;

	$sphinx->pageSize = $pgsize = 15;

	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

###################################################

	if (true) {
		$db = GeographDatabaseConnection(false);

		$offset = (($pg -1)* $sphinx->pageSize)+1;

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

			if (!empty($thread))
				$cl->setFilter('topic_id',array($thread));

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

				$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
				$rows = $db->getAssoc($sql = "
				select post_id,post_text,poster_name,poster_id,
					geobb_posts.topic_id,geobb_topics.forum_id,topic_title,topic_poster,topic_poster_name
				from geobb_posts
					inner join geobb_topics using (topic_id)
				where $where
				limit $limit");


				$results = array(); $i =0;
				foreach ($ids as $c => $id) {
					$row = $rows[$id];
					$row['id'] = $id;
					$row['post_time'] = $sphinx->res['matches'][$id]['attrs']['post_time'];
					$results[] = $row;
				}
			}

			if (!empty($sphinx->query_info))
				$results[] = array('query_info'=>$sphinx->query_info);
		}
	}
}


outputJSON($results);


