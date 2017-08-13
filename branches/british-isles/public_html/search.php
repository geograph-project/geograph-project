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

if (!empty($_GET['debug'])) {
	ini_set("display_errors",true);
}

if (!empty($_GET['style'])) {
	init_session();
	$USER->getStyle();
	if (!empty($_SERVER['QUERY_STRING'])) {
		$query = preg_replace('/&style=(\w+)/','&r='.rand(),$_SERVER['QUERY_STRING']);
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: /search.php?".$query);
		exit;
	}
	header("Location: /search.php");
	exit;
} else {
	init_session_or_cache(3600, 900); //cache publically, and privately
}


#if (count($_GET) === 1) {
	if (!empty($_GET['tag'])) {
		$_GET['text'] = '['.trim($_GET['tag']).']';
	} elseif (!empty($_GET['top'])) {
		$_GET['text'] = '[top:'.trim($_GET['top']).']';
	}
#}


$smarty = new GeographPage;

$i=(!empty($_GET['i']))?intval($_GET['i']):'';

$imagestatuses = array('geograph' => 'geograph only','accepted' => 'supplemental only');

$sortorders = array(''=>'','dist_sqd'=>'Distance','gridimage_id'=>'Date Submitted','imagetaken'=>'Date Taken','imageclass'=>'Image Category','realname'=>'Contributor Name','grid_reference'=>'Grid Reference','title'=>'Image Title','x'=>'West-&gt;East','y'=>'South-&gt;North','relevance'=>'Word Relevance','count'=>'Number in Group','sequence'=>'Geographically');

$breakdowns = array(''=>'','imagetaken'=>'Day Taken','imagetaken_month'=>'Month Taken','imagetaken_year'=>'Year Taken','imagetaken_decade'=>'Decade Taken','submitted'=>'Day Submitted','submitted_month'=>'Month Submitted','submitted_year'=>'Year Submitted','  '=>'','realname'=>'Contributor Name','user_id'=>'Contributor','imageclass'=>'Image Category',' '=>'','grid_reference'=>'Grid Square','myriad'=>'Myriad','hectad'=>'Hectad');

$groupbys = array(''=>'','takendays'=>'Day Taken','submitted'=>'Day Submitted','submitted_month'=>'Month Submitted','submitted_year'=>'Year Submitted','  '=>'','auser_id'=>'Contributor','classcrc'=>'Image Category',' '=>'','agridsquare'=>'Grid Square','amyriad'=>'Myriad','ahectad'=>'Hectad','scenti'=>'Centisquare');

$displayclasses =  array(
			'full' => 'full listing',
			'more' => 'full listing + links',
			'thumbs' => 'thumbnails only',
			'thumbsmore' => 'thumbnails + links',
			'bigger' => 'thumbnails - bigger',
			'excerpt' => 'highlighted keywords',
			'map' => 'on a map',
			'slide' => 'slideshow',
			'slidebig' => 'slideshow - full page',
			'reveal' => 'slideshow - map imagine',
			'black' => 'georiver - full images + detail',
			'cooliris' => 'cooliris 3d wall',
			'mooflow' => 'cover flow',
			'text' => 'text list only',
			'spelling' => 'multi editor'
			);
$smarty->assign_by_ref('displayclasses',$displayclasses);


if (isset($_GET['legacy']) && isset($CONF['curtail_level']) && $CONF['curtail_level'] > 4 ) {
        header("HTTP/1.1 503 Service Unavailable");
	dieUnderHighLoad(0.1);
        die("server busy, please try later");
}



if (isset($_GET['fav']) && $i) {

	$db=GeographDatabaseConnection(false);
	
	$fav = ($_GET['fav'])?'Y':'N';
	$db->query("UPDATE queries SET favorite = '$fav' WHERE id = $i AND user_id = {$USER->user_id}");
	
	sleep(2);//fake delay to allow replication to catch up - ekk!
	header("Location:/search.php?d=".time());
	exit;

} else if (!empty($_GET['first']) || !empty($_GET['blank']) || !empty($_GET['glue']) || (!empty($_GET['my_squares']) &&  intval($_GET['user_id'])) ) {
	dieUnderHighLoad(2,'search_unavailable.tpl');
	// -------------------------------
	//  special handler to build a special query for myriads/numberical squares.
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');

	$data = $_GET;
	$data['searchq'] = '';
	$error = false;

	if (!empty($_GET['my_squares'])) {
		$u = intval($_GET['user_id']);
		$profile=new GeographUser($u);
		$data['description'] = "in squares photographed by ".($profile->realname);

		$data['searchq'] = "inner join user_gridsquare ug on (gs.grid_reference = ug.grid_reference and ug.user_id = $u) where 1";

	} elseif (!empty($_GET['first'])) {
		$_GET['first'] = strtoupper(preg_replace('/\s+/','',$_GET['first']));

		if (preg_match('/^[A-Z_%]{0,2}[\d_%]{1,4}$/',$_GET['first']) ) {

			//replace for myriads
			$gr = preg_replace('/^([A-Z]{1,2}|%)(\d|_)(\d|_)$/','$1$2_$3_',$_GET['first']);

			//replace for numberical squares
			$gr = preg_replace('/\w*(\d{4})/','%$1',$gr);

			$name = preg_replace('/\w+(\d{4})/','$1',$_GET['first']);

			$data['description'] = "first geographs in $name";

			$data['searchq'] = "grid_reference LIKE '$gr' and ftf = 1";
		} else {
			$error = "Unable to understand Location String";
			$_GET['form'] = 'first';
		}
	} elseif (!empty($_GET['blank'])) {
		$data['description'] = "with blank comment";
		$data['searchq'] = "(comment = '' OR title='')";
	} elseif (!empty($_GET['glue'])) {
		$sql = $l = array();
		foreach ($_GET['check'] as $check) {
			switch ($check) {
				case 'gr': $sql[] = "gi.nateastings=0"; $l[] = 'A'; break;
				case 'pg': if (in_array('p6',$_GET['check']) === FALSE) {
								$sql[] = "gi.viewpoint_eastings=0"; $l[] = 'B'; 
							} break;
				case 'p6': $sql[] =
							( (in_array('pg',$_GET['check']) === FALSE)?
								"viewpoint_eastings>0 AND ":'').
							 "gi.viewpoint_grlen = '4'"; 
							 $l[] = ( (in_array('pg',$_GET['check']) !== FALSE)?
								'B ':'').'C'; break;
				case 'dir': $sql[] = "gi.view_direction=-1"; $l[] = 'D'; break;
				case 'dat': $sql[] = "imagetaken LIKE'%-00%' OR imagetaken LIKE'0%'"; $l[] = 'E'; break;
				case 'com': if (in_array('sho',$_GET['check']) === FALSE) {
								$sql[] = "comment=''"; $l[] = 'F';
							} break;
				case 'sho': $sql[] = "comment!='' AND substring_index(comment,' ',9) = comment"; $l[] = 'G'; break;
				case 'dup': $sql[] = "comment=title"; $l[] = 'H'; break;
				case 'lon': $sql[] = "comment!='' AND substring_index(comment,' ',15) != comment"; $l[] = 'I'; break;
			}
		}
		if (count($sql)) {
			$glued = (isset($_GET['glue']) && $_GET['glue'] == 'and')?'all':'any';
			$glue = (isset($_GET['glue']) && $_GET['glue'] == 'and')?'AND':'OR';
			//arrg, might have to trim some...
			while (strlen($data['searchq'] = '(('.join(")$glue(",$sql).'))') > 255) {
				array_pop($sql);
				array_pop($l);
			}			
			$data['description'] = "with incomplete data (matches $glued of ".join(' ',$l).")";
		} else {
			$data['description'] = "";
			$data['searchq'] = '1';
		}
		$_SESSION['editpage_options'] = $_GET['editpage_options'];

	}

	if (!$error) {
		if (empty($data['orderby'])) {
			$data['orderby'] = 'gridimage_id';
			if (!preg_match('/\w*(\d{4})/',$_GET['first']))
				$data['reverse_order_ind'] = '1';
		}

		if (!empty($_GET['u']))
			$data['user_id'] = $_GET['u'];

		$data['adminoverride'] = 1;

		$engine = new SearchEngineBuilder('#');
		$engine->buildAdvancedQuery($data);

		//should never fail?? - but display form 'in case'

		//if we get this far then theres a problem...
		$smarty->assign('errormsg', $engine->errormsg);
	} else {
		$smarty->assign('errormsg', $error);
	}

	fallBackForm($data);

} else if (!empty($_GET['marked']) && isset($_COOKIE['markedImages']) || isset($_GET['markedImages'])) { //
	dieUnderHighLoad(2,'search_unavailable.tpl');
	// -------------------------------
	//  special handler to build a special query for marked list.
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
 customNoCacheHeader();
	$data = $_GET;
	$error = false;

	if (!$error) {
		if (!empty($_GET['u']))
			$data['user_id'] = $_GET['u'];

		if (!empty($_GET['markedImages'])) {
			$data['description'] = "Imported list at ".strftime("%A, %e %B, %Y. %H:%M");
			$ids = explode(',',$_GET['markedImages']);
		} else {
			$ids = explode(',',$_COOKIE['markedImages']);
		}

		$engine = new SearchEngineBuilder('#');
		$engine->buildMarkedList($ids,$data,'auto');

		//should never fail?? - but display form 'in case'

		//if we get this far then theres a problem...
		$smarty->assign('errormsg', $engine->errormsg);
	} else {
		$smarty->assign('errormsg', $error);
	}

	fallBackForm($data);

} else if (!empty($_GET['article_id']) || !empty($_GET['profile_id'])) { //
	dieUnderHighLoad(2,'search_unavailable.tpl');
	// -------------------------------
	//  special handler to build a search from an article
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');
 customNoCacheHeader();

	$data = $_GET;
	$error = false;

	$db=GeographDatabaseConnection(false);

	$isadmin=$USER->hasPerm('moderator')?1:0;

	if (!empty($_GET['article_id'])) {
		$page = $db->getRow("
		select concat('in Article: ',title) as title,content
		from article
		where ( (licence != 'none' and approved > 0)
			or article.user_id = {$USER->user_id}
			or $isadmin )
			and article_id = ".$db->Quote($_GET['article_id']).'
		limit 1');
	} else {
		$page = $db->getRow("
		select concat('in ',realname,'\'s profile') as title, about_yourself as content
		from user
		where user_id = ".$db->Quote($_GET['profile_id']).'
		limit 1');
	}

	if (count($page) && !$error) {
		$data['description'] = $page['title'];

		$ids = array();
		if (preg_match_all("/\[\[\[?(\d+)\]?\]\]|\[image id=(\d+)/",$page['content'],$g_matches)) {
                         foreach ($g_matches[1] as $idx => $g_id) {
                                if (!empty($g_id))
					$ids[] = $g_id;
                                if (!empty($g_matches[2][$idx]))
					$ids[] = $g_matches[2][$idx];
                        }
		}

		$engine = new SearchEngineBuilder('#');
		$engine->buildMarkedList($ids,$data,'auto');

		//should never fail?? - but display form 'in case'

		//if we get this far then theres a problem...
		$smarty->assign('errormsg', $engine->errormsg);
	} else {
		$smarty->assign('errormsg', $error);
	}

	fallBackForm($data);

} elseif (is_int($i) && !empty($_GET['redo'])) {
	// -------------------------------
	//  special handler to 'refine' a query by setting a new 'text' string.
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');

	$engine = new SearchEngineBuilder($i);

	if (is_int($i)) {

		$data = array();

		if (empty($engine->criteria)) {
			dieUnderHighLoad(0,'search_unavailable.tpl');
			die("Invalid Search Parameter");
		}

		$query = $engine->criteria;
		$data['searchclass'] = $query->searchclass;

		if (!empty($_GET['gridref'])) {
			if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\b/",$_GET['gridref'],$gr)) {
				$data['gridref'] = $gr[0];
				$data['distance'] = $CONF['default_search_distance'];
		 	}
		} else {
			switch ($query->searchclass) {
				case "Special":
					die("ERROR:Attempt to edit a locked search");
					break;
				case "Postcode":
					$data['postcode'] = $query->searchq;
					break;
				case "Text":
					$data['searchtext'] = $query->searchq;
					break;
				case "GridRef":
					$data['gridref'] = $query->searchq;
					break;
				case "County":
					$data['county_id'] = $query->searchq;
					break;
				case "Placename":
					$data['placename'] = $query->searchq;
					break;
			}
			$data['distance'] = $query->limit8;
		}
		
		if (!empty($_GET['text'])) {
			if (!empty($_GET['strip'])) {
				$_GET['text'] = trim(preg_replace('/\s+/',' ',preg_replace('/[^\w]+/',' ',$_GET['text'])));
			}
			$sphinx = new sphinxwrapper($_GET['text']);
			#$sphinx->processQuery();
			
			$data['searchtext'] = $sphinx->qclean;
		} else {
			$data['searchtext'] = $query->searchtext;
		}
		

		if (!empty($query->limit1)) {
			$user_id = $query->limit1;
			if (strpos($user_id,'!') === 0) {
				$user_id = preg_replace('/^!/','',$user_id);
				$data['user_invert_ind'] = 'on';
			}
			$data['user_id'] = $user_id;
		}
		$data['moderation_status'] = $query->limit2;
		$data['imageclass'] = $query->limit3;
		$data['reference_index'] = $query->limit4;
		$data['gridsquare'] = $query->limit5;


		if (!empty($_GET['submitted_end']) && preg_match('/^\d{4}[\d-]*$/',$_GET['submitted_end'])) {
			$data['submitted_end'] = $_GET['submitted_end'];
		} elseif (!empty($_GET['submitted_start']) && preg_match('/^\d{4}[\d-]*$/',$_GET['submitted_start'])) {
			$data['submitted_start'] = $_GET['submitted_start'];
		} elseif (!empty($query->limit6)) {
			$dates = explode('^',$query->limit6);
			if ($dates[0])
				$data['submitted_start'] = $dates[0];
			if ($dates[1])
				$data['submitted_end'] = $dates[1];
		}
		if (!empty($_GET['taken_end']) && preg_match('/^\d{4}[\d-]*$/',$_GET['taken_end'])) {
			$data['taken_end'] = $_GET['taken_end'];
		} elseif (!empty($_GET['taken_start']) && preg_match('/^\d{4}[\d-]*$/',$_GET['taken_start'])) {
			$data['taken_start'] = $_GET['taken_start'];
		} elseif (!empty($query->limit7)) {
			$dates = explode('^',$query->limit7);
			if ($dates[0])
				$data['taken_start'] = $dates[0];
			if ($dates[1])
				$data['taken_end'] = $dates[1];
		}
		

		$data['topic_id'] = $query->limit9;

		$query->orderby = preg_replace('/^submitted/','gridimage_id',$query->orderby);

		if (strpos($query->orderby,' desc') > 0) {
			$data['orderby'] = preg_replace('/ desc$/','',$query->orderby);
			$data['reverse_order_ind'] = 'on';
		} else {
			$data['orderby'] = $query->orderby;
		}
		$data['groupby'] = $query->groupby;
		$data['breakby'] = $query->breakby;
		$data['displayclass'] = $query->displayclass;
		$data['resultsperpage'] = $query->resultsperpage;
		
		$engine->buildAdvancedQuery($data);
	} 
	
	fallBackForm($data);
	
} else if (isset($_GET['cluster2'])) {
	dieUnderHighLoad(2,'search_unavailable.tpl');
	// -------------------------------
	//  special handler to build a advanced query experimental cluster 
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');

	$data = $_GET;
	
	$data['adminoverride'] = 1;
	
	//a few we dont want to allow overriding
	$data['searchtext'] = '';
	$data['q'] = '';
	$data['location'] = '';
		
	
	
	
	if (!empty($_GET['label'])) {
		$data['description'] = "labeled [".strip_tags($_GET['label'])."]";
	
		$db=GeographDatabaseConnection(true);
		$where = "label = ".$db->Quote($_GET['label']);
	} else {
		$data['description'] = "in a cluster";
		$where = 1;
	}
	
	$data['searchq'] = "inner join gridimage_group using (gridimage_id) where $where group by gridimage_id";
		
	$data['distance'] = 1;
	$nearstring = 'in';
	if (!empty($data['gridref'])) {
		require_once('geograph/gridsquare.class.php');
		$square=new GridSquare;
		if ($square->validGridRef(preg_replace('/[^\w]/','',$data['gridref']))) {
			//todo - update this to cope with 6fig+ GRs now the engine can. 
			$grid_ok=$square->setByFullGridRef($data['gridref'],false,true);
			if ($grid_ok || $square->x && $square->y) {
				$data['description'] .= ", $nearstring grid reference ".$square->grid_reference;
				$data['x'] = $square->x;
				$data['y'] = $square->y;
			} 
		} 
		unset($data['gridref']);
	}
	
	if (empty($data['displayclass']))
		$data['displayclass'] = 'cluster2';

	$data['breakby'] = 'label+';
	
	switch ($data['orderby']) {
		case 'label': 
		case 'crc32(label)': 
		case 'score': 
		case 'score desc': 
		case 'grid_reference': break;
		default: $data['orderby'] = '';
	}
	if ($data['orderby'] == 'score desc') {
		$data['orderby'] = 'score desc,label,sort_order';
	}
	
	$engine = new SearchEngineBuilder('#');
	$engine->buildAdvancedQuery($data);

	//should never fail?? - but display form 'in case'

	//if we get this far then theres a problem...
	$smarty->assign('errormsg', $engine->errormsg);

	fallBackForm($_GET);
		
} else if (!empty($_GET['do']) || !empty($_GET['imageclass']) || !empty($_GET['u']) || !empty($_GET['gridsquare'])) {
	dieUnderHighLoad(2,'search_unavailable.tpl');
	// -------------------------------
	//  special handler to build a advanced query from the link in stats or profile.
	// -------------------------------
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');


	if (!empty($_GET['u']))
		$_GET['user_id'] = $_GET['u'];

	$_GET['adminoverride'] = 0; //prevent overriding it

	if (!empty($_GET['searchq']) && is_array($_GET['searchq'])) {
		$_GET['searchq'] = implode(' ',$_GET['searchq']);
	}
	if (!empty($_GET['searchtext']) && is_array($_GET['searchtext'])) {
		$_GET['searchtext'] = implode(' ',$_GET['searchtext']);
	}
	if (!empty($_GET['gridsquare']) && isset($_GET['eastings']) && isset($_GET['centin'])) {
		$_GET['gridref'] = sprintf("%s%02d%1d%02d%1d",$_GET['gridsquare'], $_GET['eastings'], $_GET['centie'], $_GET['northings'],$_GET['centin']);
		unset($_GET['gridsquare']);
	}

	if (!empty($_GET['submit']) && $_GET['submit'] == 'Browser') {
		$bits = array('');
		if (!empty($_GET['q'])) {
			$bits[] = "q=".urlencode($_GET['q']);
		}
		if (!empty($_GET['location'])) {
			if ($_GET['distance'] === '1') {//TODO check really is a 4fig GR!
				$bits[] = "grid_reference+%22".urlencode($_GET['location'])."%22";
			} else {
				#http://www.geograph.org.uk/browser/#!/loc=TQ5050/dist=2000
				$bits[] = "loc=".urlencode($_GET['location']);
				if (!empty($_GET['distance']))
					$bits[] = "dist=".($_GET['distance']*1000);
			}
		}
		$url = "/browser/#!".implode('/',$bits);
		header("Location: $url");
		exit;
	}


	$engine = new SearchEngineBuilder('#');
 	$engine->buildAdvancedQuery($_GET);

	//should never fail?? - but display form 'in case'

	//if we get this far then theres a problem...
	$smarty->assign('errormsg', $engine->errormsg);

	fallBackForm($_GET);

} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	dieUnderHighLoad(2,'search_unavailable.tpl');
	// -------------------------------
	//  Build advacned query
	// -------------------------------

	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');


	if (!empty($_POST['refine'])) {
		//we could use the selected item but then have to check for numberic placenames
		if (!empty($_POST['location'])) {
			$_POST['placename'] = $_POST['location'];
		} else {
			$_POST['placename'] = $_POST['old-placename'];
		}
		
		$_POST['searchtext'] = $_POST['q'];
	} else {
		if (!empty($_POST['first'])) {
			$_POST['searchtext'] .= " ftf:1";
			unset($_POST['first']);
		}
	
	
		$_POST['adminoverride'] = 0; //prevent overriding it
		$engine = new SearchEngineBuilder('#');
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
		
		exit;
	} else {
		fallBackForm($_POST);
	}
} elseif ((!empty($_GET['q']) && $_GET['q'] != '(anything)') || !empty($_GET['text']) || (!empty($_GET['location']) && $_GET['location'] != '(anywhere)')) {
	dieUnderHighLoad(2,'search_unavailable.tpl');

	// -------------------------------
	//  Build a query from simple text
	// -------------------------------
	foreach ($_GET as $key => $value) {
		$_GET[$key] = preg_replace('/\{\w+:\w+\?\}/','',$value);
	}

	if (!empty($_GET['q']) && is_array($_GET['q'])) {
		$_GET['q'] = implode(' ',$_GET['q']);
	}
	if (!empty($_GET['q']) && $_GET['q'] == '(anything)') {
		$_GET['q'] = '';
	}
	if (!empty($_GET['lat']) && !empty($_GET['lon'])) {
		$_GET['location'] = $_GET['lat'].','.$_GET['lon'];
	}
	if (!empty($_GET['text'])) {
		if (is_array($_GET['text'])) {
			$_GET['text'] = implode(' ',$_GET['text']);
		}
		$q=trim($_GET['text']).' near (anywhere)';
	} elseif (!empty($_GET['location'])) {
		if (!empty($_GET['q'])) {
			$q=trim($_GET['q']).' near '.trim($_GET['location']);
		} else {
			$q='near '.trim($_GET['location']);
		}
	} elseif (!empty($_GET['BBOX'])) {
		$q=trim($_GET['q']).' near (anywhere)';
	} else {
		$q=trim($_GET['q']);
	}

	$q = preg_replace('/\b(\w{1,2}\s?\d{2,5}\s?\d{2,5}) E: \d{2,7}\.?\d* N: \d{2,7}\.?\d*\b/','$1',$q);

	if (!isset($_GET['location']) && !empty($CONF['metacarta_auth']) && strpos($q,'near ') === FALSE && substr_count($q,' ') >= 1 && !preg_match("/\b([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9]?)([A-Z]{0,2})\b/i",$q)) {
		$urlHandle = connectToURL('ondemand.metacarta.com',80,"/webservices/QueryParser/JSON/basic?version=1.0.0&bbox=-14.1707,48.9235,6.9506,61.7519&query=".rawurlencode($q),$CONF['metacarta_auth'],6);
		if ($urlHandle) {
			$r = '';
			while ($urlHandle && !feof($urlHandle) && ($s = fgets($urlHandle)) !== false) {
				$r .= $s;
			}
			fclose($urlHandle);
			#$r = '{"Styles": {"loc": {"DefaultSymbol": {"URL": "http://developers.metacarta.com/img/symbols/LocationMarker.png", "Width": 30, "Height": 30}}}, "Warnings": [], "MinConfidence": 0.0, "Locations": [{"Confidence": 0.451807, "Name": "Skellingthorpe, United Kingdom", "Style": "loc", "Centroid": {"Latitude": 53.2333, "X": -0.616666, "Y": 53.2333, "Longitude": -0.616666}, "RemainingQuery": "mitchel close", "Path": ["Skellingthorpe", "United Kingdom"], "ViewBox": {"MaxX": -0.589905177287, "MaxY": 53.2600950873, "MaxLongitude": -0.589905177287, "MinY": 53.2065720927, "MinLatitude": 53.2065720927, "MinX": -0.643428171913, "MaxLatitude": 53.2600950873, "MinLongitude": -0.643428171913}}], "SRS": "epsg:4326", "SystemVersion": "MetaCarta GTS v3.7.0, JSON Query Parser API v1.0.0", "BBox": {"MaxX": 180.0, "MaxY": 90.0, "MaxLongitude": 180.0, "MinY": -90.0, "MinLongitude": -180.0, "MinX": -180.0, "MaxLatitude": 90.0, "MinLatitude": -90.0}, "Query": "mitchel close Skellingthorpe", "ResultsCreationTime": "Fri Mar 02 13:40:19 2007 UTC"}';

			if (preg_match('/"RemainingQuery": "(.*?)"/',$r,$m)) {
				$q = $m[1];
			}
			if (preg_match('/"Path": \["(.*?)"/',$r,$m)) {
				$q .= ' near '.$m[1];
			}
		}
	}

	//remember the query in the session
	$_SESSION['searchq']=$q;

	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/searchenginebuilder.class.php');

 	$engine = new SearchEngineBuilder('#');
        if (!empty($_GET['submit']) && $_GET['submit'] == 'Browser') {
                $bits = array('');
                if (!empty($_GET['q'])) {
                        $bits[] = "q=".urlencode($_GET['q']);
                }
                if (!empty($_GET['location'])) {
                        if ($_GET['distance'] === '1') {//TODO check really is a 4fig GR!
                                $bits[] = "grid_reference+%22".urlencode($_GET['location'])."%22";
                        } else {
                                ##http://www.geograph.org.uk/browser/#!/loc=TQ5050/dist=2000
                                $bits[] = "loc=".urlencode($_GET['location']);
                                if (!empty($_GET['distance']))
                                        $bits[] = "dist=".($_GET['distance']*1000);
                        }
                }
                $url = "/browser/#!".implode('/',$bits);
                header("Location: $url");
                exit;
        }


 	if ((isset($_GET['form']) && $_GET['form'] == 'simple') || (isset($_GET['BBOX']) && empty($_GET['BBOX'])) ) {
 		$autoredirect = 'simple';

		if (preg_match('/^[\w\.-]+@[\w+\.-]+\.\w+$/',$q) && !$USER->registered) {
			header("Location: /login.php?email=$q");
			exit;
		}

		if ( !preg_match('/^\w{1,2}(\d{4}| \d{2} ?\d{2})$/',$q) ) {
			if ($USER->registered) {
				//if registered users an option
				$option = $USER->getPreference('search.engine','of.php',true);
				if ($option && $option != 'default') {
					customNoCacheHeader();
					header("HTTP/1.0 307 Temporary Redirect");
                                	header("Status: 307 Temporary Redirect");

					$q2 = urlencode($q);
					switch($option) {
						case 'browser': $bits = preg_split('/(?<![":])\s*near\s+/',$q);
							if (count($bits) == 2) {
								if ($bits[1] == '(anywhere)') $bits[1] = '';
								header("Location: /browser/#!/q=".urlencode($bits[0])."/loc=".urlencode($bits[1]));
							} else {
								header("Location: /browser/#!/q=$q2");
							} break;
						case 'of.php': header("Location: /of/".str_replace('%2F','/',str_replace('%3A',':',urlencode($q)))); break;
						case 'multi2.php': header("Location: /finder/multi2.php?q=$q2"); break;
						case 'multi.php': header("Location: /finder/multi.php?q=$q2"); break;
						case 'full-text.php': header("Location: /full-text.php?q=$q2"); break;
						case 'bytag.php': header("Location: /finder/bytag.php?q=$q2"); break;
						case 'sqim.php': header("Location: /finder/sqim.php?q=$q2"); break;
						case 'images.google.co.uk': header("Location: http://images.google.co.uk/images?q=$q2&as_q=site:geograph.org.uk+OR+site:geograph.ie&btnG=Search"); break;
						case 'www.google.co.uk': header("Location: http://www.google.co.uk/search?q=$q2&as_q=site:geograph.org.uk+OR+site:geograph.ie&btnG=Search"); break;
						case 'www.google.co.uk/tbs': header("Location: http://www.google.co.uk/search?q=$q2&as_q=site:geograph.org.uk+OR+site:geograph.ie&btnG=Search&tbs=img:1"); break;
					}
					exit;
				}
			} else {
				//redirect everyone else
				customNoCacheHeader();
				header("HTTP/1.0 307 Temporary Redirect");
				header("Location: /of/".($url = str_replace('%2F','/',str_replace('%3A',':',urlencode($q)))));
				print "<a href=\"/of/$url\">continue...</a>";
				exit;
			}
		}

 	} elseif ($_SERVER['SCRIPT_NAME'] == '/results/') {
 		$autoredirect = false;
 	} else {
 		$autoredirect = 'auto';
 	}

        $distance = $CONF['default_search_distance'];
        if (!empty($_GET['distance']) && $_GET['distance'] < $CONF['default_search_distance'])
                $distance = floatval($_GET['distance']);

 	$i = $engine->buildSimpleQuery($q,$distance,$autoredirect,(!empty($_GET['user_id']))?intval($_GET['user_id']):0);

 	if ($_SERVER['SCRIPT_NAME'] == '/results/' && !empty($i)) {
 		unset($_GET['form']);

 		$i = intval($i);

 		//falls though to display search results below...

 	} else {

		//query failed!
		if (isset($engine->criteria) && $engine->criteria->is_multiple) {
			if (empty($_GET['distance']))
				$_GET['distance'] = $CONF['default_search_distance'];

			//todo these shouldnt be hardcoded as there other possiblities for suggestions
			$smarty->assign('multipletitle', "Placename");
			$smarty->assign('multipleon', "placename");

			if (!empty($engine->criteria->realname)) {
				$smarty->assign('pos_realname', $engine->criteria->realname);
				$smarty->assign('pos_user_id', $engine->criteria->user_id);
				if (!empty($engine->criteria->nickname)) 
					$smarty->assign('pos_nickname', $engine->criteria->nickname);
			} else {
				$usercriteria = new SearchCriteria_All();
				$usercriteria->setByUsername($q);
				if (!empty($usercriteria->realname)) {
					//could also be a username
					$smarty->assign('pos_realname', $usercriteria->realname);
					$smarty->assign('pos_user_id', $usercriteria->user_id);
					if (!empty($usercriteria->nickname)) 
						$smarty->assign('pos_nickname', $usercriteria->nickname);
				}
			}

if (!empty($engine->criteria->searchq)) {
	if (empty($db))
		$db=GeographDatabaseConnection(true);
	if ($tag = $db->getRow("SELECT * FROM tag_public WHERE tag = ".$db->Quote($engine->criteria->searchq)." LIMIT 1")) {
		$smarty->assign('pos_tag', $tag);
	}
}

			$smarty->assign_by_ref('criteria', $engine->criteria);
			$smarty->assign_by_ref('post', $_GET);
			$smarty->assign_by_ref('references',$CONF['references']);
			$smarty->assign('searchdesc', $engine->searchdesc);
			if (isset($_GET['form']) && $_GET['form'] == 'simple') {
				$smarty->assign('form','simple');
			}
			$smarty->display('search_multiple.tpl');
		} else {

			$smarty->assign('errormsg', $engine->errormsg);
			list($q,$loc) = explode(' near ',$q,2);
			$smarty->assign('searchlocation', $loc);
			$smarty->assign('searchq', $q);


			require_once('geograph/imagelist.class.php');
			require_once('geograph/gridimage.class.php');
			require_once('geograph/gridsquare.class.php');
			//lets find some recent photos
			new RecentImageList($smarty);
			$smarty->display('search.tpl');
		}
		
		exit;
	}
} 

if (isset($_GET['form']) && ($_GET['form'] == 'advanced' || $_GET['form'] == 'text' || $_GET['form'] == 'first' || $_GET['form'] == 'check' || $_GET['form'] == 'cluster2')) {
	dieUnderHighLoad(1.5,'search_unavailable.tpl');
	// -------------------------------
	//  Advanced Form
	// -------------------------------

	$db=GeographDatabaseConnection(true);

		$smarty->assign('submitted_start', "0-0-0");
		$smarty->assign('submitted_end', "0-0-0");

		$smarty->assign('taken_start', "0-0-0");
		$smarty->assign('taken_end', "0-0-0");
		$smarty->assign('taken', "0-0-0");

	if (is_int($i)) {
		require_once('geograph/searchcriteria.class.php');
		$engine = new SearchEngine($i);

		if (empty($engine->criteria)) {
			dieUnderHighLoad(0,'search_unavailable.tpl');
			die("Invalid Search Parameter");
		}
		
		if ($_GET['form'] == 'advanced' && empty($_GET['legacy'])) {
			$engine->criteria->getSQLParts();
					
			if (!empty($CONF['sphinx_host']) && 
				isset($engine->criteria->sphinx) && 
				(strlen($engine->criteria->sphinx['query']) || !empty($engine->criteria->sphinx['d']) || !empty($engine->criteria->sphinx['filters']))
				&& $engine->criteria->sphinx['impossible'] == 0) {
				$_GET['form'] = 'text';
				$smarty->assign('fullText', 1);
			}
		}
		
		$query = $engine->criteria;
		$smarty->assign('searchclass', $query->searchclass);
		switch ($query->searchclass) {
			case "Special":
				die("ERROR:Attempt to edit a locked search");
				break;
			case "Postcode":
				$smarty->assign('postcode', $query->searchq);
				$smarty->assign('elementused', 'postcode');
				break;
			case "Text":
				$smarty->assign('searchtext', $query->searchq);
				break;
			case "GridRef":
				$smarty->assign('gridref', $query->searchq);
				$smarty->assign('elementused', 'gridref');
				break;
			case "County":
				$smarty->assign('county_id', $query->searchq);
				$smarty->assign('elementused', 'county_id');
				break;
			case "Placename":
				$smarty->assign('placename', $query->searchq);
				$smarty->assign('elementused', 'placename');
				break;
			case "All":
				$smarty->assign('all_checked', 'checked="checked"');
				if ($_GET['form'] != 'text') {
					$smarty->assign('elementused', 'all_ind');
				}
				break;
		}
		
		if (!empty($query->searchtext)) {
			if ($_GET['form'] == 'text' && preg_match('/ftf:1$/',$query->searchtext)) {
				$query->searchtext= preg_replace('/\s*ftf:1$/','',$query->searchtext);
				$smarty->assign('first', 1);
			}
			$smarty->assign('searchtext', $query->searchtext);
		}
				
		if (!empty($query->limit1)) {
			$user_id = $query->limit1;
			if (strpos($user_id,'!') === 0) {
				$user_id = preg_replace('/^!/','',$user_id);
				$smarty->assign('user_invert_checked', 'checked="checked"');
			}
			$smarty->assign('user_id', $user_id);

			$profile=new GeographUser($user_id);
			$smarty->assign('user_name', "$user_id:{$profile->realname}");
		}
		$smarty->assign('moderation_status', $query->limit2);
		$smarty->assign('imageclass', $query->limit3);
		$smarty->assign('reference_index', $query->limit4);
		$smarty->assign('gridsquare', $query->limit5);
		if (empty($query->limit4)) {
			$smarty->assign('reference_index','0');
		}
		

		if (!empty($query->limit6)) {
			$dates = explode('^',$query->limit6);
			if ($dates[0])
				$smarty->assign('submitted_start', $dates[0]);
			if ($dates[1])
				$smarty->assign('submitted_end', $dates[1]);
		}
		if (!empty($query->limit7)) {
			$dates = explode('^',$query->limit7);
			if ($dates[0]) {
				$smarty->assign('taken_start', $dates[0]);
				$smarty->assign('taken', $dates[0]);
			}
			if ($dates[1])
				$smarty->assign('taken_end', $dates[1]);
		}
		$smarty->assign('distance', $query->limit8);

		$smarty->assign('topic_id', $query->limit9);

		$query->orderby = preg_replace('/^submitted/','gridimage_id',$query->orderby);

		if (strpos($query->orderby,' desc') > 0) {
			$smarty->assign('orderby', preg_replace('/ desc$/','',$query->orderby));
			$smarty->assign('reverse_order_checked', 'checked="checked"');
		} else {
			$smarty->assign('orderby', $query->orderby);
		}
		$smarty->assign('breakby', $query->breakby);
		$smarty->assign('groupby', $query->groupby);
		$smarty->assign('displayclass', $query->displayclass);
		$smarty->assign('resultsperpage', $query->resultsperpage);
		$smarty->assign('searchdesc', $query->searchdesc);
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

		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}
		if ($pg > 1000)
			$pg = 1000;

	$engine = new SearchEngine($i);

	if (empty($engine->criteria)) {
		dieUnderHighLoad(0,'search_unavailable.tpl');
		die("Invalid Search Parameter");
	}
	
	if (isset($_GET['legacy']) 
		&& (!empty($engine->criteria->searchq) || !empty($engine->criteria->searchtext) || !empty($engine->criteria->x) )
		&& empty($engine->criteria->limit6) && empty($engine->criteria->limit1) ) {
		header("HTTP/1.1 503 Service Unavailable");
		$smarty->assign('searchq',stripslashes($_GET['q']));
		$smarty->display('function_disabled.tpl');
		exit;
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
	} elseif (isset($_GET['temp_displayclass']) && preg_match('/^\w+$/',$_GET['temp_displayclass'])) {
		$display = $_GET['temp_displayclass'];
	}
	if (empty($display))
		$display = 'full';
	$engine->display = $display;
	$template = 'search_results_'.$display.'.tpl';
	
	$ab=floor($i%10000);
	$cacheid="search|$ab|$i.$pg";
	if (!empty($_GET['count'])) {
		$engine->countOnly = 1;
		$cacheid.=".";
	}

	//what style should we use?
	$style = $USER->getStyle();
	$smarty->assign('maincontentclass', 'content_photo'.$style);

	if (!empty($_GET['legacy'])) {
		$cacheid.="X";
		$smarty->assign('legacy', 1);
	}

	if (!empty($_GET['t'])) {
		$token=new Token;
		if ($token->parse($_GET['t']) && $token->getValue("i") == $i)
			$smarty->clear_cache($template, $cacheid);
	}

	$smarty->register_function("votestars", "smarty_function_votestars");
	if (!$smarty->is_cached($template, $cacheid)) {
		dieUnderHighLoad(3,'search_unavailable.tpl');

		$smarty->assign_by_ref('google_maps_api_key', $CONF['google_maps_api_key']);

		$smarty->register_function("searchbreak", "smarty_function_searchbreak");

		if ($display == 'reveal' || $display == 'story') {
			$engine->noCache = true;
			$engine->criteria->limit4 = 1; //only works in GB
		}

		$src = 'data-src';
		if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
		        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
		        $src = 'src';//revert back to standard non lazy loading
		}
		$smarty->assign('src', $src);

		$smarty->assign('querytime', $engine->Execute($pg));

		$page_title = "Photos".$engine->criteria->searchdesc;
		if ($engine->islimited && $engine->resultCount ) {
			$pname = ($engine->resultCount == 1)?'Photo':'Photos';
			$page_title = preg_replace("/Photos, (matching|containing) (['\[])/",number_format($engine->resultCount).' '.$pname.' of $2',$page_title);
			$page_title = str_replace("Photos, by ",number_format($engine->resultCount)." $pname by ",$page_title);
			$page_title = str_replace("Photos, within ",number_format($engine->resultCount)." $pname within ",$page_title);
		} elseif (!$engine->islimited) {
			$page_title = "All Photos".$engine->criteria->searchdesc;
		}
		$smarty->assign('page_title', $page_title);

		$smarty->assign('i', $i);
		$smarty->assign('currentPage', $pg);
		$smarty->assign_by_ref('engine', $engine);

		if (!$engine->countOnly && $pg == 1 ) {
			if ($engine->criteria->searchclass == 'GridRef' && $engine->criteria->issubsetlimited == false
					&& preg_match('/^\w{1,2}\d{4}$/',$engine->criteria->searchq)
					&& ( $engine->criteria->orderby == 'dist_sqd' || $engine->criteria->orderby == '' )
					&& (!$engine->resultCount || stripos($engine->criteria->searchdesc,$engine->results[0]->grid_reference) === FALSE)) {
				$smarty->assign('nofirstmatch', true);
			}
			if ($engine->criteria->x && $engine->criteria->y) {
				$smarty->assign('singlesquares', $engine->criteria->countSingleSquares($CONF['search_prompt_radius']));
				$smarty->assign('singlesquare_radius', $CONF['search_prompt_radius']);
			}
		}
		
		if ($engine->fullText 
			&& $engine->numberOfPages == $engine->currentPage 
			&& $engine->resultCount > $engine->maxResults
			&& count($engine->results)
			&& preg_match('/(gridimage_id|submitted|imagetaken)( desc|)/',$engine->criteria->orderby,$m)
			) {
			
			$name = ($m[1] == 'imagetaken')?'imagetaken':'submitted';
			
			$value= substr((string)$engine->results[0]->{$name},0,10);
			
			$name = ($m[1] == 'imagetaken')?'taken':'submitted';
			
			if (!empty($m[2])) { //desending
				$name .= "_end";
			} else {
				$name .= "_start";
			}
			
			$engine->nextLink = "/search.php?i=$i&redo=1&$name=$value";
		}
		
		if ($engine->resultCount) {
		if ($display == 'reveal') {
			foreach ($engine->results as $idx => $image) {
			
				if ($engine->results[$idx]->gridsquare_id) {
					$engine->results[$idx]->grid_square=new GridSquare;
					$engine->results[$idx]->grid_square->loadFromId($engine->results[$idx]->gridsquare_id);
					$engine->results[$idx]->grid_reference=$engine->results[$idx]->grid_square->grid_reference;
					if ($engine->results[$idx]->nateastings) {
						$engine->results[$idx]->natspecified = 1;
						$engine->results[$idx]->grid_square->natspecified = 1;
						$engine->results[$idx]->grid_square->natgrlen=$engine->results[$idx]->natgrlen;
						$engine->results[$idx]->grid_square->nateastings=$engine->results[$idx]->nateastings;
						$engine->results[$idx]->grid_square->natnorthings=$engine->results[$idx]->natnorthings;
					}
				
					//lets add an rastermap too
					$engine->results[$idx]->rastermap = new RasterMap($engine->results[$idx]->grid_square,false);

					if (!empty($engine->results[$idx]->viewpoint_northings)) {
						$engine->results[$idx]->rastermap->addViewpoint($engine->results[$idx]->viewpoint_eastings,$engine->results[$idx]->viewpoint_northings,$engine->results[$idx]->viewpoint_grlen,$engine->results[$idx]->view_direction);
					} elseif (isset($engine->results[$idx]->view_direction) && strlen($engine->results[$idx]->view_direction) && $engine->results[$idx]->view_direction != -1) {
						$engine->results[$idx]->rastermap->addViewDirection($engine->results[$idx]->view_direction);
					}
				}
			}
		} elseif ($display == 'excerpt' || $display == 'bytag' || $display == 'landing' || $display == 'human') {
			
			if (empty($engine->criteria->searchtext) && preg_match('/labeled \[(.*)\],/',$engine->criteria->searchdesc,$m)) {
				$sphinx = new sphinxwrapper($m[1]);
			} else {
				$sphinx = new sphinxwrapper($engine->criteria->searchtext);
			}
			
			$docs = array();
			foreach ($engine->results as $idx => $image) {
				$docs[$idx] = strip_tags($image->comment?$image->comment:$image->title).(empty($image->imageclass)?'':(" Category: ".strip_tags($image->imageclass)));
			}
			$reply = $sphinx->BuildExcerpts($docs, 'gi_stemmed', $sphinx->q, array("query_mode"=>(strpos($sphinx->q,'~')===FALSE)?1:0,"limit"=>350));
			
			foreach ($engine->results as $idx => $image) {
				$engine->results[$idx]->excerpt = $reply[$idx];
			}
		} elseif (strpos($display,'slide') === 0 || $display == 'more') { 
			$buckets = array(
				'Closeup',
				'CloseCrop', //was telephoto
				'Wideangle',
				'Landscape',
				'Arty',
				'Informative',
				'Aerial',
				'Indoor',
				'Subterranean', 
				'Gone',
				'Temporary',
				'People',
				'Life',
				'Transport');
			$smarty->assign_by_ref('buckets',$buckets);
		} 
		
		if ($display == 'cluster') {
			foreach ($engine->results as $idx => $image) {
				$engine->results[$idx]->simple_title = preg_replace('/\s*\(?\s*\d+\s*\)?\s*$/','',$engine->results[$idx]->title);
				$found = -1;
				for($ic = 0;$ic< $idx;$ic++) {
					if ($engine->results[$ic] 
						&& $engine->results[$ic]->simple_title == $engine->results[$idx]->simple_title
						&& $engine->results[$ic]->user_id == $engine->results[$idx]->user_id
						&& $engine->results[$ic]->grid_reference == $engine->results[$idx]->grid_reference
						) {
						$found = $ic;
						break;
					}
				}
				if ($found > -1) {
					if (!isset($engine->results[$found]->cluster)) 
						$engine->results[$found]->cluster = array();

					$image->simple_title = $engine->results[$idx]->simple_title;
					array_push($engine->results[$found]->cluster,$image);
					unset($engine->results[$idx]);
				}
			}
		} elseif ($display == 'cluster2') {
			$breakby = preg_replace('/_(year|month|decade)$/','',$engine->criteria->breakby);
			if (preg_match('/^(\w+)\+$/i',$breakby,$m) ) {
				$breakby  = $m[1];
			}
			foreach ($engine->results as $idx => $image) {
				$found = -1;
				for($ic = 0;$ic< $idx;$ic++) {
					if ($engine->results[$ic] 
						&& $engine->results[$ic]->{$breakby} == $engine->results[$idx]->{$breakby}
						) {
						$found = $ic;
						break;
					}
				}
				if ($found > -1) {
					if (!isset($engine->results[$found]->cluster)) 
						$engine->results[$found]->cluster = array();
					
					$engine->results[$found]->simple_title = $engine->results[$idx]->{$breakby};
					array_push($engine->results[$found]->cluster,$image);
					unset($engine->results[$idx]);
				}
			}
		} elseif ($display == 'map' || $display == 'gmap' || $display == 'gmap_embed' || $display == 'landing') {
			$markers = array();
			$conv = new Conversions();
			
			if ($engine->criteria->x && $engine->criteria->y) {
			
				$onekm = (floor($engine->criteria->x) == $engine->criteria->x && floor($engine->criteria->y) == $engine->criteria->y)?1:0;
				if ($onekm) {
					list($lat,$long) = $conv->internal_to_wgs84($engine->criteria->x,$engine->criteria->y);
				} else {
					list ($e,$n,$reference_index) = $conv->internal_to_national($engine->criteria->x,$engine->criteria->y,0,0);
					list($lat,$long) = $conv->national_to_wgs84($e,$n,$reference_index);
				}
				$markers[] = array('Center Point',$lat,$long);
			}
			if (preg_match_all('/\b([a-zA-Z]{1,2} ?\d{1,5}[ \.]?\d{1,5})\b/',$engine->criteria->searchdesc,$m)) {
				$m = array_unique($m[1]);
				foreach ($m as $gr) {
					$sq = new GridSquare();
					if ($sq->setByFullGridRef($gr,false,true)) {
						list($lat,$long) = $conv->gridsquare_to_wgs84($sq);
						$markers[] = array($gr,$lat,$long);
					}
				}
			}
			$smarty->assign_by_ref('markers',$markers);
		}
		
		if ($display == 'landing' && !empty($engine->criteria->searchtext)) {
			$sphinx = new sphinxwrapper();
			$sphinx->pageSize = $pgsize = 5;
			$pg = 1;

			$sphinx->prepareQuery($engine->criteria->searchtext." @source -themed -portal");
			
	                $cl = $sphinx->_getClient();
        	        $cl->SetFieldWeights(array('title'=>20));

			$ids = $sphinx->returnIds($pg,'content_stemmed');

			if (!empty($ids) && count($ids) > 0) {
				if (empty($db)) {
					$db = GeographDatabaseConnection(true);
				}
				
				$id_list = implode(',',$ids);
				
				$related = $db->getAll("
					SELECT c.url,c.title,'Collection' AS `type`,realname,user_id,images
					FROM content c
					LEFT JOIN user u USING (user_id)
					WHERE c.content_id IN($id_list)
					ORDER BY FIELD(c.content_id,$id_list)"); 

				$smarty->assign_by_ref('related',$related);
			} 
		}
		
		}
	}

	if ($engine->criteria->user_id == $USER->user_id) {
		if (!$db || $db->readonly) {
			$db=GeographDatabaseConnection(false);
		}
		$db->query("UPDATE queries SET use_timestamp = null WHERE id = $i");
		if (!$db->Affected_Rows()) {
			$db->query("UPDATE queries_archive SET use_timestamp = null WHERE id = $i");
		}
	}

	if (!empty($engine->error)) {
		if (preg_match('/no field (.+) found in schema/',$engine->info,$m)) {
			$engine->error = "Error: Field {$m[1]} not found";
		} elseif (preg_match('/query is non-computable \((node )?(.* NOT operato.*)\)/',$engine->info,$m)) {
			$engine->error = "Error: Impossible Query ({$m[2]})";
		} elseif (preg_match('/offset out of bounds/',$engine->info,$m)) {
			$engine->error = "Error: Geograph Search can only access the first 1000 results of a given query";
		} elseif (preg_match('/unexpected \$end/',$engine->info,$m)) {
			$engine->error = "Error: Mismatched quotes/brackets";
		} elseif (preg_match('/unexpected (\'.*?\')/',$engine->info,$m)) {
			$engine->error = "Error: Unexpected {$m[1]}";
		}
	}

	if (!empty($_SERVER['HTTP_COOKIE']))
		customExpiresHeader(3600,false,true);
	$smarty->display($template, $cacheid);



} else {
	dieUnderHighLoad(2,'search_unavailable.tpl');
	// -------------------------------
	//  Simple Form
	// -------------------------------

	$template = 'search.tpl';
	if (!empty($_GET['new']) || !empty($_SESSION['new_search'])) {
		$template = 'search-new.tpl';
	}
	if (!empty($_GET['preview'])) {
		$template = 'search2.tpl';
	}

	if (is_int($i)) {
		require_once('geograph/searchcriteria.class.php');
		$engine = new SearchEngine($i);
		if (empty($engine->criteria)) {
			dieUnderHighLoad(0,'search_unavailable.tpl');
			die("Invalid Search Parameter");
		}
		$query = $engine->criteria;
		if ($query->searchclass != 'Special') {
			$smarty->assign('searchq', $query->searchq);
			list($q,$loc) = preg_split('/\bnear(\b|$)/',$query->searchq,2);
			$smarty->assign('searchlocation', $loc);
			$smarty->assign('searchtext', $q);
		}
	} else if (isset($_SESSION['searchq'])) {
		list($q,$loc) = preg_split('/\s*near\s+/',$_SESSION['searchq'],2);
		$smarty->assign('searchlocation', $loc);
		$smarty->assign('searchtext', $q);
	}
	if (!$smarty->is_cached($template)) {
		if (!isset($db)) {
			$db = GeographDatabaseConnection(true);
		}
		//list of a few tags
		$rnd = rand(1,1000)/1000;
		$arr = $db->getAssoc("SELECT if(prefix!='',concat(prefix,':',tag),tag) as tag,`count` FROM tag INNER JOIN tag_stat USING (tag_id) WHERE prefix != 'top' AND rnd > $rnd and count >= 50 ORDER BY rnd LIMIT 5");
		$smarty->assign_by_ref('taglist',$arr);

		$arr2 = $db->GetAll("select id,searchdesc
			from queries_featured
				inner join queries using (id)
			where approved = 1
			order by rand() limit 5");
		$smarty->assign_by_ref('featured',$arr2);
	}
	if ($USER->registered) {
		if (!$db) {
			$db = GeographDatabaseConnection(true);
		}
		if (isset($_GET['all'])) {
			$flimit = "";
			$nlimit = "limit 10000";
			$smarty->assign('all',1);
		} elseif (isset($_GET['more'])) {
			$flimit = "";
			$nlimit = "limit 40";
			$smarty->assign('more',1);
		} else {
			$flimit = "limit 12";
			$nlimit = "limit 12";
		}
		#group by searchdesc,searchq,displayclass,resultsperpage
		$recentsearchs = $db->getAssoc("
			(select queries.id,favorite,searchdesc,`count`,use_timestamp,searchclass ,searchq,displayclass,resultsperpage from queries
			left join queries_count using (id)
			where user_id = {$USER->user_id} and favorite = 'N' and searchuse = 'search'
			order by use_timestamp desc,id desc	$nlimit)
		UNION ALL
			(select queries.id,favorite,searchdesc,`count`,use_timestamp,searchclass ,searchq,displayclass,resultsperpage from queries
			left join queries_count using (id)
			where user_id = {$USER->user_id} and favorite = 'Y' and searchuse = 'search'
			order by use_timestamp desc,id desc	$flimit)
		order by use_timestamp desc,id desc	");

		$a = array();
		foreach ($recentsearchs as $i => $row) {
			if ($a["{$row['searchdesc']},{$row['searchq']},{$row['displayclass']},{$row['resultsperpage']}"]) {
				unset($recentsearchs[$i]);
			} else {
				$a["{$row['searchdesc']},{$row['searchq']},{$row['displayclass']},{$row['resultsperpage']}"] = 1;
				if ($row['searchq'] == "inner join gridimage_query using (gridimage_id) where query_id = $i") {
					$recentsearchs[$i]['edit'] = 1;
				}
			}
		}
		unset($a);

		$smarty->assign_by_ref('recentsearchs',$recentsearchs);
	}

	require_once('geograph/imagelist.class.php');
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	if ($template == 'search.tpl' || $template == 'search2.tpl') {
		//lets find some recent photos
		new RecentImageList($smarty);
	}

	//if (!empty($_SERVER['HTTP_COOKIE']))
        //        customExpiresHeader(360,false,true);
	$smarty->display($template);
}


	function fallBackForm(&$data) {
		global $smarty,$db;
		$smarty->assign($data);
		$_POST = $data;
		
		foreach (array('postcode','gridref','county_id','placename','all_checked') as $key) {
			if (isset($_POST[$key]))
				$smarty->assign('elementused', $key);
		}
		
		$smarty->reassignPostedDate("submitted_start");
		$smarty->reassignPostedDate("submitted_end");
		$smarty->reassignPostedDate("taken_start");
		$smarty->reassignPostedDate("taken_end");

		if (!empty($_POST['searchtext'])) {
			if ($_GET['form'] == 'text' && preg_match('/ftf:1$/',$_POST['searchtext'])) {
				$_POST['searchtext']= preg_replace('/\s*ftf:1$/','',$_POST['searchtext']);
				$smarty->assign('first', 1);
			}
			$smarty->assign('searchtext', $_POST['searchtext']);
		}

		if (!empty($_POST['all_ind']))
			$smarty->assign('all_checked', 'checked="checked"');
		if (!empty($_POST['user_invert_ind']))
			$smarty->assign('user_invert_checked', 'checked="checked"');
		if (!empty($_POST['reverse_order_ind']))
			$smarty->assign('reverse_order_checked', 'checked="checked"');
		if (empty($db)) {
			$db = GeographDatabaseConnection(true);
		}
		if (empty($_POST['reference_index'])) {
			$smarty->assign('reference_index','0');
		}
		advanced_form($smarty,$db);
		
		exit;
	}
	
	function advanced_form(&$smarty,&$db,$is_cachable = false) {
		global $CONF,$imagestatuses,$sortorders,$breakdowns,$groupbys,$USER;

		if ($_GET['form'] == 'first') {
			$template = 'search_first.tpl';
			
			//as the feature is now disabled, the page is just a static html page :)
			$smarty->display($template);
			exit;

		} elseif ($_GET['form'] == 'cluster2') {
			$template = 'search_cluster2.tpl';
		} elseif ($_GET['form'] == 'check') {
			$USER->mustHavePerm("basic");

			$template = 'search_check.tpl';
			if (!$_GET['i']) {
				$smarty->assign('user_name', "{$USER->user_id}:{$USER->realname}");
				$smarty->assign('glue', 'or');
				$smarty->assign('displayclass', 'spelling');
				$smarty->assign('seditpage_options', array('simple','small_redirect'));
			}
			$checks = array(
				'gr' => ' A. 4-figure Subject Grid Reference',
				'pg' => ' B. No Camera Grid Reference',
				'p6' => ' C. 4-figure Camera Grid Reference', #Photographer Grid Reference less than 6 figure (but because works with B it ca be a check for anything less)
				'dir' => ' D. No View Direction',
				'dat' => ' E. Incomplete Taken Date',
				'com' => ' F. No Description',
				'sho' => ' G. Description fewer than 10 words',
				'dup' => ' H. Description same as Title',
			);			
			$smarty->assign_by_ref('checks',$checks);
			
			$editpage_options = array(
				'simple' => ' Simplifed Edit Image Page',
				'small_redirect' => ' Simplified Success Page',
			);			
			$smarty->assign_by_ref('editpage_options',$editpage_options);
			
			$glues = array(
				'or' => 'Any',
				'and' => 'All',
			);			
			$smarty->assign_by_ref('glues',$glues);
				
			global $displayclasses;
			unset($displayclasses['full']);
			unset($displayclasses['thumbs']);
			unset($displayclasses['slide']);
			unset($displayclasses['black']);
			unset($displayclasses['text']);
			$displayclasses['searchtext'] = "Text-based Sidebar (IE Only)";
			
		} elseif ($_GET['form'] == 'text') {
			$template = 'search_text.tpl';
			
			global $sortorders;
			
			unset($sortorders['imageclass']);
			unset($sortorders['realname']);
			unset($sortorders['title']);
			unset($sortorders['grid_reference']);
			$sortorders['random'] = "Random";
			
			unset($breakdowns['realname']);
			unset($breakdowns['title']);
			
		} elseif (isset($_GET['Special'])) {
			$USER->mustHavePerm("admin");
			$template = 'search_admin_advanced.tpl';
		} else {
			$template = 'search_advanced.tpl';
		}
		if ($is_cachable && $smarty->caching) {
			$smarty->caching = 2; // lifetime is per cache
			$smarty->cache_lifetime = 3600*3; //3hr cache

			if (!empty($_SERVER['HTTP_COOKIE']))
				customExpiresHeader($smarty->cache_lifetime,false,true);
		} else {
			$smarty->caching = 0; // NO caching
		}
		$sizes = array(5,10,15,20,30,50);
		if (!empty($_GET['i']))
			$sizes[] = 75;
		$smarty->assign('pagesizes', $sizes);

		if (!$is_cachable || !$smarty->is_cached($template, $is_cachable)) {
			function addkm($a) {
				return str_replace('1.001','1',$a)."km";
			}
			if ($_GET['form'] == 'text' || $_GET['form'] == 'cluster2') {
				$d = array(0.1,0.3,0.5,0.7,1.001,1.5,2,2.5,3,4,5,7,8,10,20);
				$d = array(1=>'in same square')+array_combine($d,array_map('addkm',$d));
			} else {
				$d = array(1,2,3,4,5,7,8,10,20);
				$d = array_combine($d,array_map('addkm',$d));
				$d += array(-5=>'5km square',-10=>'10km square');

				$topicsraw = $db->GetAssoc("select gp.topic_id,concat(topic_title,' [',count(*),']') as title,forum_name from gridimage_post gp
					inner join geobb_topics using (topic_id)
					inner join geobb_forums using (forum_id)
					group by gp.topic_id
					having count(*) > 4
					order by geobb_topics.forum_id desc,topic_title");

				$topics=array("1"=>"Any Topic");

				$options = array();
				foreach ($topicsraw as $topic_id => $row) {
					if ($last != $row['forum_name'] && $last) {
						$topics[$last] = $options;
						$options = array();
					}
					$last = $row['forum_name'];

					$options[$topic_id] = $row['title'];
				}
				$topics[$last] = $options;

				$smarty->assign_by_ref('topiclist',$topics);

			}

			$smarty->assign_by_ref('distances',$d);

			$countylist = array();
			$recordSet = &$db->Execute("SELECT reference_index,county_id,name FROM loc_counties WHERE n > 0");
			while (!$recordSet->EOF)
			{
				$countylist[$CONF['references'][$recordSet->fields[0]]][$recordSet->fields[1]] = $recordSet->fields[2];
				$recordSet->MoveNext();
			}
			$recordSet->Close();
			$smarty->assign_by_ref('countylist', $countylist);

			require_once('geograph/gridsquare.class.php');
			$square=new GridSquare;
			$smarty->assign('prefixes', $square->getGridPrefixes());

			$smarty->assign_by_ref('references',$CONF['references']);
			$smarty->assign_by_ref('sortorders', $sortorders);
			$smarty->assign_by_ref('imagestatuses', $imagestatuses);
			$smarty->assign_by_ref('breakdowns', $breakdowns);
			$smarty->assign_by_ref('groupbys', $groupbys);
		}

		$smarty->display($template, $is_cachable);
	}

function smarty_function_votestars($params) {
	global $CONF;
	static $last;
	
	$type = $params['type'];
	$id = $params['id'];
	$names = array('','Hmm','Below average','So So','Good','Excellent');
	foreach (range(1,5) as $i) {
		print "<a href=\"javascript:void(record_vote('$type',$id,$i));\" title=\"{$names[$i]}\"><img src=\"{$CONF['STATIC_HOST']}/img/star-light.png\" width=\"14\" height=\"14\" alt=\"$i\" onmouseover=\"star_hover($id,$i,5)\" onmouseout=\"star_out($id,5)\" name=\"star$i$id\"/></a>";
	}
	if ($last != $type) {
		print " (<a href=\"/help/voting\">about</a>)";
	} 
	$last = $type;
}

function smarty_function_searchbreak($params) {
	global $engine;

	if (!$engine->criteria->breakby)
		return;

	$last = $engine->breaklast;
	$image = &$params['image'];
	$b = 0;
	switch ($engine->criteria->breakby) {
		case 'imagetaken':
			if ($last != $image->imagetaken)
				$b = $image->imagetakenString?$image->imagetakenString:getFormattedDate($image->imagetaken);
			$last = $image->imagetaken;
			break;
		case 'imagetaken_month':
			$s = substr($image->imagetaken,0,7);
			if ($last != $s)
				$b = getFormattedDate($s);
			$last = $s;
			break;
		case 'imagetaken_year':
			$s = substr($image->imagetaken,0,4);
			if ($last != $s)
				$b = getFormattedDate($s);
			$last = $s;
			break;
		case 'imagetaken_decade':
			$s = substr($image->imagetaken,0,3);
			if ($last != $s)
				$b = $s."0 s";
			$last = $s;
			break;
		case 'submitted':
			$s = substr($image->submitted,0,10);
			if ($last != $s)
				$b = getFormattedDate($image->submitted);
			$last = $s;
			break;
		case 'submitted_month':
			$s = substr($image->submitted,0,7);
			if ($last != $s)
				$b = getFormattedDate($s);
			$last = $s;
			break;
		case 'submitted_year':
			$s = substr($image->submitted,0,4);
			if ($last != $s)
				$b = getFormattedDate($s);
			$last = $s;
			break;
		case 'user_id':
			$s = $image->realname;
			if ($last != $s)
				$b = $s;
			$last = $s;
			break;
		case 'hectad':
		case 'myriad':
			preg_match('/^(\w+)(\d)\d(\d)\d$/',$image->grid_reference,$m);
			$s = $m[1].($engine->criteria->breakby=='hectad'?$m[2].$m[3]:'');
			if ($last != $s)
				$b = $s;
			$last = $s;
			break;
		default:
			$name = str_replace('+','',$engine->criteria->breakby);
			if ($last != $image->{$name})
				$b = $image->{$name};
			$last = $image->{$name};
			break;
	}

	if ($b) {
		if (isset($params['extra']))
			print "</ul>";
		if (isset($params['table']))
			print "<tr><td colspan=2>";
		print "<div style=\"clear:both;margin-left:0px;padding:2px;\"><b>$b</b></div>";
		if (isset($params['extra']))
			print "<ul>";
		if (isset($params['table']))
			print "</td></tr>";
		$image->breakby = $b;
	}
	$engine->breaklast = $last;
}

