<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

#########################################
# redirect for non JS clients

if (strpos($_SERVER['REQUEST_URI'],'/finder/of.php') === 0) {
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");

        $url = "/of/".urlencode2($_GET['q']);
	//todo add sorting?
	if (!empty($_GET['sort']))
		$url .= "?sort=".urlencode($_GET['sort']);

        header("Location: ".$url);
        print "<a href=\"".htmlentities($url)."\">moved</a>";

        exit;
}

#########################################
# general page startup

require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;

customExpiresHeader(3600,false,true);

$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
	$src = 'src';//revert back to standard non lazy loading
}

#########################################
# quick query parsing, to possibly redirect to the nearby page.

$qh = $qu = '';
if (!empty($_GET['q'])) {
	$_GET['q'] = str_replace(" near (anywhere)",'',$_GET['q']);

	if (preg_match('/^(\(anything\)\s* |)near (.+)$/',$_GET['q'],$m) && !isset($_GET['redir'])) {
		header("Location: /near/".urlencode2($m[2]));
                exit;
	}


        $sphinx = new sphinxwrapper(trim($_GET['q']));

	$qu = urlencode(trim($_GET['q']));
	$qu2 = urlencode2(trim($_GET['q']));
	$qh = htmlentities(trim($_GET['q']));

	if (preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$_GET['q'],$ll) && !isset($_GET['redir'])) {
		header("Location: /near/$qu2");
		exit;
	}

	$smarty->assign("page_title",'Photos of '.$_GET['q']);
	$smarty->display("_std_begin.tpl",$_SERVER['PHP_SELF'].md5($_GET['q']));

	if ($memcache->valid && $mkey = md5(trim($_GET['q']));
		$str =& $memcache->name_get('of',$mkey);
		if (!empty($str)) {
			print $str;
			$smarty->display('_std_end.tpl');
			exit;
		}
	
		ob_start();
	}

	$str = file_get_contents("http://www.geograph.org.uk/finder/places.json.php?q=$qu&new=1");
	if (strlen($str) > 40) {
        	$decode = json_decode($str);
	}

} else {
	$smarty->display('_std_begin.tpl');
}

#########################################
# the top of page form

?>
<form onsubmit="location.href = '/of/'+encodeURIComponent(this.q.value); return false;">
<div class="interestBox">
	<? if (!empty($_GET['q'])) { ?>
	<div style="float:right">
		More:
		<a href="/finder/groups.php?q=<? echo $qu; ?>&group=decade">Over Time</a> &middot;
		<a href="/finder/groups.php?q=<? echo $qu; ?>&group=segment">Recent</a> &middot;
		<!--a href="/finder/recent.php?q=<? echo $qu; ?>">Recent</a> &middot; -->
		<!--a href="/of/<? echo $qu2; ?>?sort=recent">Recent</a> &middot; -->
		<?
		$db = GeographDatabaseConnection(true);
		//todo memcache!
		if ($tag_id = $db->getOne("SELECT tag_id FROM tag WHERE status = 1 AND tag = ".$db->Quote($_GET['q']))) { ?>
			<a href="/tagged/<? echo $qu2; ?>">Tagged</a> &middot;
		<? } ?>
		<? if (preg_match("/^\s*([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\s*$/",$_GET['q'])) { ?>
		        <a href="/gridref/<? echo $qu2; ?>">Browse Page</a> &middot;
                <? } ?>
		<a href="/browser/#!/q=<? echo $qu; ?>/display=map_dots/pagesize=50">Map</a> &middot;
		<a href="/browser/#!/q=<? echo $qu; ?>/pagesize=50">Browser</a> &middot;
		<a href="/finder/multi2.php?q=<? echo $qu; ?>">Others</a>
	</div>
	<? } ?>
	Images of: <input type=search name=q value="<? echo $qh; ?>" size=40><input type=submit value=go><br>
<?

#########################################
# display the location results dropdown, for directing to near page.

if (!empty($_GET['q'])) {

	if (!empty($decode)) {
		if ($decode->total_found == 1) {
			$object = $decode->items[0];
			if (strpos($object->name,$object->gr) === false)
                                 $object->name .= "/{$object->gr}";
                        print "Or view images <i>near</i> <b><a href=\"/near/".urlencode2($object->name)."\">".htmlentities($object->name)."</a></b>";

		} else if ($decode->total_found > 0) {
			print "Or view images <i>near</i> <select onchange=\"location.href = '/near/'+encodeURI(this.value);\"><option value=''>Choose Location...</option>";
			foreach ($decode->items as $object) {
				if (strpos($object->name,$object->gr) === false)
                                	$object->name .= "/{$object->gr}";
                                printf('<option value="%s"%s>%s</option>', $val = $object->name, ($gr == $object->gr)?' selected':'',
                                        str_replace('/',' &nbsp; ',$object->name).($object->localities?", ".$object->localities:''));
			}

			print '<optgroup></optgroup>';
			if (!empty($decode->query_info))
				printf('<optgroup label="%s"></optgroup>', $decode->query_info);
			if (!empty($decode->copyright))
				printf('<optgroup label="%s"></optgroup>', $decode->copyright);
			print "</select> ({$decode->total_found})";
		}
	}

}

#########################################

?>

</div>
</form>
<?


if (!empty($_GET['q'])) {

#########################################
# yet another catch for nearby queries

	$bits = explode(' near ',$_GET['q']);
	if (count($bits) == 2) {
		print "<div>Looking for keywords '".htmlentities($bits[0])."' <i>near</i> place '".htmlentities($bits[1])."'? If so <a href=\"/search.php?q=$qu\">click here</a>.</div>";
		$sphinx->q = str_replace(' near ',' @(Place,County,Country) ',$sphinx->q);
	}


	$limit = 50;

                $prev_fetch_mode = $ADODB_FETCH_MODE;
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

                $CONF['sphinxql_dsn'] = 'mysql://192.168.77.35:9306/';

                $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());

#########################################
# experient to find related images

		if (preg_match('/^related:(\d+)$/',$_GET['q'],$m)) {
			$id = intval($m[1]);
			$row = $sph->getRow("
                        select realname,title,grid_reference,hectad,tags,place,county,format,takenday,takenmonth,decade,realname,contexts
                        from sample8
                        where id =$id
                        limit 1");
			$words = implode(" ",$row);
			$words = str_replace('_SEP_','',$words);
			$words = trim(preg_replace('/[^\w]+/',' ',$words));
			$where = "match(".$sph->Quote('"'.$words.'"/0.5').")";
			print '<iframe src="http://t0.geograph.org.uk/tile-info.php?id='.$id.'" width="100%" height="250" frameborder=0></iframe>';

		} else {
	                $where = "match(".$sph->Quote($sphinx->q).")";
		}

                //convert gi_stemmed -> sample8 format.
                $where = preg_replace('/@by/','@realname',$where);
                $where = preg_replace('/__TAG__/i','_SEP_',$where);

		$rows = array();

#########################################
# special handler to catch entered id numbers

		if (preg_match('/\b(\d{2,})\b/',$_GET['q'],$m) && $m[1]>3 && $m[1] < 10000000) {
			 $rows['single'] = $sph->getAll($sql = "
                                select id,realname,user_id,title,grid_reference
                                from sample8
                                where id = {$m[1]}");
		}

#########################################
# retreive a small number of high scoring images

		if (empty($_GET['sort'])) {
			$rows['score'] = $sph->getAll($sql = "
        	                select id,realname,user_id,title,grid_reference
                	        from sample8
                        	where $where
	                        order by score desc
        	                limit 8
				option ranker=none, max_query_time=800");

			if (!empty($rows['score']))
				$data = $sph->getAssoc("SHOW META");

			if (empty($rows['score']) && preg_match('/\s\w+\s+\w/',$_GET['q'])) {
				print "<i>No results found for '".htmlentities($_GET['q'])."', showing results containing only <b>some of the words</b>...</i><br/>";
				$words = $_GET['q'];
	                        $words = str_replace('_SEP_','',$words);
        	                $words = trim(preg_replace('/[^\w]+/',' ',$words));
                	        $where = "match(".$sph->Quote('"'.$words.'"/0.5').")";
			}
		}

#########################################
# get 4 results from Google Images!

		if (empty($id)) {
			$opts = array('http' =>
			    array(
			        'timeout'  => 1.8,
			        'header'  => 'Connection: close',
			    )
			);

			$context = stream_context_create($opts);

			$str = file_get_contents("http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=$qu+site:geograph.org.uk+OR+site:geograph.ie&userip=".getRemoteIP(), false, $stream);
			if (strlen($str) > 300) {
				$decode = json_decode($str);
				$ids = array();
				foreach ($decode->responseData->results as $result) {
					if (preg_match('/photo\/(\d+)/',$result->originalContextUrl,$m)) {
						$ids[] = $m[1];
					} elseif (preg_match('/\/\d{2}\/(\d{6,7})_/',$result->url,$m)) {
						$ids[] = $m[1];
	                                }
				}
				if (!empty($ids))
					$rows['google'] = $sph->getAll($sql = "
                        			select id,realname,user_id,title,grid_reference
		                        	from sample8
	                		        where id IN(".implode(',',$ids).")
			                        order by score desc
                			        limit 8");
			}
		}

#########################################
# the main results set!

		//$option = ", ranker=expr('sum(lcs*lccs*user_weight)*1000+bm25')";
		$option = "";

		if (preg_match('/^\w+\s+\w+[\w\s]*$/',$_GET['q'])) {
	                $where = "match(".$sph->Quote('('.$_GET['q'].') | "'.$_GET['q'].'"').")";
			$option = ", ranker=expr('sum((word_count+(lcs-1)*max_lcs)*user_weight)')";
		}

		$order = "w2ln desc, combined asc";
		$columns = ", sequence / baysian as combined";
		if (!empty($_GET['sort']) && $_GET['sort'] == 'recent') {
			$offset = time()-3600*24*356;
			$columns = ", sequence / ln(submitted-$offset) as combined";
			$where .= " and submitted > $offset";
		} elseif (!empty($data) && $data['total_found'] > 50000) {
			//the combined calculation can be expensive!
			$order = "w2ln desc, sequence asc";
			$columns = "";
		}
                $rows['combined'] = $sph->getAll($sql = "
                        select id,realname,user_id,title,grid_reference, integer(ln(weight())) as w2ln $columns
                        from sample8
                        where $where
                        order by $order
                        limit {$limit}
			option field_weights=(place=10,county=8,country=7,title=5,tags=3,imageclass=4)
			, cutoff=1000000 $ranker ");

if (!empty($_GET['d']))
	print $sql;

#########################################
# merge all the results into one

		if (empty($data))
			$data = $sph->getAssoc("SHOW META");

		$final = array();
		foreach ($rows as $idx => $arr) {
			if (!empty($arr)) {
				foreach ($arr as $row)
					$final[$row['id']] = $row;
				//unset($rows[$idx]);
			}
		}

        print "<br style=clear:both>";

#########################################
# display normal thumbnail results!

	if (count($final) > 3) {
		$thumbh = 120;
		$thumbw = 120;

		print "<div id=thumbs>";
                foreach ($final as $idx => $row) {
			$row['gridimage_id'] = $row['id'];
                        $image = new GridImage();
                        $image->fastInit($row);

?>
          <div style="float:left;position:relative; width:120px; height:120px;padding:1px;">
          <div align="center">
          <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,$src); ?></a></div>
          </div>
<?

		}

		print "<br style=clear:both></div>";
		if ($src == 'data-src')
			print '<script src="/js/lazy.v2.js" type="text/javascript"></script>';
		if (!empty($USER->registered))
			print '<script src="/preview.js.php?d=preview" type="text/javascript"></script>';

#########################################
# fallback and use the preview iframe for a few results

	} elseif (count($final)) {
		foreach ($final as $idx => $row) {
			print '<iframe src="http://t0.geograph.org.uk/tile-info.php?id='.$row['id'].'" width="100%" height="250" frameborder=0></iframe>';
			print "<hr/><br/>";
		}

#########################################
# handler for no results

	} else {
		print "<p>No keywords Results found. ";

        	if (!empty($decode) && $decode->total_found > 0) {
                        $object = $decode->items[0];
			if ($decode->total_found == 1 && !isset($_GET['redir']))
				print "<script>location.href='/near/".urlencode2($object->name)."';</script>";
                        if (strpos($object->name,$object->gr) === false && $decode->total_found > 1)
                                $object->name .= "/{$object->gr}";
			print "Or try a <a href=\"/near/".urlencode2($object->name)."\">searching for images <i>near</i> <b>".htmlentities($object->name)."</b></a>.";
		}
		print "</p>";

		if (empty($decode) || $decode->total_found != 1) {
			$str = file_get_contents("http://suggestqueries.google.com/complete/search?output=toolbar&hl=en&q=$qu");
			if (preg_match_all('/ data="(.+?)"/',$str,$m)) {
				print "<p>Alternative Queries: ";
				foreach ($m[1] as $item) {
					print "<a href=\"/of/".urlencode($item)."\">".htmlentities($item)."</a> &middot; ";
				}
				print " (may or may not return results)</p>";
				//todo verify these links at least might return results, with CALL KEYWORDS
			}
		}
	}

#########################################
# footer links

if (!empty($final) && empty($words) && count($final) != count($rows['google'])) {
	print "<br/><div class=interestBox>";
	if (!empty($data['total_found']) && $data['total_found'] > 10)
		print "About ".number_format($data['total_found'])." results. ";
	print '<a href="/browser/#!/q='.$qu.'"><b>Explore these images more</b> in the Browser</a> or ';
	print '<a href="/search.php?do=1&searchtext='.(empty($words)?'':'~').$qu.'">in the standard search</a> (may return slightly different results).';
	$suggestions = array();
	if ($data['total_found'] > 300) {
		if (!empty($tag_id) && strpos($_GET['q'],'[') !== 0)
			$suggestions[] = '<a href="/of/['.$qu2.']">Viewing images tagged with ['.$qh.']</a>';
		if (strpos($_GET['q'],'"') !== 0 && strpos($_GET['q'],' ') > 3)
			$suggestions[] = "<a href=\"/of/%22$qu2%22\">View images containing the phrase &quot;$qh&quot</a>";
	}
	if (!empty($suggestions)) {
		print "<br/>&middot; To many imprecise results? Try ".implode(' or ',$suggestions);
	}
	print "</div>";

} elseif (!empty($final) && count($final) == count($rows['google'])) {
	print "<br/><div class=interestBox>";
	print '<a href="https://www.google.co.uk/search?q='.$qu.'+site:geograph.org.uk+OR+site:geograph.ie&amp;tbm=isch" target=_blank><b>Explore these images more</b> via Google Images</a>';
	print "</div>";
} else {
	print "<hr/>";
}

#########################################

if ($memcache->valid && $mkey) {
	$str = ob_get_flush();

	$memcache->name_set('of',$mkey,$memcache->compress,$memcache->period_long);
}

#########################################
# special footer just for registered users - who have had their default changed

if (!empty($USER->registered)) {
	print "<p>If you prefer the traditional search, you can <a href=\"/choose-search.php\">choose your default search engine to use</a>.</p>";
	if ($CONF['forums']) {
		print "<p>Having trouble with this page? <a href=\"/discuss/index.php?&action=vthread&forum=12&topic=26439\">Please let us know on the discussion forum</a>,
		or fill out <a href='https://docs.google.com/forms/d/1EghtKiKGkLbLUJ1gBAMiENNgMChQotBwI3n7XSyw1z0/viewform' target=_blank>Feedback Form</a>, thank you!</p>";
	}
}

#########################################

        $smarty->display('_std_end.tpl');
        exit;

#########################################
# fallback if not using magic - but never used

} elseif (!empty($_GET['q'])) {
	print '<iframe src="/finder/search-service.php?q='.$qu.'" width="700" height="700" name="searchwindow" style="width:100%"></iframe>';
        $smarty->display('_std_end.tpl');
        exit;
}

#########################################
# do something instead of an empty page when no query... 

	$db = GeographDatabaseConnection(true);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("SELECT * FROM geograph_tmp.random_images where moderation_status = 'geograph'");

	if (count($list)) {

	print "<p>Enter a search above, in the meantime here are some example searches";

	print '&middot; <a href="/of/pylons">pylons</a> ';
	print '&middot; <a href="/of/tobermory">tobermory</a> ';
	print '&middot; <a href="/of/canals">canals</a> ';
	print '&middot; <a href="/near/newquay">newquay</a> ';

	print "<p> and some random images...</p>";

                $thumbh = 160;
                $thumbw = 213;
                foreach ($list as $idx => $row) {
                        $image = new GridImage();
                        $image->fastInit($row);
?>
                                <div style="float:left;" class="photo33"><div style="height:<? echo $thumbh; ?>px;vertical-align:middle"><a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,$src); ?></a></div>
                                <div class="caption"><div class="minheightprop" style="height:2.5em"></div><a href="/gridref/<? echo $image->grid_reference; ?>"><? echo $image->grid_reference; ?></a> : <a title="view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo htmlentities($image->title); ?></a><div class="minheightclear"></div></div>
                                <div class="statuscaption">by <a href="<? echo $image->profile_link; ?>"><? echo htmlentities($image->realname); ?></a></div>
                                </div>
<?
                }
		print "<br style=\"clear:both\"/>";
	} else {
		print "nothing to display";
	}

	if ($src == 'data-src')
		print '<script src="/js/lazy.v2.js" type="text/javascript"></script>';
	if (!empty($USER->registered))
		print '<script src="/preview.js.php" type="text/javascript"></script>';

	$smarty->display('_std_end.tpl');
	exit;

#########################################
# functions!

function urlencode2($input) {
        return str_replace(array('%2F','%3A','%20'),array('/',':','+'),$input);
}

