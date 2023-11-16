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

$param = array('sleep'=>0, 'folder'=>'/mnt/efs/data/','filename'=>'geograph_railway_images', 'start'=>0,
	 'keyword' => '@(tags,contexts) "_SEP_ railway _SEP_"', 'geo'=>false, 'prefix'=>'', 'original'=>false, '1024'=>true);

chdir(__DIR__);
require "./_scripts.inc.php";

######################################################################################################################################################

$sph = GeographSphinxConnection('sphinxql',true);

$cols = "id as gridimage_id,realname,title,takenday,user_id,wgs84_lat,wgs84_long,submitted,original";
$pop = 2; //remove two cols! from the CSV

if (!empty($param['geo'])) {
        require_once('3rdparty/facet-functions.php');
        require_once('geograph/conversions.class.php');

	list($lat,$long,$distance) = explode(',',$param['geo']);
        $prefix = 'wgs84_';

	$query = geotiles($lat,$long,$distance);

        $where[] = "MATCH(". $sph->Quote($param['keyword'].' '.$query).")";
        $where[] = "geodist < ".floatval($distance);

        //make a BBOX too?
                 //top/right  --- north/east
                 list($long1,$lat1) = calcLatLong($lat,$long,$distance*2.2,45); //sqrt(2) + some leeway
                 //bottom/left -- south/west
                 list($long2,$lat2) = calcLatLong($lat,$long,$distance*2.2,225);

                 $where[] = "{$prefix}lat BETWEEN ".deg2rad($lat2).' AND '.deg2rad($lat1);
                 $where[] = "{$prefix}long BETWEEN ".deg2rad($long1).' AND '.deg2rad($long2);


        $sql = "SELECT $cols, geodist({$prefix}lat,{$prefix}long,".deg2rad($lat).','.deg2rad($long).") as geodist
        FROM sample8
        WHERE ".implode(" AND ",$where)." \$and
        ORDER BY id ASC
        LIMIT 1000
        OPTION ranker=none";

	$pop++; //remove the dist too!

} else {
	$match = $sph->Quote($param['keyword']);
	$filter = '';

	$sql = "select $cols FROM sample8 WHERE MATCH($match) \$and $filter ORDER BY id ASC LIMIT 1000 OPTION ranker=none";
}

######################################################################################################################################################

print "Writing to {$param['folder']}/{$param['filename']}\n";
print preg_replace('/\s+/',' ',$sql).";\n";

//log the command
$h = fopen($param['folder'].'/'.$param['filename'].".cmd.txt", 'a');
fwrite($h, implode(' ',$argv)."\n");
fclose($h);

######################################################################################################################################################

$h = fopen($param['folder'].'/'.$param['filename'].'.metadata.csv','wb'); //we writing utf8!

$loop = 1;
$c = 0;

//in theory is MUCH quicker to loop though shards one at a time....
foreach (array('sample8A','sample8B','sample8C','sample8D','sample8E') as $index) {
	$sql = preg_replace('/ sample8\w? /'," $index ",$sql);
	$lastid = $param['start'];
	while (true) {
		print "Loop $index.$loop from $lastid";

		$and = ($lastid)?" AND id > $lastid":'';

		$recordSet = $sph->Execute(str_replace('$and', $and, $sql));
		$meta = $sph->getAssoc("SHOW META");

		if ($loop == 1)
			print_r($meta);

		$count = $recordSet->RecordCount();
		if (!$count)
			break;
		print "=$count/{$meta['total_found']}. ";

		if ($loop == 1) {
			$keys = array_keys($recordSet->fields);
			array_unshift($keys,'filename'); foreach(range(1,$pop) as $l) { array_pop($keys); } //remove not needed
			fputcsv($h,$keys);
		}

		while (!$recordSet->EOF) {
			$row =& $recordSet->fields;
			$row['takenday'] = preg_replace('/(\d{4})(\d{2})(\d{2})/','$1-$2-$3',$row['takenday']);

			$image = new GridImage();
			$image->fastInit($row);

			if (!empty($row['original']) && $param['original']) {
				if ($param['1024'] && $row['original'] > 1024) {
					//in theory should use getImageFromOriginal, but _getOriginalpath is simpler
					// - just at risk of returning a path that doesnt exist (ie the 1024 hasnt actully been created yet) - it WONT be created.
					$path = $image->_getOriginalpath(FALSE, false, '_1024x1024');
				} else {
					$path = $image->_getOriginalpath(false, false);
				}
			} else {
				$path = $image->_getFullpath(false, false); //we dont check existinence, but if did then use $use_get=2 so that it downloads it, rather than just using HEAD
			}

			//sphinx/manticore is already utf8
			//$row['title'] = latin1_to_utf8($row['title']);
			//$row['realname'] = latin1_to_utf8($row['realname']);
			$row['wgs84_lat'] = round(rad2deg($row['wgs84_lat']),6);
			$row['wgs84_long'] = round(rad2deg($row['wgs84_long']),6);

			foreach(range(1,$pop) as $l) { array_pop($row); } //remove not needed

			fputcsv($h,array($param['prefix'].$path)+$row);

			$lastid = $image->gridimage_id;
			$c++;
		        $recordSet->MoveNext();
		}
		$recordSet->Close();


		if (!empty($param['sleep']))
			sleep($param['sleep']);
		$loop++;
	}
}
print "\n\n";

##################################


