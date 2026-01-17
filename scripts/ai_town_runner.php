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

$param = array('debug'=>false,'limit'=>10,'execute'=>false, 'views'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################


                $filesystem = new FileSystem(); //sets up configuation automagically
                //the vector lip needs S3 class setup already!

                require_once('geograph/imagelists3vector.class.php');
                $imagelist=new ImageListS3Vector;


$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

if ($param['debug'])
	print "Starting. ".date('r')."\n";


$towns = array('East Grinstead', 'Fort William/An Gearasdan', 'Abergavenny/Y Fenni', 'Bicester', 'Durham');

        function substring_index($url,$delimiter,$count) {
                $segments = explode($delimiter, $url, $count+1);
                $extracted_segments = array_slice($segments, 0, $count);
                return implode($delimiter, $extracted_segments);
        }

        require_once('geograph/conversions.class.php');
        $conv = new Conversions;


$criteria = array(); //this is the $critera/filter array for imagelists3vector.class

foreach ($towns as $idx => $town) {

        $mbr = $db->getRow("SELECT mbr_xmin, mbr_ymin, mbr_xmax, mbr_ymax, geometry_x, geometry_y, 1 as reference_index
                   FROM os_open_places
                   WHERE name1 = " . $db->Quote(substring_index($town,'/',1)) . " LIMIT 1");

        //list ($gridref,) = $conv->national_to_gridref($mbr['geometry_x'],$mbr['geometry_y'],4,$mbr['reference_index']);
        //$cols .= ", ".$db->Quote($gridref)." AS km_ref";

print "-- $town:\n";
	$town2 = substring_index($town,'/',1);

	if (!empty($param['views'])) {
		$ri = 1;
                $spatial_where = "nateastings BETWEEN {$mbr['mbr_xmin']} AND {$mbr['mbr_xmax']}
                             AND natnorthings BETWEEN {$mbr['mbr_ymin']} AND {$mbr['mbr_ymax']}";

		//gb_images filters to geograph, so make a new table with just the towns need
		$table = "gb_images_town";
		if ($idx == 0) {
			print "DROP TABLE IF EXISTS $table;\n";
			$sql = "create table $table (primary key(gridimage_id)) ";
		} else
			$sql = "insert ignore into $table "; //ignore as may have overlapping bboxes in the end

		print "$sql select gridimage_id,user_id,imagetaken,nateastings,natnorthings from gridimage inner join gridsquare using (gridsquare_id) where reference_index = $ri and $spatial_where;\n";

		continue;
	}



	$criteria['label'] = "$town2 Civic";

	//Y is reversed in lat/long vs north/south
	 list($lat1,$long1) = $conv->national_to_wgs84($mbr['mbr_xmin'],$mbr['mbr_ymin'],$mbr['reference_index']);
	 list($lat2,$long2) = $conv->national_to_wgs84($mbr['mbr_xmax'],$mbr['mbr_ymax'],$mbr['reference_index']);

	$criteria['bbox'] = "$long1,$lat1,$long2,$lat2";
	print_r($criteria);

	$results = $imagelist->getRawVectorsByCriteria($criteria, 20, true);


	if (!empty($results['vectors'])) {
		$count = count($results['vectors']);
		$distances = array_column($results['vectors'], 'distance');

		$min = min($distances);
		$max = $min*1.05; //Allow 5%?

		foreach($results['vectors'] as $row) {
			if ($row['distance'] < $max) {
				$id = intval($row['key']);
				$sql = "UPDATE clipthelandscape SET labels = CONCAT(labels, '; $town2 Landmark') WHERE gridimage_id = $id AND labels NOT like '%$town2 Landmark%'";
				print "  $sql; -- dist:{$row['distance']}\n";
				if ($param['execute'])
					$db->Execute($sql);

			}
		}
	}

}

if (!empty($param['views'])) {
	//todo, should make nateastings/narnortings SIGNED - so that easier to do maths without casting
	//alter table gb_images modify `nateastings` mediumint(8) NOT NULL DEFAULT 0, modify `natnorthings` mediumint(8) NOT NULL DEFAULT 0;

	print "alter table $table modify `nateastings` mediumint(8) NOT NULL DEFAULT 0, modify `natnorthings` mediumint(8) NOT NULL DEFAULT 0, add index(natnorthings, nateastings);\n";
}

print "--\n\n";
