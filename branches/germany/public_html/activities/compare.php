<?php
/**
 * $Project: GeoGraph $
 * $Id: search.php 2403 2006-08-16 15:55:41Z barry $
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

require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/searchcriteria.class.php');
init_session();




$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

if (isset($_GET['charge'])) {
	$USER->mustHavePerm("admin");
	
	
	$posts = $db->getAssoc("
		SELECT p.post_id,p.post_text,p.topic_id 
		FROM geobb_posts p
		LEFT JOIN compare_pair c USING (post_id)
		WHERE p.topic_id IN (2294,7494,2843) AND c.post_id IS NULL AND post_text LIKE '%[[[%'");
	
	foreach ($posts as $post_id => $row) {
		if (preg_match_all('/\[\[\[(\d+)\]\]\]\s*\[\[\[(\d+)\]\]\]/',$row['post_text'],$g_matches)) {
		
			foreach ($g_matches[1] as $g_i => $id1) {
				$id2 = $g_matches[2][$g_i];

				$sql = "INSERT INTO compare_pair SET 
				gridimage_id1 = $id1,
				gridimage_id2 = $id2,
				topic_id = {$row['topic_id']},
				post_id = {$row['post_id']},
				created = NOW(),
				status = 'new'";
				$db->Execute($sql);
				print "Saved  $id1,$id2 from {$row['post_id']}<BR>";
			}
		}
	}
	$db->Execute("UPDATE `compare_pair` SET crccol = CRC32(gridimage_id1*gridimage_id2)");
	$db->Execute("ALTER TABLE `compare_pair` ORDER BY crccol");
	print "<h3>All done</h3>";
	exit;
} elseif (!empty($_POST['pair_id']) && ($p = intval($_POST['pair_id'])) && isset($_POST['invalid'])) {
	$db->Execute("UPDATE `compare_pair` SET status='rejected' WHERE compare_pair_id = $p");
}

if (isset($_GET['t'])) {
	$token=new Token;
	if ($token->parse($_GET['t'])) {
		$pair = $db->getRow("
			SELECT p.compare_pair_id,gridimage_id1,gridimage_id2
			FROM compare_pair p
			WHERE compare_pair_id = ?",array($token->getValue("p")));
	}
} else {
	if ($USER->user_id) {
		$where = "user_id = ?";
		$a = array($USER->user_id);
	} else {
		$where = "user_id = 0 and `ipaddr` = INET_ATON(?) AND `ua` = ?";
		$a = array(getRemoteIP(),$_SERVER['HTTP_USER_AGENT']);
	}	

	$pair = $db->getRow("
		SELECT p.compare_pair_id,gridimage_id1,gridimage_id2
		FROM compare_pair p
		LEFT JOIN compare_done d ON (p.compare_pair_id = d.compare_pair_id AND $where)
		WHERE status != 'rejected' AND d.compare_pair_id IS NULL",$a);
}

if (!empty($pair['compare_pair_id'])) {
	$smarty->assign('pair_id', $pair['compare_pair_id']);
	
	$token=new Token;
			
	$token->setValue("p", $pair['compare_pair_id']);
					
	$smarty->assign('token', $token->getToken());
	
	require_once('geograph/conversions.class.php');
	$conv = new Conversions;
	
	
	$image1 = new GridImage($pair['gridimage_id1']);
	if ($image1->moderation_status!='rejected')
	{
		$rastermap1 = new RasterMap($image1->grid_square);
		if ($image1->view_direction > -1) {
			$smarty->assign('view_direction1', ($image1->view_direction%90==0)?strtoupper(heading_string($image1->view_direction)):ucwords(heading_string($image1->view_direction)) );
		}
		list($lat,$long) = $conv->gridsquare_to_wgs84($image1->grid_square);
		$smarty->assign('lat1', $lat);
		$smarty->assign('long1', $long);
		list($latdm,$longdm) = $conv->wgs84_to_friendly($lat,$long);
		$smarty->assign('latdm1', $latdm);
		$smarty->assign('longdm1', $longdm);
		$smarty->assign_by_ref('image1', $image1);
		$smarty->assign_by_ref('rastermap1', $rastermap1);
	}


	$image2 = new GridImage($pair['gridimage_id2']);
	if ($image2->moderation_status!='rejected')
	{
		$rastermap2 = new RasterMap($image2->grid_square);
		if ($image2->view_direction > -1) {
			$smarty->assign('view_direction2', ($image2->view_direction%90==0)?strtoupper(heading_string($image2->view_direction)):ucwords(heading_string($image2->view_direction)) );
		}
		list($lat,$long) = $conv->gridsquare_to_wgs84($image2->grid_square);
		$smarty->assign('lat2', $lat);
		$smarty->assign('long2', $long);
		list($latdm,$longdm) = $conv->wgs84_to_friendly($lat,$long);
		$smarty->assign('latdm2', $latdm);
		$smarty->assign('longdm2', $longdm);
		$smarty->assign_by_ref('image2', $image2);
		$smarty->assign_by_ref('rastermap2', $rastermap2);
	}


	$updates = array();
	$updates['user_id'] = $USER->user_id;
	$updates['compare_pair_id'] = $pair['compare_pair_id'];
	$updates['ua'] = $_SERVER['HTTP_USER_AGENT'];
	
	$db->Execute('REPLACE INTO compare_done SET `ipaddr` = INET_ATON(\''.getRemoteIP().'\'),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

} elseif (isset($_GET['again']) && !isset($_GET['t'])) {
	$pair = $db->getRow("
		DELETE FROM compare_done
		WHERE $where",$a);
		
	header("Location: ".$_SERVER['PHP_SELF'].(isset($_GET['v'])?'?v':''));
	exit;
}

if (isset($_GET['v'])) {
	$tamplate = 'activities_compare_v.tpl';
} else {
	$tamplate = 'activities_compare.tpl';
}

$smarty->display($tamplate);

?>
