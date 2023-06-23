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

$param = array('days'=>'1','limit'=>1);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$table = "index_coverage";


$c =0;

foreach (glob("/tmp/"."*-Coverage-*.zip") as $zipfile) {

	if (file_exists("$zipfile.done"))
		continue;


	print "$zipfile\n";
	$zp = zip_open($zipfile);

	$issue = null;
	$lines = array();
	while ($file = zip_read($zp)) {
		$filename = zip_entry_name($file);

		//print "$filename\n";
		if (zip_entry_open($zp, $file)) {
			$contents = zip_entry_read($file,1000000);
			//echo "$contents<br>";

			if ($filename == 'Metadata.csv') {
				foreach(explode("\n",str_replace("\r","",$contents)) as $line) {
					$bits = str_getcsv($line); //might have commas issues
					if ($bits[0] == 'Issue')
						$issue = $bits[1];
				}
			} elseif ($filename == 'Table.csv') {
				$last = '';
				foreach(explode("\n",str_replace("\r","",$contents)) as $line) {
/*
https://www.geograph.ie/photo/1030752,2022-11-09
"https://www.geograph.ie/stuff/list.php?title=Belcruit - St Mary's Catholic
Church&gridref=B7419",2022-09-24
"https://www.geograph.ie/stuff/list.php?title=The Rosses - Bridge to Cruit
Island&gridref=B7318",2022-09-22
https://www.geograph.ie/photo/3230423,2022-09-03
*/
					//need to cope with long URLs being wrapped!
					if ($last) {
						$lines[] = str_getcsv($last.$line);
						$last = '';
					} else {
						$bits = str_getcsv($line);
						if (count($bits) == 1) {
							$last .= $line; //join with with next line!
						} else {
							$lines[] = $bits;
						}
					}
				}

				array_shift($lines); //its the csv header!
			}
		}
	}

	$updates = array();
	//geograph.ie-Coverage-Valid-2023-06-22.zip
	if (preg_match('/-(\w+)-(\d{4}-\d{2}-\d{2})/',$zipfile,$m)) {
		if ($m[1] == 'Drilldown') $m[1] = 'Excluded'; //??todo!
		if ($m[1] == 'Valid') $issue = "Submitted and indexed"; //for now, we looking at sitemap exports, so assume submitted! (could check if metadata mentioned a sitemap!)
		$updates['status'] = "{$m[1]}: $issue";
		$updates['updated'] = $m[2];
	} else
		$updates['status'] = $issue; //todo missing verdict

	if (!empty($issue) && !empty($lines)) {

		$c=0;
		foreach($lines as $line) {
			if (count($line) != 2) {
				print "$issue => ";
				print_r($lines);
				die("no2\n");
			}
			$updates['url'] = $line[0];
			$updates['crawled'] = $line[1];
		        if (preg_match('/photo\/(\d+)/',$updates['url'],$m))
                		$updates['gridimage_id'] = $m[1];
		        if (preg_match('/gridref\/([A-Z]{1,2}\d{4})$/',$updates['url'],$m))
		                $updates['grid_reference'] = $m[1];

 		        $db->Execute($sql = 'INSERT INTO '.$table.' SET created=NOW(), `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
		 	        	   ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
        	              		  array_merge(array_values($updates),array_values($updates))) or die("$sql\n\n".$db->ErrorMsg()."\n");
			$c+=$db->Affected_Rows();

			if (isset($updates['grid_reference'])) unset($updates['grid_reference']);
			if (isset($updates['gridimage_id'])) unset($updates['gridimage_id']);
		}
		print "--> $c\n";
		touch("$zipfile.done");
	} else {
		print "$issue => ";
		print_r($lines);
		exit;
	}

}
