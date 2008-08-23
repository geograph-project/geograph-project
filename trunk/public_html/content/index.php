<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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

$cacheid = $user->registered.'.'.$CONF['forums'];

if (!empty($_GET)) {
	ksort($_GET);
	$cacheid .= ".".md5(serialize($_GET));
}

if (isset($_REQUEST['inner'])) {
	$template = 'content_iframe.tpl';
} else {
	$template = 'content.tpl';
}

$db=NewADOConnection($GLOBALS['DSN']);

$data = $db->getRow("show table status like 'content'");

//when this table was modified
$mtime = strtotime($data['Update_time']);
	
//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
customCacheControl($mtime,$cacheid,($USER->user_id == 0));

if ($template == 'content_iframe.tpl' && !$smarty->is_cached($template, $cacheid))
{


	
	$limit = 25;
	
	#$pg = empty($_GET['page'])?1:intval($_GET['page']);
	
	$order = (isset($_GET['order']) && ctype_lower($_GET['order']))?$_GET['order']:'updated';

	
	switch ($order) {
		case 'views': $sql_order = "views desc";
			$title = "Most Viewed"; break;
		case 'created': $sql_order = "created desc";
			$title = "Recently Created"; break;
		case 'title': $sql_order = "title";
			$title = "By Content Title";break;
		case 'updated':
		default: $sql_order = "updated desc";
			$title = "Recently Updated";
	}
	
	if (!empty($_GET['user_id']) && preg_match('/^\d+$/',$_GET['user_id'])) {
		$where = "content.user_id = {$_GET['user_id']}";
		$smarty->assign('extra', "&amp;user_id={$_GET['user_id']}");
	
	} elseif (!empty($_GET['q'])) {

		// --------------
		
		$q=trim($_GET['q']);
		
		$sphinx = new sphinxwrapper($q);
		$sphinx->pageSize = $pgsize = 25;
		
		if (preg_match('/\bp(age|)(\d+)\s*$/',$q,$m)) {
			$pg = intval($m[2]);
			$sphinx->q = preg_replace('/\bp(age|)\d+\s*$/','',$sphinx->q);
		} else {
			$pg = 1;
		}
		
		$smarty->assign('extra', "&amp;q=".urlencode($sphinx->q));
		$title = "Matching ".htmlentities($sphinx->q);
		
		$sphinx->processQuery();
		
		$ids = $sphinx->returnIds($pg,'content_stemmed');	
		
		$smarty->assign("query_info",$sphinx->query_info);
		
		if (count($ids)) {
			$where = "content_id IN(".join(",",$ids).")";
		} else {
			$where = "0";
		}
		// --------------
	} elseif (isset($_GET['docs'])) {
		$where = "`use` = 'document'";
		$limit = 1000;
		$title = "Geograph Documents";
	} elseif (isset($_GET['loc'])) {
		$where = "gridsquare_id > 0";
		$limit = 100;
		$title = "Location Specific Content";
	} else {
		$where = "`use` = 'info'";
	}
	
	
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("
	select content.content_id,content.user_id,url,title,extract,updated,created,realname,content.type,content.gridimage_id,
		(coalesce(views,0)+coalesce(topic_views,0)) as views,
		(coalesce(images,0)+coalesce(count(gridimage_post.seq_id),0)) as images,
		article_stat.words,posts_count
	from content 
		left join user using (user_id)
		left join article_stat on (content.type = 'article' and foreign_id = article_id)
		left join geobb_topics on (content.type = 'gallery' and foreign_id = topic_id) 
		left join gridimage_post using (topic_id)
	where $where
	group by content_id
	order by `use` = 'info' desc, $sql_order 
	limit $limit");
	
	if (false && !empty($_GET['q'])) {
		$docs = array();
		foreach ($list as $i => $row) {
			$docs[] = $row['title'].' '.$row['extract'].' '.$row['allwords'];
		}
		
		$ex = $cl->BuildExcerpts ( $docs, $index, $q);
		print "<pre>";print_r($ex);exit;
		foreach ($ex as $i => $row) {
			$list[$i]['extract'] = $row;
		}
	}
	foreach ($list as $i => $row) {
		if ($row['gridimage_id']) {
			$list[$i]['image'] = new GridImage;
			$g_ok = $list[$i]['image']->loadFromId($row['gridimage_id'],true);
			if ($g_ok && $list[$i]['image']->moderation_status == 'rejected')
				$g_ok = false;
			if (!$g_ok) {
				unset($list[$i]['image']);
			}
		}
	}
	
	$ADODB_FETCH_MODE = $prev_fetch_mode;
	
	$smarty->assign_by_ref('list', $list);
	$smarty->assign_by_ref('title', $title);
	
	if (!empty($_SERVER['QUERY_STRING']) && preg_match("/^[\w&;=+ %]/",$_SERVER['QUERY_STRING'])) {
		$smarty->assign('extra_raw', "&amp;".htmlentities($_SERVER['QUERY_STRING']));
	}
	
} else if ($template == 'content.tpl' && !$smarty->is_cached($template, $cacheid)) {
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("select title from content");

	$a = array();
	foreach ($list as $i => $row) {
		$alltext = preg_replace('/[^a-zA-Z0-9]+/',' ',str_replace("'",'',$row['title']));

		$words = preg_split('/ +/',trim($alltext));

		foreach ($words as $c => $w) {
			if (preg_match('/^(geograph|a|about|above|according|across|actually|adj|after|afterwards|again|against|all|almost|alone|along|already|also|although|always|among|amongst|an|and|another|any|anyhow|anyone|anything|anywhere|are|arent|around|as|at|b|be|became|because|become|becomes|becoming|been|before|beforehand|begin|beginning|behind|being|below|beside|besides|between|beyond|billion|both|but|by|c|can|cant|cannot|caption|co|co.|could|couldnt|d|did|didnt|do|does|doesnt|dont|down|during|e|each|eg|e.g.|eight|eighty|either|else|elsewhere|end|ending|enough|etc|etc.|even|ever|every|everyone|everything|everywhere|except|f|few|fifty|first|five|for|former|formerly|forty|found|four|from|further|g|h|had|has|hasnt|have|havent|he|hed|hell|hes|hence|her|here|heres|hereafter|hereby|herein|hereupon|hers|herself|him|himself|his|how|however|hundred|i|id|ill|im|ive|ie|if|in|inc|inc.|indeed|instead|into|is|isnt|it|its|its|itself|j|k|l|last|later|latter|latterly|least|less|let|lets|like|likely|ltd|m|made|make|makes|many|maybe|me|meantime|meanwhile|might|million|miss|more|moreover|most|mostly|mr|mrs|much|must|my|myself|n|namely|neither|never|nevertheless|next|nine|ninety|no|nobody|none|nonetheless|noone|nor|not|nothing|now|nowhere|o|of|off|often|on|once|one|ones|only|onto|or|other|others|otherwise|our|ours|ourselves|out|over|overall|own|p|per|perhaps|q|r|rather|recent|recently|s|same|seem|seemed|seeming|seems|seven|seventy|several|she|shed|shell|shes|should|shouldnt|since|six|sixty|so|some|somehow|someone|something|sometime|sometimes|somewhere|still|stop|such|t|taking|ten|than|that|thatll|thats|thatve|the|their|them|themselves|then|thence|there|thered|therell|therere|theres|thereve|thereafter|thereby|therefore|therein|thereupon|these|they|theyd|theyll|theyre|theyve|thirty|this|those|though|thousand|three|through|throughout|thru|thus|to|together|too|toward|towards|trillion|twenty|two|u|under|unless|unlike|unlikely|until|up|upon|us|used|using|v|very|via|w|was|wasnt|we|wed|well|were|weve|well|were|werent|what|whatll|whats|whatve|whatever|when|whence|whenever|where|wheres|whereafter|whereas|whereby|wherein|whereupon|wherever|whether|which|while|whither|who|whod|wholl|whos|whoever|whole|whom|whomever|whose|why|will|with|within|without|wont|would|wouldnt|x|y|yes|yet|you|youd|youll|youre|youve|your|yours|yourself|yourselves|z)$/i',$w)) {
				//skip...
			} elseif (preg_match('/^[A-Z]/',$w)) {
				//give promience to uppercased words
				$a[strtolower($w)]+=2;
			} else {
				$a[$w]++;
			}
		}
	}
	$ADODB_FETCH_MODE = $prev_fetch_mode;

	arsort($a);
	$smarty->assign('words', array_slice($a,0,50));

	if (!empty($_SERVER['QUERY_STRING']) && preg_match("/^[\w&;=+ %]/",$_SERVER['QUERY_STRING'])) {
		$smarty->assign('extra', "&amp;".htmlentities($_SERVER['QUERY_STRING']));
	}
}

$smarty->display($template, $cacheid);

?>
