<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

$cacheid = $USER->registered.'.'.$CONF['forums'];

if (empty($_GET['scope']) && !empty( $_SESSION['content_scope'])) {
	$_GET['scope'] = $_SESSION['content_scope'];
} 
if (empty($_GET['scope'])) {
	if ((isset($CONF['forums']) && empty($CONF['forums'])) || $USER->user_id == 0 ) {
		$_GET['scope'] = 'article,gallery,help';
	} else {
		$_GET['scope'] = 'article,gallery,help,blog,trip';
	}
}

ksort($_GET);
$cacheid .= md5(serialize($_GET));
	
$template = 'content.tpl';

$db = GeographDatabaseConnection(true);

$data = $db->getRow("show table status like 'content'");

//when this table was modified
$mtime = strtotime($data['Update_time']);
	
//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
customCacheControl($mtime,$cacheid,($USER->user_id == 0));

$smarty->assign("inline",$inline);


$order = (isset($_GET['order']) && ctype_lower($_GET['order']))?$_GET['order']:'updated';

if ($CONF['template']=='archive') {
	$order = 'title';
}

switch ($order) {
	case 'relevance': $sql_order = "NULL"; //will be fixed later
		$title = "Relevance"; break;
	case 'views': $sql_order = "views desc";
		$title = "Most Viewed"; break;
	case 'images': $sql_order = "images desc";
		$title = "Most Images"; break;
	case 'created': $sql_order = "created desc";
		$title = "Recently Created"; break;
	case 'rand': $sql_order = "rand()";
		$title = "Random Order"; break;
	case 'title': $sql_order = "title";
		$title = "By Collection Title";break;
	case 'updated':
	default: $sql_order = "updated desc";
		$title = "Recently Updated";
		$order = 'updated';
}
$orders = array('views'=>'Most Viewed','created'=>'Recently Created','title'=>'Alphabetical','updated'=>'Last Updated','images'=>'Most Images','rand'=>'Random Order');

$sources = array('portal'=>'Portal', 'article'=>'Article', 'blog'=>'Blog Entry', 'trip'=>'Geo-trip', 'gallery'=>'Gallery', 'themed'=>'Themed Topic', 'help'=>'Help Article', 'gsd'=>'Grid Square Discussion', 'snippet'=>'Shared Description', 'user'=>'User Profile', 'category'=>'Category', 'context'=>'Geographical Context', 'other'=>'Other');

if ((isset($CONF['forums']) && empty($CONF['forums'])) || $USER->user_id == 0 ) {
	unset($sources['themed']);
}
if (!empty($_GET['scope']) && $_GET['scope'] == 'all') {
	$_GET['scope'] = array_keys($sources);
}

if (!$smarty->is_cached($template, $cacheid)) {

	$extra = $where = array();
	
	if ($CONF['template']=='archive') {
		$pageSize = 1000;
	} else {
		$pageSize = 25;
	}
	
	$extra['order'] = $order;
		
	if (!empty($_GET['page'])) {
		$pg = intval($_GET['page']);
	} else {
		$pg = 1;
	}
		
	if (!empty($_GET['scope'])) {
		$filters = array();
		if (is_array($_GET['scope'])) {
			$s = $_GET['scope'];
		} else {
			$s = explode(',',$_GET['scope']);
		}
		foreach ($s as $scope) {
			switch($scope) {
				case 'blog':
				case 'trip':
				case 'article':
				case 'gallery':
				case 'themed':
				case 'help':
				case 'snippet':
				case 'portal':
				case 'user':
				case 'category':
				case 'context':
				case 'other':
					$filters['source'][] = $scope;
					$smarty->assign("scope_".$scope,1);
					break;
				case 'info':
				case 'document':
					$filters['type'][] = $scope;
					$smarty->assign("scope_".$scope,1);
					break;
			}
		}
		if (count($s) == 1 && $sources[$s[0]]) {
			$title = $sources[$s[0]]."s ".$title;
			$title = str_replace('ys ','ies ',$title);
		}
		foreach ($filters as $key => $value) {
			if (!empty($value)) {
				$where[] = "content.$key IN ('".implode("','",$value)."')";
			}
		}
		$extra['scope'] = implode(',',$s);

		if ($USER->registered && !empty($filters['source'])) {
			$_SESSION['content_scope'] = implode(',',$filters['source']);
		}

	}
	
	if (!empty($_GET['user_id']) && preg_match('/^\d+$/',$_GET['user_id'])) {
		$where[] = "content.user_id = {$_GET['user_id']}";
		$extra['user_id'] = $_GET['user_id'];
		$profile=new GeographUser($_GET['user_id']);
		$title = "By ".($profile->realname);
		
	} elseif (!empty($_GET['q'])) {

		$sphinx = new sphinxwrapper(trim($_GET['q']));
		$sphinx->pageSize = $pageSize;
		
		if (preg_match('/\bp(age|)(\d+)\s*$/',$q,$m)) {
			$pg = intval($m[2]);
			$sphinx->q = preg_replace('/\bp(age|)\d+\s*$/','',$sphinx->q);
		}
		
		if (!empty($_GET['in']) && $_GET['in'] == 'title') {
			if (!preg_match('/^\w+:/',$sphinx->q)) {
				$sphinx->q = "@title ".$sphinx->q;
			}
			$smarty->assign('in_title', 1);
		}
		
		$smarty->assign_by_ref('q', $sphinx->qclean);
		$extra['q'] = $sphinx->qclean;
		$title = "Matching word search [ ".htmlentities($sphinx->qclean)." ]";
		
		#$sphinx->processQuery();
		
		$sphinx->qoutput = $sphinx->q;
		if ((isset($CONF['forums']) && empty($CONF['forums'])) || $USER->user_id == 0 ) {
			$sphinx->q .= " @source -themed";
		}

		if (!empty($filters)) {
			foreach ($filters as $key => $value) {
				if (!empty($filters[$key])) {
					$filters[$key] = "(".implode('|',$filters[$key]).")";
				}
			}

                if (!empty($_REQUEST['max']) || !empty($_REQUEST['min'])) {
                        if (empty($_REQUEST['max'])) {
                                $_REQUEST['max'] = 10000000;
                        }
                        $filters['aimages'] = array(intval($_REQUEST['min']),intval($_REQUEST['max']));
                }

			$sphinx->addFilters($filters);
		}

		$cl = $sphinx->_getClient();		
		$cl->SetFieldWeights(array('title'=>100));	

		$ids = $sphinx->returnIds($pg,'content_stemmed');
		
		$smarty->assign("query_info",$sphinx->query_info);
		
		if (count($ids)) {
			$where[] = "content_id IN(".join(",",$ids).")";
			if ($order == 'relevance') {
				$sql_order = "FIELD(content_id,".join(",",$ids).")";
			}
		} else {
			$where[] = "0";
		}
		$resultCount = $sphinx->resultCount;
		$numberOfPages = $sphinx->numberOfPages;
		
		$orders['relevance'] = 'Relevance';
		
		// --------------
	} elseif (isset($_GET['docs'])) {
		$where[] = "content.`type` = 'document'";
		$pageSize = 1000;
		$title = "Geograph Documents";
		$extra['docs'] = 1;
		$smarty->assign("scope",'document');
	} elseif (isset($_GET['loc'])) {
		$where[] = "gridsquare_id > 0";
		$pageSize = 100;
		$title = "Location Specific Content";
		$extra['loc'] = 1;
	} else {
		$where[] = "content.`type` = 'info'";
	}
	
	if ((isset($CONF['forums']) && empty($CONF['forums'])) || $USER->user_id == 0 ) {
		$where[] = "content.`source` != 'themed'";
	}
	
	$where = implode(' AND ',$where);
	
	if (!isset($resultCount))
		$resultCount = $db->getOne("SELECT COUNT(*) FROM content WHERE $where");
	
	if (!isset($numberOfPages))
		$numberOfPages = ceil($resultCount/$pageSize);

	if ($numberOfPages > 1) {
		$extra2 = http_build_query($extra);
		$smarty->assign('pagesString', pagesString($pg,$numberOfPages,$_SERVER['PHP_SELF']."?$extra2&amp;page=") );
		$smarty->assign("offset",(($pg -1)* $pageSize)+1);
	}
	
	if ($pg > 1 && !isset($ids)) {
		$page = ($pg -1)* $pageSize;
		$limit = "$page,$pageSize";
	} else {
		$limit = $pageSize;
	}
	
	$datecolumn = ($order == 'created')?'created':'updated';
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll($sql = "
	select content.content_id,content.user_id,url,title,extract,unix_timestamp(content.$datecolumn) as $datecolumn,realname,content.source,content.gridimage_id,
		(content.views+coalesce(article_stat.views,0)+coalesce(topic_views,0)) as views,
		(content.images+coalesce(article_stat.images,0)+coalesce(count(gridimage_post.seq_id),0)) as images,
		article_stat.words,coalesce(posts_count,0) as posts_count,coalesce(count(distinct gridimage_post.post_id),0) as posts_with_images
	from content 
		left join user using (user_id)
		left join article_stat on (content.source = 'article' and foreign_id = article_id)
		left join geobb_topics on (content.source IN ('gallery','themed') and foreign_id = topic_id) 
		left join gridimage_post using (topic_id)
	where $where
	group by content_id
	having posts_with_images >= posts_count/2
	order by content.`type` = 'info' desc, $sql_order 
	limit $limit");
	
if (!empty($_GET['debug'])) {
	print "<pre>$sql</pre>";
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
		$diff = time() - $row[$datecolumn];
		if ($diff > (3600*24*31)) {
			$list[$i][$datecolumn] = sprintf("%d months ago",$diff/(3600*24*31));
		} elseif ($diff > (3600*24)) {
			$list[$i][$datecolumn] = sprintf("%d days ago",$diff/(3600*24));
		} elseif ($diff > 3600) {
			$list[$i][$datecolumn] = sprintf("%d hours ago",$diff/3600);
		} else {
			$list[$i][$datecolumn] = sprintf("%d minutes ago",$diff/60);
		}
	}
	
	$ADODB_FETCH_MODE = $prev_fetch_mode;
	
	$smarty->assign_by_ref('resultCount', $resultCount);
	$smarty->assign_by_ref('shown', count($list));
	$smarty->assign_by_ref('list', $list);
	$smarty->assign_by_ref('title', $title);
	$smarty->assign("order",$order);
	$smarty->assign_by_ref("orders",$orders);
	$smarty->assign_by_ref("sources",$sources);
	$colours = array('FFFFFF','FFDDFF','FFFFAA','FFAAFF','AAFFFF','DDDDDD','DDDDFF','DDFFDD','BBBBFF','BBFFBB','FFBBBB','FFFFDD','FFDDDD');
	$keys = array_keys($sources);
	foreach ($keys as $idx => $key) {
		$colours[$key] = $colours[$idx];
	}
	$smarty->assign_by_ref("colours",$colours);
	
	//these are handled by the page
	unset($extra['q']);
	unset($extra['order']);
	unset($extra['scope']);
	$smarty->assign_by_ref("extra",$extra);
} 

if ($USER->registered && empty($_SERVER['QUERY_STRING']) && !empty($db)) {
	$pending = $db->getAll("
		select title,url
		from article 
		where approved = 0 and user_id = {$USER->user_id}
		order by article_id desc");
	if (!empty($pending)) {
		$smarty->assign_by_ref("pending",$pending);
	}
}

$smarty->display($template, $cacheid);

