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
	'format' => 'jsonl',
	'model' => 'clip',
	'limit' => 100,
	'type' => 'image',
	'paths'=>false, //setting to true trigges showing 'unclassfied images' (types IS NULL)
	'status'=>'accepted', //status for the unclassified images only
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

$join = "inner join types_dataset_1 using (gridimage_id)";
$cols .= ", split, types, coalesce(distance,'0') as distance";

if ($param['paths']) {
	//lookinf for unclassified
	$where[] = "types IS NULL";
	$cols .= ", gi.user_id, gi.moderation_status"; //needed for path

	$cols .= ", IF(gi.moderation_status = 'accepted' AND g2.nateastings>0 AND viewpoint_eastings>0 AND (g2.nateastings DIV 1000 != viewpoint_eastings DIV 1000 OR g2.natnorthings DIV 1000 != viewpoint_northings DIV 1000),'crossgrid','') as grid";
	$join .= " INNER JOIN gridimage g2 USING (gridimage_id)";

} else {
	$where[] = "v = 1";
	$where[] = "types IS NOT NULL";
}
if (!empty($param['status'])) {
	$where[] = "gi.moderation_status = ".$db->Quote($param['status']);
}

        if (!empty($_REQUEST['meta']))
                $cols = "e.gridimage_id,grid_reference,title,realname,imagetaken";
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

        $sql = "SELECT $cols, embeddings
                FROM $table_embedding e
                INNER JOIN gridimage_search gi USING (gridimage_id)
                $join
                WHERE ".implode(" AND ",$where)."
                $group
                LIMIT $limit";

        if ($_REQUEST['format'] == 'jsonl') {

                $recordSet = $db->Execute($sql);

                header("Content-type: application/octet-stream");
                header("Content-Disposition: attachment; filename=\"geograph-embeddings-".date('Y-m-d').".jsonl\"");

		$h = fopen('dataset.jsonl','w');

                $c=1;
                while (!$recordSet->EOF) {
                        $recordSet->fields['gridimage_id'] = intval($recordSet->fields['gridimage_id']);
                        //we dont bother with floatval on lat/long as what will add more decimal places!
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

	print "; $c to dataset.jsonl\n";
