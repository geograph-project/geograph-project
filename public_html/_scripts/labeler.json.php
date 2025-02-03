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
require_once('geograph/imagelist.class.php');

####################################

$format = '224XX224.jpg';
if (!empty($_GET['title']))
	$format = 'title.txt';
elseif (!empty($_GET['comment']))
	$format = 'comment.txt';

if (!empty($_GET['models'])) {
	$db = GeographDatabaseConnection(true);
	$data = $db->getAll("select model,model_download,model_dir,folder,grouper,images from dataset where model_auto > 0 and model_download != '' and model != '' and model_dir != '' and imagesize='$format'");
        outputJSON($data);
	exit;

} elseif (!empty($_GET['training'])) {
	$db = GeographDatabaseConnection(true);

	//Note, this returns in theory all training datasets.
	// clients should provably still filter, eg check [ src_format = 'subdir' AND accuracy IS NULL ]
		//if a client can use other formats, can use src_format
		//or if client wants to try training a BETTER model, it can try to beat accuracy of the existing model

	$data = $db->getAll("select folder,src_format,imagesize,src_download,grouper,images,labels,model_dir,accuracy from dataset where src_download != '' and `grouper` != '' and imagesize='$format'");
        outputJSON($data);
	exit;
}

####################################

if (empty($_GET['model'])) {
	die('{"error":"No model"}');
} //todo, aos check it a valid model!
	//select model from dataset where model = _GET[model] AND model_download != ''

####################################

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if ($_GET['model'] == 'auto')
		die('{"error":"Something went horribly wrong"}');

	$db = GeographDatabaseConnection(false);

	$json = file_get_contents('php://input');
	$data = json_decode($json,true);

	//might as well do updates all in one transaction
	$db->Execute('start transaction');

	foreach($data as $image) {
		print intval($image['image_id']).": ";
		$updates = array();
		$updates['gridimage_id'] = intval($image['image_id']); //remember that gridimage_id is used as (part of!) unique key
		$updates['model'] = $_GET['model'];
		$updates['label'] = $image['label'];
		$updates['score'] = $image['score'];
		$updates['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

		$db->Execute('REPLACE INTO gridimage_label SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		print $db->Affected_Rows();
		print "\n";
	}

	$db->Execute('commit');

####################################

} else {
	$imagelist=new ImageList;

	$db = $imagelist->_getDb(false); //make sure not readonly, even thogh we may only be selecting, use primary to always get most uptodate list

	####################

	if ($_GET['model'] == 'auto') {
		//auto is a special value, meaning the server is free to choose, but it should choose from downloadable models, as need to fetch from one of the models being submitted

		//$_GET['model'] = 'typev2'; //for now, hardcoded!

		//make sure to pick one used in auto, ordering by labels, is just a contrivaance to most preferntially pick "type/typev2"
		$_GET['model'] = $db->getOne("select model from dataset where model_download != '' and model != '' and model_dir != '' and imagesize='$format' order by labels");
	}

	####################

	$cols = ',realname';
	$join = '';
	$where = array();
	$where[] = "l.seq_id IS null"; //not already labled
	$limit = 50;
	$limit = rand(40,60); //to 'desync' multiple clients!
	if (!empty($_GET['limit'])) {
		$limit = (!empty($_GET['comment']) || !empty($_GET['title'])) ? 1000 : 250;
		$limit = min($limit,intval($_GET['limit']));
	}
	$sleep = ceil(sqrt($limit));

	if (!empty($_GET['offset'])) {
		$limit = intval($_GET['offset']).",$limit";
	} else {
		$w = array();
		$w[] = "model = ".$db->Quote($_GET['model']);
		$w[] = "ipaddr = INET6_ATON('".getRemoteIP()."')";

		$offset = $db->getOne("SELECT `offset` FROM labeler_agent WHERE ".implode(' AND ',$w)." AND updated > date_sub(now(),interval 24 hour)");
		if (is_null($offset) || strlen($offset) == 0) { //offset="0" is a valid offset!
			$offsets = explode(',',$db->getOne("SELECT GROUP_CONCAT(`offset`) FROM labeler_agent WHERE ".implode(' AND NOT ',$w)." AND updated > date_sub(now(),interval 24 hour)"));
			$offset = 0;
			while (in_array("$offset",$offsets,true))
				$offset+=200; //should be 50*number-of-clients, but chicken and egg, dont know how many clients will be
			$w[] = "`offset` = $offset";
			$db->Execute($sql = "INSERT INTO labeler_agent SET ".implode(',',$w)." ON DUPLICATE KEY UPDATE ".array_pop($w).", updated = NOW()");

		}
		$limit = "$offset,$limit";
	}

	if (!empty($_GET['large'])) {
		$cols .= ", original_width";
		$join .= "inner join gridimage_size using (gridimage_id)";
	}

	####################

	if (empty($_GET['all'])) {
		if (strpos($_GET['model'],'type') === 0) {
//			$where[] = "moderation_status = 'accepted'"; //todo, we might want to also check geos? eg spot potential long-distance/cross grid
			$where[] = "tags NOT like '%type:%'";
		} elseif (strpos($_GET['model'],'top') === 0) {
			$where[] = "tags NOT like '%top:%'";
		} elseif (strpos($_GET['model'],'subject') === 0) {
			$where[] = "tags NOT like '%subject:%'";
			$where[] = "imageclass=''"; //if has a category can proabbly infer subject from that
		} elseif ($_GET['model'] == 'city') {
			//for the moment, the city dataset we wanting to retest the images used for training.
			//... later will ahve to get this data from sphinx or sphinx_placename
			$join .= " inner join gridimage_label_training t on (t.gridimage_id = gi.gridimage_id and folder = 'geograph_visiondata015')";
		} elseif ($_GET['model'] == 'class') {
			$where[] = "imageclass=''";
		}
		if (!empty($_GET['comment']))
			$where[] = "LENGTH(comment)>10";
	}

	if (!empty($_GET['user_id']))
		$where[] = "gi.user_id = ".intval($_GET['user_id']);

	if (empty($_GET['recent']) && empty($_GET['all'])) {
		//only recent users!
		$join .= "inner join user_stat using (user_id)";
		//$where[] = "last > 7300000";
		$where[] = "last > 5300000";
	}

	if (!empty($_GET['title']))
		$cols .= ", title";
	elseif (!empty($_GET['comment']))
		$cols .= ", title, comment";

	####################

	$qmod = $db->Quote($_GET['model']);
	$where = implode(" AND ",$where);

	if (!empty($_GET['recent'])) {
		if (strpos($join,'gridimage_size') === FALSE)
 			$join .= "inner join gridimage_size using (gridimage_id)"; //to help avoid failed uploads!
			//we ottherwise still want to process pending/rejects here!

		//todo, to get realname, should be joining on user table!
		$sql = "select gi.gridimage_id,user_id $cols
		from gridimage gi
		$join
		left join gridimage_label l on (l.gridimage_id = gi.gridimage_id and `model` = $qmod)
		where $where
		order by gridimage_id desc
		limit $limit";

	} elseif ($_GET['model'] == 'typev2' && empty($_GET['large'])) {
		$sql = "select t.*
		from tmp_typev2_images t
		left join gridimage_label l on (l.gridimage_id = t.gridimage_id and `model` = $qmod)
		where l.seq_id IS null
		limit $limit";

	} elseif ($_GET['model'] == 'subjectlabel') {
		$sql = "select t.*
		from tmp_subjectlabel_images t
		left join gridimage_label l on (l.gridimage_id = t.gridimage_id and `model` = $qmod)
		where l.seq_id IS null
		limit $limit";

	} elseif ($_GET['model'] == 'subjectcomment') {
		$sql = "select t.*
		from tmp_subjectcomment_images t
		left join gridimage_label l on (l.gridimage_id = t.gridimage_id and `model` = $qmod)
		where l.seq_id IS null
		limit $limit";

	} else {
		$sql = "select gi.gridimage_id,user_id $cols
		from gridimage_search gi
		$join
		left join gridimage_label l on (l.gridimage_id = gi.gridimage_id and `model` = $qmod)
		where $where
		limit $limit";
	}

	if (!empty($_GET['ddd']))
		die("$sql;\n");


	$imagelist->_getImagesBySql($sql);

	####################

	if (count($imagelist->images)) {
		foreach ($imagelist->images as $i => $image) {
		//title
			if (!empty($_GET['title']) || !empty($_GET['comment'])) {
        	                $imagelist->images[$i]->title = latin1_to_utf8($imagelist->images[$i]->title);

                        	//liner doesnt actully cope with utf8 - even with a BOM - so transliterate
                                //note we STILL convert to utf8 first, rather than detect ISO-8859-15 directly (ie more than ascii), because latin1_to_utf8 first decodes entities, which$
                	        $enc = mb_detect_encoding($imagelist->images[$i]->title, 'UTF-8, ISO-8859-15, ASCII');
        	                if ($enc == 'UTF-8') // should no longer ever detect ISO-8859-15
	                                $imagelist->images[$i]->title = translit_to_ascii($imagelist->images[$i]->title, "UTF-8");

				//may as well do the join here in server, so dont need to do it in client
				if (!empty($_GET['comment']) && !empty($imagelist->images[$i]->comment)) {
					$comment = latin1_to_utf8($imagelist->images[$i]->comment);

					//remove the links. Probably wont help
	                                $comment = preg_replace('/(?<!["\'>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/', '', $comment);
        	                        $comment = preg_replace('/(?<![>\/F\."\'])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/', '', $comment);

	                                //remove geograph links too!
        	                        $comment = preg_replace('/\[\[(\[?)([a-z]+:)?(\w{0,3} ?\d+ ?\d*)(\]?)\]\]/', '', $comment);

					$enc = mb_detect_encoding($comment, 'UTF-8, ISO-8859-15, ASCII');
	                                if ($enc == 'UTF-8') // should no longer ever detect ISO-8859-15
        	                                $comment = translit_to_ascii($comment, "UTF-8");

					$imagelist->images[$i]->title .= ".\n\n".$comment;
					unset($imagelist->images[$i]->comment);
				}

		//square thumbnail (default!)
			} elseif (empty($_GET['full'])) {
				/* //real wide panos, that a 'thin strip' at 640px might be very low resolution
				... need gridimage_size hoined in for this to work
				if (isset($row['width']) && ($row['width'] < 224 || $row['height'] < 224) && $row['original'] > 224) {
	                                $path = $image->getSquareThumbnail(224,224,'path', true, '_original');
        	                } else { */
					$imagelist->images[$i]->fullpath = $image->getSquareThumbnail(224,224,'path');
				//}

				if (basename($imagelist->images[$i]->fullpath) == 'error.jpg') {
					debug_message('[Geograph] MISSING IMAGE '.$image->gridimage_id,print_r($image,true));

					unset($imagelist->images[$i]);
					$deleted=1;
					continue;
				}

		//original
			} elseif (!empty($imagelist->images[$i]->original_width) && isset($_GET['large'])) {
				$imagelist->images[$i]->original = $imagelist->images[$i]->_getOriginalpath();

				//the original is missing!!?
				if ($image->gridimage_id == 29 || $image->gridimage_id == 5378158 || $image->gridimage_id == 1401219)
					$imagelist->images[$i]->original = str_replace('original','1024x1024',$imagelist->images[$i]->original);

		//full/640px version
			} else {
				$imagelist->images[$i]->fullpath = $imagelist->images[$i]->_getFullpath();

				if (basename($imagelist->images[$i]->fullpath) == 'error.jpg') {
					debug_message('[Geograph] MISSING IMAGE '.$image->gridimage_id,print_r($image,true));

					unset($imagelist->images[$i]);
					$deleted=1;
					continue;
				}
			}

		//remove empty values (make the json more compact)
			foreach (get_object_vars($image) as $key => $value) {
				if (empty($value))
					unset($imagelist->images[$i]->{$key});
			}
		}

		if (!empty($deleted))
			$imagelist->images = array_values($imagelist->images); //get sequential keys back!

		$data = array('prefix'=>$CONF['STATIC_HOST'],'sleep'=>$sleep,'rows'=>$imagelist->images);
		outputJSON($data); //passed by ref
	}
}
