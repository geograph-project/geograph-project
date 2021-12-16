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

$param = array('limit' => 10, 'skip'=>0, 'sleep'=>0, 'folder'=>'/mnt/efs/data/','filename'=>'geograph_dataset001', 'v'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false); //get it from the master, just incase slave has a slightly differnt sample table!
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

######################################################################################################################################################

if (empty($param['v'])) {
	$vs = $db->getAssoc("SELECT v,COUNT(*) FROM gridimage_sample GROUP BY v HAVING COUNT(*) > 9000");

	$check_http = true;
	foreach ($vs as $v => $count) {
		$filename = sprintf('geograph_dataset%03d',$v);

		if (file_exists($param['folder'].'facets/'.$filename.'.zip')) {
			print "#skipping $filename (Zip) [ ".intval(trim(`wc -l {$param['folder']}$filename/$filename.metadata.csv`))." lines ] [ wget https://staging.data.geograph.org.uk/facets/$filename.zip ] \n";
			continue;
		}

		if (file_exists($param['folder'].$filename)) {
			print "#skipping $filename (Folder) [ ".intval(trim(`wc -l {$param['folder']}$filename/$filename.metadata.csv`))." lines ]\n";
			continue;
		}

		if ($check_http) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://data.geograph.org.uk/datasets/$filename.zip");
			curl_setopt($ch, CURLOPT_NOBODY, true);
			//curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_exec($ch);
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($code == '200') {
				print "#skipping $filename (HTTP)\n";
				continue;
			}
		}

		$count+=100;
		print "php {$argv[0]} --v=$v --filename=$filename --config={$param['config']} --limit={$count}\n";
		$check_http = false; //once found one missing, stop bothering with http
	}
	exit;
}



######################################################################################################################################################

$filesystem = GeographFileSystem();
//$filesystem->log = true;

$data= $db->getAll("
SELECT gridimage_id,user_id,realname,title,grid_reference,imagetaken, wgs84_lat,wgs84_long
	, date(submitted) as submitted, width, height, original_width
FROM gridimage_sample
	INNER JOIN gridimage_search USING (gridimage_id)
	LEFT JOIN gridimage_size USING (gridimage_id)
WHERE v={$param['v']}
LIMIT {$param['limit']}");

if (empty($data))
	die("no records\n");

######################################################################################################################################################

print "Writing ".count($data)." to {$param['folder']}{$param['filename']}\n";

if (!is_dir($param['folder'].$param['filename'])) //create a folder to store the tmp files
	mkdir($param['folder'].$param['filename'], null, true);

$h = fopen($param['folder'].$param['filename'].'/'.$param['filename'].'.metadata.csv','wb'); //we writing utf8!
$keys = array_keys($data[0]);
array_unshift($keys,'filename'); foreach(range(1,4) as $l) { array_pop($keys); } //remove not needed
fputcsv($h,$keys);


$c=0;
foreach ($data as $row) {
	if (!($c%10)) {
		if (disk_free_space('/tmp') < 20000000)
			die("Not enough freespace on /tmp\n");
	}
	$c++;

	$image = new GridImage();
	$image->fastInit($row);

	if ($c > $param['skip']) {
		if (($row['width'] < 255 || $row['height'] < 255) && $row['original_width'] > 255) {
			$path = $image->getSquareThumbnail(255,255,'path', true, '_original');
		} else {
			$path = $image->getSquareThumbnail(255,255,'path');
		}

		if (!empty($path) && basename($path) != 'error.jpg') {

			$local = $_SERVER['DOCUMENT_ROOT'].$path;
			list($sbucket, $sfilename) = $filesystem->getBucketPath($local);

			//its already downloaded (by getSquareThumbnail) we just want its local filename (which comes from cache!)
        	        $tmp_src = $filesystem->_get_remote_as_tempfile($sbucket, $sfilename);

			//print "$path => $tmp_src\n";

			$output = $param['folder'].$param['filename'].$path;

			$dir = dirname($output);
			if (!is_dir($dir))
				mkdir($dir, null, true);

			rename($tmp_src, $output);

			if (!file_exists($output))
				die( $output. " - not found\n");

			touch($output, strtotime($row['submitted']));
		}
	} else {
		//assume these already downloaded
		$path = $image->getSquareThumbnail(255,255, 'path', false);
		$sfilename = preg_replace('/^\//','',$path);
	}

	$row['title'] = latin1_to_utf8($row['title']);
	$row['realname'] = latin1_to_utf8($row['realname']);
	unset($row['submitted']);
	unset($row['width']);
	unset($row['height']);
	unset($row['original_width']);

	fputcsv($h,array($sfilename)+$row);

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
}
print "\n\n";

##################################

print "cd {$param['folder']}}{$param['filename']}\n";
	chdir("{$param['folder']}{$param['filename']}");
print "cp -p ../LICENCE ./\n";
	passthru("cp -p ../LICENCE ./");
print "zip -rq {$param['filename']}.zip LICENCE {$param['filename']}.metadata.csv photos/ geophotos/\n";
	passthru("zip -rq {$param['filename']}.zip LICENCE {$param['filename']}.metadata.csv photos/ geophotos/");

##################################

if ($_SERVER['CLI_HTTP_HOST'] == 'staging.geograph.org.uk') { //even if using live config

	print "mv {$param['filename']}.zip ../facets/\n";
		passthru("mv {$param['filename']}.zip ../facets/");
	print "#--\n";
	print "cd /mnt/efs/data/datasets\n";
	print "wget https://staging.data.geograph.org.uk/facets/{$param['filename']}.zip\n";
	print "#--\n";
	print "rm ../facets/{$param['filename']}.zip\n";
} else {
        print "mv {$param['filename']}.zip ../datasets/\n";
                passthru("mv {$param['filename']}.zip ../datasets/");
}

##################################

print "rm {$param['folder']}{$param['filename']} -R\n";

