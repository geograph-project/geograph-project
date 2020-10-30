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

$param=array(
        'mode'=>'unknown',
);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if ($param['mode'] == 'history') {
	//special mode that looks though all the history tables and effiently dumps them!

	$tables =$db->getCol("SHOW TABLES LIKE 'gridimage_exif_'");
	//$tables[] = 'gridimage_exif';

	$all = array();
	$min = 0; $max = 0;
	foreach ($tables as $idx => $table) {
		$all[$table] = $db->getRow("SELECT MIN(gridimage_id) as min,MAX(gridimage_id) AS max FROM $table WHERE gridimage_id > 0");
		if (!$min || $all[$table]['min'] < $min) $min = $all[$table]['min'];
		if (         $all[$table]['max'] > $max) $max = $all[$table]['max'];
	}
	for($start = 0; $start < $max; $start+=1000) {
		$end = $start+999;

		foreach ($all as $table => $row) {
			//http://stackoverflow.com/questions/3269434/whats-the-most-efficient-way-to-test-two-integer-ranges-for-overlap

			if ($row['min'] <= $end && $row['max'] >= $start) {
				dump_file($start,$end,$table);
				//print "$table == ";print_r($row);
			}
		}
	}

} elseif ($param['mode'] == 'diff') {
	$table = 'gridimage_exif';

	//todo, we could use something like
	//  select count(*) as count,min(gridimage_id) as start,max(gridimage_id) as end,1+max(gridimage_id)-min(gridimage_id) as span
	//      from gridimage_exif group by gridimage_id div 1000;
	//maybe with 'HAVING count=1000' or even 'HAVING count=span' :)

	$row = $db->getRow("SELECT COUNT(*) as count,MIN(gridimage_id) as min,MAX(gridimage_id) AS max FROM $table WHERE gridimage_id > 0");
	print_r($row);

	if ($row['count'] < 10000)
		die("only {$row['count']} rows exist\n");

	$from = intval($row['min']/1000)*1000;
	$stop = intval(($row['max'] - 10000)/1000)*1000; //stops a bit early, and rounds DOWN to nearest 1000, in general avoid writing partial files!!

	for($start = $from; $start < $stop; $start+=1000) {
		$end = $start+999;
		print "#($start,$end,$table)\n";
		dump_file($start,$end,$table);
		sleep(1);
	}

	$cmds = array();
	$cmds[] = "php scripts/delete_exif.php --mode=run | tee /tmp/exif/delete.log";
	$cmds[] = 'find /tmp/exif/??/ -type f -name "*.exif" | xargs --no-run-if-empty gzip -vf';
	$cmds[] = 'rsync -ra /tmp/exif/?? /mnt/combined/geograph_live/exif/ --itemize-changes --size-only --inplace --remove-source-files';
	print "Prototype commands: (not run automatically yet!)\n";
	print implode("\n",$cmds)."\n";
	print "(ulimately dump_file, shoudl gzip and save the file to geogridfs directly, but doesnt yet!)\n";
}

function dump_file($start,$end,$table) {
	global $db;
	static $final_folder = "/mnt/combined/geograph_live/exif/";
	static $temp_folder = "/tmp/exif/";
	static $done = array();

	$logfile = $temp_folder."export.log";

	if (empty($done) && file_exists($logfile)) {
		$h = fopen($logfile,'r');
		while($h && !feof($h)) {
			$line = fgetcsv($h);
			//	fputcsv($h,array(date('r'),$filename,$start,$end,$table,$c));
			//                          0         1        2     3     4     5
			$done["{$line[2]},{$line[3]},{$line[4]}"]=1;
		}
		fclose($h);
	}
	if (isset($done["$start,$end,$table"]))
		return;

	$done["$start,$end,$table"] = 1;

	$start = intval($start);
	$dir1 = sprintf("%02d", floor($start/1000000)%100);
	$dir2 = sprintf("%02d", floor($start/10000)%100);
	$out_folder = "$dir1/$dir2/";
	$out_file = "$temp_folder$out_folder$start.exif";
	$final_file = "$final_folder$out_folder$start.exif.gz";

	$sql = "SELECT gridimage_id,exif FROM $table WHERE gridimage_id BETWEEN $start AND $end";
	print "$out_file  -- $sql;\n";

	if (  !is_dir("$temp_folder$out_folder"))
		mkdir("$temp_folder$out_folder",0777,true);

	if (!file_exists($out_file) && file_exists($final_file)) {
		print "Copy $final_file > $out_file\n";
		passthru("zcat $final_file > $out_file");
	}

        $h = fopen($out_file, "a");

	$c=0;
        $recordSet = $db->Execute($sql);
        while (!$recordSet->EOF) {
		fwrite($h, $recordSet->fields['gridimage_id']."\t". base64_encode($recordSet->fields['exif'])."\n") ;
                $recordSet->MoveNext();
		$c++;
        }
        $recordSet->Close();
	fclose($h);

	$h = fopen($logfile, "a");
	fputcsv($h,array(date('r'),$out_file,$start,$end,$table,$c));

	/* todo, could run - instead do it a seperate step delete_exif.php - which run at same time manually as making sure the tmp file is safely stored!
        $sql = "DELETE FROM $table WHERE gridimage_id BETWEEN $start AND $end";

                $line = `wc -l $filename`;
                list($lines,$filename) = preg_split('/\s+/',trim(`wc -l $filename`));
                if ($lines == $c && filesize($filename) > 100000) {

	automatically, AFTER verifing the file was written ok, AND saved to geogridfs ok!
	*/

	return $c;
}
