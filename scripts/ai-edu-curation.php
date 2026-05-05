<?php

// Script parameters
$param = array('print' => false, 'execute'=>false, 'gen'=>1);

chdir(__DIR__);
require "./_scripts.inc.php";

#########################################################

//not using LLM for this!
//require_once "3rdparty/llm-providers.inc.php"; // Provides getLLMResponse and other functions (and now classify_tags_batch)

//instead, is PE powered! Our imagelist has useful functions for sertting up filters for the image index
               $filesystem = new FileSystem(); //sets up configuation automagically
                //the vector lip needs S3 class setup already!
require_once('geograph/imagelists3vector.class.php');
$imagelist=new ImageListS3Vector;

//$imagelist->model = 'pe';
$imagelist->setModel('pe'); //so sets the right region!


$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#########################################################

$regions = $db->getAssoc("select region,count(*) from sphinx_placenames group by region");

if ($param['gen'] == 1) {
	//$labels = $db->getAll("select stack,name,clip_query from curated_label where length(clip_query) > 5");
	$labels = $db->getAll("select stack,name,clip_query
	 from curated_label  left join curated1 c on (`group` = 'Automated' and c.label = name)
	 where length(clip_query) > 5 AND curated_id IS NULL");

} elseif ($param['gen'] == 2) {
	$labels = $db->getAll("select label as name, concat('[id:',gridimage_id,']') as clip_query, score
	 from curated1 where score>=20 and cosine is not null AND gen=1");

}

foreach ($labels as $row) {
	$query = $row['clip_query'];

	print "Label: {$row['name']}: ".substr($query,0,30)."...\n";

	$criteria = array(
		'label' => $query,  //it will automatically 'encode'
		//largest != 640
	);
	//$imagelist->getImagesByCriteria($criteria, $limit = 30);
	//we dont need the full image results, by getting the vectors directly we can get the distance!
	print "\tNational: ";
	$vectors = $imagelist->getRawVectorsByCriteria($criteria, 100, true); //use the metadata to get region!
	if (!empty($vectors['vectors'])) {
		print count($vectors['vectors'])."\n";
		//get 'national' results
		$regions = save_results($row, $vectors);

		//print_r($regions);
		//repeat for specific regions, to try to make sure have good coverage
		foreach($regions as $region => $count) {
			if ($count < 4 || $count > 30) { //if got 30, no point do second query!{
				print "\t Skiping $region ($count)\n";
				continue;
			}

			print "\t$region: ";
			//repeat for this region specifically
			$criteria['region'] = $region;

			$vectors = $imagelist->getRawVectorsByCriteria($criteria, 30, true); //use the metadata to get region!
			if (!empty($vectors['vectors'])) {
				print count($vectors['vectors']);
		                save_results($row, $vectors);
			}
			print "\n";
		}
	} else {
		print "\n";
	}
}


function save_results($row, $vectors) {
	global $param, $db;

	$regions = array();
	if (!empty($vectors['vectors'])) {
		foreach($vectors['vectors'] as $result) {
			$updates = array();
			$updates['user_id'] = 23277; //Socket
			$updates['group'] = 'Automated';
			$updates['label'] = $row['name'];
			if (!empty($result['metadata']['region']))
				$updates['region'] = $result['metadata']['region'];
			$updates['decade'] = substr($result['metadata']['taken'],0,3)."0s";
			$updates['gridimage_id'] = intval($result['key']);
			$updates['cosine'] = floatval($result['distance']);
			if (!empty($row['score'])) {
				$updates['src_query'] = $row['clip_query']." #score=".$row['score'];
				//todo, we could 'skip' the row, if its the image from the query, BUT insert ignore, will help!
			} else {
				$updates['src_query'] = $row['clip_query'];
			}
			$updates['gen'] = $param['gen'];

			if (empty($param['execute'])) {
				print_r($updates);exit;
			}

	                $db->Execute($sql = 'INSERT IGNORE INTO curated1 SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',
	                        array_values($updates)) or die("$sql\n\n".$db->ErrorMsg()."\n");

			@$regions[$result['metadata']['region']]+=$db->Affected_Rows();
		}
	}
	return $regions;
}


