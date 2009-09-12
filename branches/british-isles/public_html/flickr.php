<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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
init_session();

$smarty = new GeographPage;

$_GET['i']=intval(stripslashes($_GET['i']));

$sortorders = array(''=>'','random'=>'Random','dist_sqd'=>'Distance','submitted'=>'Date Submitted','imageclass'=>'Image Category','realname'=>'Contributer Name','grid_reference'=>'Grid Reference','title'=>'Image Title','x'=>'West-&gt;East','y'=>'South-&gt;North');
#,'user_id'=>'Contributer ID'


if ($_GET['gridsquare']) {
	// -------------------------------
	//  special handler to build a advanced query from the link in stats or profile.  
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	require_once('geograph/searchengineflickr.class.php');
	
	if ($_GET['u'])
		$_GET['user_id'] = $_GET['u']; 

	$engine = new SearchEngineFlickr('#'); 
 	$engine->buildAdvancedQuery($_GET);
 	
 	//should never fail?? - but display form 'in case'
 	
 	$db = GeographDatabaseConnection(true);

	advanced_form($smarty,$db);
 	
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// -------------------------------
	//  Build advacned query 
	// -------------------------------
	
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	require_once('geograph/searchengineflickr.class.php');
	
	if ($_POST['refine']) {
		//we could use the selected item but then have to check for numberic placenames
		$_POST['placename'] = $_POST['old-placename'];
	} else {
		$engine = new SearchEngineFlickr('#'); 
		$engine->buildAdvancedQuery($_POST);	
		
		//if we get this far then theres a problem...
		$smarty->assign('errormsg', $engine->errormsg);
	}
	
	if ($engine->criteria->is_multiple) {
		//todo these shouldnt be hardcoded as there other possiblities for suggestions
		$smarty->assign('multipletitle', "Placename");
		$smarty->assign('multipleon', "placename");
		
		$smarty->assign_by_ref('criteria', $engine->criteria);
		$smarty->assign_by_ref('post', $_POST);
		$smarty->assign_by_ref('references',$CONF['references']);	
		$smarty->assign('searchdesc', $engine->searchdesc);
		$smarty->display('search_multiple.tpl');
	} else {
		if ($_GET['i']) {
			$db = GeographDatabaseConnection(true);
		
			$query = $db->GetRow("SELECT searchq FROM queries WHERE id = ".intval($_GET['i']));
			$smarty->assign('searchq', $query['searchq']);
		} else if ($_SESSION['searchq']) {
			$smarty->assign('searchq', $_SESSION['searchq']);
		}
		require_once('geograph/imagelist.class.php');
		require_once('geograph/gridimage.class.php');
		require_once('geograph/gridsquare.class.php');
		//lets find some recent photos
		new RecentImageList($smarty);
	}
} else if ($q=stripslashes($_GET['q'])) {
	// -------------------------------
	//  Build a query from a single text string
	// -------------------------------
	
	//remember the query in the session
	$_SESSION['searchq']=$q;

	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	require_once('geograph/searchengineflickr.class.php');
	
 	$engine = new SearchEngineFlickr('#'); 
 	$engine->buildSimpleQuery($q);
 	if ($engine->criteria->is_multiple) {
		//todo these shouldnt be hardcoded as there other possiblities for suggestions
		$smarty->assign('multipletitle', "Placename");
		$smarty->assign('multipleon', "placename");

		$usercriteria = new SearchCriteria_All();
		$usercriteria->setByUsername($q);
		if (!empty($usercriteria->realname)) {
			//could also be a username
			$smarty->assign('pos_realname', $usercriteria->realname);
			$smarty->assign('pos_user_id', $usercriteria->user_id);
		}

		$smarty->assign_by_ref('criteria', $engine->criteria);
		$smarty->assign_by_ref('post', $_GET);
		$smarty->assign_by_ref('references',$CONF['references']);	
		$smarty->assign('searchdesc', $engine->searchdesc);
		$smarty->display('search_multiple.tpl');
	} else {
 	
		$smarty->assign('errormsg', $engine->errormsg);
		
		$smarty->assign('searchq', $q);
		
		
		require_once('geograph/imagelist.class.php');
		require_once('geograph/gridimage.class.php');
		require_once('geograph/gridsquare.class.php');
		//lets find some recent photos
		new RecentImageList($smarty);
		$smarty->display('flickr.tpl');	
	}

} else if ($_GET['i'] && !$_GET['form']) {
	// -------------------------------
	//  Search Results
	// -------------------------------
	
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	require_once('geograph/searchengineflickr.class.php');
	require_once('geograph/gridsquare.class.php');
		
		$pg = $_GET['page'];
		if ($pg == '' or $pg < 1) {$pg = 1;}
		
	$engine = new SearchEngineFlickr($_GET['i']);
	$engine->Execute($pg); 
	
	$smarty->assign('i', $_GET['i']);
	$smarty->assign('currentPage', $pg);
	$smarty->assign_by_ref('engine', $engine);
	
	if ($engine->criteria->searchclass == 'GridRef' && strpos($engine->criteria->searchdesc,$engine->results[0]->grid_reference) === FALSE) {
		$smarty->assign('nofirstmatch', true);
	}	

	
	$smarty->display('flickr_results_'.$engine->getDisplayclass().'.tpl');

} else {
	// -------------------------------
	//  Simple Form
	// -------------------------------
	
	if ($_GET['i']) {
		$db = GeographDatabaseConnection(true);
	
		$query = $db->GetRow("SELECT searchq FROM queries WHERE id = ".intval($_GET['i']));
		$smarty->assign('searchq', $query['searchq']);
	} else if ($_SESSION['searchq']) {
		$smarty->assign('searchq', $_SESSION['searchq']);
	}
	
	require_once('geograph/imagelist.class.php');
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	//lets find some recent photos
	new RecentImageList($smarty);
	

	$smarty->display('flickr.tpl');
}



	
?>
