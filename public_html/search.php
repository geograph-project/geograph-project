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

$imagestatuses = array('geograph' => 'geograph','geograph,accepted' => 'geographs &amp; supplemental','geograph,accepted,pending' => 'all','pending' => 'pending only');
$sortorders = array(''=>'','random'=>'Random','dist_sqd'=>'Distance','submitted'=>'Date Submitted','imageclass'=>'Image Category','realname'=>'Contributer Name','grid_reference'=>'Grid Reference','title'=>'Image Title','x'=>'West-&gt;East','y'=>'South-&gt;North');
#,'user_id'=>'Contributer ID'


if ($_GET['imageclass'] || $_GET['u'] || $_GET['gridsquare']) {
	// -------------------------------
	//  special handler to build a advanced query from the link in stats or profile.  
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');

	if ($_GET['u'])
		$_GET['user_id'] = $_GET['u']; 

	$engine = new SearchEngine('#'); 
 	$engine->buildAdvancedQuery($_GET);
 	
 	//should never fail?? - but display form 'in case'
 	
 	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');

	advanced_form(&$smarty,$db);
 	
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// -------------------------------
	//  Build advacned query 
	// -------------------------------
	
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');

 	$engine = new SearchEngine('#'); 
 	$engine->buildAdvancedQuery($_POST);	
	
	//if we get this far then theres a problem...
	
	
	$smarty->assign('errormsg', $engine->errormsg);
	
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
		$smarty->assign('searchq', $q);
		foreach ($_POST as $key=> $value) {
			$smarty->assign($key, $value);
		}
		foreach (array('postcode','textsearch','gridref','county_id','placename','all_checked') as $key) {
			if ($_POST[$key]) 
				$smarty->assign('elementused', $key);
		}
		
		
		if ($_POST['all_ind'])
			$smarty->assign('all_checked', 'checked="checked"');
		if ($_POST['user_invert_ind'])
			$smarty->assign('user_invert_checked', 'checked="checked"');
		if ($_POST['reverse_order_ind'])
			$smarty->assign('reverse_order_ind', 'checked="checked"');
				
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
		
		advanced_form(&$smarty,$db);
	}
} else if ($q=stripslashes($_GET['q'])) {
	// -------------------------------
	//  Build a query from a single text string
	// -------------------------------
	
	//remember the query in the session
	$_SESSION['searchq']=$q;

	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');

 	$engine = new SearchEngine('#'); 
 	$engine->buildSimpleQuery($q);
 	if ($engine->criteria->is_multiple) {
		//todo these shouldnt be hardcoded as there other possiblities for suggestions
		$smarty->assign('multipletitle', "Placename");
		$smarty->assign('multipleon', "placename");

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
		$recent=new ImageList(array('pending', 'accepted', 'geograph'), 'submitted desc', 5);
		$recent->assignSmarty($smarty, 'recent');
		$smarty->display('search.tpl');	
	}

} else if ($_GET['form'] == 'advanced') {
	// -------------------------------
	//  Advanced Form
	// -------------------------------

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');

	if ($_GET['i']) {
		$query = $db->GetRow("SELECT * FROM queries WHERE id = ".$_GET['i']);

		$smarty->assign('searchclass', $query['searchclass']);
		switch ($query['searchclass']) {
			case "Postcode":
				$smarty->assign('postcode', $query['searchq']);
				$smarty->assign('elementused', 'postcode');
				break;
			case "Text":
				$smarty->assign('textsearch', $query['searchq']);
				$smarty->assign('elementused', 'textsearch');
				break;
			case "GridRef":
				$smarty->assign('gridref', $query['searchq']);
				$smarty->assign('elementused', 'gridref');
				break;
			case "County":
				$smarty->assign('county_id', $query['searchq']);
				$smarty->assign('elementused', 'county_id');
				break;
			case "Placename":
				$smarty->assign('placename', $query['searchq']);
				$smarty->assign('elementused', 'placename');
				break;
			case "All":
				$smarty->assign('all_checked', 'checked="checked"');
				$smarty->assign('elementused', 'all_ind');
				break;
		}
	
		if (strpos($query['limit1'],'!') === 0) {
			$smarty->assign('user_id', preg_replace('/^!/','',$query['limit1']));
			$smarty->assign('user_invert_checked', 'checked="checked"');
		} else {
			$smarty->assign('user_id', $query['limit1']);
		}
		$smarty->assign('moduration_status', $query['limit2']);
		$smarty->assign('imageclass', $query['limit3']);
		$smarty->assign('reference_index', $query['limit4']);
		$smarty->assign('gridsquare', $query['limit5']);

		if (strpos($query['orderby'],' desc') > 0) {
			$smarty->assign('orderby', preg_replace('/ desc$/','',$query['orderby']));
			$smarty->assign('reverse_order_checked', 'checked="checked"');
		} else {
			$smarty->assign('orderby', $query['orderby']);
		}
		$smarty->assign('displayclass', $query['displayclass']);
		$smarty->assign('resultsperpage', $query['resultsperpage']);
		$smarty->assign('i', $_GET['i']);
		
	} else {
		$smarty->assign('resultsperpage', 15);	
	}

	advanced_form($smarty,$db);


} else if ($_GET['i'] && !$_GET['form']) {
	// -------------------------------
	//  Search Results
	// -------------------------------
	
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/gridsquare.class.php');
		
		$pg = $_GET['page'];
		if ($pg == '' or $pg < 1) {$pg = 1;}
		
	$engine = new SearchEngine($_GET['i']);
	$engine->Execute($pg); 
	
	$smarty->assign('i', $_GET['i']);
	$smarty->assign('currentPage', $pg);
	$smarty->assign_by_ref('engine', $engine);
	

	$smarty->display('search_results_'.$engine->getDisplayclass().'.tpl');

} else {
	// -------------------------------
	//  Simple Form
	// -------------------------------
	
	if ($_GET['i']) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
	
		$query = $db->GetRow("SELECT searchq FROM queries WHERE id = ".$_GET['i']);
		$smarty->assign('searchq', $query['searchq']);
	} else if ($_SESSION['searchq']) {
		$smarty->assign('searchq', $_SESSION['searchq']);
	}
	
	require_once('geograph/imagelist.class.php');
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	//lets find some recent photos
	$recent=new ImageList(array('pending', 'accepted', 'geograph'), 'submitted desc', 5);
	$recent->assignSmarty($smarty, 'recent');
	

	$smarty->display('search.tpl');
}

	function advanced_form(&$smarty,&$db) {
		global $CONF,$imagestatuses,$sortorders;
		

		$smarty->assign('displayclasses', array('full' => 'full listing','text' => 'text description only','thumbs' => 'thumbnails only'));
		$smarty->assign('pagesizes', array(5,10,15,20,30,50));

		

		$countylist = array();
		$recordSet = &$db->Execute("SELECT reference_index,county_id,name FROM loc_counties WHERE n > 0"); 
		while (!$recordSet->EOF) 
		{
			$countylist[$CONF['references'][$recordSet->fields[0]]][$recordSet->fields[1]] = $recordSet->fields[2];
			$recordSet->MoveNext();
		}
		$recordSet->Close(); 
		$smarty->assign_by_ref('countylist', $countylist);

		$arr = $db->CacheGetAssoc(24*3600,"select imageclass,concat(imageclass,' [',count(*),']') from gridimage ".
			"where length(imageclass)>0 and moderation_status in ('accepted','geograph') ".
			"group by imageclass");
		$smarty->assign_by_ref('imageclasslist',$arr);	

		$topusers=$db->CacheGetAssoc(24*3600,"select user.user_id,concat(realname,' [',count(*),']')   ".
			"from user inner join gridimage using(user_id) where ftf=1 ".
			"group by user_id order by realname");
		$smarty->assign_by_ref('userlist',$topusers);

		require_once('geograph/gridsquare.class.php');
		$square=new GridSquare;
		$smarty->assign('prefixes', $square->getGridPrefixes());

		$smarty->assign_by_ref('imagestatuses', $imagestatuses);
		$smarty->assign_by_ref('sortorders', $sortorders);

		$smarty->assign_by_ref('references',$CONF['references']);	

		$smarty->display('search_advanced.tpl');
	}

	
?>
