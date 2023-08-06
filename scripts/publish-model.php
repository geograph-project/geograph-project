<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

//these are the arguments we expect
$param=array(
);

$HELP = <<<ENDHELP
    --mode=exteral|geograph
    --sleep=<seconds>   : seconds to sleep between calls (0)
    --number=<number>   : number of items to process in each batch (10)
ENDHELP;


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;



$models = $db->getAll("SELECT m.*,grouper from dataset_model m inner join dataset using (folder) where model = '' and model_file LIKE '%.zip'");

foreach ($models as $row) {

	$basename = $row['model_file'];
	$base = str_replace('.zip','',$basename);

	$sql = "UPDATE dataset SET ";
	$sql .= "model = '{$row['grouper']}', ";
	$sql .= "model_dir = '$base', ";
	$sql .= "model_download = 'https://staging.data.geograph.org.uk/facets/{$basename}', ";
	if ($row['accuracy'])
		$sql .= "accuracy = {$row['accuracy']}";

	$sql .= " WHERE folder = '{$row['folder']}'";

	print "$sql;\n\n";
	$cmd = "unzip -t /mnt/efs/data/uploads/$basename";
	print "$cmd\n";

	$cmd = "cd /tmp/";
	print "$cmd\n";

	$cmd = "unzip /mnt/efs/data/uploads/$basename";
	print "$cmd\n";

	$dir = escapeshellarg(str_replace('_',' ',$base)); //just take a punt that it spaces
	$cmd = "mv $dir $base";
	print "$cmd\n";

	$cmd = "rm $base/example.py $base/image.jpg";
	print "$cmd\n";

	$cmd = "zip -9r /mnt/efs/data/facets/$basename $base/";
	print "$cmd\n";

	print "\n\n";

}


$sql = "replace into geograph_live.dataset select * from geograph_staging.dataset where model != ''";
print "$sql;\n";


