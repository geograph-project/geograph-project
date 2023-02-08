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

#########################################

init_session();

$smarty = new GeographPage;

$smarty->assign('responsive',true);

customExpiresHeader(3600,false,true);

#########################################
# quick query parsing, to possibly redirect to the nearby page.

$distance = 1000; //meters!
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
	$smarty->assign('extra_meta', "<link rel=\"canonical\" href=\"{$CONF['SELF_HOST']}/near/$qu2\"/>"); //this is not actully the near page, but just in case this gets crawlled!

	print "<base target=_blank>";

	$smarty->display("_basic_begin.tpl",substr(md5($_SERVER['PHP_SELF']),0,6).$mkey);

	if ($memcache->valid && empty($_GET['refresh'])) {
		$str = $memcache->name_get('fnear',$mkey);
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

			//$smarty->display('_basic_end.tpl',substr(md5($_SERVER['PHP_SELF']),0,6).$mkey);
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
		$str = file_get_contents("https://api.geograph.org.uk/finder/places.json.php?q=$qu&new=1");
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
	$smarty->display('_basic_begin.tpl',substr(md5($_SERVER['PHP_SELF']),0,6).$mkey);
}

#########################################
# the top of page form

?>
<form target="_self">
<div class="interestBox">
	match:<input type=search name=filter value="<? echo htmlentities2($_GET['filter']); ?>">
	within:<input type=number name=dist value="<? echo $distance; ?>" step=0.05 max=20000 min=0.05 style="width:100px;text-align:right">m
	of:<input type=search name=q value="<? echo $qh; ?>" size=16><input type=submit value=go><br/>
<?

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
			print "Possible Locations: <select onchange=\"this.form.q.value = encodeURIComponent(this.value);\"><option value=''>Choose Location...</option>";
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
# the main results set!

                $rows['ordered'] = $sph->getAll($sql = "
                        select id,realname,user_id,title,grid_reference,contexts $columns
                        from sample8
                        where $where
                        order by distance asc, sequence asc
                        limit {$limit}
			option ranker=none");

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
          <a title="<? printf("%.1f km, ",$image->distance/1000); echo $image->grid_reference; ?> : <? echo htmlentities2($image->title) ?> by <? echo htmlentities2($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,'loading=lazy src'); ?></a></div>
          </div>
<?

		}

		print "<br style=clear:both></div>";


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
		/*
		$sph->query("SELECT id FROM sample8 WHERE MATCH(".$sph->quote($_GET['q']).") LIMIT 0");
		$data = $sph->getAssoc("SHOW META");
		if (!empty($data['total_found']))
			print " (finds about <b>{$data['total_found']}</b> images)";
		*/
		print '<a href="#" onclick="parent.closePopup(); return false">Close Window</a>';
	}
}

#########################################
# footer links

if (!empty($final)) {
	print "<p><small>";
	if (!empty($data['total_found']) && (count($final) <  $data['total_found']))
		print "only first ".count($final)." images shown. Use the links below to explore more. ";
	print "This is a selection of photos centred on the geographical midpoint of the $location you have entered. Our coverage of different areas will vary</small></p>";

	if (!empty($data['total_found']) && $data['total_found'] > 10)
		print "About <b style='font-family:verdana'>".number_format($data['total_found'])."</tt> photos within ".($distance/1000)."km</b>. ";
?>
	<div style="position:fixed;background-color:silver;bottom:0;left:0;width:100%">

	Explore these images more: <b><a href="/browser/#!<? echo $qfiltbrow; ?>/loc=<? echo $gru; ?>/dist=<? echo $distance; ?>" style=color:yellow>in the Browser</a>
	(<a href="/browser/#!<? echo $qfiltbrow; ?>/loc=<? echo $gru; ?>/dist=<? echo $distance; ?>/display=map_dots/pagesize=100" style=color:yellow>On Map</a>)
	<? if (!preg_match('/(_SEP|%40terms|%40groups)/',$qfiltmain)) {  //not ideal, but can blacklist some functions we know wont work!
	?>
	or <a href="/search.php?do=1&gridref=<? echo $gru.$qfiltmain; ?>" style=color:yellow>in the standard search</a>.
	<? } ?>
	</b>

	<a href="#" onclick="parent.closePopup(); return false">Close Window</a>
	</div>

<?
}

#########################################

if ($memcache->valid && !empty($mkey)) {
	$str = ob_get_flush();

	if (empty($_GET['d']))
		$memcache->name_set('fnear',$mkey,$str,$memcache->compress,$memcache->period_long);
}

#########################################

//	$smarty->display('_basic_end.tpl',substr(md5($_SERVER['PHP_SELF']),0,6).$mkey);

