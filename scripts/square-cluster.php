<?

//these are the arguments we expect
$param=array('execute'=>0,'count'=>0,'square'=>'NS5965', 'min'=>5, 'limit'=>1000, 'query'=>'', 'debug'=>false,'sleep'=>0, 'fix'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);

if (!empty($DSN_READ)) {
	$db_read = GeographDatabaseConnection(true);
} else {
	$db_read = $db;
}
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	require '3rdparty/Carrot2.class.php';
	$carrot = Carrot2::createDefault();

if (posix_isatty(STDOUT) && !$param['debug'])
	$param['debug']=1;

############################################

if (!empty($param['fix'])) {
	//$where = " label regexp '&\\\\w+' "; //regex, inside sql, inside php, hence 4 slashes!
	$where = " label regexp binary '&[a-z]{2,}' ";

	$squares = $db_read->getAssoc("SELECT grid_reference,gridsquare_id FROM gridimage_group_stat INNER JOIN gridsquare USING (grid_reference) WHERE $where LIMIT {$param['count']}");

} elseif (!empty($param['count'])) {
	$squares = $db_read->getAssoc("SELECT grid_reference,gridsquare_id FROM gridsquare WHERE imagecount BETWEEN {$param['min']} AND {$param['limit']} AND last_grouped < last_timestamp LIMIT {$param['count']}");

} elseif (!empty($param['square'])) {
	$squares = array(
		$param['square'] => $db_read->getOne("SELECT gridsquare_id FROM gridsquare WHERE grid_reference = ".$db->Quote($param['square']))
	);
}

if (empty($squares)) {
	if ($param['debug'])
		print "No squares to process\n";
	exit(1); //status 1 means the next command in chain doesnt execute!
}
############################################

		function cmp(&$a, &$b) {
		    return strcmp($a->label,$b->label);
		}

foreach ($squares as $square => $gridsquare_id) {
	if (empty($gridsquare_id)) {
		print "unknown id for $square\n";
		exit(1);
	}

	######################
	// fetch text

	$recordSet = $db_read->Execute($sql = "SELECT gridimage_id,title,comment FROM gridimage_search WHERE grid_reference = '{$square}' LIMIT {$param['limit']}");
	$lookup = array();
	$titles = array();
	$comments = array();
	while (!$recordSet->EOF) {
		$row =& $recordSet->fields;

		$lookup[] = $row['gridimage_id'];
		if (substr_count($row['title'],' ') > 0) //skip single words for now (we remove the last word!)
			@$titles[trim($row['title'],' .')][] = $row['gridimage_id'];

		$carrot->addDocument(
			$row['gridimage_id'],
			latin1_to_utf8($row['title']),
                        $comments[$row['gridimage_id']] = strip_tags(str_replace('<br>',' ',latin1_to_utf8($row['comment'])))
		);
		$recordSet->MoveNext();
	}
	$recordSet->Close();

	######################
	// do the carrot processing

	$c = $carrot->clusterQuery($param['query'], $param['debug']==='2', count($lookup)>1500?'stc':'lingo');
	if (empty($c)) {
		if ($param['debug']) {
			//this loop, just tries encoding each description, SimpleXMLElement will emit a fatal error if can't decode!
			foreach ($comments as $image => $value) {
				$enc = mb_detect_encoding($value);
				if ($enc != 'ASCII') { // should no longer ever detect ISO-8859-15

$value = preg_replace('/\\x0B/',"\n",$value);
print "$value\n";
print str_replace('+',' ',urlencode($value))."\n";


					$dom     = new DOMDocument('1.0', 'UTF-8');
					$results = $dom->createElement('searchresult');
					$results->appendChild($dom->createTextNode($value));
					$dom->appendChild($results);
					$xml = $dom->saveXML();
					print "Decoding $image: ($enc)\n";
					$result = new SimpleXMLElement($xml);
				}
			}
		}
		debug_message('[Geograph] Cluster Fail', "No results for $square from Carrot2 (dying without processing any more squares)", 24);
		die("no results for $square (dieing without processing any more squares)\n");
	}


	usort($c, "cmp");

	if (!$param['execute']) {
		foreach ($c as $cluster) {
			// 2. APPLY THE NEW FILTERS
			if (should_discard_label($cluster->label)) {
				if ($param['debug']) {
				    print "## {$cluster->label}    SKIP\n";
				}
				continue;
			}

			$count = count($cluster->document_ids);
			print "{$cluster->label}   x{$cluster->score}    ($count docs)\n";
		}
		//print_r($c);
		print "\n";
		foreach ($titles as $title => $ids)
			if (count($ids) > 1)
				print "Title: $title  (".count($ids)." docs)\n";
		exit;
	}

	if ($param['debug'])
		print "found ".count($c)." clusters for $square\n";

	######################
	// store the carrot2 data

	$db->Execute("delete gridimage_group.* from gridimage inner join gridimage_group using (gridimage_id) where gridsquare_id = $gridsquare_id and source in ('carrot2','title')");
	if ($param['debug'])
		print "clear1\n";

	foreach ($c as $cluster) {
		if ($param['debug']) {
			$count = count($cluster->document_ids);
			printf("%5d. %s ",$count,$cluster->label);
		}
		//we always filter these out, so might as well not bother even saving!
		// where label not in ('(other)','Other Topics')
		if ($cluster->label == 'Other Topics' || $cluster->label == '(Other)') {
			if ($param['debug'])
				print "\n";
			continue;
		}

		// 2. APPLY THE NEW FILTERS
		if (should_discard_label($cluster->label)) {
			if ($param['debug']) {
			    print "  SKIP\n";
			}
			continue;
		}

		$values = array();
		foreach ($cluster->document_ids as $sort_order => $document_id) {
                        $updates = array();

                        $updates['gridimage_id'] = $lookup[$document_id];
                        $updates['label'] = $cluster->label;
                        $updates['score'] = floatval($cluster->score);
                        $updates['sort_order'] = $sort_order;
                        $updates['source'] = 'carrot2';

                        //$db->Execute('INSERT INTO gridimage_group SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			//print ".";
			$updates['label'] = $db->Quote($updates['label']);
			$updates['source'] = $db->Quote($updates['source']);
			$values[] = "(".implode(',',$updates).")";
		}
		$sql = "INSERT INTO gridimage_group (`".implode('`,`',array_keys($updates))."`) VALUES ".implode(',',$values);
		$db->Execute($sql);
		if ($param['debug'])
			print ".. ".$db->Affected_Rows()." affected\n";
	}

	#############################
	// do custom title prefix custering

	$group_by_id = array();
	$group_by_stem = array();
	foreach ($titles as $title => $ids) {
	        $words = explode(' ',trim($title,'. '));
	        array_pop($words); //remove the LAST word!
	        $stem = preg_replace('/[^\w]+$/','',implode(' ',$words));  //the replace removes commas etc from end of words (so 'The Black Horse, Nuthurst', necomes 'The Black Horse')

		if (empty($stem))
			continue;

		foreach ($titles as $title2 => $ids2) {
			//if ($title != $title2)
			//	print "$title != $title2 && strpos($title2,$stem) == ".strpos($title2,$stem)."\n";
			if ($title != $title2 && strpos($title2,$stem) === 0) {
				foreach ($ids as $id)	@$group_by_id[$id][$stem]=1;
				foreach ($ids2 as $id)	@$group_by_id[$id][$stem]=1;

				foreach ($ids as $id)	@$group_by_stem[$stem][$id]=1; //need to store ides to deduplciate
				foreach ($ids2 as $id)	@$group_by_stem[$stem][$id]=1;
			}
        	}
	}
	//print_r2($group_by_id);
	//print_r2($group_by_stem);
	$values = array();
	foreach ($group_by_id as $id => $stems) {
		$longest = null;
		$length = 0;
		foreach ($stems as $stem => $dummy)
			if (strlen($stem) > $length && count($group_by_stem[$stem]) > 1) { //only interested in stems with multiple anyway!
				$longest = $stem;
				$length = strlen($stem);
			}
//		if ($param['debug'])
//			print "$id, $longest (".count($group_by_stem[$stem])." docs)\n";
		if ($longest) {
			if ($longest == 'The' || $longest == 'Looking') //may need to blacklist more, but this one to start!
				continue;

			// 2. APPLY THE NEW FILTERS
			if (should_discard_label($longest." #")) {
				if ($param['debug']) {
				    print "  SKIP $longest #\n";
				}
				continue;
			}
			if ($param['debug']) {
				print "SAVING $longest #\n";
			}


                        $updates = array();

                        $updates['gridimage_id'] = $id;
                        $updates['label'] = $longest." #";
                        $updates['source'] = 'title';

			$updates['label'] = $db->Quote($updates['label']);
			$updates['source'] = $db->Quote($updates['source']);
			$values[] = "(".implode(',',$updates).")";
		}
	}

	if (!empty($values)) {
		$sql = "INSERT INTO gridimage_group (`".implode('`,`',array_keys($updates))."`) VALUES ".implode(',',$values);
		$db->Execute($sql);
		if ($param['debug'])
			print ".. ".$db->Affected_Rows()." affected\n";
	}

	######################
	//update the stats table

	$db->Execute("delete from gridimage_group_stat where grid_reference = '{$square}'");
	if ($param['debug'])
		print "clear2";

	//copied almost as is from RebuildGridimageGroupStat.class.php (just changed the where clause!)
        $sql = "
                select null as gridimage_group_stat_id, grid_reference, label
                        , count(*) as images, count(distinct user_id) as users
                        , count(distinct imagetaken) as days, count(distinct year(imagetaken)) as years, count(distinct substring(imagetaken,1,3)) as decades
                        , min(submitted) as created, max(submitted) as updated, gridimage_id
                        , SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(submitted ORDER BY submitted),',',2),',',-1) AS `second`
                        , avg(wgs84_lat) as wgs84_lat, avg(wgs84_long) as wgs84_long
                from gridimage_group inner join gridimage_search using (gridimage_id)
                where label not in ('(other)','Other Topics') and grid_reference = '{$square}'
                group by grid_reference, label having images > 1 order by null";
		//TODO, mariadb, supports LIMIT in group_concat! so GROUP_CONCAT(submitted ORDER BY submitted LIMIT 1,1) AS `second` should work!!

	$db->Execute("INSERT INTO gridimage_group_stat $sql");
	if ($param['debug'])
		print " grouped\n";

	######################

	$db->Execute("UPDATE gridsquare SET last_grouped = NOW(),last_timestamp=last_timestamp WHERE gridsquare_id = $gridsquare_id");

	######################

	print "done $square.\n";

	if (!empty($_SERVER['BASE_DIR']) && file_exists($_SERVER['BASE_DIR'].'/shutdown-sential'))
        	break;

	if ($param['sleep'])
		sleep($param['sleep']);
	$carrot->clearDocuments();
}




function print_r2($var) {
	print str_repeat('#',80)."\n";
		$trace = debug_backtrace();
		print ' In ' . $trace[0]['file'] .' on line ' . $trace[0]['line']."\n";

		if (empty($var))
			var_dump($var);
		else
			print_r($var);
	print str_repeat('~',80)."\n";
}

//created by gemini 
// https://gemini.google.com/app/f1eb17284e3eab2b

function should_discard_label($label) {
    $label_lower = strtolower(trim($label));
    

// 1. "Pure Scaffolding" - Words that never contribute subject value
$scaffolding = [
    'looking', 'towards', 'view', 'viewed', 'viewing', 'from', 'the', 'opposite', 
    'direction', 'centre', 'center', 'middle', 'contains', 'includes', 'shows', 
    'photo', 'image', 'picture', 'styles', 'taken', 'on', 'at', 'with', 'and',
    'opposite', 'beside', 'facing', 'across', 'side', 'part', 'area'
];

// Nouns that are only useful when paired with something else
$generic_nouns = [
    'north', 'south', 'east', 'west', 'ireland', 'scotland', 'uk', 'britain', 
    'typical', 'usual', 'background', 'foreground', 'various', 'certain', 'approx'
];

// Not good starts for a title cluster
$dead_roots = ['on', 'the', 'a', 'an', 'at', 'by', 'from', 'with', 'in', 'of', 'great', 'small', 'near'];

    $noise = array_merge($scaffolding, $generic_nouns, $dead_roots);

    $words = preg_split('/\s+/', $label_lower, -1, PREG_SPLIT_NO_EMPTY);
    
    $has_substance = false;
    foreach ($words as $w) {
        // A word has "substance" if it's NOT in our noise lists
        if (!in_array($w, $scaffolding) && !in_array($w, $generic_nouns)) {
            // It's a "real" word (like Beach, Cathedral, Harbour, or Dundeady)
            $has_substance = true;
            break;
        }
    }

    // If the phrase is just scaffolding (e.g. "Looking towards"), DISCARD.
    if (!$has_substance) {
        // Special case: If it's a phrase like "Looking West", it's still noise 
        // because "West" is in generic_nouns.
        return true;
    }

    // 3. Handle Stemmed Tags (#)
    if (strpos($label_lower, '#') !== false) {
        // Remove # and split into words
        $stem_text = trim(str_replace('#', '', $label_lower));
        $words = preg_split('/\s+/', $stem_text, -1, PREG_SPLIT_NO_EMPTY);
        
        // If empty or all words are in the dead_roots/noise list, discard
        $meaningful = false;
        foreach ($words as $w) {
            if (!in_array($w, $dead_roots) && !in_array($w, $noise)) {
                $meaningful = true;
                break;
            }
        }
        if (!$meaningful) return true;
    }

    // 4. Single Word Manual Noise Check
    if (!strpos($label_lower, ' ')) {
        if (in_array($label_lower, $noise)) return true;
        if (strlen($label_lower) < 3) return true; // Discard very short garbage
    }

    // 5. Generic Phrase Check (e.g., "View looking")
    $words = preg_split('/\s+/', $label_lower, -1, PREG_SPLIT_NO_EMPTY);
    $all_noise = true;
    foreach ($words as $w) {
        if (!in_array($w, $noise)) {
            $all_noise = false;
            break;
        }
    }
    if ($all_noise) return true;

    return false;
}
