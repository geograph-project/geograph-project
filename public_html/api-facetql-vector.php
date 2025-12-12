<?

//todo, this should be rolled into main API, but for now its specialist enough to keep seperate!


if (!isset($ABORT_GLOBAL_EARLY))
	$ABORT_GLOBAL_EARLY = true;

require_once('geograph/global.inc.php');

//the gridimage_embedding is only on DEV instance for now!
$CONF['manticorert_host'] = "manticorert-worker-svc.dev.svc.cluster.local"; //test instance!

require_once('3rdparty/facet-functions.php');

if (defined('SPHINX_INDEX')) {
	$SPHINX_INDEX = SPHINX_INDEX;
} else {
        $SPHINX_INDEX = "gridimage_embedding";
}


if (!headers_sent())
	customGZipHandlerStart();

	switch(1) {
		case !empty($_GET['long']) : customExpiresHeader(3600*24*30,true); break;
		case !empty($_GET['mid']) : customExpiresHeader(3600*24*3,true); break;
		default : customExpiresHeader(3600*24,true); break;
	}

	$res = array();

###########################################
# this is where tricky, we now do the KNN via s3vectors.
# But if the query can't be forfilled by s3vector metadata, we now grab data from manticore, not manticorert

if (!empty($_GET['label']) && empty($_GET['match']) && empty($_GET['where'])) { //todo, where may be doable via metadata filter?
	//can be done via s3vectors
	//1. get embedding

	$criteria = array();
	$criteria['label'] = $_GET['label'];

		 $filesystem = new FileSystem(); //sets up configuation automagically
		 //the vector lib needs S3 class setup already!

	require_once('geograph/imagelists3vector.class.php');
	$imagelist=new ImageListS3Vector;
	if (!empty($_GET['model']) && $_GET['model'] == 'pe')
		$imagelist->setModel('pe');

	$metadata = false;
	if (preg_match('/^(,?(id|wgs84_lat|wgs84_long|user_id|takenday|grid_reference))+$/',$_GET['select']) && $_GET['select'] != 'id')
		$metadata = true;

	if (!empty($_GET['user_id'])) {
		$criteria['user_id'] = intval($_GET['user_id']);

	//convert this to an actual filter;
	} elseif (preg_match('/\suser(\d+)\s*$/',$criteria['label'],$m)) {
		$criteria['user_id'] = intval($m[1]);
		$criteria['label'] = str_replace($m[0],'',$criteria['label']);

	} elseif (preg_match('/\s-user(\d+)\s*$/',$criteria['label'],$m)) {
		$criteria['user_id'] = intval($m[1])*-1;
		$criteria['label'] = str_replace($m[0],'',$criteria['label']);
	}

       if (!empty($_GET['larger']) && preg_match('/^\d+\+?$/',$_GET['larger']))
               $criteria['largest'] = $_GET['larger']; //note is called largest in the vector index.

	if (!empty($_GET['geo'])) {
                $bits = explode(',',$_GET['geo']);
		$criteria['lat'] = floatval($bits[0]);
		$criteria['lng'] = floatval($bits[1]);
		$criteria['dist'] = intval($bits[2]);
	}

	if (!empty($_GET['olbounds'])) {
		$criteria['bbox'] = trim($_GET['olbounds']);
	}

	//&filterrange%5Btakendays%5D=to_days%282025-01-01%29%2Cto_days%282025-07-20%29
	if (!empty($_GET['filterrange'])) {
                foreach ($_GET['filterrange'] as $key => $value) {
                        if (preg_match('/^\d+,\d+$/',$value)) {
                                $bits = explode(',',$value);
                                $key = preg_replace('/_(\d+)$/','',$key);
                                //$where[] = "$key BETWEEN ".intval($bits[0])." AND ".intval($bits[1]);
				if ($key == 'user_id') //only one suported by getRawVectorsByCriteria!
					$criteria['user_id'] = $bits;
                        } elseif (preg_match("/to_days\('?([\d-]+)'?\),to_days\('?([\d-]+)'?\)/i",$value,$m)) {
				//s3vector has a 'simple' interger
				$criteria['taken'] = array(
					intval(str_replace('-','',$m[1])),
					intval(str_replace('-','',$m[2])),
				);
			}
		}
	}

	//2. get results
	$limit = empty($_GET['limit'])?10:intval($_GET['limit']);
	$limit = min($limit, 100);

	$start = microtime(true);
	$results = $imagelist->getRawVectorsByCriteria($criteria, $limit, $metadata);
	$end = microtime(true);

	//3. output or fetch further data from local index
	if (preg_match('/^(,?(id|wgs84_lat|wgs84_long|user_id|takenday|grid_reference))+$/',$_GET['select'])) {
		//can be fufilled entrirely by metadata!

		$res['rows'] = array();
		//$res['rows'][] ...
		if (!empty($results['vectors']))
		foreach ($results['vectors'] as $i => $vector) {
			$row = array('id'=>intval($vector['key']));
			if (isset($vector['distance'])) {
				$row['k'] = $vector['distance'];
			}
			if (!empty($vector['metadata'])) {
				//map the row, to use the same keys as our manticore indexes uses!
				foreach($vector['metadata'] as $key => $value) {
					if ($key == 'slat')
						$row['wgs84_lat'] = deg2rad($value);
					elseif ($key == 'slng')
						$row['wgs84_long'] = deg2rad($value);
					elseif ($key == 'taken')
						$row['takenday'] = $value;
					elseif ($key == 'gridref')
						$row['grid_reference'] = $value;
					else
						$row[$key] = $value;
				}
			}
			$res['rows'][] = $row;
	        }

		$res['meta'] = array('total_found'=>count($res['rows']), 'total'=>count($res['rows']), 'time' => $end-$start); //always 30 (or less), no paging!

	} elseif (!empty($results['vectors'])) {

		//will have to lookup ids, from s3vectors, then load from sample8!
		$ids = array();
		foreach ($results['vectors'] as $i => $vector) {
			$ids[intval($vector['key'])] = floatval($vector['distance']);
		}

		$idstr = implode(',',array_keys($ids));

		$SPHINX_INDEX = 'sample8';
		$_GET['label'] = ""; //already done KNN lookup, dont need to do it again!
		$_GET['where'] = "id IN ($idstr)";
		$_GET['select'] = str_replace(",image_vector",",0 as image_vector", $_GET['select']); //this attribute doesnt exist, will have to fetch from database later!

//TODO use the s3vectors time for final meta?

		$sph = GeographSphinxConnection('sphinxql',true);
		$db = $sph->_connectionID; //using old fashioned mysqli_ functions here!

	} else {
		//no results
		//todo, if due to error, add customExpiresHeader(10,true); //maybe? (to REDUCE the caching)

		$res['rows'] = false;
		$res['meta'] = array('total_found'=>0, 'total'=>0, 'time' => $end-$start);
	}

###########################################

} elseif (!empty($_GET['match'])) {
	//this is tricky, if making a match query, must be trying to use this scripts ability to fetch image_vector
	//but dont have a index available yet, use sample8 and add missing detail later!

	$SPHINX_INDEX = 'sample8';
	$_GET['select'] = str_replace(",image_vector",",0 as image_vector", $_GET['select']); //this attribute doesnt exist, will have to fetch from database later!


	$sph = GeographSphinxConnection('sphinxql',true);
	$db = $sph->_connectionID; //using old fashioned mysqli_ functions here!

} else {
	//this will need to be done on the RT index directly
	//... the orioginal purpose of this file was wrapper around the 'gridimage_embedding' index, but later evoved to testing S3Vector, and fetching image_vector

	//convert this to 'where'
	if (preg_match('/\suser(\d+)\s*$/',$_GET['label'],$m)) {
		if (!empty($_GET['where'])) {
			if (!is_array($_GET['where']))
				$_GET['where'] = array($_GET['where']);
			$_GET['where'][] = "user_id=".intval($m[1]);
		} else {
			$_GET['where'] = "user_id=".intval($m[1]);
		}
		$_GET['label'] = str_replace($m[0],'',$_GET['label']);
	}


	$rt = GeographSphinxConnection('manticorert',true);
	$db = $rt->_connectionID; //using old fashioned mysqli_ functions here!
}

###########################################
#initialize (normal) query

if (empty($res)) { //filled directly above!!!

	if (!empty($_GET['describe'])) {
		$res['rows'] = getAll("DESCRIBE $SPHINX_INDEX");
		if (!empty($res['rows']) && !empty($res['rows'][0]['Agent']) && $res['rows'][0]['Type'] == 'local') {
			//in the case of distributed index, sphinx tells us the component indexes, lets instead return result for the compoentn index.
			// Users care about teh fields/attributes available, not how its built by the server
			$res['rows'] = getAll("DESCRIBE ".$res['rows'][0]['Agent']);
		}
	} elseif (!empty($_GET['select'])) {

		$threads = getAssoc("SHOW THREADS"); //alas no quick way to just get count.
		//todo, maybe put this in apc cache??
		if (count($threads) > 70) {
		        customExpiresHeader(60,true,true);
			header("HTTP/1.1 503 Service Unavailable");
			header('Content-type: application/json');
			header('Access-Control-Allow-Origin: *');
			die(json_encode(array('error'=>'Service Unavailable')));
		}


		$select = empty($_GET['select'])?'id':$_GET['select'];
		$where = array();
		if (!empty($_GET['match']))
			$where[] = "MATCH('".mysqli_real_escape_string($db,$_GET['match'])."')";
		if (!empty($_GET['where'])) {
			if (is_array($_GET['where'])) {
				foreach($_GET['where'] as $key => $value)
					$where[] = $value;
			} else
				$where[] = $_GET['where'];
		}
		if (!empty($_GET['label'])) { //currently only specific labels supported!
			//todo! ideally this shoudl use the manticore index but label isnt attribute, and for now can't garentee all been loaded!
			//... also note, that if intecepted by s3vectors above, then arbitary query is supproted, so really this should be updated too!
			//this code will currently be unused!
			$ddb = GeographDatabaseConnection(false);
		        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

			$quoted= $ddb->Quote($_GET['label']);

			$model = (!empty($_GET['model']) && in_array($_GET['model'],array('pe','clip')))?$_GET['model']:'clip';
			$binary = $ddb->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted AND model = '$model'"); //limit 1 added

			//toodo, if $model!=clip, may need to seelet a different index!

			if (!empty($binary)) {
	                	$list = unpack('g*', $binary);
        		        $value = "(".implode(', ',$list).")";
	        	        $vector = "image_vector";

				$select .= ", knn_dist() as k";
				$limit = empty($_GET['limit'])?10:intval($_GET['limit']);
				$limit = min(1000,$limit); //not automatically subject ot max_matches!
				$where[] = "knn($vector, $limit, $value)";
			} else {
				$where[] = "0=1"; //odd way of returning no results!
			}
		}
		$group = empty($_GET['group'])?'':$_GET['group'];
		$n = empty($_GET['n'])?'':intval($_GET['n']);
		$order = empty($_GET['order'])?'':$_GET['order'];
		$having = empty($_GET['having'])?'':$_GET['having'];
		$within = empty($_GET['within'])?'':$_GET['within'];
		$offset = empty($_GET['offset'])?0:intval($_GET['offset']);
		$limit = empty($_GET['limit'])?10:intval($_GET['limit']);
		$option = array();
		if (!empty($_GET['option']))
			$option[] = $_GET['option'];

		$option[] = "max_query_time = 10000";
//		$option[] = 'max_matches=3000';

###########################################
# geo filter helpers

	//for now only accept 'geo' but done BEFORE sending to S3vectors
	//would have to reimplement it here to query manticore

###########################################
# the run the actual query

		$q = array();
		$q[] = "SELECT $select";
		$q[] = "FROM $SPHINX_INDEX";
		if (!empty($where))
			$q[] = "WHERE ".implode(' AND ',$where);
		if (!empty($group))
			$q[] = "GROUP $n BY $group";
		if (!empty($within))
                        $q[] = "WITHIN GROUP ORDER BY $within";
		if (!empty($order))
			$q[] = "ORDER BY $order";

if ($order == 'RAND()' && empty($_GET['rnd'])) {
	$option[] = "rand_seed= ".abs(crc32(implode(' ',array_slice($q,1)))); //skip select, could also skip order. Its only the where we REALLY need to use.
}

		if (isset($limit))
			$q[] = "LIMIT $offset,$limit";
		if (!empty($option))
			$q[] = "OPTION ".implode(', ',$option);

		if (!empty($_GET['debug']))
        		die(implode(' ',$q));

                $res = array(
                        'rows' => getAllWithUTF(implode(' ',$q)), //special version that can convert some known text fields in the the resultset
                        'meta' => getAssoc('SHOW META')
                );

        // If we requested the image_vector, we need to fetch it from the database now
        if (strpos($_GET['select'], 'image_vector') !== false && !empty($res['rows'])) {
            $image_ids = array_column($res['rows'], 'id');
            $id_list = implode(',', $image_ids);

            $ddb = GeographDatabaseConnection(true);
            $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		if (!empty($_GET['model']) && $_GET['model'] == 'pe') {
            $vectors_map = $ddb->GetAssoc("SELECT gridimage_id, embeddings FROM gridimage_embedding_1024 WHERE gridimage_id IN ($id_list) AND type='image' AND model='pe'");
		} else {
            $vectors_map = $ddb->GetAssoc("SELECT gridimage_id, embeddings FROM gridimage_embedding WHERE gridimage_id IN ($id_list) AND type='image'"); //AND model='clip'
		}

            foreach ($res['rows'] as &$row) {
                if (isset($vectors_map[$row['id']])) {
                    $row['image_vector'] = base64_encode($vectors_map[$row['id']]);
                } else {
                    $row['image_vector'] = null;
                }
            }
            unset($row); // Unset the reference
        }


	} else {
		die("no");
	}
}

###########################################

if (function_exists("call_with_results")) {
        call_with_results($res);
}

if (empty($res['meta'])) {
	$res['meta'] = array('error'=>'Unable to obtain results');
}

	if (isset($_GET['callback'])) {
		$callback=preg_replace('/[^\w\.$]+/','',$_GET['callback']);
		if (empty($callback)) {
			$callback = "geograph_callback";
		}

		header('Content-type: application/x-javascript');

		print "/**/{$callback}(";
	} else {
		header('Access-Control-Allow-Origin: *');
		header('Content-type: application/json');
	}

	print str_replace('-INF','0',json_encode($res));

	if (!empty($callback))
		print ");";

#end
###########################################

function getAllWithUTF($query) {
	global $db;
	global $ids;
	if (!($result = mysqli_query($db, $query))) {
		return FALSE; //SHOW META in sphinx will report the error
	}
	if (!mysqli_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	if (!empty($_GET['utf'])) {
		if ($_GET['utf'] === "2") {
			//in fact may still need to decode entities!
			while($row = mysqli_fetch_assoc($result)) {
		                if (!empty($row['title']))
		                        $row['title'] = manticore_to_utf8($row['title']);
		                if (!empty($row['realname']))
                		        $row['realname'] = manticore_to_utf8($row['realname']);
				if (!empty($ids) && !empty($ids[intval($row['id'])]))
					$row['k'] = $ids[intval($row['id'])];
				$a[] = $row;
			}
			return $a;
		}

		//manticore, should in general already be in utf8, so test without. Should change this to perhaps use 'detect_encoding' to do conditionally!
		while($row = mysqli_fetch_assoc($result)) {
			$a[] = $row;
		}
		return $a;
	}
	while($row = mysqli_fetch_assoc($result)) {
                if (!empty($row['title']))
                        $row['title'] = utf8_encode($row['title']);
                if (!empty($row['realname']))
                        $row['realname'] = utf8_encode($row['realname']);
                if (!empty($row['place']))
                        $row['place'] = utf8_encode($row['place']);
		if (!empty($ids) && !empty($ids[intval($row['id'])]))
			$row['k'] = $ids[intval($row['id'])];

		if (!empty($_GET['thumb']) && !empty($row['hash'])) {
			$row['thumb'] = getGeographUrl($row['id'],$row['hash'],'med');
		}

		$a[] = $row;
	}
	return $a;
}
function getAssoc($query) {
	global $db;
	if (!($result = mysqli_query($db, $query))) {
		return FALSE; //SHOW META in sphinx will report the error
	}
	if (!mysqli_num_rows($result)) {
		return FALSE;
	}
	$a = array();
	$row = mysqli_fetch_assoc($result);

	if (count($row) > 2) {
		do {
			$i = array_shift($row);
			$a[$i] = $row;
		} while($row = mysqli_fetch_assoc($result));
	} else {
		$row = array_values($row);
		do {
			$a[$row[0]] = $row[1];
		} while($row = mysqli_fetch_row($result));
	}
	return $a;
}


function getGeographUrl($gridimage_id,$hash,$size ='small') {
       $yz=sprintf("%02d", floor($gridimage_id/1000000));
       $ab=sprintf("%02d", floor(($gridimage_id%1000000)/10000));
       $cd=sprintf("%02d", floor(($gridimage_id%10000)/100));
       $abcdef=sprintf("%06d", $gridimage_id);
        if ($yz == '00') {
                $fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}";
        } else {
                $fullpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}";
        }
       $server =  "https://s".($gridimage_id%4).".geograph.org.uk";
       switch($size) {
               case 'full': return "https://s0.geograph.org.uk $fullpath.jpg"; break;
               case 'med': return "$server{$fullpath}_213x160.jpg"; break;
               case 'small':
               default: return "$server{$fullpath}_120x120.jpg";
       }

}
