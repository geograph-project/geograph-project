<?

include('3rdparty/facet-functions.php');
require_once('geograph/functions.inc.php');


if (!defined('SPHINX_INDEX')) {
        define('SPHINX_INDEX',empty($_GET['recent'])?"sample8":"sample8D");
}


	customGZipHandlerStart();

if (empty($_GET)) {
	customExpiresHeader(3600*24*24,true);

?>
<h2>JSON API for accessing Geograph's <? if (SPHINX_INDEX == 'sample2') {echo "'sphinx-geograph2.xml' sample";} else { echo SPHINX_INDEX." index";} ?></h2>
<p>This API is pretty much just a direct JSON wrapper around the SphinxAPI. So to use this API it helps to understand <a href="http://sphinxsearch.com/docs/2.0.1/searching.html">searching in 
sphinx</a></p>

<form method="get">
<table>
<tr><td align="right">?q=</td><td><input name="q" value="bridge"></td><td>The full-text query (in SPH_MATCH_EXTENDED format)</td></tr>
<tr><td align="right">&amp;callback=</td><td><input name="callback"></td><td>callback function name for JSONP</td></tr>
<tr><td align="right">&amp;sort=</td><td><input name="sort" value="@relevance DESC, @id DESC"></td><td>Sort by - used with SPH_SORT_EXTENDED (When grouping - its within group order)</td></tr>
<tr><td align="right">&amp;group=</td><td><input name="group"></td><td>Group by (attribute name) - see <a href="?q=&limit=1&select=*&attrs=list">list</a></td></tr>
<tr><td align="right">&amp;groupsort=</td><td><input name="groupsort" value="@group DESC"></td><td>Group Sort by (When grouping - its final result order)</td></tr>
<tr><td align="right">&amp;limit=</td><td><input name="limit" value="15"></td><td>Number of results</td></tr>
<tr><td align="right">&amp;offset=</td><td><input name="offset" value="0"></td><td>Offset (max_matches=1000 so limit+offset must be under 1000)</td></tr>
<tr><td align="right">&amp;select=</td><td><input name="select" value="user_id"></td><td>List of <a href="?q=&limit=1&select=*&attrs=list">attributes</a> to return - can use "*" to get all</td></tr>
<tr><td align="right">&amp;pretty=1</td><td><input type=checkbox name="pretty" value="1"></td><td>Pretty print the json - ONLY use for testing purposes</td></tr>
</table>
<input type=submit>
(All fields - including q - are optional)

</form>

Example Queries:
<ul>
	<li><a href="?q=bridge">Basic full-text query - in json format</a> (<a href="?q=bridge&pretty=1">View Pretty</a>)
	<li><a href="?q=bridge&callback=my_function">Basic full-text query - in jsonp format (for accessing in a webpage)</a>
	<li><a href="?q=bridge&group=user_id">Group by user_id - to get counts per contributor for facets (ordered by @group desc)</a> (<a href="?q=bridge&group=user_id&pretty=1">View Pretty</a>)
	<li><a href="?q=bridge&group=user_id&groupsort=@count+DESC">Group by user_id - ordered by @count DESC (rather than @group desc)</a>
</ul>

<hr/>
<a href="http://data.geograph.org.uk/facets/">back to Faceted Browsing for Geograph</a>

<?	exit;
}


	$cl = sphinx_client();

	if (isset($_GET['a']))
		$cl->SetArrayResult(true);

	if (!empty($_GET['range']) && preg_match('/^\d+,\d+$/',$_GET['range'])) {
		$bits = explode(',',$_GET['range']);
		$cl->SetIDRange(intval($bits[0]),intval($bits[1]));
	}

	$sort = !empty($_GET['sort'])?preg_replace('/[^@\w ,]+/','',$_GET['sort']):"@relevance DESC, @id DESC";
	$cl->SetSortMode( SPH_SORT_EXTENDED, $sort );

	$match = !empty($_GET['match'])?intval($_GET['match']):SPH_MATCH_EXTENDED;
	$cl->SetMatchMode( $match );

	if (!empty($_GET['rank'])) {
		$rank = !empty($_GET['rank'])?intval($_GET['rank']):SPH_RANK_PROXIMITY_BM25;
		$cl->SetRankingMode( $rank );
	} elseif (!empty($_GET['sort']) && preg_match('/^(sequence|id|score) (ASC|DESC)$/',$_GET['sort'])) {
		$cl->SetRankingMode( SPH_RANK_NONE );
	}

	if (!empty($_GET['geo'])) {
		$bits = explode(',',$_GET['geo']);
                $cl->SetGeoAnchor('wgs84_lat', 'wgs84_long', deg2rad($bits[0]), deg2rad($bits[1]) );
		if (!empty($bits[2])) {
	                $cl->SetFilterFloatRange('@geodist', 0.0, floatval($bits[2]));

			if ($bits[2] < 75000 && empty($_GET['q'])) { //todo, could still run it at other times too
				require_once('geograph/global.inc.php');
	        	        require_once('geograph/conversions.class.php');
				$_GET['q'] = geotiles(floatval($bits[0]),floatval($bits[1]),floatval($bits[2]));
			}
		}

		//TODO make a BBOX too?
		if (empty($_GET['bounds']) && isset($_GET['d'])) {
			//top/right  --- north/east
			list($long1,$lat1) = calcLatLong($bits[1],$bits[0],$bits[2]*2.2,45); //sqrt(2) + some leeway
			//bottom/left -- south/west
			list($long2,$lat2) = calcLatLong($bits[1],$bits[0],$bits[2]*2.2,225);

			$_GET['bounds'] = $lat2.",".$long1.",".$lat1.",".$long2;
		}
	}

	if (!empty($_GET['filter'])) {
		foreach ($_GET['filter'] as $key => $value) {
			if (!is_array($value)) {
				$value = array(intval($value));
			}
			if (preg_match('/^\d+(,\d+)*/',implode(',',$value)))
				$cl->setFilter(preg_replace('/_(\d+)$/','',$key),$value);
		}
	}
        if (!empty($_GET['filterrange'])) {
                foreach ($_GET['filterrange'] as $key => $value) {
                        if (preg_match('/^\d+,\d+$/',$value)) {
                                $bits = explode(',',$value);
                                $cl->setFilterRange(preg_replace('/_(\d+)$/','',$key),intval($bits[0]),intval($bits[1]));
                        }
                }
        }
	if (!empty($_GET['exclude'])) {
		foreach ($_GET['exclude'] as $key => $value) {
			if (!is_array($value)) {
				$value = array(intval($value));
			}
			if (preg_match('/^\d+(,\d+)*/',implode(',',$value)))
				$cl->setFilter(preg_replace('/_(\d+)$/','',$key),$value,true);
		}
	}
	if (!empty($_GET['excluderange'])) {
		foreach ($_GET['excluderange'] as $key => $value) {
			if (preg_match('/^\d+,\d+$/',$value)) {
				$bits = explode(',',$value);
				$cl->setFilterRange(preg_replace('/_(\d+)$/','',$key),intval($bits[0]),intval($bits[1]),true);
			}
		}
	}


if (!empty($_GET['bounds'])) {
        $b = str_replace('Bounds','',$_GET['bounds']);
        $b = str_replace('(','',$b);
        $b = str_replace(')','',$b);

        $b = explode(',',$b);

##        $span = max($b[3] - $b[1],$b[2] - $b[0]);

                ###                                         left         right                                     bottom     top
                ### $where = "(`$point_long_column` BETWEEN {$b[1]} AND {$b[3]}) and (`$point_lat_column` BETWEEN {$b[0]} AND {$b[2]})";

	$cl->SetFilterFloatRange('wgs84_lat',deg2rad($b[0]),deg2rad($b[2]));
	$cl->SetFilterFloatRange('wgs84_long',deg2rad($b[1]),deg2rad($b[3]));
} else if (!empty($_GET['olbounds'])) {
	$b = explode(',',trim($_GET['olbounds']));
		#### example: -10.559026590196122,46.59604915850878,7.514135843906623,54.84589681367314

        $cl->SetFilterFloatRange('wgs84_lat',deg2rad($b[1]),deg2rad($b[3]));
        $cl->SetFilterFloatRange('wgs84_long',deg2rad($b[0]),deg2rad($b[2]));
}


	############################
	# Special handling for getting a geographical coverage

		if (!empty($_GET['groupByTile'])) {

			$cl->SetLimits(0, 1);

			$cl->SetSelect("id,max(wgs84_lat) as max_lat,min(wgs84_lat) as min_lat,max(wgs84_long) as max_long,min(wgs84_long) as min_long,1 as one");
			$cl->SetGroupBy('one', SPH_GROUPBY_ATTR);

			$q = trim($_GET['q']);
				$res = $cl->Query( $q, SPHINX_INDEX );

			if (empty($res) || empty($res['total_found']) ) {
				if (empty($res)) {
					$res = array('error'=>$cl->GetLastError());
				}
				$skip = 1;
			} else {
				$result = array_pop($res['matches']);

				$row = $result['attrs'];

				$lat_div = ($row['max_lat'] - $row['min_lat']) / intval($_GET['groupByTile']);
				$long_div = ($row['max_long'] - $row['min_long']) / intval($_GET['groupByTile']);

				$tile1 = "floor( (wgs84_lat-{$row['min_lat']}) / $lat_div )";
				$tile2 = "floor( (wgs84_long-{$row['min_long']}) / $long_div )";

				$cl->ResetGroupBy();
				$_GET['select'] .= ", ($tile1*100) + $tile2 AS tile";
					$_GET['select'] = str_replace('--','+',$_GET['select']); //sphinx doesnt like (-3.456--4.456)
				$_GET['group'] = 'tile';
				if (empty($_GET['limit']))
					$_GET['limit'] =  intval($_GET['groupByTile'])*intval($_GET['groupByTile']);
			}
		}

	###########

	if (empty($skip)) {

		$select = 'user_id'; //just something minimal
		if (!empty($_GET['group'])) {

			$group = !empty($_GET['group'])?preg_replace('/[^@\w ,]+/','',$_GET['group']):"";
			$groupsort = !empty($_GET['groupsort'])?preg_replace('/[^@\w ,]+/','',$_GET['groupsort']):"@group desc";
				$cl->SetGroupBy( $group, SPH_GROUPBY_ATTR, $groupsort );
			$select = '@groupby,@count';
		}


		$select = !empty($_GET['select'])?preg_replace('/[^@\w ,\*\(\)\/\+\.-]+/','',$_GET['select']):$select;

		############################
		# Special handling to account for no sphinx to_days function

			if (preg_match("/to_days\('?([\d-]+)'?\)/",$select,$m)) {
				$link = mysql_database();

				$sql = "SELECT TO_DAYS('{$m[1]}') AS result";
				$result = mysql_query($sql,$link) or die ("Couldn't select query : $sql " . mysql_error($link) . "\n");
				if (mysql_num_rows($result) > 0) {
					$row = mysql_fetch_array($result,MYSQL_ASSOC);
					$select = str_replace($m[0],$row['result'],$select);
				}
			}

		###########

		if ($select) {
			$cl->SetSelect( $select );
		}


		$limit = !empty($_GET['limit'])?intval($_GET['limit']):10;
		$offset = !empty($_GET['offset'])?intval($_GET['offset']):0;
		if (!empty($_GET['high'])) {
                        $cl->SetLimits( $offset, $limit, intval($_GET['high']));
		} else {
			$cl->SetLimits( $offset, $limit);
		}

		$q = trim($_GET['q']);
		$res = $cl->Query( $q, SPHINX_INDEX );

		############################
		#
			if (!empty($_GET['debug'])) {
				print "<pre>";
				print_r($cl);
				print_r($res);
				exit;
			}
		###########

		// --------------

		if ( $res===false )
		{
			$res = array('error'=>$cl->GetLastError());
		} else
		{
			if ( $cl->GetLastWarning() )
				$res['warning'] = $cl->GetLastWarning();


			############################
			#
				if (isset($_GET['fields'])) {
					header("Content-Type: text/plain");
					print "AVAILABLE FIELDS:\n\n";
					print implode("\n",$res['fields']);

					$row = array_pop($res['matches']);
					if ($row) {
						print "\n\nEXAMPLE VALUES:\n\n";
						foreach ($res['fields'] as $name) {
							if (!empty($row['attrs'][$name])) {
								print "'$name' => '".addslashes($row['attrs'][$name])."'\n";
							} else {
								print "'$name' => (this field is not defined as an attribute so can't show example, but it will still be searchable)\n";
							}
						}
					}
					exit;

			############################
			#
				} elseif (isset($_GET['attrs'])) {
					header("Content-Type: text/plain");
					print "AVAILABLE ATTRIBUTE TYPES:\n\n";
					var_export($res['attrs']);

					$row = array_pop($res['matches']);
					if ($row) {
						print "\n\nEXAMPLE VALUES:\n";
						var_export($row['attrs']);
					}
					exit;

			############################
			#
				} elseif (isset($_GET['mnmx']) && !empty($res['matches'])) {

					$link = mysql_database();

					$row = current($res['matches']);

					$sql = "SELECT FROM_DAYS(".intval($row['attrs']['mn']).") AS mn,FROM_DAYS(".intval($row['attrs']['mx']).") AS mx";
					$result = mysql_query($sql,$link) or die ("Couldn't select query : $sql " . mysql_error($link) . "\n");
					if (mysql_num_rows($result) > 0) {
					     $res['data'] = mysql_fetch_array($result,MYSQL_ASSOC);
					}
				}
			###########

			//we store a sort order, because javascript objects dont maintain order
			if (!isset($_GET['a']) && !empty($res['matches'])) {
				$idx = 0;
				foreach ($res['matches'] as $key => $value) {
					$res['matches'][$key]['s'] = $idx++;
				}
			} elseif (empty($res['matches']['total_found'])) {
				unset($res['fields']);
				unset($res['attrs']); //really no point sending these...  they ignore the 'select' anyway.
			}
		}
	}

	if (empty($res) && !empty($res['error'])) {
		customExpiresHeader(90,true);
	} else {
		customExpiresHeader(3600*24,true);
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
		if (!empty($res['matches'])) {
			foreach ($res['matches'] as $idx => $row) {
				if (!empty($row['attrs']['title']))
					$res['matches'][$idx]['attrs']['title'] = utf8_encode($row['attrs']['title']);
				if (!empty($row['attrs']['realname']))
					$res['matches'][$idx]['attrs']['realname'] = utf8_encode($row['attrs']['realname']);
			}
		}

		print str_replace('-INF','0',json_encode($res));
	}
	if (!empty($callback))
		print ");";


