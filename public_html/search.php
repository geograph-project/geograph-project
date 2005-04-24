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

$imagestatuses = array('geograph' => 'geographs','geograph,accepted' => 'geographs &amp; supplemental','geograph,accepted,pending' => 'all','pending' => 'pending only');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// -------------------------------
	//  Build advacned query 
	// -------------------------------
	
	if (preg_match("/^ *([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9])([A-Z]{0,2}) *$/",strtoupper($_POST['postcode']),$pc)) {
		require_once('geograph/searchcriteria.class.php');
		$searchq = $pc[1].$pc[2]." ".$pc[3];
		$criteria = new SearchCriteria_Postcode();
		$criteria->setByPostcode($searchq);
		if ($criteria->x != 0) {
			$searchclass = 'Postcode';
			$searchdesc = ", near postcode ".$searchq;
			$searchx = $criteria->x;
			$searchy = $criteria->y;	
		} else {
			$smarty->assign('errormsg', "Invalid Postcode");
		}
	} else if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/",$_POST['gridref'],$gr)) {
		require_once('geograph/gridsquare.class.php');
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($q);
		if ($grid_ok) {
			$searchclass = 'GridRef';
			$searchq = $_POST['gridref'];
			$searchdesc = ", near grid reference ".$square->grid_reference;
			$searchx = $square->x;
			$searchy = $square->y;	
		} else {
			$smarty->assign('errormsg', $square->errormsg);
		}
	} else if ($_POST['county_id']) {
		require_once('geograph/searchcriteria.class.php');
		$criteria = new SearchCriteria_County();
		$criteria->setByCounty($_POST['county_id']);
		if (!empty($criteria->county_name)) {
			$searchclass = 'County';
			$searchq = $_POST['county_id'];
			$searchdesc = ", near center of ".$criteria->county_name;
			$searchx = $criteria->x;
			$searchy = $criteria->y;	
		} else {
			$smarty->assign('errormsg', "Invalid County????");
		}
	} else if ($_POST['placename']) {
		require_once('geograph/searchcriteria.class.php');
		$criteria = new SearchCriteria_Placename();
		$criteria->setByPlacename($_POST['placename']);
		if (!empty($criteria->placename)) {
			$searchclass = 'Placename';
			$searchq = $criteria->placename;
			$searchdesc = ", near ".$criteria->placename;
			$searchx = $criteria->x;
			$searchy = $criteria->y;	
		} else if ($criteria->is_multiple) {
			$searchdesc = ", near '".$criteria->$_POST['placename']."'?";
			$smarty->assign('multipletitle', "Placename");
			$smarty->assign('multipleon', "placename");
			$smarty->assign_by_ref('criteria', $criteria);
			$smarty->assign_by_ref('post', $_POST);
		} else {
			$smarty->assign('errormsg', "Invalid Placename????");
		}
	} else if (!empty($_POST['textsearch'])) {
		$searchclass = 'Text';
		$searchq = $_POST['textsearch'];
		$searchdesc = ", containing '{$_POST['textsearch']}' ";	
	} else { //if (!empty($_POST['random_ind'])) {
		$searchclass = 'Random';
		$searchdesc = ", in random order ";	
	} 
	
	if ($searchclass) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed'); 

		$sql = "INSERT INTO queries SET searchclass = '$searchclass',".
		"searchq = ".$db->Quote($searchq).",".
		"displayclass = ".$db->Quote($_POST['displayclass']).",".
		"resultsperpage = ".$db->Quote($_POST['resultsperpage']);
		if ($searchx > 0 && $searchy > 0)
			$sql .= ",x = $searchx,y = $searchy";
		if ($USER->registered)
			$sql .= ",user_id = {$USER->user_id}";

		if ($_POST['user_id']) {
			$sql .= ",limit1 = ".$db->Quote(($_POST['user_invert_ind']?'!':'').$_POST['user_id']);
			$searchdesc .= ", by user";//todo put in name!
		}
		if ($_POST['moduration_status']) {
			$sql .= ",limit2 = ".$db->Quote($_POST['moduration_status']);
			$searchdesc .= ", showing ".$imagestatuses[$_POST['moduration_status']]." images";
		}
		if ($_POST['imageclass']) {
			$sql .= ",limit3 = ".$db->Quote($_POST['imageclass']);
			$searchdesc .= ", classifed as ".$_POST['imageclass'];
		}
		if ($_POST['reference_index']) {
			$sql .= ",limit4 = ".$db->Quote($_POST['reference_index']);
			$searchdesc .= ", in ".$CONF['references'][$_POST['reference_index']];
		}
		if ($_POST['gridsquare']) {
			$sql .= ",limit5 = ".$db->Quote($_POST['gridsquare']);
			$searchdesc .= ", in ".$_POST['gridsquare'];
		}

		$sql .= ",searchdesc = ".$db->Quote($searchdesc);

		$db->debug=true;
		$db->Execute($sql);

		$i = $db->Insert_ID();
		header("Location:http://{$_SERVER['HTTP_HOST']}/search.php?i={$i}");
		print "<a href=\"http://{$_SERVER['HTTP_HOST']}/search.php?i={$i}\">Your Search Results</a>";
		exit;		
	} else if ($criteria->is_multiple) {
		if ($_POST['user_id']) {
			$searchdesc .= ", by user";//todo put in name!
		}
		if ($_POST['moduration_status']) {
			$searchdesc .= ", showing ".$imagestatuses[$_POST['moduration_status']]." images";
		}
		if ($_POST['imageclass']) {
			$searchdesc .= ", classifed as ".$_POST['imageclass'];
		}
		if ($_POST['reference_index']) {
			$searchdesc .= ", in ".$CONF['references'][$_POST['reference_index']];
		}
		if ($_POST['gridsquare']) {
			$searchdesc .= ", in ".$_POST['gridsquare'];
		}
		$smarty->assign('searchdesc', $searchdesc);
		$smarty->display('search_multiple.tpl');
	} else {
		$smarty->assign('searchq', $q);
		foreach ($_POST as $key=> $value) {
			$smarty->assign($key, $value);
		}
		foreach (array('postcode','textsearch','gridref','county_id','placename','random_checked') as $key) {
			if ($_POST[$key]) 
				$smarty->assign('elementused', $key);
		}
		
		
		if ($_POST['random_ind'])
			$smarty->assign('random_checked', 'checked="checked"');
		if ($_POST['user_invert_ind'])
			$smarty->assign('user_invert_checked', 'checked="checked"');
				
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

	if (preg_match("/^ *([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9])([A-Z]{0,2}) *$/",strtoupper($q),$pc)) {
		require_once('geograph/searchcriteria.class.php');
		$searchq = $pc[1].$pc[2]." ".$pc[3];
		$criteria = new SearchCriteria_Postcode();
		$criteria->setByPostcode($searchq);
		if ($criteria->x != 0) {
			$searchclass = 'Postcode';
			$searchdesc = ", near postcode ".$searchq;
			$searchx = $criteria->x;
			$searchy = $criteria->y;	
		} else {
			$smarty->assign('errormsg', "Invalid Postcode");
		}
	} else if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/",$q,$gr)) {
		require_once('geograph/gridsquare.class.php');
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($q);
		if ($grid_ok) {
			$searchclass = 'GridRef';
			$searchdesc = ", near grid reference ".$square->grid_reference;
			$searchx = $square->x;
			$searchy = $square->y;			
		} else {
			$smarty->assign('errormsg', $square->errormsg);
		}
	} else {
		require_once('geograph/searchcriteria.class.php');
		$criteria = new SearchCriteria_Placename();
		$criteria->setByPlacename($q);
		if (!empty($criteria->placename)) {
			//if one placename the search on that
			$searchclass = 'Placename';
			$searchq = $criteria->placename;
			$searchdesc = ", near ".$criteria->placename;
			$searchx = $criteria->x;
			$searchy = $criteria->y;	
		} else {
			//asuume a text search
			$searchclass = 'Text';
			$searchq = $q;
			$searchdesc = ", containing '{$q}' ";	
		}
		
		$smarty->assign('errormsg', "Query not understood!!");
	}
	
	if ($searchclass) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed'); 
		
		$sql = "INSERT INTO queries SET searchclass = '$searchclass',".
		"searchdesc = ".$db->Quote($searchdesc).",".
		"searchq = ".$db->Quote($q);
		if ($searchx > 0 && $searchy > 0)
			$sql .= ",x = $searchx,y = $searchy";
		if ($USER->registered)
			$sql .= ",user_id = {$USER->user_id}";
			
		$db->debug=true;
		$db->Execute($sql);
		
		$i = $db->Insert_ID();
		header("Location:http://{$_SERVER['HTTP_HOST']}/search.php?i={$i}");
		print "<a href=\"http://{$_SERVER['HTTP_HOST']}/search.php?i={$i}\">Your Search Results</a>";
		exit;		
	} else {
		$smarty->assign('searchq', $q);
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
			case "Random":
				$smarty->assign('random_checked', 'checked="checked"');
				$smarty->assign('elementused', 'random_checked');
				break;
		}
	
		if (preg_match('/^!/',$this->limit1)) {
			$smarty->assign('user_id', preg_replace('/^!/','',$query['limit1']));
			$smarty->assign('user_invert_checked', 'checked="checked"');
		} else {
			$smarty->assign('user_id', $query['limit1']);
		}
		$smarty->assign('moduration_status', $query['limit2']);
		$smarty->assign('imageclass', $query['limit3']);
		$smarty->assign('reference_index', $query['limit4']);
		$smarty->assign('gridsquare', $query['limit5']);



		$smarty->assign('displayclass', $query['displayclass']);
		$smarty->assign('resultsperpage', $query['resultsperpage']);
	} else {
		$smarty->assign('resultsperpage', 15);	
	}

	advanced_form($smarty,$db);


} else if ($_GET['i']) {
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
		//todo get a string from the relevent search 
	} else if ($_SESSION['searchq']) {
		$smarty->assign('searchq', $_SESSION['searchq']);
	}
	
	$smarty->display('search.tpl');
}

	function advanced_form(&$smarty,&$db) {
		global $CONF,$imagestatuses;
		

		$smarty->assign('displayclasses', array('full' => 'full listing','text' => 'text description only','thumbs' => 'thumbnails only'));
		$smarty->assign('pagesizes', array(5,10,15,20,30,50));

		

		$countylist = array();
		$recordSet = &$db->Execute("SELECT reference_index,county_id,name FROM loc_counties"); 
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

		$smarty->assign_by_ref('references',$CONF['references']);	

		$smarty->display('search_advanced.tpl');
	}

	
?>
