<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

############################################

$param = array('limit'=>'1', 'interval'=>'10 day', 'verbose'=>0, 'log'=>0);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($filesystem)) //eventually gloabl will do this!
	$filesystem = new FileSystem(); //sets up configuation automagically

if (!empty($param['log'])) $filesystem->log = true;

if (!empty($param['verbose'])) {
	$GLOBALS['curl_verbose'] = $param['verbose'];
	print_r($filesystem);
}

############################################

$db = GeographDatabaseConnection(false);

//todo, add since filter?
$where = "type='original' and status = 'accepted'";
if (!empty($param['interval']))
	$where .= " AND suggested > date_sub(now(),interval {$param['interval']})";


$ids = $db->getCol("select gridimage_id from gridimage_pending where $where group by gridimage_id div 100 limit {$param['limit']}");


foreach ($ids as $id) {

	$image = new GridImage();
	$image->gridimage_id = $id;
	$image->user_id = 0;  //fake, all we want is right folder set in path

	$path = $image->_getFullpath(false,false);
	list($bucket,$prefix) = $filesystem->getBucketPath($_SERVER['DOCUMENT_ROOT'].dirname($path)."/");


	print "list($bucket,$prefix)\n";


	if (!empty($bucket)) {

	// public static function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = fa$

		//we set unlimited, but make sure to only specify a deep folder.
	        $list = $filesystem->getBucket($bucket, $prefix, null, null, '/', false, true);

		if (empty($list)) {
			print "empty list. Trying again... \n";
			sleep(4);

			//shouldnt EVER be empty. means a transient failure, so we retyr
			$list = $filesystem->getBucket($bucket, $prefix, null, null, '/', false, true);
			if (empty($list)) {
				die("failed second time too!\n");
			}
		}

		foreach ($list as $filename => $row) {

			if (!empty($param['log'])) {
		                if (!empty($row['prefix'])) { //a virtual directory!
        		                printf("%s %10s %16s %16s %s\n",
                		                 'd', '', '', '', $filename);
	                	} else {
        	                	printf("%s %10d %16s %16s %s   %s\n",
	                                 '-', $row['size'], date('Y-m-d H:i:s',$row['time']), $row['hash'], $filename, $row['class']);
		                }
			}

			$updates= array();
			$updates['basename'] = basename($filename);

			if (!preg_match("/^\d+_\w{8}(_640x640|)\.jpg$/",$updates['basename'],$m)) //only interested in 640px which includes 'full' image!)
				continue;
			$updates['class'] = $m[1]?'thumb.jpg':'full.jpg';

			$updates['s3_date'] = date('Y-m-d H:i:s',$row['time']);
			$updates['md5sum'] = $row['hash'];
			$updates['s3_size'] = $row['size'];
			$updates['s3_class'] = preg_replace('/([A-Z])[A-Z]+/','$1',$row['class']);


 		       $db->Execute($sql = 'INSERT INTO full_md5 SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
		 	        	   ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
        	              		  array_merge(array_values($updates),array_values($updates))) or die("$sql\n\n".mysql_error()."\n");

			print ".";
        	}
		print "\n";
	}
}
