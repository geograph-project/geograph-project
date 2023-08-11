<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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

$param = array('limit' => 10, 'skip'=>0, 'sleep'=>0, 'folder'=>'/mnt/efs/data/','filename'=>'geograph_visiondata010', 'source'=>'sample8', 'large'=>false, 'shard' => '',
	'query'=>'london','group'=>'type_ids','n'=>3,'debug'=>1,'sample'=>false,'minimum'=>false,'missing'=>false,'top'=>false,'extends'=>false,'skipprefixes'=>1);

//WARNING the 'large' param should be used with caution. There is no way to cache differnt thumbs for same image

chdir(__DIR__);
require "./_scripts.inc.php";

$sph = GeographSphinxConnection('sphinxql',true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

######################################################################################################################################################

$filesystem = GeographFileSystem();
//$filesystem->log = true;

$match = $sph->Quote($param['query']);
$images = $labels = 0;

	//$cols is what gets writtne to metadata, extracols are just for sizeing, and 'poped' off end of rows. The group by columns are then included!
if ($param['source'] == 'sample8') {
	$cols = "id as gridimage_id, user_id,realname,title,grid_reference";
	$extracols = ",width,height,original,submitted";
	$pop = 4; //how many to cols to skip from end
} elseif ($param['source'] == 'curated1') {
	$cols = "gridimage_id, user_id,realname,title,grid_reference";
		//curated1 as its own attribute called gridimage_id - the 'id' is the curated1 id!
	$extracols = "";
	$pop = 0;
} else {
	die("unknown source");
}

//grouping by a MVA needs special support
$second = '';
if (preg_match('/(\w+)_ids$/',$param['group'],$m)) {
	$extractfrom = $m[1];
	$pop+=3;
}

########################################
//this is a fake dataset that isnt actully labeled!

if ($param['group'] == 'drone') {

	$limit = $param['limit'];
	if ($limit > 1000)
		$limit .= " OPTION max_matches=$limit";

	$data= $sph->getAll($sql = "
	SELECT $cols $extracols
	FROM {$param['source']}
	WHERE MATCH($match)
	LIMIT $limit");

########################################
// a special case, only want 'cities', not all places for now

} elseif ($param['group'] == 'place') {

	$db = GeographDatabaseConnection(true);

	//want the mane exactly as is from sphinx_placenames
	$groups = $db->getAll("select p.Place,p.County from sphinx_placenames  p inner join os_gaz on (seq = placename_id -1000000) where f_code = 'C' and placename_id > 1000000");

	$limit = $param['n'];
	if ($limit > 1000)
		$limit .= " OPTION max_matches=$limit";

	$data= array();
	foreach ($groups as $group) {
		$where = "place = ".$sph->Quote($group['Place']);
		$where .= " AND county = ".$sph->Quote($group['County']);

		$match = $sph->Quote(preg_replace('/[^\w]+/',' ',$group['Place'])); //ignores $param['query'] !?!

		$data = array_merge($data, $sph->getAll($sql = "
		SELECT $cols, place $extracols
		FROM {$param['source']}
		WHERE MATCH($match) AND $where
		LIMIT $limit"));

		print "$sql;\n";
		$labels++;
	}

########################################
// a special case, only want non location snippets

} elseif ($param['group'] == 'snippet_ids') {

	$param['query'] = ''; //its ignored here anyway, update it so the log at end doesnt reflect a non-used query

	$db = GeographDatabaseConnection(true);

			//minimum+top is hardcoded for now!
	$groups = $db->getCol("select snippet_id,count(*) as images
		 from snippet inner join gridimage_snippet using (snippet_id)
		 where enabled = 1 and grid_reference = ''
		 group by snippet_id
		 having images > 20
		 order by images desc limit {$param['limit']}");

	$data= array();
	foreach ($groups as $group) {

		if (!empty($extractfrom))
			$second = ", {$extractfrom}s, $group AS `group`";

		$where = "snippet_ids in ($group)";

		$data = array_merge($data, $sph->getAll($sql = "
		SELECT $cols, {$param['group']} $second  $extracols
		FROM {$param['source']}
		WHERE $where
		ORDER BY sequence ASC
		LIMIT {$param['n']}"));

		print "$sql;\n";
		$labels++;
	}

########################################
// dont have a search index for this

} elseif ($param['group'] == 'vision') {

	$param['query'] = ''; //its ignored here anyway, update it so the log at end doesnt reflect a non-used query

	$db = GeographDatabaseConnection(true);

			//minimum+top is hardcoded for now!
	$groups = $db->getCol("SELECT mid FROM vision_stat where images > 20 and type = 'label' ORDER BY images DESC LIMIT {$param['limit']}");

	$pop = 1;

	//$cols = "id as gridimage_id, user_id,realname,title,grid_reference";
	$data = array();
	foreach ($groups as $mid) {

		$data = array_merge($data, $db->getAll($sql = "
		SELECT $cols, description as `vision`, UNIX_TIMESTAMP(submitted) AS submitted
		FROM gridimage_search
		INNER JOIN vision_results ON (id = gridimage_id)
		WHERE mid = ".$db->Quote($mid)."
		LIMIT {$param['n']}"));

		print "$mid ==> ".count($data)."\n";
		$labels++;
	}

########################################
// while search index has content_ids column there isnt a 'contents' column (for use with extractfrom!)

} elseif ($param['group'] == 'content_ids') {

	$param['query'] = ''; //its ignored here anyway, update it so the log at end doesnt reflect a non-used query

	$db = GeographDatabaseConnection(true);

			//minimum is hardcoded for now! (currently < 1000 so dont need top)
	$groups = $db->getAll("select content_id,foreign_id,source,title from content where images>20 and source in ('article','gallery','themed') AND title NOT like 'SCIMPY%' AND title NOT like 'YOMP %' LIMIT {$param['limit']}");

	$extractfrom = '';  //there isnt a 'contents' column
	$pop = 1;

	$cols = "gridimage_id, user_id,realname,title,grid_reference";
	$data = array();
	foreach ($groups as $row) {
		$title = $db->Quote($row['title']);

		if ($row['source'] == 'article') {
			$data = array_merge($data, $db->getAll($sql = "
			SELECT $cols, $title as `content_ids`, UNIX_TIMESTAMP(submitted) AS submitted
			FROM gridimage_search
			INNER JOIN gridimage_content USING (gridimage_id)
			WHERE content_id = ".intval($row['content_id'])."
			LIMIT {$param['n']}"));
		} else {

			$data = array_merge($data, $db->getAll($sql = "
			SELECT DISTINCT $cols, $title as `content_ids`, UNIX_TIMESTAMP(submitted) AS submitted
			FROM gridimage_search
			INNER JOIN gridimage_post USING (gridimage_id)
			WHERE topic_id = ".intval($row['foreign_id'])."
			LIMIT {$param['n']}"));
		}

		print "$title ==> ".count($data)."\n";
		$labels++;
	}

########################################
// exploit group N by for small numbers

} elseif ($param['n'] < 5) {

	if ($param['n'] > 20)
		die("n=100 causes crash\n");

	if (!empty($extractfrom))
		$second = ", {$extractfrom}s, GROUPBY() AS `group`";

	$data= $sph->getAll($sql = "
	SELECT $cols, {$param['group']} $second $extracols
	FROM {$param['source']}
	WHERE MATCH($match)
	GROUP {$param['n']} BY {$param['group']}
	LIMIT {$param['limit']}");

########################################
// full loop

} else {
	$sql = array();
	$sql['columns'] = array('GROUPBY() as group');
	$sql['tables'] = array($param['source']);
	$sql['wheres'] = array("MATCH($match)");
	$sql['group'] = $param['group'];
	$sql['limit'] = $param['limit'];

	if ($param['group'] == 'imageclass') {
//		$sql['wheres'][] = "imageclass!=''";
		$sql['wheres'][] = "id > 3000000"; //only use images, after tags added, so in theory it just stallwarts using categories, which MIGHt lead to better quality selections?
	}

	if ($param['minimum']) {
		$sql['columns']['images'] = "COUNT(*) AS images";
		$sql['having'] = "images>{$param['minimum']}";
	}
	if ($param['top']) {
		$sql['columns']['images'] = "COUNT(*) AS images";
		$sql['order'] = "images DESC";
	}
	$total = array_sum(explode(',',$param['limit']));
	if ($total > 1000)
		$sql['option'] = "max_matches=".($total+100);

	if (!empty($extractfrom) && ($param['missing'] || $param['skipprefixes'])) {
		//we only need the extra columns if checking missing. otherwise this outer loop doesnt need to do extraction
		$sql['columns'][] = "{$extractfrom}s";
		$sql['columns'][] = $param['group'];

		print sqlBitsToSelect($sql).";\n\n";
		$groups = $sph->getAll(sqlBitsToSelect($sql));
	} else {

		print sqlBitsToSelect($sql).";\n\n";
		$groups = $sph->getCol(sqlBitsToSelect($sql));
	}


$shards = array(
	0=>explode(' ','Lowlands DefenceMilitary Canals HeathScrub LakesWetlandBog ParkandPublicGardens DocksHarbours Historicsitesandartefacts SuburbUrbanfringe FarmFisheryMarketGardening'),
	1=>explode(' ','Paths Communications SportLeisure HousingDwellings RocksScreeCliffs Geologicalinterest AirSkyWeather BusinessRetailServices EstuaryMarine Flatlandscapes BoundaryBarrier BarrenPlateaux Uplands Religioussites'),
	2=>explode(' ','VillageRuralsettlement Industry Grassland Educationalsites WildAnimalsPlantsandMushrooms Moorland PeopleEvents WoodlandForest RiversStreamsDrainage Countryestates Railways QuarryingMining ConstructionDevelopment Waterresources'),
	3=>explode(' ','RoadsRoadtransport BurialgroundCrematorium Airtransport Coastal Healthandsocialservices WasteWastemanagement DerelictDisused Publicbuildingsandspaces CityTowncentre Energyinfrastructure Islands'),
);

	$data = array();
	$value = ''; //gets set by extractfrom
	foreach ($groups as $group) {
		if ($group === 'Unknown')
			continue;

		if (!empty($extractfrom)) {
			if ($param['skipprefixes']) {
				$ids = explode(',',$group[$extractfrom.'_ids']);
                		$names = explode('_SEP_',$group[$extractfrom.'s']);array_shift($names); //the first is always blank!
		                $value = trim($names[array_search($group['group'],$ids)]);

if ($param['group'] == 'context_ids' && strlen($param['shard'])) {
	$value = preg_replace('/[^\w]+/','',$value);
	if (!empty($shards[$param['shard']]) && !in_array($value,$shards[$param['shard']])) {
		 print "Skipping $value\n";
        	 continue;
	}
}

if (strlen($param['shard'])) {
	$crc = sprintf("%u", crc32($value));
	if ($crc%10 != $param['shard']) {
		 print "Skipping $value (crc $crc % ".($crc%10).")\n";
	         continue;
	}
	//todo, keep a list of labels, so can output a custom zip command! (for now use inodes-shard.php!)
}
					//top/type/subject etc, should already be excluded in sample8 index, but include here just in case!
				if (preg_match('/^(top|subject|type|camera|place|season|country|county|suburb|district|island|islands|london borough|bucket|postcode district|postcode|postcode area|time|taken|area postcode|region|near|at|wiki|category|of|off|in|month|name|area|location|approaching|district|p150 hill|p600 hill|location|to|city|between):/',$value)) {
					print "Skipping $value\n";
                                        continue;
				}
			}
			if ($param['missing']) {
				$ids = explode(',',$group[$extractfrom.'_ids']);
                		$names = explode('_SEP_',$group[$extractfrom.'s']);array_shift($names); //the first is always blank!
		                $value = trim($names[array_search($group['group'],$ids)]);

			        $value = preg_replace('/[^\w]+/','',$value);
			        $output = $param['folder'].$param['filename'].'/'.$value;
				if (is_dir($output)) {
					print "Skipping $value\n";
					continue;
				}
			}

			if (is_array($group)) //because missing/skipprefixes needed a lookup!
				$group = $group['group']; //$group actully a array, turn it back to just GROUPBY()
			$second = ", {$extractfrom}s, $group AS `group`";

		} elseif ($param['missing']) {
			$value = $group;
		        $value = preg_replace('/[^\w]+/','',$value);
		        $output = $param['folder'].$param['filename'].'/'.$value;
			if (is_dir($output)) {
				print "Skipping $value\n";
				continue;
			}
		}

		$limit = $param['n'];
		if ($param['group'] == 'type_ids') {
			if ($group == 172412)
				$limit = min(1000,$param['n']*3); //we should have many more Geograph!
			if ($group == 195749)
				continue; //actully we should skip catch all supplemental
			if ($group == 150435)		//if 'crossgrid' (group = 150435) then distance>500? (to exclude ones that would be geo if not so close to gridline)
				$second = ",distance $second";
		}

		if ($limit > 1000)
			$limit .= " OPTION max_matches=$limit";

		if (!empty($extractfrom)) { //if type=mva!
			$where = "{$param['group']} IN ($group)";
		} else { //if type=string
			$where = "{$param['group']} = ".$sph->Quote($group);
		}

		if ($param['group'] == 'imageclass')
			$where .= " AND id > 3000000";

		if (!empty($param['sample']))
			$where .= " GROUP BY ".$param['sample'];

		$data = array_merge($data, $sph->getAll($sql = "
		SELECT $cols, {$param['group']}$second $extracols
		FROM {$param['source']}
		WHERE MATCH($match) AND $where
		LIMIT $limit"));

		print "$group($value) ==> ".count($data)."\n";
		if ($param['debug']) {
			print "$sql;\n";
			exit;
		}
		$labels++;
	}
}

########################################

if (empty($data))
	die("no records\n");

print "$sql;\n\n";
if ($param['debug'])
	exit;

######################################################################################################################################################

if (!is_dir($param['folder'].$param['filename'])) //create a folder to store the tmp files
	mkdir($param['folder'].$param['filename'], null, true);

//log the command
$h = fopen($param['folder'].$param['filename']."/cmd.txt", 'a');
fwrite($h, implode(' ',$argv)."\n");
fclose($h);

######################################################################################################################################################
// ... scan if already already have the file!

$files = array();

if (empty($param['large'])) // There is no way to cache differnt thumbs for same image
if (true) {
	//scan using caches

	$dirs = glob("{$param['folder']}geograph_visiondata*");

	foreach ($dirs as $dir) {
		print "Scanning $dir\n";
	        $filename = "$dir/files.txt";
		//todo, if $param['sahrd'] && dir == $param['folder'].$param['filename']) assume self is changing!

	        if (!file_exists($filename) || filemtime($dir) > filemtime($filename)+300 && $dir == $param['folder'].$param['filename']) {
	                $cmd = "find $dir  -regextype posix-extended -regex '.*/[0-9]+\\.jpg$'";

	                print "$cmd > $filename\n";
                        passthru("$cmd > $filename");
	        }

		$h = fopen($filename,'r');
		while($h&&!feof($h)) {
			$line = trim(fgets($h));
			if (!empty($line))
				$files[basename($line)] = str_replace("{$param['folder']}geograph_visiondata",'X',$line);
		}
		fclose($h);
	}

} else {
	print "Scanning {$param['folder']}geograph_visiondata* for .jpg\n";
	//find /mnt/efs/data/geograph_visiondata0* -regextype posix-extended -regex '.*/[0-9]+\.jpg$'
	$h = popen("find {$param['folder']}geograph_visiondata*  -regextype posix-extended -regex '.*/[0-9]+\\.jpg$'",'r');
	while($h&&!feof($h)) {
		$line = trim(fgets($h));
		if (!empty($line))
			$files[basename($line)] = str_replace("{$param['folder']}geograph_visiondata",'X',$line);
	}
	fclose($h);
}

print "Found ".count($files)." existing files\n";

######################################################################################################################################################

print "Writing ".count($data)." to {$param['folder']}{$param['filename']}\n";

$h = fopen($param['folder'].$param['filename'].'/'.$param['filename'].'.metadata.csv','ab'); //we writing utf8!
if (!filesize($param['folder'].$param['filename'].'/'.$param['filename'].'.metadata.csv')) {
	$keys = array_keys(reset($data));
	array_unshift($keys,'filename'); foreach(range(1,$pop) as $l) { array_pop($keys); } //remove not needed
	fputcsv($h,$keys);
}

$c=0;
$cnt = array();
foreach ($data as $idx => $row) {
	if (!($c%10)) {
		if (disk_free_space('/tmp') < 20000000)
			die("Not enough freespace on /tmp\n");
	}
	$c++;

	$image = new GridImage();
	$image->fastInit($row);

########################################

	//grouping by a MVA needs special support
	if (!empty($extractfrom)) {
		$ids = explode(',',$row[$extractfrom.'_ids']);
                $names = explode('_SEP_',$row[$extractfrom.'s']);array_shift($names); //the first is always blank!
                $value = trim($names[array_search($row['group'],$ids)]);
	} elseif ($param['group'] == 'drone') {
		$value = 'drone';
	} else {
		$value = $row[$param['group']];
	}

	$value = preg_replace('/[^\w]+/','',$value);
	if (empty($value))
		$value = 'None';
	if ($value == 'CrossGrid' && !empty($row['distance']) && is_numeric($row['distance']) && $row['distance'] < 500)
		continue;

	$filename = sprintf('%d.jpg', $row['gridimage_id']);
	$output = $param['folder'].$param['filename'].'/'.$value;

	if (!is_dir($output))
		 mkdir($output, null, true);

	$output .= '/'.$filename;

	if (!empty($param['extends']) && file_exists($param['folder'].$param['extends'].'/'.$value.'/'.$filename))
		continue;

########################################

//	print "$c. $output\n";

	if ($c > $param['skip'] && !file_exists($output) ) {
		if (isset($files[$filename])) {
			$expanded = preg_replace('/^X/',"{$param['folder']}geograph_visiondata", $files[$filename]);

			print "$expanded => $output\n";
			//copy($expanded, $output);
			link($expanded, $output);
			//if (!empty($row['submitted'])) touch($output, $row['submitted']);
		} else {
			if ($param['large'] && $row['original'] > 224) {
				$path = $image->getSquareThumbnail(224,224,'path', true, '_original');
			} elseif (isset($row['width']) && ($row['width'] < 224 || $row['height'] < 224) && $row['original'] > 224) {
				$path = $image->getSquareThumbnail(224,224,'path', true, '_original');
			} else {
				$path = $image->getSquareThumbnail(224,224,'path');
			}

			if (!empty($path) && basename($path) != 'error.jpg') {

				$local = $_SERVER['DOCUMENT_ROOT'].$path;
				list($sbucket, $sfilename) = $filesystem->getBucketPath($local);

				//its already downloaded (by getSquareThumbnail) we just want its local filename (which comes from cache!)
	        	        $tmp_src = $filesystem->_get_remote_as_tempfile($sbucket, $sfilename);

				print "$path => $output\n";

				rename($tmp_src, $output);

				if (!file_exists($output))
					die( $output. " - not found\n");

				if (!empty($row['submitted'])) touch($output, $row['submitted']);
			}
		}
	} else {
		//assume these already downloaded
		//$path = $image->getSquareThumbnail(224,224, 'path', false);
	}

########################################

	//manitcore is already utf8
	//$row['title'] = latin1_to_utf8($row['title']);
	//$row['realname'] = latin1_to_utf8($row['realname']);

	foreach(range(1,$pop) as $l) { array_pop($row); } //remove not needed

	fputcsv($h,array($filename)+$row);
	$images++;

	if (!($c%10)) {
		//we still need to run delete, because if getSquareThumbnail downloaded a big image its in the cache too!
		$filesystem->shutdown_function(); //filesystem class only deletes the temp files on shutdown. We to clear them out as go along
		$filesystem->filecache = array(); //the class doesnt bother clearing the array (as it normally on shutdown anyway)
		print "$c. ";

		if (!($c%1000)) {
			//the S3 token doesnt last forever, so recreate the object periodically to get a new secruity token!
			//S3::putObject(): [ExpiredToken] The provided token has expired.
			$filesystem = new FileSystem(); // dont use GeographFileSystem as it return the same object!
		}

		if (!empty($param['sleep']))
			sleep($param['sleep']);
	}

########################################

}
print "\n\n";

##################################

print "cd {$param['folder']}{$param['filename']}\n";
	chdir("{$param['folder']}{$param['filename']}");
print "cp -p ../LICENCE ./\n";
	passthru("cp -p ../LICENCE ./");
print "zip -rq {$param['filename']}.zip LICENCE {$param['filename']}.metadata.csv */\n";
//	passthru("zip -rq {$param['filename']}.zip LICENCE {$param['filename']}.metadata.csv */");


//print "mv {$param['folder']}{$param['filename']}/*.zip {$param['folder']}/facets/\n";

print "./aws/dist/aws s3 mv --storage-class INTELLIGENT_TIERING --acl public-read {$param['folder']}{$param['filename']}/{$param['filename']}.zip s3://data.geograph.org.uk/datasets/{$param['filename']}.zip\n";


print "insert into dataset set src_format = 'subdir', folder = '{$param['filename']}', imagesize = '224XX224.jpg', grouper='{$param['group']}'";
//print ", src_download = 'https://staging.data.geograph.org.uk/facets/{$param['filename']}.zip'";
print ", src_download = 'https://s3.eu-west-1.amazonaws.com/data.geograph.org.uk/datasets/{$param['filename']}.zip'";
 $size = filesize("{$param['filename']}.zip");
 $time = filemtime("{$param['filename']}.zip");
print ", src_size=$size,src_time=FROM_UNIXTIME($time)";

if (!empty($param['extends']))
	print ", extends = '{$param['extends']}'";
print ", query = '{$param['query']}', images = {$images}, labels = {$labels};\n\n";

print "find {$param['folder']}{$param['filename']}/ -name '*.jpg' | wc -l\n\n";
