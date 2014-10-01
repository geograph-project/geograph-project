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
	if (substr_count($_GET['q'], ' ') > 3 && strpos($_GET['q'],'the ') === 0) {
		$_GET['q'] = str_replace('the ','',$_GET['q']);
	}

	if (preg_match('/^(\(anything\)\s* |)near (.+)$/',$_GET['q'],$m) && !isset($_GET['redir'])) {
		header("Location: /near/".urlencode2($m[2]));
                exit;
	}


        $sphinx = new sphinxwrapper(trim($_GET['q']));

	$qu = urlencode(trim($_GET['q']));
	$qu2 = urlencode2(trim($_GET['q']));
	$qh = htmlentities(trim($_GET['q']));

	if (preg_match("/^(-?\d+\.\d+)[, ]+(-?\d+\.\d+)$/",$_GET['q'],$ll) && !isset($_GET['redir'])) {
		header("Location: /near/$qu2");
		exit;
	}

	$smarty->assign("page_title",'Photos of '.$_GET['q']);
	$smarty->display("_std_begin.tpl",$_SERVER['PHP_SELF'].md5($_GET['q']));

	if ($memcache->valid) {
		$mkey = md5(trim($_GET['q']).$_SERVER['HTTP_HOST']).isset($_GET['redir']).$src;
		$str =& $memcache->name_get('of',$mkey);
		if (!empty($str)) {
			print $str;
			$smarty->display('_std_end.tpl');
			exit;
		}

		ob_start();
	}

	$domains = "site:geograph.ie";
	if ($_SERVER['HTTP_HOST'] != 'www.geograph.ie')
		$domains .= "+OR+site:geograph.org.uk";

	$remotes = parallel_get_contents(array(
		"http://www.geograph.org.uk/finder/places.json.php?q=$qu&new=1",
		"http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=$qu+$domains&userip=".getRemoteIP(),
		"http://suggestqueries.google.com/complete/search?output=toolbar&hl=en&q=$qu"
	));
//if many words? "https://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20contentanalysis.analyze%20where%20text%3D'london+bridge'%3B&diagnostics=true&format=json"



	foreach ($remotes as $idx => $remote) {
		if (strlen($remote) > 40 && $idx != 2) { //awkward, but 2 is not json!
			list($header,$body) = explode("\n\n",str_replace("\r",'',$remote),2);
        	        $decode[$idx] = json_decode($body);
			unset($remotes[$idx]);//save some memory, ha!
	        }
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
		<?
		$db = GeographDatabaseConnection(true);
		//todo memcache!
		if ($tag = $db->getRow("SELECT * FROM tag WHERE status = 1 AND tag = ".$db->Quote($_GET['q']))) { ?>
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

	if (!empty($decode[0])) {
		if ($decode[0]->total_found == 1) {
			$object = $decode[0]->items[0];
			if (strpos($object->name,$object->gr) === false)
                                 $object->name .= "/{$object->gr}";
			if (strpos($object->name,'Grid Reference') === 0)
				$h = str_replace('/','/ <b>',htmlentities($object->name)).'</b>';
			else
				$h = '<b>'.str_replace('/','</b> /',htmlentities($object->name));
                        print "Or view images <i>near</i> <a href=\"/near/".urlencode2($object->name)."\">$h</a>";

		} else if ($decode[0]->total_found > 0) {
			$prefixMatch = 0;
			print "Or view images <i>near</i> <select onchange=\"location.href = '/near/'+encodeURI(this.value);\"><option value=''>Choose Location...</option>";
			foreach ($decode[0]->items as $object) {
				if (strpos(strtolower($object->name),strtolower($_GET['q'])) === 0)
					$prefixMatch++;
				if (strpos($object->name,$object->gr) === false)
                                	$object->name .= "/{$object->gr}";
                                printf('<option value="%s"%s>%s</option>', $val = $object->name, ($gr == $object->gr)?' selected':'',
                                        str_replace('/',' &nbsp; ',$object->name).($object->localities?", ".$object->localities:''));
			}

			print '<optgroup></optgroup>';
			if (!empty($decode[0]->query_info))
				printf('<optgroup label="%s"></optgroup>', $decode[0]->query_info);
			if (!empty($decode[0]->copyright))
				printf('<optgroup label="%s"></optgroup>', $decode[0]->copyright);
			print "</select> ({$decode[0]->total_found})";
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

	} elseif (!empty($prefixMatch) && $prefixMatch > 1) {
		print "<div style=\"font-size:0.8em;\">There are a <a href=\"/finder/groups.php?q=place:$qu&group=place\">number of places matching '".htmlentities($_GET['q'])."'</a>, below are combined results. To search a specific one, select from the dropdown above.</div>";
	}


	$limit = 50;

                $prev_fetch_mode = $ADODB_FETCH_MODE;
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

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
			$sphinx->q = preg_replace('/@text\b/','@(title,comment,imageclass,tags)',$sphinx->q);

	                $where = "match(".$sph->Quote($sphinx->q).")";
		}

                //convert gi_stemmed -> sample8 format.
                $where = preg_replace('/@by/','@realname',$where);
                $where = preg_replace('/__TAG__/i','_SEP_',$where);

		$rows = array();

#########################################
# special handler to catch entered id numbers

		if (preg_match_all('/\b(\d{2,})\b/',$_GET['q'],$m) && !preg_match("/^\s*([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\s*$/",$_GET['q'])) {
			$rows['single'] = $sph->getAll($sql = "
                                select id,realname,user_id,title,grid_reference
                                from sample8
                                where id IN (".implode(',',$m[1]).") limit ".count($m[1]) );

			if (!empty($_GET['d']))
				print $sql;
			print "<p>Includings images with ID(s): ".implode(', ',$m[1]).".</p>";
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

			if (empty($rows['score']) && empty($rows['single']) && preg_match('/\s\w+\s+\w/',$_GET['q'])) {

				$idx = count($decode);
				$body = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=$qu&key=AIzaSyCC1etuG6mND1hsTjgIIIsVceKxq8g5d5k&region=uk");
				$decode[$idx] = json_decode($body);

				if ($decode[$idx] && $decode[$idx]->results && $decode[$idx]->status == 'OK') {
					$r = reset($decode[$idx]->results);
					$coord = $r->geometry->location->lat.','.$r->geometry->location->lng;
					print "<div style=\"float:right\"><a href=\"/near/$coord\"><img src=\"https://maps.googleapis.com/maps/api/staticmap?markers=size:mid|$coord&zoom=13&key=AIzaSyDrnpX8oponupk5rMqCg126cuVtiypmIH0&size=250x120&maptype=terrain\"></a> ";
					print "<a href=\"/near/$coord\"><img src=\"https://maps.googleapis.com/maps/api/staticmap?markers=size:mid|$coord&zoom=7&key=AIzaSyDrnpX8oponupk5rMqCg126cuVtiypmIH0&size=250x120&maptype=terrain\"></a></div>";

					print "Looks like you might of been entering an address? If you searching for '";
					print "<a href=\"/near/$coord\">".htmlentities($r->formatted_address)."</a>";
					print "'. Click the map on the right to view images near that location.";
					print "<hr style=\"clear:both\"/>";
				}

				print "<i>No results found for '".htmlentities($_GET['q'])."', showing results containing only <b>some of the words</b>...</i><br/>";
				$words = $_GET['q'];
	                        $words = str_replace('_SEP_','',$words);
        	                $words = trim(preg_replace('/[^\w]+/',' ',$words));
                	        $where = "match(".$sph->Quote('"'.$words.'"/0.5').")";
			}
		}

#########################################
# get 4 results from Google Images!

		if (!empty($decode[1])) {
			$ids = array();
			foreach ($decode[1]->responseData->results as $result) {
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
                			        limit ".count($ids) );
		}

#########################################
# the main results set!

		//$option = ", ranker=expr('sum(lcs*lccs*user_weight)*1000+bm25')";
		$option = "";


		if (preg_match('/^\w+\s+\w+[\w\s]*$/',$_GET['q']) || $_SERVER['HTTP_HOST'] == 'www.geograph.ie') {
			//todo - restructure this to use MAYBE!
			$bits = array();
			//todo, if a great many (over 30?) then switch to high quorum?
			$bits[] = '('.$_GET['q'].')';
			if ($_SERVER['HTTP_HOST'] == 'www.geograph.ie') {
		                $bits[] = '('.$_GET['q'].' @country Ireland )';
			}
			if (preg_match('/^\w+\s+\w+[\w\s]*$/',$_GET['q'])) {
		                $bits[] = '"'.$_GET['q'].'"';
			}
		        $where = "match(".$sph->Quote(implode(' | ',$bits)).")";
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
			option field_weights=(place=8,county=6,country=4,title=12,tags=10,imageclass=5)
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

if (!empty($_GET['d'])) {
	print "<p><a href=\"/search.php?displayclass=map&marked=1&markedImages=".implode(',',array_keys($final))."\">View on Map</a></p>";
}



#########################################
# display normal thumbnail results!

	if (count($final) > 3) {
		if (count($final) <= 12) {
			$thumbw = 213;
			$thumbh = 160;
		} else {
			$thumbw = 120;
			$thumbh = 120;
		}

		print "<div id=thumbs>";
                foreach ($final as $idx => $row) {
			$row['gridimage_id'] = $row['id'];
                        $image = new GridImage();
                        $image->fastInit($row);

?>
          <div style="float:left;position:relative; width:<? echo $thumbw;?>px; height:<? echo $thumbw;?>px;padding:1px;">
          <div align="center">
          <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,$src); ?></a></div>
          </div>
<?

		}

		print "<br style=clear:both></div>";
		if ($src == 'data-src')
			print '<script src="http://'.$CONF['STATIC_HOST'].'/js/lazy.v2.js" type="text/javascript"></script>';
		print '<script src="/preview.js.php?d=preview" type="text/javascript"></script>';

#########################################
# fallback and use the preview iframe for a few results

	} elseif (count($final)) {
		foreach ($final as $idx => $row) {
			print '<iframe src="http://t0.geograph.org.uk/tile-info.php?id='.$row['id'].'" width="100%" height="250" frameborder=0></iframe>';
			print "<hr/><br/>";
		}
	}

#########################################
# handler for no results

	if (empty($final) || count($final) == count($rows['single'])) {
		print "<p>No keywords Results found. ";

        	if (!empty($decode[0]) && $decode[0]->total_found > 0) {
                        $object = $decode[0]->items[0];
			if ($decode[0]->total_found == 1 && !isset($_GET['redir']))
				print " Redirecting to a location based search... <script>location.href='/near/".urlencode2($object->name)."';</script>";
                        if (strpos($object->name,$object->gr) === false && $decode[0]->total_found > 1)
                                $object->name .= "/{$object->gr}";
			print "Or try a <a href=\"/near/".urlencode2($object->name)."\">search for images <i>near</i> <b>".htmlentities($object->name)."</b></a>.";
		}
		print "</p>";

		if (!empty($remotes[2])) {
			if (preg_match_all('/ data="(.+?)"/',$remotes[2],$m)) {
				$bits = array();
				foreach ($m[1] as $item) {
					$sph->query("SELECT id FROM sample8 WHERE MATCH(".$sph->quote($item).") LIMIT 0");
					$data = $sph->getAssoc("SHOW META");
					if (!empty($data['total_found'])) {
						$h = ($data['total_found'] > 100)?"<b>".htmlentities($item)."</b>":htmlentities($item);
						$bits[] = "<span class=nowrap><a href=\"/of/".urlencode($item)."\">$h</a> (~{$data['total_found']} images)</span>";
					}
				}
				if (!empty($bits))
					print "<p>Alternative Queries: ".implode(" &middot; ",$bits)."</p>";
			}
		}

		if (isset($_GET['redir'])) { //ie redir=false
		        $square=new GridSquare;
		        if (preg_match_all('/\b([a-zA-Z]{1,2}) ?(\d{2,5})(\.\d*|) ?(\d{2,5})(\.*\d*|)\b/',$_GET['q'],$matches)) {
		                $gr = array_pop($matches[0]); //take the last, so that '/near/Grid%20Reference%20in%20C1931/C198310' works!
		                $grid_ok=$square->setByFullGridRef($gr,true,true);
				if ($grid_ok && $square->imagecount) {
					$qnew = urlencode2($square->grid_reference." \"".preg_replace('/[^\w]+/',' ',$_GET['q'])."\"/1");
					print "<p>Or <a href=\"/of/$qnew\">View images in {$square->grid_reference}</a> (~{$square->imagecount} images)</p>";
				}
			}
		}
}

#########################################

if (strlen($_GET['q']) > 10 && preg_match('/\b(19|20|21)(\d{2})\b/',$_GET['q'],$m)) {
	$y = $m[1].$m[2];
	$qw = trim(str_replace($y,'',$_GET['q']));

	print "<p>Looking for dated images of <b>".htmlentities($qw)."</b>? ";
	print "If so try a <a href=\"/finder/groups.php?q=".urlencode($qw)."&amp;group=takenyear\">Over Time search for ".htmlentities($qw)."</a>.</p>";
}

#########################################
# footer links

if (!empty($final) && empty($words) && count($final) != count($rows['google']) && count($final) != count($rows['single'])) {
	print "<br/><div class=interestBox>";
	if (!empty($data['total_found']) && $data['total_found'] > 10)
		print "About ".number_format($data['total_found'])." results. ";
	print '<a href="/browser/#!/q='.$qu.'"><b>Explore these images more</b> in the Browser</a> or ';
	print '<a href="/search.php?do=1&searchtext='.(empty($words)?'':'~').$qu.'">in the standard search</a> (may return slightly different results).';
	$suggestions = array();
	if ($data['total_found'] > 60) {
		if (!empty($tag) && strpos($_GET['q'],'[') !== 0) {
			$t = $tag['tag']; //we need to use the actual tag, rather than the query, because it might be a prefixed tag!
			if (!empty($tag['prefix']))
                                $t = $tag['prefix'].':'.$t;
			$suggestions[] = '<a href="/of/['.urlencode($t).']">Images <i>tagged</i> with ['.htmlentities($t).']</a>';
		}
		if (strpos($_GET['q'],'"') !== 0 && strpos($_GET['q'],' ') > 3)
			$suggestions[] = "<a href=\"/of/%22$qu2%22\">Images with <i>phrase</i> &quot;$qh&quot</a>";
		if (strpos($_GET['q'],':') !== 0 && !empty($decode[0]) && $decode[0]->total_found > 0)
			$suggestions[] = "<a href=\"/of/text:$qu2\">Pure Keyword Match for '$qh'</a>";
	}
	if (!empty($suggestions)) {
		print "<br/>&middot; To many imprecise results? Try ".implode(' or ',$suggestions);
	}
	print "</div>";

} elseif (!empty($final) && count($final) == count($rows['google'])) {
	print "<br/><div class=interestBox>";
	print '<a href="https://www.google.co.uk/search?q='.$qu.'+site:geograph.org.uk+OR+site:geograph.ie&amp;tbm=isch" target=_blank><b>Explore these images more</b> via Google Images</a>';

	if ($decode[1]->responseData->cursor && $decode[1]->responseData->cursor->estimatedResultCount) {
		print " (Estimated <b>".intval($decode[1]->responseData->cursor->estimatedResultCount)."</b> Results)";
	}

	print "</div>";
} else {
	print "<hr/>";
}

#########################################
# powered by footer, required by using images search api

if (!empty($decode[1])) {
	print "<div style=\"text-align:right\">";
	if (count($final) != count($rows['google']))
		print "<i>Results in part</i> ";
	print "Powered by Google</div>";
}

#########################################
# if we have some suggestions may as well display them...

if (!empty($final) && !empty($remotes[2])) {
	if (preg_match_all('/ data="(.+?)"/',$remotes[2],$m)) {
		$bits = array();
		foreach ($m[1] as $item) {
			if (preg_match('/^'.preg_quote(strtolower($_GET['q']),'/').'\b/',$item)) //skip prefix expansions
				continue;
			$sph->query("SELECT id FROM sample8 WHERE MATCH(".$sph->quote($item).") LIMIT 0");
			$data = $sph->getAssoc("SHOW META");
			if (!empty($data['total_found']))
				$bits[] = "<span class=nowrap><a href=\"/of/".urlencode($item)."\">".htmlentities($item)."</a> (~{$data['total_found']} images)</span>";
		}
		if (!empty($bits))
			print "<p>Alternative Queries: ".implode(" &middot; ",$bits)."</p>";
	}
}

#########################################

if ($memcache->valid && $mkey) {
	$str = ob_get_flush();

	$memcache->name_set('of',$mkey,$str,$memcache->compress,$memcache->period_long);
}

#########################################
# special footer just for registered users - who have had their default changed

if (!empty($USER->registered)) {
	print "<p>If you prefer the traditional search, you can <a href=\"/choose-search.php\">choose your default search engine to use</a>.</p>";
	if (false && $CONF['forums']) {
		print "<p>Having trouble with this page? <a href=\"/discuss/index.php?&action=vthread&forum=12&topic=26439\">Please let us know on the discussion forum</a>,
		or fill out <a href='https://docs.google.com/forms/d/1EghtKiKGkLbLUJ1gBAMiENNgMChQotBwI3n7XSyw1z0/viewform' target=_blank>Feedback Form</a>, thank you!</p>";
	}
} elseif (false) {
	print "<p>Have feedback on this search? <a href='https://docs.google.com/forms/d/1EghtKiKGkLbLUJ1gBAMiENNgMChQotBwI3n7XSyw1z0/viewform' target=_blank>please let us know</a>!</p>";
}


#########################################


if ($src == 'data-src') { //because we need jQuery!

	print "<p id=votediv>Have these results helped you today? Rate these results: ";

	$id = 1;
	$qstr = "'".urlencode($_GET['q'])."'";
	$names = array('','Hmm','Below average','So So','Good','Excellent');
	foreach (range(1,5) as $i) {
		print "<a href=\"javascript:void(vote_log('of',$qstr,$i));\" title=\"{$names[$i]}\"><img src=\"http://{$CONF['STATIC_HOST']}/img/star-light.png\" width=\"14\" height=\"14\" alt=\"$i\" onmouseover=\"star_hover($id,$i,5)\" onmouseout=\"star_out($id,5)\" name=\"star$i$id\"/></a>";
	}

	print " (1 no much, 5 very much, <a href=\"/help/voting\">more</a>)</p>";
?>
<script>
function vote_log(action,param,value) {
   $.ajax({
      url: '/stuff/record_usage.php',
      data: {action: action,param: param,value: value},
      xhrFields: { withCredentials: true }
   });
        document.getElementById("votediv").innerHTML = "Thank you!";
	setTimeout(function() {
		document.getElementById("votediv").style.display='none';
	},3000);
}
</script>
<?
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

	print "<p>Enter a search above, for example: ";

	if ($_SERVER['HTTP_HOST'] == 'www.geograph.ie') {
		print '&middot; <a href="/of/castle">castles</a> ';
		print '&middot; <a href="/of/dublin">dublin</a> ';
		print '&middot; <a href="/of/canals">canals</a> ';
		print 'or <a href="/near/limerick">limerick</a> ';
	} else {
		print '&middot; <a href="/of/pylons">pylons</a> ';
		print '&middot; <a href="/of/tobermory">tobermory</a> ';
		print '&middot; <a href="/of/canals">canals</a> ';
		print 'or <a href="/near/newquay">newquay</a> ';
	}

	print "<p>In the meantime here are some random images...</p>";

                $thumbw = 213;
                $thumbh = 160;
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
		print '<script src="http://'.$CONF['STATIC_HOST'].'/js/lazy.v2.js" type="text/javascript"></script>';
	print '<script src="/preview.js.php" type="text/javascript"></script>';

	$smarty->display('_std_end.tpl');
	exit;

#########################################
# functions!

function urlencode2($input) {
        return str_replace(array('%2F','%3A','%20'),array('/',':','+'),urlencode($input));
}

//qudos:  http://wezfurlong.org/blog/2005/may/guru-multiplexing/
function parallel_get_contents($urls, $timeout = 3) {

  $status = $sockets = $strs = $paths = $hosts = array();

  /* Initiate connections to all the hosts simultaneously */
  foreach ($urls as $id => $url) {
    $a = parse_url($url);
    $s = stream_socket_client($a['host'].":80", $errno, $errstr, $timeout,
        STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT);
    if ($s) {
        $sockets[$id] = $s;
        $status[$id] = "in progress";
	$strs[$id] = '';
	$hosts[$id] = $a['host'];
	$paths[$id] = $a['path'].'?'.$a['query']; //always assume there is a query!
    } else {
        $status[$id] = "failed, $errno $errstr";
    }
  }

  /* Now, wait for the results to come back in */
  while (count($sockets)) {
    $read = $write = $sockets;
    /* This is the magic function - explained below */
    $n = stream_select($read, $write, $e = null, $timeout);
    if ($n > 0) {
        /* readable sockets either have data for us, or are failed
         * connection attempts */
        foreach ($read as $r) {
            $id = array_search($r, $sockets);
            $data = fread($r, 8192);
            if (strlen($data) == 0) {
                if ($status[$id] == "in progress") {
                    $status[$id] = "failed to connect";
                }
                fclose($r);
                unset($sockets[$id]);
            } else {
                $strs[$id] .= $data;
            }
        }
        /* writeable sockets can accept an HTTP request */
        foreach ($write as $w) {
            $id = array_search($w, $sockets);
            fwrite($w, "GET {$paths[$id]} HTTP/1.0\r\nHost: {$hosts[$id]}\r\nConnection: close\r\n\r\n");
            $status[$id] = "waiting for response";
        }
    } else {
        /* timed out waiting; assume that all hosts associated
         * with $sockets are faulty */
        foreach ($sockets as $id => $s) {
            $status[$id] = "timed out " . $status[$id];
        }
        break;
    }
  }
  return $strs;
}
