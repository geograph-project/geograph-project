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
# general page startup

if (empty($_SERVER['HTTP_USER_AGENT']))
	die("no scraping");

require_once('geograph/global.inc.php');

#header("HTTP/1.0 503 Unavailable");
#$smarty = new GeographPage;
#
#if (!empty($_GET['q']))
#	$smarty->assign('q',$_GET['q']);
#
#$smarty->display("sample8_unavailable.tpl");
#exit;

#########################################
# redirect for non JS clients

if (basename($_SERVER['PHP_SELF']) == 'of.php' && strpos($_SERVER['REQUEST_URI'],'/finder/of.php') === 0) {
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");

	if (!empty($_GET['place']))
        	$url = "/place/".urlencode2($_GET['q']);
	else
	        $url = "/of/".urlencode2($_GET['q']);
	//todo add sorting?
	if (!empty($_GET['sort']))
		$url .= "?sort=".urlencode($_GET['sort']);

        header("Location: ".$url);
        print "<a href=\"".htmlentities2($url)."\">moved</a>";

        exit;
}

#########################################

if (!empty($_GET['q']) && preg_match('/^\/(of|place|near)\/([^\?]+)/',$_SERVER['REQUEST_URI'],$m) && $_GET['q'] != $m[2])
        //fix for /of/B&B    (the & is has already been urldcoded in QUERY_STRING)
        $_GET['q'] = urldecode($m[2]);

if (!empty($_GET['q']))
	//nginx seems to have reencoded the + in the URL as %2B by the time reaches PHP, so reading QUERY_STRING gets %2B, which is then decoded as + (not space!)
	$_GET['q'] = str_replace('+',' ',$_GET['q']);

#########################################

if ($_SERVER['HTTP_HOST'] == 'www.geograph.org.uk') {
	if (!empty($_GET['place']))
	        $mobile_url = "https://m.geograph.org.uk/place/".urlencode2($_GET['q']);
	else
	        $mobile_url = "https://m.geograph.org.uk/of/".urlencode2($_GET['q']);
}

init_session();

$smarty = new GeographPage;

$smarty->assign('responsive',true);

if ($CONF['template']!='ireland') {
	if (!empty($_GET['place']))
                $smarty->assign('welsh_url',"/chwilio/?q=@place+".urlencode(str_replace(array('-',"'"),' ', $_GET['q']))."&lang=cy");
        else
                $smarty->assign('welsh_url',"/chwilio/?q=".urlencode($_GET['q'])."&lang=cy");
        //$smarty->assign('english_url',"/"); //needed by the welsh template!
}


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

	if (mb_detect_encoding($_GET['q'], 'UTF-8, ISO-8859-1') == "UTF-8") {
		$_GET['q'] = utf8_to_latin1($_GET['q']); //even though this page is latin1, browsers can still send us UTF8 queries
	}

	$_GET['q'] = str_replace(" near (anywhere)",'',$_GET['q']);
	if (substr_count($_GET['q'], ' ') > 3 && strpos($_GET['q'],'the ') === 0) {
		$_GET['q'] = str_replace('the ','',$_GET['q']);
	}

	if (preg_match('/^(\(anything\)\s* |)near (.+)$/',$_GET['q'],$m) && !isset($_GET['redir'])) {
		header("Location: /near/".urlencode2($m[2]));
                exit;
	}

	if (!empty($_GET['place'])) {
		//bodge, but the hyphen(s) messes up the placename searching :(
		$_GET['q'] = str_replace(array('-',"'"),' ', $_GET['q']);
	}

        $sphinx = new sphinxwrapper(trim($_GET['q']), true);

	if (!empty($_GET['place'])) {
		$sphinx->q = $sphinx->exact_field_match($sphinx->q,'place');
	}


	$qu = urlencode(trim($_GET['q']));
	$qu2 = urlencode2(trim($_GET['q']));
	$qh = htmlentities2(trim($_GET['q']));

	if (preg_match("/^(-?\d+\.\d+)[, ]+(-?\d+\.\d+)$/",$_GET['q'],$ll) && !isset($_GET['redir'])) {
		header("Location: /near/$qu2");
		exit;
	}

	$smarty->assign("page_title",'Photos of '.preg_replace('/^title:/','',$_GET['q']));
	if (!empty($_GET['place']))
		$smarty->assign('extra_meta', "<link rel=\"canonical\" href=\"{$CONF['SELF_HOST']}/place/$qu2\"/>");
	else
		$smarty->assign('extra_meta', "<link rel=\"canonical\" href=\"{$CONF['SELF_HOST']}/of/$qu2\"/>");
	$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF'].$_GET['q']));

	if ($memcache->valid) {
		$mkey = md5("#".trim($sphinx->q).$_SERVER['HTTP_HOST']).isset($_GET['redir']).$src;
		if (empty($_GET['refresh'])) {
			$str = $memcache->name_get('of-new',$mkey);
			if (!empty($str)) {
				if ($CONF['PROTOCOL'] == "https://") {
					//it may be a http:// page cached!?!
					$str = str_replace('http://',$CONF['PROTOCOL'],$str);
				}

				if (strpos($str,"No keywords Results found.") !== FALSE) {
			                //might be too late, but might as well try!
			                header("HTTP/1.0 404 Not Found");
                			header("Status: 404 Not Found");
				}

				print $str;

				//print "<hr><p style='background-color:purple;color:white;padding:1em;margin:0'>Dissatisfied with these results? <a style='color:yellow' href='#' onclick=\"jQl.loadjQ('/js/search-feedback.js');return false\">Please take this short survey</a>.</p>";

				$smarty->display('_std_end.tpl',md5($_SERVER['PHP_SELF'].$_GET['q']));
				exit;
			}
		}

		ob_start();
	}

	$domains = "site:geograph.ie";
	if ($_SERVER['HTTP_HOST'] != 'www.geograph.ie')
		$domains .= "+OR+site:geograph.org.uk";
	else
		print "<div class=interestBox>This search will mainly show images from Ireland - however some Great Britain may be included. Add the keyword &quot;<tt>Ireland</tt>&quot; to focus the results even more.</div>";

} else {
	$smarty->display('_std_begin.tpl',md5($_SERVER['PHP_SELF'].$_GET['q']));
}

#########################################
# the top of page form

?>
<form onsubmit="location.href = '/of/'+encodeURIComponent(this.q.value); return false;">
<div class="interestBox">
	<? if (!empty($_GET['q'])) { ?>
	<div style="float:right">
		More:
		<a href="/browser/#!/q=<? echo $qu; ?>/display=group/group=decade/n=4/gorder=alpha%20desc">Over Time</a> &middot;
		<a href="/finder/groups.php?q=<? echo $qu; ?>&group=segment">Recent</a> &middot;
		<?
		$db = GeographDatabaseConnection(true);
		//todo memcache!
		if ($tag = $db->getRow("SELECT * FROM tag_stat WHERE tagtext = ".$db->Quote(preg_replace('/[\[\]]+/','',$_GET['q'])))) { ?>
			<a href="/tagged/<? echo urlencode2($tag['tagtext']); ?>">Tagged</a> &middot;
		<? } ?>
		<? if (preg_match("/^\s*([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\s*$/",$_GET['q'])) { ?>
		        <a href="/gridref/<? echo strtoupper($qu2); ?>">Browse Page</a> &middot;
                <? } ?>
		<a href="/browser/#!/q=<? echo $qu; ?>/display=map_dots/pagesize=50">Map</a> &middot;
		<a href="/browser/#!/q=<? echo $qu; ?>">Browser</a> &middot;
		<a href="/finder/multi2.php?q=<? echo $qu; ?>">Others</a>
	</div>
	<? } ?>
	Images of: <input type=search name=q value="<? echo $qh; ?>" size=40 id="mainquery"><input type=submit value=go><br>
<?

#########################################
# display the location results dropdown, for directing to near page.

if (!empty($_GET['q'])) {
	print "<div id=\"location_prompt\"></div>";  $need_client = true;
}

#########################################

?>

</div>
</form>
<div id="correction_prompt"></div>
<?


if (!empty($_GET['q'])) {

#########################################
# yet another catch for nearby queries

	$bits = explode(' near ',$_GET['q']);
	if (count($bits) == 2) {
		print "<div>Looking for keywords '<b>".htmlentities2($bits[0])."</b>' <i>near</i> place '<b>".htmlentities2($bits[1])."</b>'? If so <a href=\"/search.php?q=$qu\">click here</a>.</div><br><br>";
		$sphinx->q = str_replace(' near ',' @(Place,County,Country) ',$sphinx->q);

	} elseif (!empty($prefixMatch) && $prefixMatch > 1 && empty($_GET['place'])) {
		print "<div style=\"font-size:0.9em;padding:4px;border-bottom:1px solid gray\">There are a <a href=\"/finder/groups.php?q=place:$qu&group=place\">number of places matching '".htmlentities2($_GET['q'])."'</a>, below are <b>combined</b> keyword results.";
		print " To search near specific place, select from the dropdown above. Or <a href=\"/browser/#!/q=$qu/display=group/group=place/n=4/gorder=images%20desc\">View images grouped by nearby Place</a></div>";

	} elseif (!empty($db)) {
		//todo - rewrite this to use a full-text index.
		$usercnt = $db->getOne("SELECT COUNT(*) FROM user INNER JOIN user_stat USING (user_id) WHERE realname = ".$db->Quote(trim($_GET['q'])));
		if ($usercnt > 0) {
			print "<div style=\"font-size:0.8em;\">There ".($usercnt>1?'are a number of contributors':'is a contributor')." with this name, below are combined results. Can also <a href=\"/browser/#!/q=$qu/display=group/group=user_id/n=4/gorder=images%20desc\">view images by contributor</a>.</div>";
		}
	}


	$limit = 50;

		$sph = GeographSphinxConnection('sphinxql',true);

                $prev_fetch_mode = $ADODB_FETCH_MODE;
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

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
			print '<iframe src="'.$CONF['TILE_HOST'].'/tile-info.php?id='.$id.'" width="100%" height="250" frameborder=0></iframe>';

		} else {
	                $where = "match(".$sph->Quote($sphinx->q).")";
		}

#########################################
# list some content?

		if (!preg_match('/@|_SEP/',$sphinx->q)) {
			$ids2 = $sph->getCol("SELECT id FROM content_stemmed WHERE match(".$sph->Quote("@title ".$sphinx->q." @source -themed").") LIMIT 5");

			if (!empty($ids2)) {
				$data2 = $sph->getAssoc("SHOW META");

			        if (empty($db))
		        	        $db = GeographDatabaseConnection(true);
		                	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

				$id_list = implode(',',$ids2);
	                        $related = $db->getAll("
        	                         SELECT c.url,c.title,`source`,realname,user_id,images
                	                 FROM content c
                        	         LEFT JOIN user u USING (user_id)
                                	 WHERE c.content_id IN($id_list)
	                                 ORDER BY FIELD(c.content_id,$id_list)");

				print "<div style=\"margin-left:20px;background-color:#DDEA8E;padding:3px\"><p>Potential Collection matches: (<a href=\"/content/?q=$qu&scope=all&in=title\">View all</a>)<ul>";
        	                foreach ($related as $idx => $row) {
					print "<li><a href=\"{$row['url']}\">".htmlentities2($row['title'])."</a> ";
					print $CONF['content_sources'][$row['source']].($row['images']?" with {$row['images']} images":'');
					if ($idx == 3 && $data2['total_found'] > 3) {
						print " &nbsp; &nbsp;&nbsp;<i>... plus at least ".($data2['total_found']-3)." <a href=\"/content/?q=$qu&scope=all&in=title\">more results</a></i></li>";
						break;
					}
					print "</li>";
                                }
				print "</ul></div>";
			}

		}

#########################################

		$rows = array();

#########################################
# special handler to catch entered id numbers

		if (empty($words) && preg_match_all('/(?<!:)\b(\d{2,})\b/',$_GET['q'],$m) && !preg_match("/^\s*([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\s*$/",$_GET['q'])) {
			$rows['single'] = $sph->getAll($sql = "
                                select id,realname,user_id,title,grid_reference
                                from sample8
                                where id IN (".implode(',',$m[1]).") limit ".count($m[1]) );

			if (!empty($_GET['d']))
				print $sql;
			if (!empty($rows['single'])) {
				$s = (count($rows['single'])>1)?'s':'';
				print "<p><i>Including image$s with ID$s: ".implode(', ',$m[1]).".</i></p>";
			}
		}

#########################################
# the main results set!

		//$option = ", ranker=expr('sum(lcs*lccs*user_weight)*1000+bm25')";
		$option = "";

		if (empty($_GET['place']) && (preg_match('/^\w+\s+\w+[\w\s]*$/',$sphinx->q) || $_SERVER['HTTP_HOST'] == 'www.geograph.ie')) {
			//todo - restructure this to use MAYBE!
			$bits = array();
			//todo, if a great many (over 30?) then switch to high quorum?
			$bits[] = '('.$sphinx->q.')';
			if ($_SERVER['HTTP_HOST'] == 'www.geograph.ie') {
		                $bits[] = '('.$sphinx->q.' @country Ireland )';
			}
			if (preg_match('/^\w+\s+\w+[\w\s]*$/',$sphinx->q)) {
		                $bits[] = '"'.$sphinx->q.'"';
			}
		        $where = "match(".$sph->Quote(implode(' | ',$bits)).")";
			$option = ", ranker=expr('sum((word_count+(lcs-1)*max_lcs)*user_weight)')";
		}

restart:

		$rank = 'floor(ln(weight()*weight()))';
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
                        select id,realname,user_id,title,grid_reference, $rank as w2ln $columns
                        from sample8
                        where $where
                        order by $order
                        limit {$limit}
			option field_weights=(place=8,county=6,country=4,title=12,tags=10,imageclass=5)
			, cutoff=1000000 $option ");

if (!empty($_GET['d']))
	print $sql;

		if (empty($data))
			$data = $sph->getAssoc("SHOW META");

#########################################
# retreive a small number of high scoring images

		if (empty($_GET['sort'])) {
			$rows['score'] = $sph->getAll($sql = "
        	                select id,realname,user_id,title,grid_reference, $rank as w2ln $columns
                	        from sample8
                        	where $where
	                        order by score desc
        	                limit 8
				option field_weights=(place=8,county=6,country=4,title=12,tags=10,imageclass=5)
				, max_query_time=800 $option");

			if (empty($data) && !empty($rows['score']))
				$data = $sph->getAssoc("SHOW META");

#########################################
# in case of no matches retry as a quorum search

			if (empty($restarted) && empty($rows['score']) && empty($rows['single']) && preg_match('/\s\w+\s+\w/',$_GET['q'])) {

				print "<div id=\"geocode_results\"></div>";  $need_client = true;

				print "<i>No results found for '".htmlentities($_GET['q'])."', showing results containing only <b>some of the words</b>...</i><br/>";
				$words = $_GET['q'];
	                        $words = str_replace('_SEP_','',$words);
        	                $words = trim(preg_replace('/[^\w]+/',' ',$words));
                	        $where = "match(".$sph->Quote('"'.$words.'"/0.5').")";

				$restarted = true;
				goto restart;
			}
		}

#########################################
# merge all the results into one

		$final = array();
		foreach ($rows as $idx => $arr) {
			if (!empty($arr)) {
				foreach ($arr as $row)
					$final[$row['id']] = $row;
				//unset($rows[$idx]);
			}
		}

#########################################
# some random testing stuff

        print "<br style=clear:both>";

	if (!empty($_GET['d'])) {
		print "<p><a href=\"/search.php?displayclass=map&marked=1&markedImages=".implode(',',array_keys($final))."\">View on Map</a></p>";
	}

	if (count($final) > 1 && preg_match('/^title:\s*(\w.*)/',$_GET['q'],$m)) {
		$ext = '';
		if (!empty($data['total_found']) && $data['total_found'] > count($final))
			$ext = " (of ".number_format($data['total_found'],0)." total)";
		print "<h2>".count($final)."$ext Photos of ".htmlentities2($m[1])."</h2>";
	}

	if (!empty($data['total_found'])) {
		print "<input id=total_found type=hidden value=".intval($data['total_found']).">";
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

		print "<div class=shadow id=thumbs>";
                foreach ($final as $idx => $row) {
			$row['gridimage_id'] = $row['id'];
                        $image = new GridImage();
                        $image->fastInit($row);

?>
          <div style="float:left;position:relative; width:<? echo $thumbw+5;?>px; height:<? echo $thumbw+5;?>px;padding:1px;">
          <div align="center">
          <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities2($image->title) ?> by <? echo htmlentities2($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,$src); ?></a></div>
          </div>
<?

		}

		print "<br style=clear:both></div>";
		if ($src == 'data-src')
			print "<script src=\"".smarty_modifier_revision("/js/lazy.js")."\"></script>";
		print '<script src="/preview.js.php?d=preview" type="text/javascript"></script>';

#########################################
# fallback and use the preview iframe for a few results

	} elseif (!empty($final)) {
		foreach ($final as $idx => $row) {
			print '<iframe src="'.$CONF['TILE_HOST'].'/tile-info.php?id='.$row['id'].'" width="100%" height="250" frameborder=0></iframe>';
			print "<hr/><br/>";
		}
	}

#########################################
# handler for no results

	if (empty($final) && !empty($_GET['place'])) {
		print "<p>No exact place found. ";

		print "<div id=\"location_list\"></div>"; $need_client = true;

	} elseif (empty($final) || (!empty($rows['single']) && count($final) == count($rows['single']))) {

		//might be too late, but might as well try!
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");

		print "<p>No keywords Results found. ";
		print "<span id=\"location_link\"></span>";
		print "</p>";
		print "<div id=\"alternates\"></div>";  $need_client = true;

		if (isset($_GET['redir'])) { //ie redir=false
		        $square=new GridSquare;
		        if (preg_match_all('/\b([a-zA-Z]{1,2}) ?(\d{2,5})(\.\d*|) ?(\d{2,5})(\.*\d*|)\b/',$_GET['q'],$matches)) {
		                $gr = array_pop($matches[0]); //take the last, so that '/near/Grid%20Reference%20in%20C1931/C198310' works!
		                $grid_ok=$square->setByFullGridRef($gr,true,true);
				if ($grid_ok && $square->imagecount) {
					$qnew = urlencode2($square->grid_reference." \"".preg_replace('/[^\w]+/',' ',$_GET['q'])."\"/1");
					print "<p>Or <a href=\"/of/$qnew\" rel=\"nofollow\">View images in {$square->grid_reference}</a> (~{$square->imagecount} images)</p>";
				}
			}
		}
	}

#########################################

if (strlen($_GET['q']) > 10 && preg_match('/\b(19|20|21)(\d{2})\b/',$_GET['q'],$m)) {
	$y = $m[1].$m[2];
	$qw = trim(str_replace($y,'',$_GET['q']));

	print "<p>Looking for dated images of <b>".htmlentities2($qw)."</b>? ";
	print "If so try a <a href=\"/finder/groups.php?q=".urlencode($qw)."&amp;group=takenyear\">Over Time search for ".htmlentities2($qw)."</a>.</p>";
}

#########################################
# footer links

if (!empty($final) && empty($words) && count($final) != @count($rows['single'])) {

	print "<br><div class=interestBox style=color:white;background-color:gray;font-size:1.05em>";
	if (!empty($data['total_found']) && $data['total_found'] > 10) {
		if (count($final) < $data['total_found'])
			print "showing ".count($final)." of ";
		print "About <b>".number_format($data['total_found'])."</b> results. ";
	}

	$bits = array();
	if (!empty($_GET['place']))
		$bits[] = '<a href="/browser/#!/place+%22'.$qu.'%22" style=color:yellow>in the Browser</a>';
	elseif (!preg_match('/\b(user_id|snippet_id|snippet|snippet_title|month|points|viewsquare|grid):.+/',$_GET['q'])) //gi_stemmed fields!, not in new search
		$bits[] = '<a href="/browser/#!/q='.$qu.'" style=color:yellow>in the Browser</a>';

	if (!preg_match('/\b(decade|monthname|user|contexts|subjects|types|buckets|groups|terms|snippets|wikis|distance|direction|format|place|county|country|hash|larger|landcover):.+/',$_GET['q'])) //sample8 fields!, not in old search
		$bits[] = '<a href="/search.php?do=1&amp;searchtext='.(empty($words)?'':'~').$qu.'&amp;sugg_off=1" style=color:yellow>in the standard search</a>';

	if (!empty($bits)) {
		print 'Explore these images more: <b>'.implode('</b> or <b>',$bits)."</b>";
		if (count($bits) > 1) {
			print " (may return slightly different results).";
		}
	}
	print "</div>";

	$suggestions = array();
	if ($data['total_found'] > 60 && strpos($_GET['q'],'/') === FALSE) {
		if (!empty($tag) && strpos($_GET['q'],'[') !== 0) {
			$suggestions[] = '<a href="/of/['.urlencode2($tag['tagtext']).']" rel="nofollow">Images <i>tagged</i> with ['.htmlentities2($tag['tagtext']).']</a>';
		}
		if (strpos($_GET['q'],'"') === FALSE && strpos($_GET['q'],' ') > 3 && strpos($_GET['q'],':') === FALSE)
			$suggestions[] = "<a href=\"/of/%22$qu2%22\" rel=\"nofollow\">Images with <i>phrase</i> &quot;$qh&quot</a>";
		if (strpos($_GET['q'],':') === FALSE) //!empty($decode[0]) && $decode[0]->total_found > 0)  -- THIS means was place match //TODO, hide, this and unhine with ajaz?
			$suggestions[] = "<a href=\"/of/text:$qu2\" rel=\"nofollow\">Pure Keyword Match for '$qh'</a>";
	}
	if (!empty($suggestions)) {
		print "<div class=interestBox>&middot; Too many imprecise results? Try ".implode(' or ',$suggestions)."</div>";
	}

} else {
	print "<hr/>";
}

#########################################
# powered by footer, required by using images search api

print "<div id=\"footer_message\" style=\"text-align:right\"></div>";

#########################################
# if we have some suggestions may as well display them...

if (!empty($_GET['q'])) {
	print "<div id=\"alternates\"></div>";  $need_client = true;
}

#########################################

if (!empty($need_client)) { //TODO, could check $src='data-src'??
	print "<script src=\"".smarty_modifier_revision("/js/finder.js")."\"></script>";
}

#########################################

if ($memcache->valid && $mkey) {
	$str = ob_get_flush();

	$memcache->name_set('of-new',$mkey,$str,$memcache->compress,$memcache->period_long);
}

#########################################
# special footer just for registered users - who have had their default changed

if (!empty($USER->registered)) {
	print "<hr><p>If you looking for different results page, you can <a href=\"/choose-search.php\">choose which search engine to use</a>.</p>";
}

#########################################

//print "<hr><p style='background-color:purple;color:white;padding:1em;margin:0'>Dissatisfied with these results? <a style='color:yellow' href='#' onclick=\"jQl.loadjQ('/js/search-feedback.js');return false\">Please take this short survey</a>.</p>";

#########################################

        $smarty->display('_std_end.tpl',md5($_SERVER['PHP_SELF'].$_GET['q']));
        exit;

#########################################
# fallback if not using magic - but never used

} elseif (!empty($_GET['q'])) {
	print '<iframe src="/finder/search-service.php?q='.$qu.'" width="700" height="700" name="searchwindow" style="width:100%"></iframe>';
        $smarty->display('_std_end.tpl',md5($_SERVER['PHP_SELF'].$_GET['q']));
        exit;
}

#########################################
# do something instead of an empty page when no query...

	if (empty($db))
		$db = GeographDatabaseConnection(true);
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$list = $db->getAll("SELECT * FROM geograph_tmp.random_images where moderation_status = 'geograph'");

	if (!empty($list)) {

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
                                <div style="float:left;" class="photo33"><div style="height:<? echo $thumbh; ?>px;vertical-align:middle"><a title="<? echo $image->grid_reference; ?> : <? echo htmlentities2($image->title) ?> by <? echo htmlentities2($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,$src); ?></a></div>
                                <div class="caption"><div class="minheightprop" style="height:2.5em"></div><a href="/gridref/<? echo $image->grid_reference; ?>"><? echo $image->grid_reference; ?></a> : <a title="view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo htmlentities2($image->title); ?></a><div class="minheightclear"></div></div>
                                <div class="statuscaption">by <a href="<? echo $image->profile_link; ?>"><? echo htmlentities2($image->realname); ?></a></div>
                                </div>
<?
                }
		print "<br style=\"clear:both\"/>";
	} else {
		print "nothing to display";
	}

	if ($src == 'data-src')
		print "<script src=\"".smarty_modifier_revision("/js/lazy.js")."\"></script>";
	print '<script src="/preview.js.php" type="text/javascript"></script>';

	$smarty->display('_std_end.tpl',md5($_SERVER['PHP_SELF'].$_GET['q']));

