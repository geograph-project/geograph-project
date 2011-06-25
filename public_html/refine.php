<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');
require_once('geograph/searchenginebuilder.class.php');
init_session();




$smarty = new GeographPage;

$i=(!empty($_GET['i']))?intval($_GET['i']):'';

$sortorders = array(''=>'','dist_sqd'=>'Distance','gridimage_id'=>'Date Submitted','imagetaken'=>'Date Taken','imageclass'=>'Image Category','realname'=>'Contributor Name','grid_reference'=>'Grid Reference','title'=>'Image Title','x'=>'West-&gt;East','y'=>'South-&gt;North','seq_id'=>'** Import Order **');
$breakdowns = array(''=>'','imagetaken'=>'Day Taken','imagetaken_month'=>'Month Taken','imagetaken_year'=>'Year Taken','imagetaken_decade'=>'Decade Taken','imageclass'=>'Image Category','realname'=>'Contributor Name','grid_reference'=>'Grid Reference','submitted'=>'Day Submitted','submitted_month'=>'Month Submitted','submitted_year'=>'Year Submitted',);

$displayclasses =  array(
			'full' => 'full listing',
			'more' => 'full listing + links',
			'thumbs' => 'thumbnails only',
			'thumbsmore' => 'thumbnails + links',
			'bigger' => 'bigger thumbnails',
			'gmap' => 'on a map',
			'slide' => 'slideshow - fullsize',
			'reveal' => 'slideshow - map imagine',
			'cooliris' => 'cooliris 3d wall',
			'mooflow' => 'cover flow',
			'text' => 'text list only',
			'spelling' => 'spelling utility'
			);
$smarty->assign_by_ref('displayclasses',$displayclasses);
$smarty->assign('pagesizes', array(5,10,15,20,30,50));

$smarty->assign_by_ref('imagestatuses', $imagestatuses);

$smarty->assign_by_ref('sortorders', $sortorders);
$smarty->assign_by_ref('breakdowns', $breakdowns);


	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$engine = new SearchEngine($i);

	if ($engine->criteria && $engine->criteria->searchclass == "Special" && ($USER->user_id == $engine->criteria->user_id || $USER->hasPerm('moderator')) ) {
		//you can pass!
	} else {
		$smarty->display('no_permission.tpl');
		exit;
	}

	$smarty->assign('i', $i);
	
	$query = (array)$engine->criteria;
		
	$smarty->assign('searchdesc', $query['searchdesc']);
	
	$smarty->assign('count',$engine->getMarkedCount());

	
	if (!empty($_POST['submit'])) {
	
		$ok = true;
		
		$dataarray = $_POST;
		
		if (empty($db)) {
			$db=NewADOConnection($GLOBALS['DSN']);
			if (!$db) die('Database connection failed');
		}
		
		$sql = "UPDATE queries SET searchdesc = ".$db->Quote($dataarray['searchdesc']);
				
		if (isset($dataarray['displayclass']))
			$sql .= ",displayclass = ".$db->Quote($dataarray['displayclass']);
		if (isset($dataarray['resultsperpage'])) {
			$sql .= ",resultsperpage = ".$db->Quote(min(100,$dataarray['resultsperpage']));
		} elseif (isset($USER) && !empty($USER->search_results)) {
			$sql .= ",resultsperpage = ".$db->Quote($USER->search_results);				
		}
		
		if (!isset($dataarray['orderby']))
			$dataarray['orderby'] = '';

		$orderby = $dataarray['orderby'];
		if ($dataarray['reverse_order_ind']) {
			$orderby = preg_replace('/(,|$)/',' desc$1',$orderby);
		}
		$sql .= ",orderby = ".$db->Quote($orderby);
		
		if (!empty($dataarray['breakby'])) {
			$sql .= ",breakby = ".$db->Quote($dataarray['breakby']);
		}
		
		$sql .= " WHERE id = ".intval($i);
		
		
		$ok = $db->Execute($sql);
		
		if ($ok) {
			$token=new Token;
			$token->setValue("i", $i);
			$smarty->assign('token',$token->getToken());

			$smarty->display('search_refine.tpl');
			exit;
		}
	}


	
	
	$display = $engine->getDisplayclass();
	if (isset($_GET['displayclass']) && preg_match('/^\w+$/',$_GET['displayclass'])) {
		$display = $_GET['displayclass'];
		if ($USER->registered && $USER->user_id == $engine->criteria->user_id && $_GET['displayclass'] != 'search' && $_GET['displayclass'] != 'searchtext') {
			$engine->setDisplayclass($_GET['displayclass']);
		} else {
			//don't store search override permently
			$engine->temp_displayclass = $display;
		}
	}
	if (empty($display))
		$display = 'full';
	
	
	if (strpos($query['orderby'],' desc') > 0) {
		$smarty->assign('orderby', preg_replace('/ desc$/','',$query['orderby']));
		$smarty->assign('reverse_order_checked', 'checked="checked"');
	} else {
		$smarty->assign('orderby', $query['orderby']);
	}
	$smarty->assign('breakby', $query['breakby']);
	$smarty->assign('displayclass', $query['displayclass']);
	$smarty->assign('resultsperpage', $query['resultsperpage']);
	$smarty->assign('searchdesc', $query['searchdesc']);
	
	$smarty->assign_by_ref('criteria', $engine->criteria);



$smarty->display('search_refine.tpl');

	
?>
