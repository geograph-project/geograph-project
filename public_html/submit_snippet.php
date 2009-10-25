<?php
/**
 * $Project: GeoGraph $
 * $Id: show_exif.php 5875 2009-10-20 17:43:17Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
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

$smarty = new GeographPage;
$template='submit_snippet.tpl';	

$USER->mustHavePerm("basic");

$gid = 0;

if (!empty($_GET['upload_id'])) {

	$gid = crc32($_GET['upload_id'])+4294967296;
	$gid += $USER->user_id * 4294967296;

	$smarty->assign('gridimage_id',$gid);
} elseif (!empty($_REQUEST['gridimage_id'])) {

	$gid = intval($_REQUEST['gridimage_id']);
	
	$image=new GridImage();
	$ok = $image->loadFromId($gid);
		
	if (!$ok) {
		die("invalid image");
	} elseif ($image->user_id != $USER->user_id && !$USER->hasPerm('moderator')) {
		die("unable to access this image");
	}
	
	$smarty->assign('gridimage_id',$gid);
}

$db = GeographDatabaseConnection(false);


if (!empty($_POST['create']) && (!empty($_POST['title']) || !empty($_POST['comment'])) ) {

	$updates = array();
	$updates['user_id'] = $USER->user_id;
	$updates['title'] = $_POST['title'];
	$updates['comment'] =  $_POST['comment'];
	
	$square=new GridSquare;
	if ((!empty($_POST['grid_reference']) && $square->setByFullGridRef($_POST['grid_reference'],true)) || $square->setByFullGridRef($_GET['gr'],true) ) {
		
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		list($lat,$long) = $conv->gridsquare_to_wgs84($square);

		if (!empty($_POST['grid_reference'])) {
			//we store these so can recreate the original GR - but only if specifically entered
			$updates['nateastings'] = $square->nateastings;
			$updates['natnorthings'] = $square->natnorthings;
			$updates['natgrlen'] = $square->natgrlen;
		}
		$updates['reference_index'] = $square->reference_index;
		
		//for the sphinx index
		$updates['grid_reference'] = $square->grid_reference;

		$updates['wgs84_lat'] = $lat;
		$updates['wgs84_long'] = $long;
		
		//for mysql indexing (where sphinx not available) 
		$point = "'POINT({$square->nateastings} {$square->natnorthings})'";
	} else {
		$point = "'POINT(0 0)'";
	}
	
	$db->Execute('INSERT INTO snippet SET created=NOW(),point_en=GeomFromText('.$point.'),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	
	$updates = array();
	$updates['user_id'] = $USER->user_id;
	$updates['snippet_id'] = $db->Insert_ID();
	$updates['gridimage_id'] = $gid;
	
	$db->Execute('INSERT INTO gridimage_snippet SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	
} elseif ($gid && !empty($_POST['remove'])) {
	
	$criteria = array();
	$criteria['gridimage_id'] = $gid;
	
	foreach ($_POST['remove'] as $id => $text) {
		
		$criteria['snippet_id'] = $id;
		
		$db->Execute('DELETE FROM gridimage_snippet WHERE `'.implode('` = ? AND `',array_keys($criteria)).'` = ?',array_values($criteria));
	}

} elseif ($gid && !empty($_POST['add'])) {
	
	$updates = array();
	$updates['gridimage_id'] = $gid;
	$updates['user_id'] = $USER->user_id;
	
	foreach ($_POST['add'] as $id => $text) {
		
		$updates['snippet_id'] = $id;
		
		$db->Execute('INSERT IGNORE INTO gridimage_snippet SET `'.implode('` = ?, `',array_keys($updates)).'` = ?',array_values($updates));
	}
}



if ($gid) {
	list($usec, $sec) = explode(' ',microtime());
		$querytime_before = ((float)$usec + (float)$sec);
	
	$used = $db->getAll("SELECT * FROM gridimage_snippet INNER JOIN snippet USING (snippet_id) WHERE gridimage_id = $gid ORDER BY gridimage_snippet.created");

	$smarty->assign_by_ref('used',$used);
}


if (!empty($_GET['gr'])) {
	$square=new GridSquare;
	
	$grid_given=true;
	if ($grid_ok=$square->setByFullGridRef($_GET['gr'],true)) {
	
		$smarty->assign('gr',$_GET['gr']);
		
		if ($square->natgrlen > 4) {
			$smarty->assign('centisquare',1);
		}
		
	} else {
		print "invalid GR!";
	}
	$where = array();
	$orderby = "ORDER BY s.snippet_id";
	
	if ($CONF['sphinx_host'] && !empty($_POST['q'])) {  //todo - for the moment we only use sphinx for full text searches- because of the indexing delay 
	
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		
		if (!empty($_GET['page'])) {
			$pg = intval($_GET['page']);
		} else {
			$pg = 1;
		}
		
		$q=trim($_POST['q']);
		
		$sphinx = new sphinxwrapper($q);
		$sphinx->pageSize = $pgsize = 25;

		if (preg_match('/\bp(age|)(\d+)\s*$/',$q,$m)) {
			$pg = intval($m[2]);
			$sphinx->q = preg_replace('/\bp(age|)\d+\s*$/','',$sphinx->q);
		}

		$smarty->assign('q', $sphinx->qclean);
		if ($q) {
			$title = "Matching word search [ ".htmlentities($sphinx->qclean)." ]";
		}
		
		$data = array();
		$data['x'] = $square->x;
		$data['y'] = $square->y;
		if ($square->natgrlen > 4) {
			list($data['lat'],$data['long']) = $conv->gridsquare_to_wgs84($square);
		}
		$data['d'] = !empty($_POST['radius'])?floatval($_POST['radius']):1;
		$data['sort'] = "@geodist ASC, @relevance DESC, @id DESC";
		
		$sphinx->setSort($data['sort']);
		$sphinx->setSpatial($data);

		$ids = $sphinx->returnIds($pg,'snippet');

		$smarty->assign("query_info",$sphinx->query_info);

		if (!empty($ids) && count($ids)) {
			$id_list = implode(',',$ids);
			$where[] = "s.snippet_id IN($id_list)";
			$orderby = "ORDER BY FIELD(s.snippet_id,$id_list)";
		} else {
			$where[] = '0';
		}
	} else {
		$radius = !empty($_POST['radius'])?intval($_POST['radius']*1000):1000;

		$left=$square->nateastings-$radius;
		$right=$square->nateastings+$radius;
		$top=$square->natnorthings-$radius;
		$bottom=$square->natnorthings+$radius;

		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

		$fields = ",if(natnorthings > 0,(nateastings-{$square->nateastings})*(nateastings-{$square->nateastings})+(natnorthings-{$square->natnorthings})*(natnorthings-{$square->natnorthings}),0) as distance";
		
		$where[] = "CONTAINS(
				GeomFromText($rectangle),
				point_en)";
		
		if (!empty($_POST['q'])) {
			$q=mysql_real_escape_string(trim($_POST['q']));
			
			$where[] = "(title LIKE '%$q%' OR comment LIKE '%$q%')";
			$smarty->assign('q',trim($_POST['q']));
		}
		
		$where[] = "enabled = 1"; 
	}
	
	$smarty->assign_by_ref('radius',$_POST['radius']);
	
	$where[] = 'gridimage_id IS NULL';
	$where= implode(' AND ',$where);
	
	$results = $db->getAll($sql="SELECT s.* $fields FROM snippet s LEFT JOIN gridimage_snippet gs ON (s.snippet_id = gs.snippet_id AND gridimage_id = $gid) WHERE $where $orderby"); //the left join is to exclude results already attached to this image
	#print $sql;
	
	list($usec, $sec) = explode(' ',microtime());
	$querytime_after = ((float)$usec + (float)$sec);
	
	#$smarty->assign("query_info", "time: ".($querytime_after - $querytime_before));
	
	if ($fields) {
		foreach ($results as $id => $row) {
			if ($row['distance'] > 0)
				$results[$id]['distance'] = round(sqrt($row['distance'])/1000)+0.01;
		}
	}
	
	$smarty->assign_by_ref('grid_reference',$square->grid_reference);
	$smarty->assign_by_ref('results',$results);
} 

if ($CONF['sphinx_host']) {
	$smarty->assign('sphinx',1);
}





$smarty->display($template);

?>
