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


require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;

customExpiresHeader(3600,false,true);

$qh = $qu = '';
if (!empty($_GET['q'])) {
        $sphinx = new sphinxwrapper(trim($_GET['q']));

	$qu = urlencode(trim($_GET['q']));
	$qu2 = urlencode2(trim($_GET['q']));
	$qh = htmlentities(trim($_GET['q']));

	if (preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$_GET['q'],$ll)) {
		header("Location: /near/$qu2");
		exit;
	}


	$data = file_get_contents("http://www.geograph.org.uk/finder/places.json.php?q=$qu&new=1");
	if (strlen($data) > 40) {
        	$decode = json_decode($data);
	}


	$smarty->assign("page_title",'Photos of '.$_GET['q']);
	$smarty->display("_std_begin.tpl",$_SERVER['PHP_SELF'].md5($_GET['q']));

} else {
	$smarty->display('_std_begin.tpl');
}

?>
<form onsubmit="location.href = '/of/'+encodeURIComponent(this.q.value); return false;">
<div class="interestBox">
	<? if (!empty($_GET['q'])) { ?>
	<div style="float:right">
		More:
		<a href="/finder/groups.php?q=<? echo $qu; ?>&group=decade">Over Time</a> &middot;
		<a href="/finder/recent.php?q=<? echo $qu; ?>">Recent</a> &middot;
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

if (!empty($_GET['q'])) {

	if (!empty($decode)) {
		if ($decode->total_found > 0) {
			print "Or view images near <select onchange=\"location.href = '/near/'+encodeURI(this.value);\"><option value=''>Choose Location...</option>";
			foreach ($decode->items as $object) {
				if (strpos($object->name,$object->gr) === false)
                                	$object->name .= "/{$object->gr}";
                                printf('<option value="%s"%s>%s</option>', $val = $object->name, ($gr == $object->gr)?' selected':'',
                                        $object->name.($object->localities?", ".$object->localities:''));
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

?>

</div>
</form>
<?


if (!empty($_GET['q'])) {
	$limit = 50;

                $prev_fetch_mode = $ADODB_FETCH_MODE;
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

                $CONF['sphinxql_dsn'] = 'mysql://192.168.77.35:9306/';

                $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());

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

		} else {
	                $where = "match(".$sph->Quote($sphinx->q).")";
		}

                //convert gi_stemmed -> sample8 format.
                $where = preg_replace('/@by/','@realname',$where);
                $where = preg_replace('/__TAG__/i','_SEP_',$where);

		$rows = array();
		if (empty($_GET['sort'])) {
			$rows[] = $sph->getAll($sql = "
        	                select id,realname,user_id,title,grid_reference
                	        from sample8
                        	where $where
	                        order by score desc
        	                limit 8");

			if (empty($rows[0]) && preg_match('/\s\w+\s+\w/',$_GET['q'])) {
				print "<i>No results found for '".htmlentities($_GET['q'])."', showing results containing only some of the words...</i><br/>";
				$words = $_GET['q'];
	                        $words = str_replace('_SEP_','',$words);
        	                $words = trim(preg_replace('/[^\w]+/',' ',$words));
                	        $where = "match(".$sph->Quote('"'.$words.'"/0.5').")";
			}
		}

		if (empty($id)) {
			$data = file_get_contents("https://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=$qu+site:geograph.org.uk");
			if (strlen($data) > 400) {
				$decode = json_decode($data);
				$ids = array();
				foreach ($decode->responseData->results as $result) {
					if (preg_match('/photo\/(\d+)/',$result->originalContextUrl,$m)) {
						$ids[] = $m[1];
					} elseif (preg_match('/\/\d{2}\/(\d{6,7})_/',$result->url,$m)) {
						$ids[] = $m[1];
	                                }
				}
				if (!empty($ids))
					$rows[] = $sph->getAll($sql = "
                        			select id,realname,user_id,title,grid_reference
		                        	from sample8
	                		        where id IN(".implode(',',$ids).")
			                        order by score desc
                			        limit 8");
			}
		}

		if (preg_match('/^\w+\s+\w+[\w\s]*$/',$_GET['q'])) {
	                $where = "match(".$sph->Quote('('.$_GET['q'].') | "'.$_GET['q'].'"').")";
		}

		$order = "w2ln desc, combined asc";
		$columns = "sequence / baysian as combined";
		if (!empty($_GET['sort']) && $_GET['sort'] == 'recent') {
			$offset = time()-3600*24*356;
			$columns = "sequence / ln(submitted-$offset) as combined";
			$where .= " and submitted > $offset";
		}
                $rows[] = $sph->getAll($sql = "
                        select id,realname,user_id,title,grid_reference, integer(ln(weight())) as w2ln, $columns
                        from sample8
                        where $where
                        order by $order
                        limit {$limit}
			option field_weights=(place=10,county=8,country=7,title=5,tags=3,imageclass=4) ");
			//,ranker=expr('sum(lcs*lccs*user_weight)*1000+bm25')");

if (!empty($_GET['d']))
	print $sql;

		$data = $sph->getAssoc("SHOW META");

		$final = array();
		foreach ($rows as $idx => $arr) {
			if (!empty($arr)) {
				foreach ($arr as $row)
					$final[$row['id']] = $row;
				unset($rows[idx]);
			}
		}

        print "<br style=clear:both>";


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
          <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a></div>
          </div>
<?

		}

		print "<br style=clear:both></div>";
		print '<script src="/js/preview.v43.js" type="text/javascript"></script>';

	} elseif (count($final)) {
		foreach ($final as $idx => $row) {
			print '<iframe src="http://www.geograph.org.uk/tile-info.php?id='.$row['id'].'" width="100%" height="250" frameborder=0></iframe>';
			print "<hr/><br/>";
		}
	} else {
		print "<p>No keywords Results found. ";

        	if (!empty($decode) && $decode->total_found > 0) {
                        $object = $decode->items[0];
			if ($decode->total_found == 1)
				print "<script>location.href='/near/".urlencode2($object->name)."';</script>";
                        if (strpos($object->name,$object->gr) === false && $decode->total_found > 1)
                                $object->name .= "/{$object->gr}";
			print "Or try a <a href=\"/near/".urlencode2($object->name)."\">searching for images <i>near</i> <b>".htmlentities($object->name)."</b></a>.";
		}
		print "</p>";
	}

	print "<br/><div class=interestBox>";
	if (!empty($data['total_found']) && $data['total_found'] > 10)
		print "About ".number_format($data['total_found'])." results. ";
	print '<a href="/browser/#!/q='.$qu.'">Explore these images more in the Browser</a> or ';
	print '<a href="/search.php?do=1&searchtext='.$qu.'">in the standard search</a>';
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


if (!empty($USER->registered)) {
	print "<p>If you prefer the traditional search, you can <a href=\"/choose-search.php\">choose your default search engine to use</a>.</p>";
	if ($CONF['forums']) {
		print "<p>Having trouble with this page (no matter how small), <a href=\"/discuss/index.php?&action=vthread&forum=12&topic=26439\">please let us know</a>.</p>";
	}
}


        $smarty->display('_std_end.tpl');
        exit;


} elseif (!empty($_GET['q'])) {
	print '<iframe src="/finder/search-service.php?q='.$qu.'" width="700" height="700" name="searchwindow" style="width:100%"></iframe>';
        $smarty->display('_std_end.tpl');
        exit;
}



	$db = GeographDatabaseConnection(true);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("SELECT * FROM geograph_tmp.random_images where moderation_status = 'geograph'");

	if (count($list)) {

	print "<p>Enter a search above, in the meantime here are some random images...</p>";

                $thumbh = 160;
                $thumbw = 213;
                foreach ($list as $idx => $row) {
                        $image = new GridImage();
                        $image->fastInit($row);
?>
                                <div style="float:left;" class="photo33"><div style="height:<? echo $thumbh; ?>px;vertical-align:middle"><a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a></div>
                                <div class="caption"><div class="minheightprop" style="height:2.5em"></div><a href="/gridref/<? echo $image->grid_reference; ?>"><? echo $image->grid_reference; ?></a> : <a title="view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo htmlentities($image->title); ?></a><div class="minheightclear"></div></div>
                                <div class="statuscaption">by <a href="<? echo $image->profile_link; ?>"><? echo htmlentities($image->realname); ?></a></div>
                                </div>
<?
                }
		print "<br style=\"clear:both\"/>";
	} else {
		print "nothing to display";
	}

	$smarty->display('_std_end.tpl');
	exit;


function urlencode2($input) {
        return str_replace(array('%2F','%3A','%20'),array('/',':','+'),$input);
}

