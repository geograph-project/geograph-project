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

if (strpos($_SERVER['REQUEST_URI'],'/finder/near.php') === 0) {
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");

        $url = "/near/".urlencode2($_GET['q']);
	//todo add sorting?
	if (!empty($_GET['filter']))
		$url .= "?filter=".urlencode($_GET['filter']);
	if (!empty($_GET['sort']))
		$url .= "?sort=".urlencode($_GET['sort']);

        header("Location: ".$url);
        print "<a href=\"".htmlentities($url)."\">moved</a>";

        exit;
}

if ($_SERVER['HTTP_HOST'] == 'www.geograph.org.uk') {
        $mobile_url = "http://m.geograph.org.uk/near/".urlencode2($_GET['q']);
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
	$qu = urlencode(trim($_GET['q']));
	$qu2 = urlencode2(trim($_GET['q']));
	$qh = htmlentities(trim($_GET['q']));

	$sphinxq = '';

	$mkey = md5('v2'.trim($_GET['q']).$src);
	if (!empty($_GET['filter'])) {
		$sphinx = new sphinxwrapper(trim($_GET['filter']), true); //this is for sample8 index. 
		$sphinxq = $sphinx->q;
		$mkey = md5($sphinxq.'.'.$mkey);
	}

	$smarty->assign("page_title",'Photos near '.$_GET['q']);
	$smarty->assign('extra_meta', "<link rel=\"canonical\" href=\"http://{$_SERVER['HTTP_HOST']}/near/$qu2\"/>");
	$smarty->display("_std_begin.tpl",$_SERVER['PHP_SELF'].$mkey);

	if ($memcache->valid) {
		$str =& $memcache->name_get('near',$mkey);
		if (!empty($str)) {
			print $str;
			$smarty->display('_std_end.tpl');
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
		$gru = urlencode($gr);
	} elseif (!empty($decode) && !empty($decode->total_found)) {
		$gr = $decode->items[0]->gr;
		$grid_ok=$square->setByFullGridRef($gr,true,true);
		$gru = urlencode($gr);
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

	if ($grid_ok) {
	        require_once('geograph/conversions.class.php');
        	$conv = new Conversions;

		$e = floor($square->nateastings/1000);
                $n = floor($square->natnorthings/1000);

		//todo - make the radius dynamic (maybeing checking square->imagecount as a proxy for now popular the area is
		$d = 10; //units is km!
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
	$smarty->display('_std_begin.tpl');
}

#########################################
# the top of page form

?>
<form onsubmit="location.href = '/near/'+encodeURI(this.q.value).(this.form.elements['filter']?"?filter="+encodeURI(this.form.elements['filter'].value):''); return false;">
<div class="interestBox">
	<? if (!empty($_GET['q'])) { ?>
	<div style="float:right">
		More:
		<a href="/of/<? echo urlencode2($_GET['q']); ?>?redir=false" rel="nofollow">Keyword Search</a> &middot;
		<a href="/finder/groups.php?q=<? echo $qu; ?>&group=decade">Over Time</a> &middot;
                <a href="/gridref/<? echo strtoupper($gru); ?>">Browse Page</a> &middot;
		<? if (!empty($square->reference_index) && $square->reference_index == 1) { ?>
		        <a href="/search.php?do=1&gridref=<? echo $gru; ?>&amp;displayclass=map">OS Map</a> &middot;
		        <a href="/finder/dblock.php?gridref=<? echo $gru; ?>">D-block</a> &middot;
		<? } else { ?>
			<a href="/browser/#!/loc=<? echo $gru; ?>/dist=10000/display=map_dots/pagesize=50"><b>Map</b></a> &middot;
		<? } ?>
		<a href="/browser/#!/loc=<? echo $gru; ?>/pagesize=50">Browser</a> &middot;
	</div>
	<? } ?>
	Images near: <input type=search name=q value="<? echo $qh; ?>" size=40><input type=submit value=go><br/>
<?
	if (isset($_GET['filter'])) {
		print "Matching: <input type=text name=filter value=\"".htmlentities($_GET['filter'])."\">";
	}

#########################################
# display the location results dropdown, for directing to near page.

if (!empty($_GET['q'])) {

	if (!empty($decode)) {
		if ($decode->total_found == 1) {
			$object = $decode->items[0];
			if (strpos($object->name,$object->gr) === false)
                                 $object->name .= " / {$object->gr}";
			print "Matched Location: <b>{$object->name}</b>".($object->localities?", ".$object->localities:'');

		} elseif ($decode->total_found > 0) {
			print "Possible Locations: <select onchange=\"location.href = '/near/'+encodeURI(this.value);\"><option value=''>Choose Location...</option>";
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

	$limit = 50;



        if ($grid_ok) {

#########################################
# setup search results

		list($lat,$lng) = $conv->national_to_wgs84($square->nateastings,$square->natnorthings,$square->reference_index);

print "<!-- ($lat,$lng) -->";

                $prev_fetch_mode = $ADODB_FETCH_MODE;
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

                $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());


		$where = array();
                if (!empty($sphinxq))
			$where[] = "match(".$sph->Quote($sphinxq).")";
		$lat = deg2rad($lat);
		$lng = deg2rad($lng);
		$columns = ", GEODIST($lat, $lng, wgs84_lat, wgs84_long) as distance";
		$where[] = "distance < 10000";



		$where = implode(' and ',$where);

		$rows = array();

#########################################
# retreive a small number of high scoring images

		if (!empty($_GET['score'])) {
			$rows['score'] = $sph->getAll($sql = "
        	                select id,realname,user_id,title,grid_reference $columns
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
                        select id,realname,user_id,title,grid_reference $columns
                        from sample8
                        where $where
                        order by disti asc, atan asc, sequence asc
                        limit {$limit}
			option ranker=none");


} else {

                $rows['ordered'] = $sph->getAll($sql = "
                        select id,realname,user_id,title,grid_reference $columns
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
	print "<p><a href=\"/search.php?displayclass=map&marked=1&markedImages=".implode(',',array_keys($final))."\">View on Map</a></p>";
}


#########################################
# display normal thumbnail results!

	if (!empty($final)) {
		$thumbh = 120;
		$thumbw = 120;

		print "<div id=thumbs>";

	        if (!empty($data['total_found']) && $data['total_found'] > 10)
			print '<div style="position:relative;float:right">About '.number_format($data['total_found'])." photos within 10km.</div>";

		$last = 0;
                foreach ($final as $idx => $row) {
			$row['gridimage_id'] = $row['id'];
                        $image = new GridImage();
                        $image->fastInit($row);
			if ($image->distance < 800 && $square->precision < 1000) {
				if ($image->distance < 100) {
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
          <a title="<? printf("%.1f km, ",$image->distance/1000); echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,$src); ?></a></div>
          </div>
<?

		}

		print "<br style=clear:both></div>";
		if ($src == 'data-src')
			print '<script src="'.$CONF['STATIC_HOST'].'/js/lazy.v4.js" type="text/javascript"></script>';
		print '<script src="/preview.js.php?d=preview" type="text/javascript"></script>';


#########################################
# handler for no results

	} else {
		if (empty($sph)) {
	                $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());
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
	print "<form action=\"/browser/redirect.php\"><br><div class=interestBox>";
	if (!empty($data['total_found']) && $data['total_found'] > 10)
		print "About ".number_format($data['total_found'])." photos within 10km. ";
?>
	<a href="/browser/#!/loc=<? echo $gru; ?>/dist=10000">Explore these images more in the Browser</a> or
	<a href="/search.php?do=1&gridref=<? echo $gru; ?>">in the standard search</a>.<br/><br/>

	Search <i>within</i> these images, keywords: <input type="search" name="q"><input type=submit value="Browser">
	<input type="hidden" name="loc" value="<? echo $gr; ?>"/>
	</div>
	</form>
<?
}

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

if ($memcache->valid && $mkey) {
	$str = ob_get_flush();

	if (empty($_GET['d']))
		$memcache->name_set('near',$mkey,$str,$memcache->compress,$memcache->period_long);
}

#########################################
# special footer just for registered users - who have had their default changed

if (!empty($USER->registered)) {
	print "<p>If you prefer the traditional search, you can <a href=\"/choose-search.php\">choose your default search engine to use</a>.</p>";
	if (false && $CONF['forums']) {
		print "<p>Having trouble with this page? No matter how small, <a href=\"/discuss/index.php?&action=vthread&forum=12&topic=26439\">please let us know</a>, thank you!</p>";
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

	$smarty->display('_std_end.tpl');
	exit;

#########################################
# functions!

function urlencode2($input) {
        return str_replace(array('%2F','%3A','%20'),array('/',':','+'),urlencode($input));
}

