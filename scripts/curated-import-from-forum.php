<?

//these are the arguments we expect
$param=array('execute'=>0, 'topic'=>31185, 'socket'=>23277, 'group'=>'Geography and Geology', 'delta'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################


if ($param['delta']) {
	$row = $db->getRow(" select post_id,label, (description like 'x%' OR notes like 'x%') as skipped
                from geobb_posts left join curated_headword on (label_id = substring_index(substring_index(post_text,'(label#',-1),')',1))
                where topic_id = {$param['topic']} and poster_id = {$param['socket']} order by post_id desc limit 1");

	if (!empty($row['skipped']) || empty($row['label']))
		die($param['execute']?'':'skped');

	$label = $row['label'];
        $skipped = $row['skipped'];
	$next_post_id = 0;

        $recordSet = $db->Execute("select post_id,gridimage_id,poster_id,post_time
                         from gridimage_post left join geobb_posts using (topic_id,post_id)
                        where topic_id = {$param['topic']} and type ='I' and post_id > {$row['post_id']} order by post_id");

} else {
	$labels = $db->getAll($sql = " select post_id,label, (description like 'x%' OR notes like 'x%') as skipped
		from geobb_posts left join curated_headword on (label_id = substring_index(substring_index(post_text,'(label#',-1),')',1))
		where topic_id = {$param['topic']} and poster_id = {$param['socket']} order by post_id");


	$label = $labels[0]['label'];
	$skipped = $labels[0]['skipped'];
	$next_post_id = $labels[1]['post_id'];

	$recordSet = $db->Execute("select post_id,gridimage_id,poster_id,post_time
			 from gridimage_post left join geobb_posts using (topic_id,post_id)
			where topic_id = {$param['topic']} and type ='I' order by post_id");
}

if (!$recordSet || !$recordSet->RecordCount())
	die($param['execute']?'':'no records');

while (!$recordSet->EOF) {
	//$row = $recordSet->fields;

	while (!empty($next_post_id) && $recordSet->fields['post_id'] > $next_post_id) {
		array_shift($labels);
		$label = $labels[0]['label'];
		$skipped = $labels[0]['skipped'];
		@$next_post_id = $labels[1]['post_id'];

		print str_repeat('#',60)."\n";
		print "## NOW $label\n";

	}

	if ($skipped)  {
		//need to skip each row indiviudally, as still need to loop though them, to keep in sync. 
		if (!$param['execute']) {
			print "SKIPPED $label [{$recordSet->fields['post_id']}]\n";
		}
	} else {


		$updates = array();

	        $updates['gridimage_id'] = $recordSet->fields['gridimage_id'];
        	$updates['group'] = $param['group'];
	        $updates['label'] = $label;
        	$updates['user_id'] = $recordSet->fields['poster_id'];
		$updates['created'] = $recordSet->fields['post_time'];

	        if ($param['execute']) {
        	        $db->Execute($sql = 'INSERT IGNORE INTO curated1 SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	        } else {
			$updates['#post_id'] = $recordSet->fields['post_id'];
	                print_r($updates);
        	}
	}

	$recordSet->MoveNext();
}
$recordSet->Close();

if ($param['execute']) {
	$sql = "UPDATE curated1 INNER JOIN gridimage_search USING (gridimage_id)
        SET decade = CONCAT(SUBSTRING(imagetaken,1,3),'0s')
	WHERE decade ='' AND imagetaken NOT LIKE '0000%'";
	$db->Execute($sql);

	if ($db->Affected_Rows()) {
	        $param = array('table' => 'curated1', 'debug'=>0);
        	include __DIR__."/process_regions.php";
	}
}
