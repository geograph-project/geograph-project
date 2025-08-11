<?php
/**
 * $Project: GeoGraph $
 * $Id: contributors.php 8217 2015-01-05 16:33:42Z geograph $
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

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($q)) {
    header('Content-Type: application/javascript');
    $data = array("error" => "Query parameter is missing.");
	outputJSON($data);
    exit;
}


if (!empty($_GET['vector'])) {
	$results = array();

		//dont use mode to signify vector, because vector itselt supports different modes!

		require_once("geograph/vectors.inc.php");

        	if (empty($filesystem))
                	$filesystem = new FileSystem(); //sets up S3 configuation automagically - needed for s3Vectors!

		##########################

		$vector = getTextEmbedding($_GET['q'], 'mpnet');

			if (!empty($vector) && count($vector) == 768) { //for mpnet!

				$limit = min(intval($_GET['limit'] ?? 20), 30);

			        $queryPayload = [
			            'vectorBucketName' => $CONF['s3_vector_bucket'],
			            'indexName' => 'user-mpnet', // Hardcoded for now
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
					$row['user_id'] = intval($r['key']);
					if (!empty($r['distance']))
                                        	$row['distance'] = floatval($r['distance']);

					$results['items'][] = $row;
				}

				if (!empty($_GET['rerank'])) {
					rerank_items($results['items'], 'realname', $_GET['q']); //results passed by reference
				}

				$results['total_found'] = count($result['vectors']); //can't providle a real total for vector search
				$time = sprintf('%.3f',$end-$start);
				$results['query_info'] = "Query '".preg_replace('/[^\w ]+/',' ',$_GET['q'])."' retrieved {$results['total_found']} of ? matches in $time sec.\n";
			} else {
				$results = array('error'=>'unable to lookup vector');
			}

		##########################

	outputJSON($results);
	exit;

}

$sphinx = new sphinxwrapper($q);
$sphinx->pageSize = 15;
$sphinx->processQuery();
if (!empty($_GET['new'])) {
	$client = $sphinx->_getClient();
	$client->SetRankingMode(SPH_RANK_SPH04); // this should be enough for simple queries. shouldnt need to manipulate the query like do for tags
}
$ids = $sphinx->returnIds(1, 'user');
$results = array();

if (!empty($ids) && !empty($sphinx->res['matches'])) { //should be able extract the metedata from attributes!

	foreach($sphinx->res['matches'] as $id => $result) {
            $results[] = array(
                'user_id' => $id,
                'nickname' => $result['attrs']['nickname'],
                'realname' => $result['attrs']['realname'],
                'images' => $result['attrs']['images'],
            );
	}

} elseif (!empty($ids)) {
    $where = "user_id IN(" . join(",", $ids) . ")";
    $db = GeographDatabaseConnection(true);
    $limit = 25;

    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    $rows = $db->getAssoc("
        SELECT user.user_id, nickname, realname, images
        FROM user
        LEFT JOIN user_stat USING (user_id)
        WHERE $where
        LIMIT $limit");

    foreach ($ids as $id) {
        if (isset($rows[$id])) {
            $results[] = array(
                'user_id' => $id,
                'nickname' => $rows[$id]['nickname'],
                'realname' => $rows[$id]['realname'],
                'images' => $rows[$id]['images'],
            );
        }
    }
}

$output = array(
    'items' => $results,
    'query_info' => $sphinx->query_info,
    'copyright' => 'Geograph Project & contributors',
);

	outputJSON($output);

