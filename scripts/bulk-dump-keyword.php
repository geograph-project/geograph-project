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

$param = array('sleep'=>0, 'folder'=>'/mnt/efs/data/','filename'=>'geograph_railway_images', 'keyword' => '@(tags,contexts) "_SEP_ railway _SEP_"');

chdir(__DIR__);
require "./_scripts.inc.php";

######################################################################################################################################################

$sph = GeographSphinxConnection('sphinxql',true);

$match = $sph->Quote($param['keyword']);
$filter = '';

$sql = "select id as gridimage_id,realname,title,takenday,user_id,submitted,original
                                FROM sample8 WHERE MATCH($match) \$and $filter ORDER BY id ASC LIMIT 1000 OPTION ranker=none";

######################################################################################################################################################

print "Writing to {$param['folder']}{$param['filename']}\n";
print preg_replace('/\s+/',' ',$sql).";\n";

if (!is_dir($param['folder'].$param['filename'])) //create a folder to store the tmp files
	mkdir($param['folder'].$param['filename'], null, true);

$h = fopen($param['folder'].$param['filename'].'/'.$param['filename'].'.metadata.csv','wb'); //we writing utf8!
$missing = fopen($param['folder'].$param['filename'].'/'.$param['filename'].'.missing.txt','w');

$loop = 1;
$c = 0;
$lastid = 0;
while (true) {
	print "Loop $loop from $lastid";
	if (!($c%10)) {
		if (disk_free_space('/tmp') < 20000000)
			die("Not enough freespace on /tmp\n");
	}

	$and = ($lastid)?" AND id > $lastid":'';

	$recordSet = $sph->Execute(str_replace('$and', $and, $sql));
	$meta = $sph->getAssoc("SHOW META");

	if ($loop == 1) {
		print_r($meta);
		$keys = array_keys($recordSet->fields);
		array_unshift($keys,'filename'); foreach(range(1,2) as $l) { array_pop($keys); } //remove not needed
		fputcsv($h,$keys);
	}

	$count = $recordSet->RecordCount();
	if (!$count)
		break;
	print "=$count/{$meta['total_found']}. ";

	//the S3 token doesnt last forever, so recreate the object periodically to get a new secruity token!
	//S3::putObject(): [ExpiredToken] The provided token has expired.
	$filesystem = new FileSystem(); // dont use GeographFileSystem as it return the same object!

	while (!$recordSet->EOF) {
		$row =& $recordSet->fields;
		$row['takenday'] = preg_replace('/(\d{4})(\d{2})(\d{2})/','$1-$2-$3',$row['takenday']);

		$image = new GridImage();
		$image->fastInit($row);

		if (!empty($row['original'])) {
			$path = $image->_getOriginalpath(false, false);
		} else {
			$path = $image->_getFullpath(false, false); //we dont check existinence, but if did then use $use_get=2 so that it downloads it, rather than just using HEAD
		}

		$output = $param['folder'].$param['filename'].$path;

		if (!file_exists($output)) {
			//do this 'long' form, so we can move the actual temp file. Using $filesystem->copy() would actully copy the tmp file!
			$local = $_SERVER['DOCUMENT_ROOT'].$path;
			list($sbucket, $sfilename) = $filesystem->getBucketPath($local);

			//its already downloaded (by getSquareThumbnail) we just want its local filename (which comes from cache!)
        	        $tmp_src = $filesystem->_get_remote_as_tempfile($sbucket, $sfilename);

			//print "$path => $tmp_src\n";

			$dir = dirname($output);
			if (!is_dir($dir))
				mkdir($dir, null, true);

			rename($tmp_src, $output);

			if (file_exists($output))
				touch($output, strtotime($row['submitted']));
			else
				fwrite($missing, "$sfilename\n");
			//	die( $output. " - not found\n");
		} else {
			$sfilename = preg_replace('/^\//','',$path);
		}

		//sphinx/manticore is already utf8
		//$row['title'] = latin1_to_utf8($row['title']);
		//$row['realname'] = latin1_to_utf8($row['realname']);
		unset($row['submitted']);
		unset($row['original']);

		fputcsv($h,array($sfilename)+$row);

		$lastid = $image->gridimage_id;
		$c++;
	        $recordSet->MoveNext();
		usleep(2000);
	}
	$recordSet->Close();

	//we doing to create a new object next time, so need to let it cleanup!
	$filesystem->shutdown_function(); //filesystem class only deletes the temp files on shutdown. We to clear them out as go along
	$filesystem->filecache = array(); //the class doesnt bother clearing the array (as it normally on shutdown anyway)

	if (!empty($param['sleep']))
		sleep($param['sleep']);
	$loop++;
}
print "\n\n";

##################################

print "cd {$param['folder']}{$param['filename']}\n";
//	chdir("{$param['folder']}{$param['filename']}");
print "cp -p ../LICENCE ./\n";
//	passthru("cp -p ../LICENCE ./");
print "zip -rq {$param['filename']}.zip LICENCE {$param['filename']}.metadata.csv photos/ geophotos/\n";
//	passthru("zip -rq {$param['filename']}.zip LICENCE {$param['filename']}.metadata.csv photos/ geophotos/");

##################################

print "rm {$param['folder']}{$param['filename']} -R\n";

