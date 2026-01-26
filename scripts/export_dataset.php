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
	'source'=>'types_dataset_1',
        'col'=>'types',
	'cols' => "split, types, coalesce(distance,'0') as distance, weight",

        //'mode'=>'unknown',
	'format' => 'jsonl',
	'model' => 'clip',
	'limit' => 100,
	'type' => 'image',

	'paths'=>false, //setting to true trigges showing 'unclassfied images' (types IS NULL)
	'status'=>'accepted', //status for the unclassified images only

	'file'=>'dataset.jsonl',
	'execute'=>false,
);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$_REQUEST = $param; //so code copied from web pae, can be used as is!

############################################


        //for CLIP
        $table_embedding = "gridimage_embedding";
        $table_progress = "embedding_progress_clip";
        $model = 'clip';

        $dimensions = 512;
        $title = "CLIP";
        $variation = 'ViT-B/32';
        $hfref = 'openai/clip-vit-base-patch3';


        //Perception Encoder
        if (!empty($_REQUEST['model']) && $_REQUEST['model'] == 'pe') {
                $table_embedding = "gridimage_embedding_1024";
                $table_progress = "embedding_progress_pe";
                $model = 'pe';

                $dimensions = 1024;
                $title = "Perception Encoder";
                $variation = 'PE-Core-B16-224';
                $hfref = "facebook/PE-Core-B16-224";
        }


############################################


        $where = array();
        $cols = "e.gridimage_id"; //the actual embedding added at end!
        $join = '';
        $group = '';

	$join = "inner join {$param['source']} force index(v) using (gridimage_id)";
	$cols .= ", {$param['cols']}";

	//just the 'offical' subjects tags
	if (strpos($cols, 'subject_id') !== FALSE) {
		$join .= " inner join subjects using (subject)";
	}

	if ($param['paths']) {
		//lookinf for unclassified
		$where[] = "{$param['col']} IS NULL"; //or maybe rely on v=100?

		$cols .= ", gi.user_id, gi.moderation_status"; //needed for path
	        if (empty($_REQUEST['meta']))
			$cols .= ", title, realname"; //still include these - for CC credit!

		if ($param['col'] == 'types') { //types processing needs to know cross-grid
			$cols .= ", IF(gi.moderation_status = 'accepted' AND g2.nateastings>0 AND viewpoint_eastings>0 AND (g2.nateastings DIV 1000 != viewpoint_eastings DIV 1000 OR g2.natnorthings DIV 1000 != viewpoint_northings DIV 1000),'crossgrid','') as grid";
			$join .= " INNER JOIN gridimage g2 USING (gridimage_id)";
		}

	//	$where[] = "gi.title like '%drone%'";
		$where[] = "v =100"; //the test set of unlabled!

	} else {
		//this works to only include the tags we are interested in (although v=1 has already mostly done tha!)
		if (strpos($param['source'], 'places') !== FALSE || strpos($cols, 'classification') !== FALSE) {
			$join .= " inner join tag_named_stat USING (tag_id)";
			$where[] = "COALESCE(classification2, '') NOT IN ('other', 'geographical-generic')";
		}

		$where[] = "v in (1,2)"; //now v1+v2!
		$where[] = "{$param['col']} IS NOT NULL";
	}
	if (!empty($param['status'])) {
		$where[] = "gi.moderation_status = ".$db->Quote($param['status']);
	}

        if (!empty($_REQUEST['meta']))
                $cols .= ",grid_reference,title,realname,imagetaken";
        if (!empty($_REQUEST['ll']))
                $cols .= ",wgs84_lat,wgs84_long";

        if (!empty($_REQUEST['type']) && preg_match('/^\w+$/',$_REQUEST['type'])) {
                $where[] = "e.type = ".$db->Quote($_REQUEST['type']);
        } else {
                $cols .= ", e.type";
        }

        $limit = 10;
        if (!empty($_REQUEST['limit']))
                $limit = min(200000, intval($_REQUEST['limit']));

        $where[] = "model = '$model'"; // not technically needed as the table is all one model, but just in case

        if (empty($where)) $where[] = 1;
        if (!empty($group)) $group = "GROUP BY $group ORDER BY NULL";

#########################################

	$h = fopen($param['file'],'w');

	//types_dataset_1 is already in $join

	$max = $db->getOne("SELECT max(gridimage_id) from {$param['source']}");
	$shard = 100000;

	if ($limit <= 1000 || $param['paths'])
		$shard = "1000000";

	$c=0;
	for($start=0; $start<$max; $start+=$shard) {
		print "\n$start, ";
		//use a str so always update the same row
		$where['filter'] = sprintf("gridimage_id BETWEEN %d AND %d", $start, $start+$shard-1);

	        $sql = "SELECT $cols, embeddings
	                FROM $table_embedding e
	                INNER JOIN gridimage_search gi USING (gridimage_id)
	                $join
	                WHERE ".implode(" AND ",$where)."
	                $group
			LIMIT $limit";

		if (empty($param['execute'])) {
			$sql = str_replace(', embeddings',', LENGTH(embeddings) as embeddings_len', $sql); //make it easy to copy and paste for debug!
			print "\n$sql;\n";
			exit;
		}

                $recordSet = $db->Execute($sql);

		while (!$recordSet->EOF) {
			//ots generally best to make numeric columns in json (come as string from mysql)
                        foreach ($recordSet->fields as $key => $value) {
                                if (is_numeric($value) && $key != 'title') {
                                        $num = $value + 0;
                                        // If it's a coordinate, pin it to 6 decimal places
                                        if ($key === 'wgs84_lat' || $key === 'wgs84_long' || $key == 'lat' || $key == 'lng') {
                                                $num = round((float)$num, 6);
                                        }
                                        $recordSet->fields[$key] = $num;
                                }
			}
                        if (!empty($recordSet->fields['title'])) {
                                $recordSet->fields['title'] = latin1_to_utf8($recordSet->fields['title']);
                                $recordSet->fields['realname'] = utf8_encode($recordSet->fields['realname']);
                        }
                        $recordSet->fields['embeddings'] = base64_encode($recordSet->fields['embeddings']);

if ($param['paths']) {
	$image = new GridImage();
        $image->fastInit($recordSet->fields);
	$recordSet->fields['path'] = $image->_getFullpath(false, false); //we dont check existinence,
}

                        fwrite($h, json_encode($recordSet->fields)."\n");
                        $recordSet->MoveNext();
                        if (!($c%1000))
				print "$c. ";
                        $c++;
                }
        }

	print "; $c to {$param['file']}\n";
