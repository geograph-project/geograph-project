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

$param = array('max'=>10, 'verbose'=>0, 'log'=>0, 'file'=>false);

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

if (!empty($param['file'])) {
	list($localbucket, $localpath) = $filesystem->getBucketPath($param['file']);

	$local = $filesystem->_get_remote_as_tempfile($localbucket, $localpath);

	print "$local\n";
	print `ls -l $local`;
	print `identify $local`;

	exit;
}

############################################

$db = GeographDatabaseConnection(true);

$rows = $db->getAll("SELECT gridimage_id,user_id FROM gridimage_search
inner join gridimage_thumbsize using (gridimage_id)
 where maxw=800 LIMIT {$param['max']}");

$stat = array();
foreach ($rows as $row) {
	$image = new GridImage();
	$image->fastInit($row);


	$hash = $image->_getAntiLeechHash();

	$url = "https://{$param['config']}/reuse.php?id={$row['gridimage_id']}&download=$hash&size=800";


	$r = rand(1,3);
	if ($r == 1) {
		$method = '';
	} elseif ($r == 2) {
		$method = 'send';
	} else {
		$method = 'read';
	}


		$url .= "&method=$method";

		$before = microtime(true);

		file_get_contents($url);

		$after = microtime(true);

print ".";

		@$stat[$method][] = $after - $before;
}

//print_r($stat);

foreach ($stat as $method => $data) {
        $sum = array_sum($data);
        $count = count($data);

        printf("%40s : %.3f   (%.3f/%d)\n", $method, $sum/$count, $sum, $count );
}
