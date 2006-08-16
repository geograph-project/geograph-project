<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
init_session();

$smarty = new GeographPage;

$i=(!empty($_GET['i']))?intval(stripslashes($_GET['i'])):'';

$sortorders = array('dist_sqd'=>'Distance','topic_time desc'=>'Topic Started','post_time desc'=>'Latest Post','grid_reference'=>'Grid Reference','x'=>'West-&gt;East','y'=>'South-&gt;North');
#,'user_id'=>'Contributer ID'


if (!empty($_GET['gridsquare']) || !empty($_GET['u'])) {
	// -------------------------------
	//  special handler to build a advanced query from the link in stats or profile.  
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	require_once('geograph/searchenginediscuss.class.php');
	
	if (!empty($_GET['u']))
		$_GET['user_id'] = $_GET['u']; 

	$engine = new SearchEngineDiscuss('#'); 
 	$engine->buildAdvancedQuery($_GET);
 	
 	//should never fail?? - but display form 'in case'
 	
 	$db=NewADOConnection($GLOBALS['DSN']);
	if (empty($db)) die('Database connection failed');

	advanced_form($smarty,$db);
 	
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// -------------------------------
	//  Build advacned query 
	// -------------------------------
	
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	require_once('geograph/searchenginediscuss.class.php');
	
	if ($_POST['refine']) {
		//we could use the selected item but then have to check for numberic placenames
		$_POST['placename'] = $_POST['old-placename'];
	} else {
		$engine = new SearchEngineDiscuss('#'); 
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
		if ($i) {
			$db=NewADOConnection($GLOBALS['DSN']);
			if (empty($db)) die('Database connection failed');
		
			$query = $db->GetRow("SELECT searchq FROM queries WHERE id = $i");
			$smarty->assign('searchq', $query['searchq']);
		} else if (isset($_SESSION['searchq'])) {
			$smarty->assign('searchq', $_SESSION['searchq']);
		}
		require_once('geograph/imagelist.class.php');
		require_once('geograph/gridimage.class.php');
		require_once('geograph/gridsquare.class.php');
		//lets find some recent photos
		new RecentImageList($smarty);
	}
} elseif (!empty($_GET['q'])) {
	// -------------------------------
	//  Build a query from a single text string
	// -------------------------------
	
	$q=trim(stripslashes($_GET['q']));
	
	//remember the query in the session
	$_SESSION['searchq']=$q;

	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	require_once('geograph/searchenginediscuss.class.php');
	
 	$engine = new SearchEngineDiscuss('#'); 
 	
 	#$engine->buildSimpleQuery($q);
	if (preg_match("/^([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9])([A-Z]{0,2})$/",strtoupper($q))) {
		$dataarray['postcode'] = $q;
	} else if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/",$q)) {
		$dataarray['gridref'] = $q;
	} else {
		$dataarray['placename'] = $q;
	}
	$dataarray['orderby'] = $_GET['orderby'];	
 	$dataarray['distance'] = 100;	
 	$engine->buildAdvancedQuery($dataarray);
 	
 	
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
	}

} elseif (is_int($i) && empty($_GET['form'])) {
	// -------------------------------
	//  Search Results
	// -------------------------------
	
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
	require_once('geograph/searchenginediscuss.class.php');
	require_once('geograph/gridsquare.class.php');
		
		$pg = (!empty($_GET['page']))?intval($_GET['page']):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}
		
	$engine = new SearchEngineDiscuss($i);
	$engine->Execute($pg); 
	
	$smarty->assign('i', $i);
	$smarty->assign('currentPage', $pg);
	$smarty->assign_by_ref('engine', $engine);
	
	$smarty->display('discuss_results_'.$engine->getDisplayclass().'.tpl');

} else {
	// -------------------------------
	//  Simple Form
	// -------------------------------
	
	if (is_int($i)) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
	
		$query = $db->GetRow("SELECT searchq,orderby FROM queries WHERE id = $i");
		$smarty->assign('searchq', $query['searchq']);
		$smarty->assign('orderby', $query['orderby']);
	} else if ($_SESSION['searchq']) {
		$smarty->assign('searchq', $_SESSION['searchq']);
	}
	
	$smarty->assign_by_ref('sortorders', $sortorders);

	
	require_once('geograph/imagelist.class.php');
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	//lets find some recent photos
	new RecentImageList($smarty);
	

	$smarty->display('discuss_search.tpl');
}



	
?>
