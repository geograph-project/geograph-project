<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6417 2010-03-04 22:14:53Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');
init_session();

$data = array();
customNoCacheHeader();

if (!empty($_GET['label'])) {
	$imagelist=new ImageList;
	$db = $imagelist->_getDB(true);

	$where = array();
	$where[] = "c.label = ".$db->Quote($_GET['label']);
	$where[] = "c.active = 1";

	$where[] = "e.model = 'pe'";
	$where[] = "e.type = 'image'";
	$where[] = "e.embeddings != ''";

	$where = implode(' AND ', $where);
	$sql = "select gridimage_id,title,realname,gi.user_id, label, c.score, cosine, embeddings
	from gridimage_search gi inner join curated1 c using (gridimage_id)
	inner join gridimage_embedding_1024 e using (gridimage_id)
	where $where order by c.score = 10 asc limit 300";

	$ADODB_COUNTRECS = false; //so MYSQLI_USE_RESULT - ie unbuffered

        $recordSet = $db->Execute($sql) or die("$sql\n".$db->ErrorMsg()."\n\n");

	if ($recordSet && !$recordSet->EOF) { //not sure this is good idea unbuffered! if ($recordSet->RecordCount()) {

		//write line by line to avoid the entire array in memory
		header("Content-Type:application/json");
		print '{"rows":['; $sep = '';

		$c=0;
		while (!$recordSet->EOF) {
			$image = new GridImage();
			$image->fastInit($recordSet->fields);

			$row = array();
			$row['id'] = intval($image->gridimage_id);
			$row['user_id'] = intval($image->user_id);
			$row['title'] = latin1_to_utf8($image->title);
			$row['realname'] = latin1_to_utf8($image->realname);
			$row['hash'] = $image->_getAntiLeechHash();
			$row['image_vector'] = base64_encode($image->embeddings);
			if ($image->score < 10)
				$row['bucket'] = 0;
			elseif ($image->score > 10)
				$row['bucket'] = 2;
			elseif (floor(rand(1,80)) ==1)
				$row['bucket'] = 1;

			print $sep.json_encode($row)."\n";
			$sep = ',';
			//$data['rows'][] = $row;

			$recordSet->MoveNext();
			$c++;
		}
		$recordSet->Close();
		print "]";

		print ',"meta": '.json_encode(array('total'=>$c));
		print "}";
		exit;
	} else {
		$data["error"] = "no results";
	}
}

outputJSON($data);

