<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7424 2011-09-22 21:37:52Z barry $
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

$db = GeographDatabaseConnection(true);

$sql = array();

$sql['tables'] = array();
$sql['tables']['t'] = 'content';

$sql['columns'] = "content_id,url,title,extract,source,updated";

if (!empty($_GET['h'])) {
	$sql['columns'] .= ",words";
}

if (isset($_GET['q'])) {

	customExpiresHeader(3600);

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
			            'indexName' => 'doc-mpnet', // Hardcoded for now
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
					$row['content_id'] = intval($r['key']);
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
				$results = array('error'=>'unable to lookup vector');
			}

		##########################

		outputJSON($results);
		exit;

	}



	if (!empty($CONF['sphinx_host'])) {

                $q = trim(preg_replace('/[^\w]+/',' ',str_replace("'",'',$_REQUEST['q'])));

		$sphinx = new sphinxwrapper($q);

		$sphinx->pageSize = $pgsize = 25;

		$pg = (!empty($_REQUEST['page']))?intval(str_replace('/','',$_REQUEST['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}

		$offset = (($pg -1)* $sphinx->pageSize)+1;

		if ($offset < (1000-$pgsize) ) {

			$ids = $sphinx->returnIds($pg,'document_stemmed');

			if (!empty($ids) && count($ids)) {
				$idstr = join(",",$ids);
				$where = "content_id IN(".join(",",$ids).")";

				$sql['wheres'] = array("`content_id` IN ($idstr)");
				$sql['order'] = "FIELD(`content_id`,$idstr)";

				$sql['limit'] = count($ids);
				$query_info = $sphinx->query_info;
			} else {
				$sql['wheres'] = array(0);
			}
		} else {
			$sql['wheres'] = array(0);
		}
	} else {
		$sql['wheres'] = array("`title` LIKE ".$db->Quote('%'.$_GET['q'].'%'));

		$sql['limit'] = 100;
	}

} else {
	die("todo");
}

$query = sqlBitsToSelect($sql);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$data = $db->getAll($query);

if (!empty($_GET['h']) && !empty($data) && !empty($sphinx)) {
	$docs = array();
        foreach ($data as $c => $row) {
                $docs[$c] = strip_tags(preg_replace('/<i>.*?<\/i>/',' ',$row['words']?$row['words']:$row['extract']));
        }

        $reply = $sphinx->BuildExcerpts($docs, 'document_stemmed', $sphinx->q);

	if (!empty($reply)) {
		foreach ($reply as $c => $text) {
			$data[$c]['words'] = utf8_encode($text);
		}
	}
}

if (!empty($query_info)) {
	$data[] = array('query_info'=>$query_info);
}

outputJSON($data);

