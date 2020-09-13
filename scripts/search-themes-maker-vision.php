<?

//these are the arguments we expect
$param=array('execute'=>0,'query'=>'bridge','limit'=>50);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

   $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());

	require '3rdparty/Carrot2.class.php';
	$carrot = Carrot2::createDefault();

############################################

	$param['query'] = '"llyn peninsula" | llynpeninsula';

	$limit = intval($param['limit']);
        $query = $sph->Quote($param['query']);

	if ($limit > 1000)
		$limit .= " OPTION max_matches=".($limit+100);

	/*
	$recordSet = $sph->Execute("select id,realname,user_id,title,grid_reference, integer(ln(weight())) as w2ln , sequence / baysian as combined
	 from sample8 where match($query)
	order by w2ln desc, combined asc limit $limit option field_weights=(place=8,county=6,country=4,title=12,tags=10,imageclass=5) , cutoff=1000000 ");
	*/

	$recordSet = $sph->Execute("select id,realname,user_id,title,grid_reference,descriptions,hash,sequence from vision where match($query) limit $limit");

	if ($param['execute'] > 1) {
		$rows = array();
		while (!$recordSet->EOF) {
			$rows[] = $recordSet->fields;
			$recordSet->MoveNext();
		}
		$recordSet->Close();

		if ($param['execute'] == 3) { //sequence
			function cmpS(&$a, &$b) {
			    if ($a['sequence'] == $b['sequence']) {
			        return 0;
			    }
			    return ($a['sequence'] < $b['sequence']) ? -1 : 1;
                	}
	                usort($rows, "cmpS");

		} elseif ($param['execute'] == 4) { //single label
			$ids = array(); foreach ($rows as $row) $ids[] = $row['id']; $ids = implode(',',$ids);
			$labels = $db->getAssoc("select id,substring_index(group_concat(description order by score desc),',',1) as description
					from vision_results where id in ($ids) group by id order by null");
			foreach ($rows as $idx => $row) {
				$rows[$idx]['label'] = $labels[$row['id']];
			}
                        function cmpL(&$a, &$b) {
				return strcmp($a['label'],$b['label']);
                        }
                        usort($rows, "cmpL");
		}

		foreach ($rows as $row)
			printimage($row);

		exit;
	}







	$lookup = array();
	while (!$recordSet->EOF) {
		$row =& $recordSet->fields;
		$lookup[] = $row;
		$carrot->addDocument(
			(string)$row['id'],
			(string)'', //utf8_encode(htmlentities($row['title'])),
			(string)utf8_encode(htmlentities(trim(str_replace('_SEP_',',',$row['descriptions']),', ')))
		);
		$recordSet->MoveNext();
	}
	$recordSet->Close();

//	$c = $carrot->clusterQuery();
	$c = $carrot->clusterQuery($query_hint='',$debug = false, $algorithm = 'kmeans');

		function cmp(&$a, &$b) {
		    return strcmp($a->label,$b->label);
		}
		//usort($c, "cmp");

	if (!$param['execute']) {
		foreach ($c as $cluster) {
			$count = count($cluster->document_ids);
			print "{$cluster->label}   x{$cluster->score}    ($count docs)\n";
		}
		//print_r($c);
		exit;
	}

	$d=array();
	foreach ($c as $cluster) {
		$count = count($cluster->document_ids);

		foreach ($cluster->document_ids as $sort_order => $document_id) {
			$row = $lookup[$document_id];
			if (isset($d[$row['id']]))
				continue;
			$d[$row['id']]=1;
			//print $row['id'].": ".$row['title']." by ".$row['realname']."\n";
			$row['label'] = $cluster->label;
			printimage($row);
		}
		print "<br style=clear:both>";
	}





########

function printimage($row) {
	$append = '';
        $url = getGeographUrl($row['id'],$row['hash'],'small');
	if (!empty($row['label']))
		$append = " ({$row['label']})";


	print "<div style=\"float:left;width:125px;height:125px\">";
        print "<a href=\"http://www.geograph.org.uk/photo/{$row['id']}\" title=\"".htmlentities($row['title'].' by '.$row['realname'].$append)."\">";
        print "<img src=$url>";
        print "</a>";
	print "</div>\n";
}


function getGeographUrl($gridimage_id,$hash,$size = 'small') {
        $ab=sprintf("%02d", floor(($gridimage_id%1000000)/10000));
      $cd=sprintf("%02d", floor(($gridimage_id%10000)/100));
      $abcdef=sprintf("%06d", $gridimage_id);
                if ($gridimage_id<1000000) {
                        $fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}";
                } else {
                        $yz=sprintf("%02d", floor($gridimage_id/1000000));
                        $fullpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}";
                }
      $server =  "https://s".($gridimage_id%4).".geograph.org.uk";

      switch($size) {
              case 'full': return "https://s0.geograph.org.uk$fullpath.jpg"; break;
              case 'med': return "$server{$fullpath}_213x160.jpg"; break;
              case 'small':
              default: return "$server{$fullpath}_120x120.jpg";

        }
}


