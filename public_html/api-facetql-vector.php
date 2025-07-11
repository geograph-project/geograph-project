<?

//todo, this should be rolled into main API, but for now its specialist enough to keep seperate!


if (!isset($ABORT_GLOBAL_EARLY))
	$ABORT_GLOBAL_EARLY = true;

require_once('geograph/global.inc.php');

//the gridimage_embedding is only on DEV instance for now!
$CONF['manticorert_host'] = "manticorert-worker-svc.dev.svc.cluster.local"; //test instance!

require_once('3rdparty/facet-functions.php');

if (!defined('SPHINX_INDEX')) {
        define('SPHINX_INDEX',"gridimage_embedding");
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
#initialize query

	$obj = GeographSphinxConnection('manticorert',true);
	$db = $obj->_connectionID; //using old fashioned mysqli_ functions here!

	if (!empty($_GET['describe'])) {
		$res['rows'] = getAll("DESCRIBE ".SPHINX_INDEX);
		if (!empty($res['rows']) && !empty($res['rows'][0]['Agent']) && $res['rows'][0]['Type'] == 'local') {
			//in the case of distributed index, sphinx tells us the component indexes, lets instead return result for the compoentn index.
			// Users care about teh fields/attributes available, not how its built by the server
			$res['rows'] = getAll("DESCRIBE ".$res['rows'][0]['Agent']);
		}
	} elseif (!empty($_GET['select'])) {

		$threads = getAssoc("SHOW THREADS"); //alas no quick way to just get count.
		//todo, maybe put this in apc cache??
		if (count($threads) > 10) {
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
			$ddb = GeographDatabaseConnection(false);
		        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

			$quoted= $ddb->Quote($_GET['label']);

	                $binary = $ddb->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted"); //limit 1 added
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
# the run the actual query

		$q = array();
		$q[] = "SELECT $select";
		$q[] = "FROM ".SPHINX_INDEX;
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

	} else {
		die("no");
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

