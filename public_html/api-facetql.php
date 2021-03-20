<?

//if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'],'66.249.76.') !== FALSE) {
//	header("HTTP/1.1 503 Service Unavailable");
//	exit;
//}

$ABORT_GLOBAL_EARLY = true;

require_once('geograph/global.inc.php');
require_once('3rdparty/facet-functions.php');

if (!defined('SPHINX_INDEX')) {
	if (!empty($_GET['cc'])) {
	        define('SPHINX_INDEX',"content_stemmed");
	} elseif (!empty($_GET['gg'])) {
	        define('SPHINX_INDEX',"germany");
	} elseif (!empty($_GET['is'])) {
        	define('SPHINX_INDEX',"islands");
	} elseif (!empty($_GET['vv'])) {
	        define('SPHINX_INDEX',"viewpoint");
	} else
        	define('SPHINX_INDEX',empty($_GET['recent'])?"sample8":"sample8E,sample8D");
}


if (!headers_sent())
	customGZipHandlerStart();


if (empty($_GET)) {
	customExpiresHeader(3600*24*24,true);

?>
<h2>JSON API for accessing Geograph's <? if (SPHINX_INDEX == 'sample2') {echo "'sphinx-geograph2.xml' sample";} else { echo SPHINX_INDEX." index";} ?></h2>
<p>This API is pretty much just a direct JSON wrapper around the SphinxQL Queries. So to use this API it helps to understand <a href="http://sphinxsearch.com/docs/2.0.1/searching.html">searching in 
sphinx</a></p>

<form method="get">
<table>
<tr><td align="right">?match=</td><td><input name="match" value="bridge"></td><td>The full-text query (in SPH_MATCH_EXTENDED format)</td></tr>
<tr><td align="right">&amp;callback=</td><td><input name="callback"></td><td>callback function name for JSONP</td></tr>
<tr><td align="right">&amp;where=/td><td><input name="where" value="user_id = 93"></td><td>General WHERE clause for filtering attributes</td></tr>
<tr><td align="right">&amp;order=</td><td><input name="order" value="WEIGHT() DESC, id DESC"></td><td>ORDER BY (When grouping)</td></tr>
<tr><td align="right">&amp;group=</td><td><input name="group"></td><td>Group by (attribute name) - see <a href="?q=&limit=1&select=*&attrs=list">list</a></td></tr>
<tr><td align="right">&amp;within=</td><td><input name="within" value=""></td><td>Within Group ORDER</td></tr>
<tr><td align="right">&amp;limit=</td><td><input name="limit" value="15"></td><td>Number of results</td></tr>
<tr><td align="right">&amp;offset=</td><td><input name="offset" value="0"></td><td>Offset (max_matches=1000 so limit+offset must be under 1000)</td></tr>
<tr><td align="right">&amp;select=</td><td><input name="select" value="user_id"></td><td>List of <a href="?describe=1">attributes</a> to return - can use "*" to get all</td></tr>
<tr><td align="right">&amp;pretty=1</td><td><input type=checkbox name="pretty" value="1"></td><td>Pretty print the json - ONLY use for testing purposes</td></tr>
</table>
<input type=submit>
(All fields - except select - are optional)
</form>

There are some other params, includeing filter,filterrange,exclude,geo,bounds,olbounds and mnmx - ask us for more info.

Example Queries:
<ul>
	<li><a href="?select=id,user_id,realname,title,grid_reference&match=@title+bridge&pretty=1">Basic full-text query - in json format</a>
	<li><a href="?select=id,user_id,realname,title,grid_reference&match=@title+bridgebridge&callback=my_function&pretty=1">Basic full-text query - in jsonp format (for accessing in a webpage)</a>
	<li><a href="?match=@title+bridge&group=user_id&select=id,user_id,count(*)+as+count&pretty=1">Group by user_id - to get counts per contributor for facets</a>
</ul>

<hr/>
<a href="http://data.geograph.org.uk/facets/">back to Faceted Browsing for Geograph</a>

<?	exit;
}
	switch(1) {
		case !empty($_GET['long']) : customExpiresHeader(3600*24*30,true); break;
		case !empty($_GET['mid']) : customExpiresHeader(3600*24*3,true); break;
		default : customExpiresHeader(3600*24,true); break;
	}

	$res = array();

###########################################
#initialize query

	$db = mysql_sphinx();


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
		if (count($threads) > 90) {
		        customExpiresHeader(60,true,true);
			header("HTTP/1.1 503 Service Unavailable");
			header('Content-type: application/json');
			header('Access-Control-Allow-Origin: *');
			die("{'error': 'Service Unavailable'}");
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

//todo groupByTile

###########################################
# hack for filters, as need mysql to decode FROM_DAYS

			if (preg_match("/to_days\('?([\d-]+)'?\)/",$select,$m)) {
				$link = mysql_database();

                                $sql = "SELECT TO_DAYS('{$m[1]}') AS result";
                                $result = mysqli_query($link,$sql) or die ("Couldn't select query : $sql " . mysqli_error($link) . "\n");
                                if (mysqli_num_rows($result) > 0) {
                                        $row = mysqli_fetch_assoc($result);
                                        $select = str_replace($m[0],$row['result'],$select);
                                }
                        }

###########################################
# extra filters

        if (!empty($_GET['filter'])) {
                foreach ($_GET['filter'] as $key => $value) {
                        if (!is_array($value)) {
                                $value = array(intval($value));
                        }
                        if (preg_match('/^\d+(,\d+)*/',$value = implode(',',$value))) {
				$key = preg_replace('/_(\d+)$/','',$key);
				$where[] = "$key IN ($value)";
			}
                }
        }
        if (!empty($_GET['filterrange'])) {
                foreach ($_GET['filterrange'] as $key => $value) {
                        if (preg_match('/^\d+,\d+$/',$value)) {
                                $bits = explode(',',$value);
				$key = preg_replace('/_(\d+)$/','',$key);
				$where[] = "$key BETWEEN ".intval($bits[0])." AND ".intval($bits[1]);
                        } elseif (preg_match("/to_days\('?([\d-]+)'?\),to_days\('?([\d-]+)'?\)/i",$value,$m)) {
                                $link = mysql_database();

                                $sql = "SELECT TO_DAYS('{$m[1]}') AS `from`,TO_DAYS('{$m[2]}') as `to`";
                                $result = mysqli_query($link,$sql) or die ("Couldn't select query : $sql " . mysqli_error($link) . "\n");
                                if (mysqli_num_rows($result) > 0) {
                                        $row = mysqli_fetch_assoc($result);
					$where[] = "$key BETWEEN ".intval($row['from'])." AND ".intval($row['to']);
                                }
                        }
                }
        }
        if (!empty($_GET['exclude'])) {
                foreach ($_GET['exclude'] as $key => $value) {
                        if (!is_array($value)) {
                                $value = array(intval($value));
                        }
                        if (preg_match('/^\d+(,\d+)*/',$value = implode(',',$value))) {
				$key = preg_replace('/_(\d+)$/','',$key);
				$where[] = "$key NOT IN ($value)";
			}
                }
        }
        if (!empty($_GET['excluderange'])) {
                foreach ($_GET['excluderange'] as $key => $value) {
                        if (preg_match('/^\d+,\d+$/',$value)) {
                                $bits = explode(',',$value);
				$key = preg_replace('/_(\d+)$/','',$key);
				$where[] = "$key NOT BETWEEN ".intval($bits[0])." AND ".intval($bits[1]);
                        }
                }
        }

###################################################
# geo filter helpers

	$prefix = 'wgs84_';
	//dont use a 4th param to geo, like in geo2, because needs to apply yo bounds/olbounds too!
	if (!empty($_GET['geo_prefix']) && preg_match('/^\w+$/',$_GET['geo_prefix']))
		$prefix = $_GET['geo_prefix'];
	elseif (SPHINX_INDEX == 'viewpoint')
		$prefix = 'v'; //set a default

        if (!empty($_GET['geo'])) {
                $bits = explode(',',$_GET['geo']);
		$select .= ",geodist({$prefix}lat,{$prefix}long,".deg2rad($bits[0]).','.deg2rad($bits[1]).') as geodist';

		if (!empty($bits[2])) {
			$where[] = 'geodist < '.floatval($bits[2]);

			//make a field filter
        	        if ($bits[2] < 75000 && empty($_GET['match']) && strpos(implode('',$where),'MATCH') === FALSE  //todo, could still run it at other times too
					&& ($prefix == 'wgs84_' || $prefix == 's')) {
                        	require_once('geograph/conversions.class.php');
	                        $_GET['match'] = geotiles(floatval($bits[0]),floatval($bits[1]),floatval($bits[2]));

		                if (!empty($_GET['match']))
        		                $where[] = "MATCH('".mysqli_real_escape_string($db,$_GET['match'])."')";
	                }

	                //make a BBOX too?
        	        if (empty($_GET['bounds']) && empty($_GET['olbounds']) && isset($_GET['d'])) {
                	        //top/right  --- north/east
                        	list($long1,$lat1) = calcLatLong($bits[1],$bits[0],$bits[2]*2.2,45); //sqrt(2) + some leeway
	                        //bottom/left -- south/west
        	                list($long2,$lat2) = calcLatLong($bits[1],$bits[0],$bits[2]*2.2,225);

				$where[] = "{$prefix}lat BETWEEN ".deg2rad($lat2).' AND '.deg2rad($lat1);
				$where[] = "{$prefix}long BETWEEN ".deg2rad($long1).' AND '.deg2rad($long2);
        	        }
		}
        }

	if (!empty($_GET['bounds'])) {
	        $b = str_replace('Bounds','',$_GET['bounds']);
	        $b = str_replace('(','',$b);
	        $b = str_replace(')','',$b);

	        $b = explode(',',$b);

		$where[] = "{$prefix}lat BETWEEN ".deg2rad($b[0]).' AND '.deg2rad($b[2]);
	        $where[] = "{$prefix}long BETWEEN ".deg2rad($b[1]).' AND '.deg2rad($b[3]);

	} else if (!empty($_GET['olbounds'])) {
	        $b = explode(',',trim($_GET['olbounds']));
        	        #### example: -10.559026590196122,46.59604915850878,7.514135843906623,54.84589681367314

	        $where[] = "{$prefix}lat BETWEEN ".deg2rad($b[1]).' AND '.deg2rad($b[3]);
	        $where[] = "{$prefix}long BETWEEN ".deg2rad($b[0]).' AND '.deg2rad($b[2]);
	}

        if (!empty($_GET['geo2'])) {
		if (!empty($_GET['geo'])) {
			//awkward workaround, manticore doesn like using two geodist() functions when alias is called geodist
			//  https://github.com/manticoresoftware/manticoresearch/issues/192
			$select = str_replace(' as geodist',' as geo1',$select);
			foreach ($where as $key => $value)
				$where[$key] = str_replace('geodist ','geo1 ',$value);
		}

                $bits = explode(',',$_GET['geo2']);
		if (preg_match('/^\w+$/',$bits[3]))
			$prefix = $bits[3];
		$select .= ",geodist({$prefix}lat,{$prefix}long,".deg2rad($bits[0]).','.deg2rad($bits[1]).') as geo2';

		if (!empty($bits[2])) {
			if ($bits[2] < 0) {
				$where[] = 'geo2 > '.abs($bits[2]);
			} else {
				$where[] = 'geo2 < '.floatval($bits[2]);

		                //make a BBOX too?
        		        if (empty($_GET['bounds']) && empty($_GET['olbounds']) && isset($_GET['d'])) {
                		        //top/right  --- north/east
                        		list($long1,$lat1) = calcLatLong($bits[1],$bits[0],$bits[2]*2.2,45); //sqrt(2) + some leeway
		                        //bottom/left -- south/west
        		                list($long2,$lat2) = calcLatLong($bits[1],$bits[0],$bits[2]*2.2,225);

					$where[] = "{$prefix}lat BETWEEN ".deg2rad($lat2).' AND '.deg2rad($lat1);
					$where[] = "{$prefix}long BETWEEN ".deg2rad($long1).' AND '.deg2rad($long2);
	        	        }
			}
		}
	}

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
                        'rows' => getAll(implode(' ',$q)),
                        'meta' => getAssoc('SHOW META')
                );
	} elseif (!empty($_GET['q'])) {
		$q = trim($_GET['q']);
		if (empty($q) || !preg_match('/ FROM '.SPHINX_INDEX.' /',$q) || !preg_match('/^SELECT /',$q) || preg_match('/;/',$q)) {
			die("sorry");
		}
		$res = array(
			'rows' => getAll($q),
			'meta' => getAssoc('SHOW META')
		);
	} else {
		die("no");
	}


###########################################
# hack for filters, as need mysql to decode FROM_DAYS

				if (isset($_GET['mnmx']) && !empty($res['rows'])) {

                                        $link = mysql_database();

                                        $row = current($res['rows']);

                                        $sql = "SELECT FROM_DAYS(".intval($row['mn']).") AS mn,FROM_DAYS(".intval($row['mx']).") AS mx";
                                        $result = mysqli_query($link,$sql) or die ("Couldn't select query : $sql " . mysqli_error($link) . "\n");
                                        if (mysqli_num_rows($result) > 0) {
                                             $res['data'] = mysqli_fetch_assoc($result);
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

	if (!empty($_GET['pretty'])) {
		print indent(json_encode($res));

	} else {
		print str_replace('-INF','0',json_encode($res));
	}
	if (!empty($callback))
		print ");";

#end
###########################################


function getAll($query) {
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

