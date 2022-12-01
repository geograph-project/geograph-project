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
#$smarty->display("sample8_unavailable.tpl");
#exit;

#########################################
# redirect for non JS clients

if (strpos($_SERVER['REQUEST_URI'],'/finder/near.php') === 0) {
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");

        $url = "/near/".urlencode2($_GET['q']); $sep = '?';
	//todo add sorting?
	if (!empty($_GET['filter'])) {
		$url .= $sep."filter=".urlencode($_GET['filter']); $sep = '&'; }
	if (!empty($_GET['dist'])) {
		$url .= $sep."dist=".intval($_GET['dist']); $sep = '&'; }
	if (!empty($_GET['sort'])) {
		$url .= $sep."sort=".urlencode($_GET['sort']); $sep = '&'; }

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

if (preg_match('/q=.+&q=([^&]+)/',$_SERVER['QUERY_STRING'],$m))
	//the first q= has been extracted above, need to extract the second one into the filter var!!!
	$_GET['filter'] = urldecode($m[1]);

#########################################

init_session();

$smarty = new GeographPage;

$smarty->assign('responsive',true);

if ($CONF['template']!='ireland') {
        $smarty->assign('welsh_url',"/chwilio/?loc=".urlencode($_GET['q'])."&lang=cy");
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

$distance = 10000; //meters!
if (!empty($_GET['dist']))
	$distance = intval($_GET['dist']);
if ($distance < 1) $distance = 1;
if ($distance > 20000) $distance = 20000;


$qh = $qu = ''; $qfiltbrow = ''; $qfiltmain = '';
if (!empty($_GET['q'])) {

	if (mb_detect_encoding($_GET['q'], 'UTF-8, ISO-8859-1') == "UTF-8") {
		$_GET['q'] = utf8_to_latin1($_GET['q']); //even though this page is latin1, browsers can still send us UTF8 queries
	}


	$qu = urlencode(trim($_GET['q']));
	$qu2 = urlencode2(trim($_GET['q']));
	$qh = htmlentities2(trim($_GET['q']));

	$sphinxq = '';

	$mkey = md5('#'.trim($_GET['q']).$src.$distance);
	if (!empty($_GET['filter'])) {
		$sphinx = new sphinxwrapper(trim($_GET['filter']), true); //this is for sample8 index.
		$sphinxq = $sphinx->q;
		$mkey = md5($sphinxq.'.'.$mkey);
		$qfiltbrow = "/q=".urlencode($sphinxq);
		$qfiltmain = "&searchtext=".urlencode($sphinxq);
	}

	$smarty->assign("page_title",'Photos near '.$_GET['q']);
	$smarty->assign('extra_meta', "<link rel=\"canonical\" href=\"{$CONF['SELF_HOST']}/near/$qu2\"/>");
	$smarty->display("_std_begin.tpl",substr(md5($_SERVER['PHP_SELF']),0,6).$mkey);

	if ($memcache->valid) {
		$str = $memcache->name_get('near',$mkey);
		if (!empty($str)) {
                        if ($CONF['PROTOCOL'] == "https://") {
                                //it may be a http:// page cached!?!
                                $str = str_replace('http://',$CONF['PROTOCOL'],$str);
                        }

			if (strpos($str,"No Results found.") !== FALSE) {
		                //might be too late, but might as well try!
		                header("HTTP/1.0 404 Not Found");
               			header("Status: 404 Not Found");
			}

			print $str;

			//print "<hr><p style='background-color:purple;color:white;padding:1em;margin:0'>Dissatisfied with these results? <a style='color:yellow' href='#' onclick=\"jQl.loadjQ('/js/search-feedback.js');return false\">Please take this short survey</a>.</p>";

			$smarty->display('_std_end.tpl',substr(md5($_SERVER['PHP_SELF']),0,6).$mkey);
			exit;
		}

		ob_start();
	}

	if (preg_match("/^(\d+),\s*(\d+)\s*([OSIGB]*)$/i",$_GET['q'],$ee)) {
                require_once('geograph/conversions.class.php');
                $conv = new Conversions;
		$e = intval($ee[1]);
		$n = intval($ee[2]);
		$reference_index = (stripos($ee[3],'i')!==FALSE)?2:1;
		list($gr,$len) = $conv->national_to_gridref($e,$n,null,$reference_index,false);

		$_GET['q'] = $gr;

	} elseif (preg_match("/^(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)$/",$_GET['q'],$ll)) {
                require_once('geograph/conversions.class.php');
                $conv = new Conversions;

		list($e,$n,$reference_index) = $conv->wgs84_to_national($ll[1],$ll[2],true);
		list($gr,$len) = $conv->national_to_gridref($e,$n,10,$reference_index,false);

		$_GET['q'] = $gr;

	} else {
		$str = file_get_contents("http://www.geograph.org.uk/finder/places.json.php?q=$qu&new=1");
		if (strlen($str) > 40) {
        		$decode = json_decode($str);
		}
	}

	$square=new GridSquare;
	if (preg_match_all('/\b([a-zA-Z]{1,2}) ?(\d{2,5})(\.\d*|) ?(\d{2,5})(\.*\d*|)\b/',$_GET['q'],$matches)) {
		$gr = array_pop($matches[0]); //take the last, so that '/near/Grid%20Reference%20in%20C1931/C198310' works!
	        $grid_ok=$square->setByFullGridRef($gr,true,true);
		$gru = urlencode(str_replace(' ','',$gr));
		$location = "grid reference";
	} elseif (!empty($decode) && !empty($decode->total_found)) {
		$gr = $decode->items[0]->gr;
		$grid_ok=$square->setByFullGridRef($gr,true,true);
		$gru = urlencode(str_replace(' ','',$gr));
		$location = "location";
		if (strpos($decode->items[0]->name,'Grid') !== FALSE)
			$location = "grid reference";
		elseif (strpos($decode->items[0]->name,'Postcode') !== FALSE)
			$location = "postcode";
	}

	//for some unexplainable reason, setByFullGridRef SOMETIMES returns false, and fails to set nateastings - even though allow-zero-percent is set. Fix that...
	if (!$square->nateastings && $square->x && $square->y) {
		require_once('geograph/conversions.class.php');
                $conv = new Conversions;
		list($e,$n,$reference_index) = $conv->internal_to_national($square->x,$square->y);
		$square->nateastings = $e;
		$square->natnorthings = $n;
		$square->reference_index = $reference_index;
		$grid_ok = 1;
	}

	if (!empty($grid_ok)) {
	        require_once('geograph/conversions.class.php');
        	$conv = new Conversions;

		$e = floor($square->nateastings/1000);
                $n = floor($square->natnorthings/1000);

		//todo - make the radius dynamic (maybeing checking square->imagecount as a proxy for now popular the area is
		// - also should be redone with geoTiles from facet-functions
		$d = 10; //units is km! but need in 10km hectad resoilution for now
		$d = ceil($distance/10000)*10;

			$grs = array();
                        for($x=$e-$d;$x<=$e+$d;$x+=10) {
                                for($y=$n-$d;$y<=$n+$d;$y+=10) {
                                        list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,2,$square->reference_index,false);
                                        if (strlen($gr2) > 2)
                                                $grs[] = $gr2;
                                }
                        }
                        $sphinxq .= " @hectad (".join(" | ",$grs).")";

		$qu = urlencode(trim($sphinxq));
	} else {
		print "<!-- Couldn't identify Grid Reference -->";
	}

} else {
	$mkey = ''; //used by the footer too
	$smarty->display('_std_begin.tpl',substr(md5($_SERVER['PHP_SELF']),0,6).$mkey);
}

#########################################
# the top of page form

?>
<form onsubmit="location.href = '/near/'+encodeURI(this.elements['q'].value)+(this.elements['filter']?'?filter='+encodeURI(this.elements['filter'].value):''); return false;">
<div class="interestBox">
	<? if (!empty($_GET['q'])) { ?>
	<div style="float:right">
		More:
		<a href="/of/<? echo urlencode2($_GET['q']); ?>?redir=false" rel="nofollow">Keyword Search</a> &middot;
		<a href="/finder/groups.php?q=<? echo $qu; ?>&group=decade">Over Time</a> &middot;
                <a href="/gridref/<? echo strtoupper($gru); ?>">Browse Page</a> &middot;
		<? if (!empty($square->reference_index) && $square->reference_index == 1) { ?>
		        <a href="/search.php?do=1&gridref=<? echo $gru.$qfiltmain; ?>&amp;displayclass=map">OS Map</a> &middot;
		        <a href="/finder/dblock.php?gridref=<? echo $gru; ?>">D-block</a> &middot;
		<? } else { ?>
			<a href="/browser/#!<? echo $qfiltbrow; ?>/loc=<? echo $gru; ?>/dist=<? echo $distance; ?>/display=map_dots/pagesize=50"><b>Map</b></a> &middot;
		<? } ?>
		<a href="/browser/#!<? echo $qfiltbrow; ?>/loc=<? echo $gru; ?>/pagesize=50">Browser</a> &middot;
	</div>
	<? } ?>
	Images near: <input type=search name=q value="<? echo $qh; ?>" size=40><input type=submit value=go><br/>
<?
	if (isset($_GET['filter'])) {
		print "Matching: <input type=text name=filter value=\"".htmlentities2($_GET['filter'])."\">";
	}

#########################################
# display the location results dropdown, for directing to near page.

if (!empty($_GET['q'])) {

	if (!empty($decode)) {
		if ($decode->total_found == 1) {
			$object = $decode->items[0];
			$object->name = utf8_decode($object->name);
			if (strpos($object->name,$object->gr) === false)
                                 $object->name .= " / {$object->gr}";
			print "Matched Location: <b>{$object->name}</b>".($object->localities?", ".$object->localities:'');

		} elseif ($decode->total_found > 0) {
			print "Possible Locations: <select onchange=\"location.href = '/near/'+encodeURI(this.value);\"><option value=''>Choose Location...</option>";
			foreach ($decode->items as $object) {
				$object->name = utf8_decode($object->name);
				if (strpos($object->name,$object->gr) === false)
                                	$object->name .= "/{$object->gr}";
                                printf('<option value="%s"%s>%s</option>', $val = $object->name, ($gr == $object->gr)?' selected':'',
                                        preg_replace('/\/([A-Z]{1,2}\d+)/',' &middot; $1',$object->name).($object->localities?", ".$object->localities:''));
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

	$sph = GeographSphinxConnection('sphinxql',true);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


	if (!empty($decode) && $decode->total_found == 1) {
		$db = GeographDatabaseConnection(true);
	        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$suggestions = array();
		$name = $db->Quote($plain = preg_replace('/\/.+$/','',$_GET['q']));

		if ($tag = $db->getRow("SELECT * FROM tag WHERE status = 1 AND tag = $name ORDER BY prefix='place' DESC,prefix='near' DESC")) {
			$t = $tag['tag']; //we need to use the actual tag, rather than the query, because it might be a prefixed tag!
                        if (!empty($tag['prefix']))
                                $t = $tag['prefix'].':'.$t;
       	                $suggestions[] = '<a href="/of/['.urlencode($t).']" rel="nofollow">Images <i>tagged</i> with ['.htmlentities2($t).']</a>';
		}
		$name2= $db->Quote("$plain/{$square->grid_reference}");
		if ($place = $db->getRow("SELECT * FROM sphinx_placenames WHERE (Place = $name OR Place = $name2) AND images > 0")) {
			$suggestions[] = '<a href="/place/'.urlencode2($place['Place']).'" rel="nofollow">'.$place['images'].' Images <i>nearest</i> '.htmlentities2($place['Place']).', '.htmlentities2($place['County']).'</a>';
		}

		if (!empty($grid_ok) && !empty($square->nateastings) && $square->natgrlen == 4 && $square->reference_index == 1) {
			$sql = sprintf("SELECT geometry_x,geometry_y,dist FROM opennames WHERE MATCH(%s)  AND geometry_x BETWEEN %d AND %d  AND geometry_y BETWEEN %d AND %d",
				$db->Quote("@(name1,name2) $plain"), $square->nateastings-4000, $square->nateastings+4000, $square->natnorthings-4000, $square->natnorthings+4000);
			if ($row = $sph->getRow($sql)) {
				list($gr,$len) = $conv->national_to_gridref($row['geometry_x'],$row['geometry_y'],8,$square->reference_index,false);
				$row['dist'] = round($row['dist'],-2);
				$suggestions[] = 'Badly centered? Try centering on <a href="/near/'.urlencode2($plain).'/'.$gr.'?dist='.$row['dist'].'">'.$gr.'</a>';
			}
		}

		if (!empty($suggestions)) {
			print "<div style=\"font-size:0.9em;padding:4px;border-bottom:1px solid gray\">Showing nearby results, alternatively: ";
			print implode(" &middot; ",$suggestions);
			print "</div>";
		}
	}


#########################################

	$limit = 50;

        if (!empty($grid_ok)) {

#########################################
# setup search results

		list($lat,$lng) = $conv->national_to_wgs84($square->nateastings,$square->natnorthings,$square->reference_index);

print "<!-- ($lat,$lng) -->";


		$where = array();
                if (!empty($sphinxq))
			$where[] = "match(".$sph->Quote($sphinxq).")";
		$lat = deg2rad($lat);
		$lng = deg2rad($lng);
		$columns = ", GEODIST($lat, $lng, wgs84_lat, wgs84_long) as distance";
		$where[] = "distance < $distance";


		$where = implode(' and ',$where);

		$rows = array();

#########################################
# retreive a small number of high scoring images

		if (!empty($_GET['score'])) {
			$rows['score'] = $sph->getAll($sql = "
        	                select id,realname,user_id,title,grid_reference,contexts $columns
                	        from sample8
                        	where $where
	                        order by score desc
        	                limit 8
				option ranker=none, max_query_time=800");
		}

#########################################
# the main results set!

if (!empty($_GET['preview'])) {

##                $columns = ", GEODIST($lat, $lng, wgs84_lat, wgs84_long) as distance";

$columns = preg_replace('/GEODIST\((.+?)\)/',"atan2($lat-wgs84_lat,$lng-wgs84_long) AS atan, INTERVAL(GEODIST($1), 0,100,300,600,1000,2000,3000,4000,5000,6000,7000,8000,9000,10000) as disti, GEODIST($1)",$columns);

print "<hr>$columns<hr>";

                $rows['ordered'] = $sph->getAll($sql = "
                        select id,realname,user_id,title,grid_reference,contexts $columns
                        from sample8
                        where $where
                        order by disti asc, atan asc, sequence asc
                        limit {$limit}
			option ranker=none");


} else {

                $rows['ordered'] = $sph->getAll($sql = "
                        select id,realname,user_id,title,grid_reference,contexts $columns
                        from sample8
                        where $where
                        order by distance asc, sequence asc
                        limit {$limit}
			option ranker=none");
}

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
				unset($rows[$idx]);
			}
		}

	}

        print "<br style=clear:both>";

if (!empty($_GET['d']) && !empty($final)) {
	print "<p><a href=\"/search.php?displayclass=map&marked=1&markedImages=".implode(',',array_keys($final))."$qfiltmain\">View on Map</a></p>";
}


#########################################
# display normal thumbnail results!

	if (!empty($final)) {
		$thumbh = 120;
		$thumbw = 120;

		print "<div id=thumbs>";

	        if (!empty($data['total_found']) && $data['total_found'] > 10)
			print '<div style="position:relative;float:right">About '.number_format($data['total_found'])." photos within ".($distance/1000)."km of $gru</div>";

		$last = 0;
		$contexts = array();
                foreach ($final as $idx => $row) {
			$row['gridimage_id'] = $row['id'];
                        $image = new GridImage();
                        $image->fastInit($row);
			if ($image->distance < 800 && $square->precision < 1000) {
				if ($image->distance < 10 && $square->precision <= 100) {
					$d2 = 0.01;
				} elseif ($image->distance < 100) {
					$d2 = 0.1;
				} else
					$d2 = sprintf("%0.1f",(intval($image->distance/300)/3)+0.3);
			} else
				$d2 = intval($image->distance/1000)+1;
			if ($last != $d2) {
				print "<div style=\"clear:left;font-size:0.8em;padding:2px;background-color:#eee\">Within <b>$d2</b> km</div>";
				$last = $d2;
			}
?>
          <div style="float:left;position:relative; width:120px; height:120px;padding:1px;">
          <div align="center">
          <a title="<? printf("%.1f km, ",$image->distance/1000); echo $image->grid_reference; ?> : <? echo htmlentities2($image->title) ?> by <? echo htmlentities2($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,$src); ?></a></div>
          </div>
<?

                        if (!empty($row['contexts'])) {
                                foreach (explode('_SEP_',$row['contexts']) as $context) {
                                        if (strlen($context = trim($context)) > 1) {
                                                @$contexts[$context]++;
                                        }
                                }
                        }

		}

		print "<br style=clear:both></div>";
		if ($src == 'data-src')
			print "<script src=\"".smarty_modifier_revision("/js/lazy.js")."\"></script>";
		print '<script src="/preview.js.php?d=preview" type="text/javascript"></script>';


#########################################
# handler for no results

	} else {
		//might be too late, but might as well try!
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");

		if (empty($sph)) {
			$sph = GeographSphinxConnection('sphinxql',true);
		}
		print "<p>No Results found. Try a <a href=\"/of/$qu\" rel=\"nofollow\">keyword search for <b>$qh</b></a> ";
		$sph->query("SELECT id FROM sample8 WHERE MATCH(".$sph->quote($_GET['q']).") LIMIT 0");
		$data = $sph->getAssoc("SHOW META");
		if (!empty($data['total_found']))
			print " (finds about <b>{$data['total_found']}</b> images)";
	}
}

#########################################
# footer links

if (!empty($final)) {
	print "<p><small>";
	if (!empty($data['total_found']) && (count($final) <  $data['total_found']))
		print "only first ".count($final)." images shown. Use the links below to explore more. ";
	print "This is a selection of photos centred on the geographical midpoint of the $location you have entered. Our coverage of different areas will vary</small></p>";

	print "<form action=\"/browser/redirect.php\"><br><div class=interestBox style=color:white;background-color:gray;font-size:1.05em>";
	if (!empty($data['total_found']) && $data['total_found'] > 10)
		print "About <b style='font-family:verdana'>".number_format($data['total_found'])."</tt> photos within ".($distance/1000)."km</b>. ";
?>
	Explore these images more: <b><a href="/browser/#!<? echo $qfiltbrow; ?>/loc=<? echo $gru; ?>/dist=<? echo $distance; ?>" style=color:yellow>in the Browser</a>
	(<a href="/browser/#!<? echo $qfiltbrow; ?>/loc=<? echo $gru; ?>/dist=<? echo $distance; ?>/display=map_dots/pagesize=100" style=color:yellow>On Map</a>)
	<? if (!preg_match('/(_SEP|%40terms|%40groups)/',$qfiltmain)) {  //not ideal, but can blacklist some functions we know wont work!
	?>
	or <a href="/search.php?do=1&gridref=<? echo $gru.$qfiltmain; ?>" style=color:yellow>in the standard search</a>.
	<? } ?>
	</b></div>

	<div class=interestBox>
	Too many photos in a small area? Try a <a href="/browser/#!<? echo $qfiltbrow; ?>/loc=<? echo $gru; ?>/dist=<? echo ($distance == 10000)?3000:$distance; ?>/pagesize=30/sort=spread">sample selection of the general area</a>.
	(<a href="/browser/#!<? echo $qfiltbrow; ?>/loc=<? echo $gru; ?>/dist=<? echo ($distance == 10000)?3000:$distance; ?>/display=map/pagesize=50/sort=spread">On a map</a>)
	<br><br>

	Search <i>within</i> these images, keywords: <input type="search" name="q" value="<? echo @htmlentities2($_GET['filter']); ?>"><input type=submit value="Browser">
	<input type="hidden" name="loc" value="<? echo $gr; ?>"/>
	<input type="hidden" name="dist" value="<? echo $distance; ?>"/>
	</div>
	</form>
<?
}

#########################################

if (!empty($contexts)) {
        print "<p>Geographical Contexts for these images (click to view more images): ";
        foreach ($contexts as $context => $count) {
                print "&middot; <a href=\"/browser/#!/contexts+%22".urlencode($context)."%22/loc=$gru/dist=1000/\">".htmlentities2($context)."</a>[$count] ";
        }
        print "</p>";
}

#########################################

if (false) {
?>
    <script type="text/javascript">
        var _mfq = _mfq || [];
        (function () {
        var mf = document.createElement("script"); mf.type = "text/javascript"; mf.async = true;
        mf.src = "//cdn.mouseflow.com/projects/57522984-763c-43cc-98c8-33ff8d5634c4.js";
        document.getElementsByTagName("head")[0].appendChild(mf);
      })();
    </script>
<?
}

#########################################

if ($memcache->valid && !empty($mkey)) {
	$str = ob_get_flush();

	if (empty($_GET['d']))
		$memcache->name_set('near',$mkey,$str,$memcache->compress,$memcache->period_long);
}

#########################################
# special footer just for registered users - who have had their default changed

if (!empty($USER->registered)) {
	print "<hr><p>If you prefer the traditional search, you can <a href=\"/choose-search.php\">choose your default search engine to use</a>.</p>";
	if (false && $CONF['forums']) {
		print "<p>Having trouble with this page? No matter how small, <a href=\"/discuss/index.php?&action=vthread&forum=12&topic=26439\">please let us know</a>, thank you!</p>";
	}
} elseif (false) {
	print "<hr><p>Have feedback on this search? <a href='https://docs.google.com/forms/d/1EghtKiKGkLbLUJ1gBAMiENNgMChQotBwI3n7XSyw1z0/viewform' target=_blank>please let us know</a>!</p>";
}

#########################################

//print "<hr><p style='background-color:purple;color:white;padding:1em;margin:0'>Dissatisfied with these results? <a style='color:yellow' href='#' onclick=\"jQl.loadjQ('/js/search-feedback.js');return false\">Please take this short survey</a>.</p>";

if (false && $src == 'data-src') { //because we need jQuery!

	print "<p id=votediv>Have these results helped you today? Rate these results: ";

	$id = 1;
	$qstr = "'".urlencode($_GET['q'])."'";
	$names = array('','Hmm','Below average','So So','Good','Excellent');
	foreach (range(1,5) as $i) {
		print "<a href=\"javascript:void(vote_log('near',$qstr,$i));\" title=\"{$names[$i]}\"><img src=\"{$CONF['STATIC_HOST']}/img/star-light.png\" width=\"14\" height=\"14\" alt=\"$i\" onmouseover=\"star_hover($id,$i,5)\" onmouseout=\"star_out($id,5)\" name=\"star$i$id\"/></a>";
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

	$smarty->display('_std_end.tpl',substr(md5($_SERVER['PHP_SELF']),0,6).$mkey);

