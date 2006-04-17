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

$i=(!empty($_GET['i']))?intval($_GET['i']):'';

$imagestatuses = array('geograph' => 'geograph only','geograph,accepted' => 'geographs &amp; supplemental','accepted' => 'supplemental only');
$sortorders = array(''=>'','random'=>'Random','dist_sqd'=>'Distance','gridimage_id'=>'Date Submitted','imagetaken'=>'Date Taken','imageclass'=>'Image Category','realname'=>'Contributor Name','grid_reference'=>'Grid Reference','title'=>'Image Title','x'=>'West-&gt;East','y'=>'South-&gt;North');
#,'user_id'=>'Contributer ID'

//available as a function, as doesn't come into effect if just re-using a smarty cache
function dieUnderHighLoad($threshold = 2) {
	global $smarty,$USER;
	if (strpos($_ENV["OS"],'Windows') === FALSE) {
		//lets give registered users a bit more leaway!
		if ($USER->registered) {
			$threshold *= 2;
		}
		//check load average, abort if too high
		$buffer = "0 0 0";
		$f = fopen("/proc/loadavg","r");
		if ($f)
		{
			if (!feof($f)) {
				$buffer = fgets($f, 1024);
			}
			fclose($f);
		}
		$loads = explode(" ",$buffer);
		$load=(float)$loads[0];

		if ($load>$threshold)
		{
			$smarty->assign('searchq',stripslashes($_GET['q']));	
			$smarty->display('search_unavailable.tpl');	
			exit;
		}
	}
}

if (isset($_GET['fav']) ) {
	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
	}
	$fav = ($_GET['fav'])?'Y':'N';
	$db->query("UPDATE queries SET favorite = '$fav' WHERE id = $i AND user_id = {$USER->user_id}");
	
	header("Location:/search.php");	
	exit;
	
} else if (!empty($_GET['first']) || !empty($_GET['blank']) ) {
	dieUnderHighLoad();
	// -------------------------------
	//  special handler to build a special query for myriads/numberical squares.
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	
	$data = array();
	
	
	if (!empty($_GET['first'])) {
		//replace for myriads
		$gr = preg_replace('/^([A-Z]{1,2})(\d)(\d)$/','$1$2_$3_',$_GET['first']);


		//replace for numberical squares
		$gr = preg_replace('/\w*(\d{4})/','%$1',$gr);


		$name = preg_replace('/\w+(\d{4})/','$1',$_GET['first']);


		$data['description'] = "first geographs in $name";

		$data['searchq'] = "grid_reference LIKE '$gr' and ftf = 1";
	} elseif (!empty($_GET['blank'])) {
		$data['description'] = "with blank comment";
		$data['searchq'] = "(comment = '' OR title='')";
	}
	
	$data['orderby'] = 'gridimage_id';
	if (!preg_match('/\w*(\d{4})/',$_GET['first']))
		$data['reverse_order_ind'] = '1';
	
	if (!empty($_GET['u']))
		$data['user_id'] = $_GET['u']; 

	$data['adminoverride'] = 1;

	$engine = new SearchEngine('#'); 
 	$engine->buildAdvancedQuery($data);
 	
 	//should never fail?? - but display form 'in case'
 	
 	//if we get this far then theres a problem...
	$smarty->assign('errormsg', $engine->errormsg);
  	
   	foreach ($data as $key=> $value) {
		$smarty->assign($key, $value);
	}
	$smarty->reassignPostedDate("submitted_start");
	$smarty->reassignPostedDate("submitted_end");
	$smarty->reassignPostedDate("taken_start");
	$smarty->reassignPostedDate("taken_end");
	
 	$db=NewADOConnection($GLOBALS['DSN']);
	if (empty($db)) die('Database connection failed');

	advanced_form($smarty,$db);
 	
} else if (!empty($_GET['do']) || !empty($_GET['imageclass']) || !empty($_GET['u']) || !empty($_GET['gridsquare'])) {
	dieUnderHighLoad();
	// -------------------------------
	//  special handler to build a advanced query from the link in stats or profile.  
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');

	if (!empty($_GET['u']))
		$_GET['user_id'] = $_GET['u']; 

	$_GET['adminoverride'] = 0; //prevent overriding it
		
	$engine = new SearchEngine('#'); 
 	$engine->buildAdvancedQuery($_GET);
 	
 	//should never fail?? - but display form 'in case'
 	
 	//if we get this far then theres a problem...
	$smarty->assign('errormsg', $engine->errormsg);
 	
 	foreach ($_GET as $key=> $value) {
		$smarty->assign($key, $value);
	}
	$smarty->reassignPostedDate("submitted_start");
	$smarty->reassignPostedDate("submitted_end");
	$smarty->reassignPostedDate("taken_start");
	$smarty->reassignPostedDate("taken_end");
 	
 	$db=NewADOConnection($GLOBALS['DSN']);
	if (empty($db)) die('Database connection failed');

	advanced_form($smarty,$db);
 	
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	dieUnderHighLoad();
	// -------------------------------
	//  Build advacned query 
	// -------------------------------
	
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');

	if (!empty($_POST['refine'])) {
		//we could use the selected item but then have to check for numberic placenames
		$_POST['placename'] = $_POST['old-placename'];
	} else {
		$_POST['adminoverride'] = 0; //prevent overriding it
		$engine = new SearchEngine('#'); 
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
		foreach ($_POST as $key=> $value) {
			$smarty->assign($key, $value);
		}
		foreach (array('postcode','textsearch','gridref','county_id','placename','all_checked') as $key) {
			if (isset($_POST[$key])) 
				$smarty->assign('elementused', $key);
		}
		
		$smarty->reassignPostedDate("submitted_start");
		$smarty->reassignPostedDate("submitted_end");
		$smarty->reassignPostedDate("taken_start");
		$smarty->reassignPostedDate("taken_end");
		
		if (!empty($_POST['all_ind']))
			$smarty->assign('all_checked', 'checked="checked"');
		if (!empty($_POST['user_invert_ind']))
			$smarty->assign('user_invert_checked', 'checked="checked"');
		if (!empty($_POST['reverse_order_ind']))
			$smarty->assign('reverse_order_ind', 'checked="checked"');
				
		$db=NewADOConnection($GLOBALS['DSN']);
		if (empty($db)) die('Database connection failed');
		
		advanced_form($smarty,$db);
	}
} elseif (!empty($_GET['q'])) {
	dieUnderHighLoad();
	
	// -------------------------------
	//  Build a query from a single text string
	// -------------------------------
	
	$q=trim($_GET['q']);
	
	//remember the query in the session
	$_SESSION['searchq']=$q;

	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');

 	$engine = new SearchEngine('#'); 
 	$engine->buildSimpleQuery($q,$CONF['default_search_distance'],(isset($_GET['form']) && $_GET['form'] == 'simple')?'simple':'auto');
 	if (isset($engine->criteria) && $engine->criteria->is_multiple) {
 		if (empty($_GET['distance']))
 			$_GET['distance'] = $CONF['default_search_distance']; 
 	
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
		$smarty->display('search.tpl');	
	}

} else if (isset($_GET['form']) && $_GET['form'] == 'advanced') {
	dieUnderHighLoad(1.5);
	// -------------------------------
	//  Advanced Form
	// -------------------------------

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');

		$smarty->assign('submitted_start', "0-0-0");
		$smarty->assign('submitted_end', "0-0-0");

		$smarty->assign('taken_start', "0-0-0");
		$smarty->assign('taken_end', "0-0-0");

	if (is_int($i)) {
		$query = $db->GetRow("SELECT * FROM queries WHERE id = $i");

		$smarty->assign('searchclass', $query['searchclass']);
		switch ($query['searchclass']) {
			case "Special":
				die("ERROR:Attempt to edit a locked search");
				break;
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
		$smarty->assign('moderation_status', $query['limit2']);
		$smarty->assign('imageclass', $query['limit3']);
		$smarty->assign('reference_index', $query['limit4']);
		$smarty->assign('gridsquare', $query['limit5']);
		
		
		if (!empty($query['limit6'])) {
			$dates = explode('^',$query['limit6']);
			if ($dates[0]) 
				$smarty->assign('submitted_start', $dates[0]);
			if ($dates[1]) 
				$smarty->assign('submitted_end', $dates[1]);
		}
		if (!empty($query['limit7'])) {
			$dates = explode('^',$query['limit7']);
			if ($dates[0]) 
				$smarty->assign('taken_start', $dates[0]);
			if ($dates[1]) 
				$smarty->assign('taken_end', $dates[1]);
		}
		$smarty->assign('distance', $query['limit8']);
	
		$smarty->assign('topic_id', $query['limit9']);
	
		$query['orderby'] = preg_replace('/^submitted/','gridimage_id',$query['orderby']);
		
		if (strpos($query['orderby'],' desc') > 0) {
			$smarty->assign('orderby', preg_replace('/ desc$/','',$query['orderby']));
			$smarty->assign('reverse_order_checked', 'checked="checked"');
		} else {
			$smarty->assign('orderby', $query['orderby']);
		}
		$smarty->assign('displayclass', $query['displayclass']);
		$smarty->assign('resultsperpage', $query['resultsperpage']);
		$smarty->assign('i', $i);
		
		advanced_form($smarty,$db);
	} else {
		$smarty->assign('resultsperpage', $USER->search_results?$USER->search_results:15);	
		$smarty->assign('distance', $CONF['default_search_distance']);	
		
		advanced_form($smarty,$db,true); //we can cache the blank form!
	}

	


} elseif (is_int($i) && empty($_GET['form'])) {
	// -------------------------------
	//  Search Results
	// -------------------------------
	
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/gridsquare.class.php');
		
		$pg = (!empty($_GET['page']))?intval($_GET['page']):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}
		
	$engine = new SearchEngine($i);
	
	$template = 'search_results_'.$engine->getDisplayclass().'.tpl';
	$cacheid="search|$i.$pg";
	if (!empty($_GET['count'])) {
		$engine->countOnly = 1;
		$cacheid.=".";
	}

	if (!$smarty->is_cached($template, $cacheid)) {
		dieUnderHighLoad(3);
		
		
		
		$smarty->assign('querytime', $engine->Execute($pg)); 
		
		$smarty->assign('i', $i);
		$smarty->assign('currentPage', $pg);
		$smarty->assign_by_ref('engine', $engine);

		if (!$engine->countOnly && $pg == 1 
			&& $engine->criteria->searchclass == 'GridRef'
			&& $engine->criteria->issubsetlimited == false
			&& ( $engine->criteria->orderby == 'dist_sqd' || $engine->criteria->orderby == '' )
			&& strpos($engine->criteria->searchdesc,$engine->results[0]->grid_reference) === FALSE) {
			$smarty->assign('nofirstmatch', true);
		}	
	}
	
	if ($engine->criteria->user_id == $USER->user_id) {
		if (!$db) {
			$db=NewADOConnection($GLOBALS['DSN']);
			if (!$db) die('Database connection failed');
		}
		$db->query("UPDATE queries SET use_timestamp = null WHERE id = $i");
	}
	
	$smarty->display($template, $cacheid);



} else {
	dieUnderHighLoad();
	// -------------------------------
	//  Simple Form
	// -------------------------------
	

	if (is_int($i)) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
		$query = $db->GetRow("SELECT searchq FROM queries WHERE id = $i");
		$smarty->assign('searchq', $query['searchq']);
	} else if ($_SESSION['searchq']) {
		$smarty->assign('searchq', $_SESSION['searchq']);
	}
	if (!$smarty->is_cached('search.tpl')) {
		if (!isset($db)) {
			$db=NewADOConnection($GLOBALS['DSN']);
			if (empty($db)) die('Database connection failed');
		}
		//list of a few image classes 
		$arr = $db->GetAssoc("select imageclass,concat(imageclass,' [',count(*),']') from gridimage_search 
			where length(imageclass)>0 
			group by imageclass order by rand() limit 5");
		$smarty->assign_by_ref('imageclasslist',$arr);	
	}
	if ($USER->registered) {
		if (!$db) {
			$db=NewADOConnection($GLOBALS['DSN']);
			if (!$db) die('Database connection failed');
		}
		if (isset($_GET['all'])) {
			$limit = 999;
			$smarty->assign('all',1);	
		} elseif (isset($_GET['more'])) {
			$limit = 30;
			$smarty->assign('more',1);	
		} else
			$limit = 8;
		
		$recentsearchs = $db->GetAssoc("
			(select queries.id,favorite,searchdesc,`count`,use_timestamp,searchclass from queries 
			left join queries_count using (id) 
			where user_id = {$USER->user_id} and favorite = 'N' and searchuse = 'search'
			group by searchdesc,searchq,displayclass,resultsperpage order by use_timestamp desc,id desc	limit $limit) 
				UNION
			(select queries.id,favorite,searchdesc,`count`,use_timestamp,searchclass from queries 
			left join queries_count using (id) 
			where user_id = {$USER->user_id} and favorite = 'Y' and searchuse = 'search'
			group by searchdesc,searchq,displayclass,resultsperpage order by use_timestamp desc,id desc	limit $limit)
			order by use_timestamp desc,id desc	");
		$smarty->assign_by_ref('recentsearchs',$recentsearchs);	
	}
	
	require_once('geograph/imagelist.class.php');
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	//lets find some recent photos
	new RecentImageList($smarty);
	

	$smarty->display('search.tpl');
}

	function advanced_form(&$smarty,&$db,$is_cachable = false) {
		global $CONF,$imagestatuses,$sortorders,$USER;
		
		if (isset($_GET['Special'])) {
			$USER->mustHavePerm("admin");
			$template = 'search_admin_advanced.tpl';
		} else {
			$template = 'search_advanced.tpl';
		}
		if ($is_cachable) {
			$smarty->caching = 2; // lifetime is per cache
			$smarty->cache_lifetime = 3600*3; //3hr cache
		} else {
			$smarty->caching = 0; // NO caching
		}
		
		$smarty->assign('pagesizes', array(5,10,15,20,30,50));
		
		if (!$is_cachable || !$smarty->is_cached($template, $is_cachable)) {
			$smarty->assign('displayclasses', array('full' => 'full listing','text' => 'text description only','thumbs' => 'thumbnails only','slide' => 'slide-show mode'));
			$smarty->assign('distances', array(1,5,10,20,30,50,100,250,500,1000,2000));

			$countylist = array();
			$recordSet = &$db->Execute("SELECT reference_index,county_id,name FROM loc_counties WHERE n > 0"); 
			while (!$recordSet->EOF) 
			{
				$countylist[$CONF['references'][$recordSet->fields[0]]][$recordSet->fields[1]] = $recordSet->fields[2];
				$recordSet->MoveNext();
			}
			$recordSet->Close(); 
			$smarty->assign_by_ref('countylist', $countylist);

			$arr = $db->CacheGetAssoc(24*3600,"select imageclass,concat(imageclass,' [',count(*),']') from gridimage_search
				where length(imageclass)>0
				group by imageclass");
			$arr = array_merge(array('-'=>'-unclassified-'),$arr);
			$smarty->assign_by_ref('imageclasslist',$arr);	

			$topics = $db->GetAssoc("select gp.topic_id,concat(topic_title,' [',count(*),']') from gridimage_post gp
				inner join geobb_topics using (topic_id)
				group by gp.topic_id 
				having count(*) > 5
				order by topic_title");
				
			$topics=array("1"=>"Any Topic") + $topics; 	
			$smarty->assign_by_ref('topiclist',$topics);	

			$topusers=$db->CacheGetAssoc(24*3600,"select user.user_id,concat(realname,' [',count(*),']')
				from user inner join gridimage using(user_id) where ftf=1
				group by user_id order by realname");
			$smarty->assign_by_ref('userlist',$topusers);

			require_once('geograph/gridsquare.class.php');
			$square=new GridSquare;
			$smarty->assign('prefixes', $square->getGridPrefixes());

			$smarty->assign_by_ref('imagestatuses', $imagestatuses);

			$smarty->assign_by_ref('sortorders', $sortorders);

			$smarty->assign_by_ref('references',$CONF['references']);
		}
		
		$smarty->display($template, $is_cachable);
	}

	
?>
