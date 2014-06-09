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


if (false && strpos($_SERVER['REQUEST_URI'],'/finder/near.php') === 0) {
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");

        $url = "/near/".urlencode2($_GET['q']);
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
	$qu = urlencode(trim($_GET['q']));
	$qu2 = urlencode2(trim($_GET['q']));
	$qh = htmlentities(trim($_GET['q']));

	$smarty->assign("page_title",'Photos near '.$_GET['q']);
	$smarty->display("_std_begin.tpl",$_SERVER['PHP_SELF'].md5($_GET['q']));


	if (preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$_GET['q'],$ll)) {
                require_once('geograph/conversions.class.php');
                $conv = new Conversions;

		list($e,$n,$reference_index) = $conv->wgs84_to_national($ll[1],$ll[2],true);
		list($gr,$len) = $conv->national_to_gridref($e,$n,10,$reference_index,false);

		$_GET['q'] = $gr;

	} else {
		$data = file_get_contents("http://www.geograph.org.uk/finder/places.json.php?q=$qu&new=1");
		if (strlen($data) > 40) {
        		$decode = json_decode($data);
		}
	}

	$square=new GridSquare;
	if (preg_match('/\b([a-zA-Z]{1,2}) ?(\d{2,5})(\.\d*|) ?(\d{2,5})(\.*\d*|)\b/',$_GET['q'],$matches)) {
	        $grid_ok=$square->setByFullGridRef($matches[0],true,true);
		$gru = urlencode($gr = $matches[0]);
	} elseif (!empty($decode) && !empty($decode->total_found)) {
		$grid_ok=$square->setByFullGridRef($decode->items[0]->gr,true,true);
		$gru = urlencode($gr = $decode->items[0]->gr);
	}

	if ($grid_ok) {
	        require_once('geograph/conversions.class.php');
        	$conv = new Conversions;

		$e = floor($square->nateastings/1000);
                $n = floor($square->natnorthings/1000);

		$d = 10; //units is km!
			$grs = array();
                        for($x=$e-$d;$x<=$e+$d;$x+=10) {
                                for($y=$n-$d;$y<=$n+$d;$y+=10) {
                                        list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,2,$square->reference_index,false);
                                        if (strlen($gr2) > 2)
                                                $grs[] = $gr2;
                                }
                        }
                        $sphinxq = "@hectad (".join(" | ",$grs).")";

		$qu = urlencode(trim($sphinxq));
	} else {
		print "Couldn't identify Grid Reference";
	}

} else {
	$smarty->display('_std_begin.tpl');
}

?>
<form onsubmit="location.href = '/near/'+encodeURIComponent(this.q.value); return false;">
<div class="interestBox">
	<? if (!empty($_GET['q'])) { ?>
	<div style="float:right">
		More:
		<a href="/of/<? echo urlencode2($_GET['q']); ?>">Keyword Search</a> &middot;
		<a href="/finder/groups.php?q=<? echo $qu; ?>&group=decade">Over Time</a> &middot;
		<a href="/browser/#!/loc=<? echo $gru; ?>/dist=10000/display=map_dots/pagesize=50">Map</a> &middot;
		<a href="/browser/#!/loc=<? echo $gru; ?>/pagesize=50">Browser</a> &middot;
		<a href="/finder/multi2.php?q=<? echo $qu; ?>">Others</a>
	</div>
	<? } ?>
	Images near: <input type=search name=q value="<? echo $qh; ?>" size=40><input type=submit value=go><br/>
<?

if (!empty($_GET['q'])) {

	if (!empty($decode)) {
		if ($decode->total_found > 1) {
			print "Location: <select onchange=\"location.href = '/near/'+encodeURI(this.value);\"><option value=''>Choose Location...</option>";
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
		} elseif ($decode->total_found == 1) {
			$object = $decode->items[0];
			if (strpos($object->name,$object->gr) === false)
                               	$object->name .= " / {$object->gr}";
			print "Matched Location: {$object->name}".($object->localities?", ".$object->localities:'');
		}
	}

}

?>


</div>
</form>
<?


if (!empty($_GET['q'])) {
	$limit = 50;



        if ($grid_ok) {


		list($lat,$lng) = $conv->national_to_wgs84($square->nateastings,$square->natnorthings,$square->reference_index);


                $prev_fetch_mode = $ADODB_FETCH_MODE;
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

                $CONF['sphinxql_dsn'] = 'mysql://192.168.77.35:9306/';

                $sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());

		$where = array();
                //$where[] = "match(".$sph->Quote($_GET['q']).")";
		$lat = deg2rad($lat);
		$lng = deg2rad($lng);
		$columns = ", GEODIST($lat, $lng, wgs84_lat, wgs84_long) as distance";
		$where[] = "distance < 10000";



		$where = implode(' and ',$where);

                //convert gi_stemmed -> sample8 format.
                $where = preg_replace('/@by/','@realname',$where);
                $where = preg_replace('/__TAG__/i','_SEP_',$where);

		$rows = array();
		if (!empty($_GET['score'])) {
			$rows[] = $sph->getAll($sql = "
        	                select id,realname,user_id,title,grid_reference $columns
                	        from sample8
                        	where $where
	                        order by score desc
        	                limit 8
				option ranker=none");
		}

                $rows[] = $sph->getAll($sql = "
                        select id,realname,user_id,title,grid_reference $columns
                        from sample8
                        where $where
                        order by distance asc, sequence asc
                        limit {$limit}
			option ranker=none");

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

	}

        print "<br style=clear:both>";


	if (!empty($final)) {
		$thumbh = 120;
		$thumbw = 120;

		print "<div id=thumbs>";

	        if (!empty($data['total_found']) && $data['total_found'] > 10)
			print '<div style="float:left;position:relative; width:120px; height:120px;padding:1px;float:right">About '.number_format($data['total_found'])." photos within 10km.</div>";

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

	} else {
		print "<p>No Results found. Try a <a href=\"/of/$qu\">keyword search for <b>$qh</b></a>.</p>";
	}

	print "<form action=\"/browser/redirect.php\"><div class=interestBox>";
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

if (!empty($USER->registered)) {
	print "<p>If you prefer the traditional search, you can <a href=\"/choose-search.php\">choose your default search engine to use</a>.</p>";
	if ($CONF['forums']) {
		print "<p>Having trouble with this page (no matter how small), <a href=\"/discuss/index.php?&action=vthread&forum=12&topic=26439\">please let us know</a>.</p>";
	}
}


	$smarty->display('_std_end.tpl');
	exit;

function urlencode2($input) {
	return str_replace(array('%2F','%3A','%20'),array('/',':','+'),$input);
}
