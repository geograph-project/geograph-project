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

if (empty($_SERVER['HTTP_USER_AGENT']))
        die("no scraping");

require_once('geograph/global.inc.php');


init_session();


$smarty = new GeographPage;

pageMustBeHTTPS();

customExpiresHeader(3600,false,true);

//basic wrapper, to remove the day of the week. Too much detail
function getFormattedDate2($in) {
	return preg_replace('/^[A-Z]\w+, *(\d+ \w+,)/','$1',getFormattedDate($in));
}


$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

        $imagelist = new ImageList();

	$imagelist->cols .= ",imagetaken";

if (!empty($_GET['cluster'])) {

	//no JOIN or subqueries, in sphinx :( - and location index doesnt have all the attributes!)

	$sph = GeographSphinxConnection('sphinxql', true);

	if (!empty($_GET['d'])) {
		//emulate what scripts/cluster_location.php does

		$param=array('geo1'=>200, 'geo2'=>200, 'diff'=>22);

		$param['diff'] = intval($_GET['d']);


		//NOTE `viewpoint` index is used, as it has distance/direction as numberic attributes!
		//could also use `location`, but that is already filtered, and less updated!

		$row = $sph->getRow("select id,wgs84_lat,wgs84_long,vlat,vlong,direction FROM viewpoint WHERE id = ".intval($_GET['cluster']));
		if (empty($row))
			die ("unable to load image");

		$rads=deg2rad(0.01); //need rads, 0.01degree shoud be bigger than 200m!

        	$row['lat1'] = $row['wgs84_lat']-$rads;
        	$row['lat2'] = $row['wgs84_lat']+$rads;

		$find = 'select id,geodist($wgs84_lat,$wgs84_long,wgs84_lat,wgs84_long) as geo1,
		        geodist($vlat,$vlong,vlat,vlong) as geo2,
		        180 - abs(abs($direction-direction) - 180) as diff
		        from viewpoint
			 where geo1 <= '.$param['geo1'].' and geo2 < '.$param['geo2'].' and diff <= '.$param['diff'].'
		         and wgs84_lat>$lat1 and wgs84_lat<$lat2
			 and natgrlen>=6 and vgrlen>=6 and distance>8 and direction != -1
		        limit 1000';

	        $sql = preg_replace_callback('/\$(\w+)/', function ($m) { return $GLOBALS['row'][$m[1]]; }, $find );

if (!empty($_GET['debug']))
	print "<!-- $sql -->";

	        $ids = $sph->getCol($sql);

	} else {
		$ids = $sph->getCol("SELECT id FROM location WHERE cluster_id = ".intval($_GET['cluster']));
		if (empty($ids) || count($ids) <2) {
			//second attempt, just in case the image is part of another cluster!
			$_GET['cluster'] = $sph->getOne("select cluster_id from location where id = ".intval($_GET['cluster']));

			if (empty($_GET['cluster']) || $_GET['cluster'] == 9999999)
				die("this image is not part of any cluster");

			$ids = $sph->getCol("SELECT id FROM location WHERE cluster_id = ".intval($_GET['cluster']));
			if (empty($ids))
				die ("unable to load images");
		}
	}

	if (!empty($ids)) {
		$ids = implode(',',$ids);

		$row = $db->getRow("SELECT avg(view_direction) as d, avg(viewpoint_eastings) as e, avg(viewpoint_northings) as n FROM gridimage WHERE gridimage_id IN ($ids)");
		$ri = $db->getOne("SELECT reference_index FROM gridimage_search WHERE gridimage_id = ".intval($_GET['cluster']));

		$conv = new Conversions;
		list ($grid_reference,$len) = $conv->national_to_gridref(intval($row['e']),intval($row['n']),6,$ri);

		$direction = heading_string($row['d']);

		$title = "Looking roughly $direction from $grid_reference";
	} else {
		$ids = '0';
		$title = "Unknown Cluster";
	}


	$sql = "SELECT id,title,realname,user_id,grid_reference,takenday,place,county,country FROM sample8
		WHERE id IN ($ids) ORDER BY takenday DESC, realname ASC, id DESC LIMIT 100";

} else {
	if (!empty($_GET['title'])) {
		if (substr($_GET['title'],-2) == ' #') {
			$prefix = preg_replace('/ #$/','',$_GET['title']);
			$title = "Images titled: ".$prefix;
			$q = '@title "^'.$prefix.'"';
		} elseif (substr($_GET['title'],-1) == ' ') { //the space would already invalidate the field end modifier, but can give it a nice page title!
			$title = "Image titles starting with: ".$_GET['title'];
			$q = '@title "^'.$_GET['title'].'"';
		} else {
			$title = "Images titled: ".$_GET['title'];
			$q = '@title "^'.$_GET['title'].'$"';
		}

		if (!empty($_GET['q'])) {
			$q = $_GET['q'].' '.$q; //put at start so it before the @title!
		}

	} elseif (!empty($_GET['label'])) {
		$title = "images in cluster ".$_GET['label'];
		$q = '@groups "_SEP_ '.$_GET['label'].' _SEP_"';

	} elseif (!empty($_GET['q'])) {
		$title = "Images matching: ".$_GET['q'];
		$q = $_GET['q']; //todo, run this via sphinxClient?

	} elseif (!empty($_GET['premill'])) {
		$title = "Images Taken Pre 2000";
		$q = "@takenyear 19*|18*"; //decade isnt a prefix_field, and min_prefix_len =2;

	} else {
		die("unknown query");
	}

	if (!empty($_GET['gridref']) && preg_match('/^\w{1,3}\d{4,10}/',$_GET['gridref'])) {
		$q .= " @grid_reference {$_GET['gridref']}";
		$title .= " in {$_GET['gridref']}";
	}

	$sql = "SELECT id,title,realname,user_id,grid_reference,takenday,place,county,country FROM sample8
		WHERE MATCH(".$db->Quote($q).") ORDER BY takenday DESC, realname ASC, id DESC LIMIT 100";
}

if (!empty($_GET['debug']))
	print "<!-- $sql -->";


       	$imagelist->getImagesBySphinxQL($sql);

	if (!empty($imagelist->meta) && !empty($imagelist->meta['total_found']) && $imagelist->meta['total_found'] > 1 && preg_match('/^image/i',$title)) {
		$title = $imagelist->meta['total_found']." ".$title;
	}


	$smarty->assign('page_title', $title); //is automatically escaped
	$smarty->display('_std_begin.tpl',md5($title));

	if (!empty($_GET['gridref']))
		print "<div style=float:right;color:gray>Showing most recent first, in descending order</div>";
	print "<h2>".htmlentities2($title)."</h2>";
	if (!empty($_GET['label'])) {
		print "<p style=color:gray><i>Image clustering - assigning images labels - is an automated process, based on the image title/description. It's not totally accurate, and can sometimes assign images to odd clusters</i></p>";
	}

	if (count($imagelist->images)) {
		$s = array('grid_reference'=>array(),'imagetaken'=>array(),'realname'=>array(),'place'=>array(),'title'=>array());
		foreach ($imagelist->images as $image)
			foreach ($s as $key => $dummy)
				@$s[$key][$image->{$key}]++;
		$v = array();
		if (count($s['grid_reference']) == 1 && reset($s['grid_reference']) && ($value = key($s['grid_reference'])) )
			$v[] = "in <a href=\"/gridref/$value\">$value</a>";
		if (count($s['place']) == 1 && reset($s['place']) && ($value = key($s['place'])) )
			$v[] = "near <a href=\"/place/".urlencode2($value)."\">".htmlentities2($value)."</a>";
		if (count($s['imagetaken']) == 1 && reset($s['imagetaken']) && ($value = key($s['imagetaken'])) )
			$v[] = "taken <b>".getFormattedDate($value)."</b>";
		if (count($s['realname']) == 1 && reset($s['realname']) && ($value = key($s['realname'])) )
			$v[] = "by <a href=\"/profile/{$imagelist->images[0]->user_id}\">".htmlentities2($value)."</a>";
		if (!empty($v))
			print "<p style=font-size:1.1em>".implode(', ',$v)."</p>";

		print "<hr>";


		$l = array('grid_reference'=>null,'imagetaken'=>null,'realname'=>null);
			function ooo($image,$attribute,$value) {
				global $l;
				if (isset($l[$attribute]) && $l[$attribute] == $image->{$attribute})
					return $value;
				$l[$attribute] = $image->{$attribute};
				return "<b>$value</b>";
			}
		$thumbw = $thumbh = 120;

		$thumbh = 160;
        	$thumbw = 213;

?><style>
.gridded.med {
    display: grid;
    grid-template-columns: repeat(auto-fit, <? echo ($thumbw+10); ?>px);
    grid-gap: 18px;
    grid-row-gap: 20px;
}
.gridded > div {
        text-align:left;

 /* ignored in grid, but to support older browsers! */
        float:left;
        width: <? echo ($thumbw+10); ?>px;
}
.gridded .shadow {
	text-align:center;
	height: <? echo ($thumbh+8); ?>px;
}
</style><?

		print "<div class=\"gridded med\">";
		foreach ($imagelist->images as $image) {
		?>
        	  <div id="img<? echo $image->gridimage_id; ?>">
	            <div class="shadow">
        	    <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities2($image->title) ?> by <? echo htmlentities2($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a></div>
		    <?
			if (count($s['title']) > 1)
				print highlight_changes(htmlentities2($image->title))."<br>";
			if (count($s['grid_reference']) > 1)
				print '<span style="color:gray">In:</span> '.ooo($image,'grid_reference',"<a href=\"/gridref/{$image->grid_reference}\">{$image->grid_reference}</a>").'<br>';
			if (count($s['place']) > 1)
                                print '<span style="color:gray">near '.ooo($image,'place',htmlentities2($image->place)).'<small>, '.
					ooo($image,'county',htmlentities2($image->county)).', '.
					ooo($image,'country',htmlentities2($image->country)).'</small></span><br>';
			if (count($s['imagetaken']) > 1)
                                print '<span style="color:gray">When:</span> '.ooo($image,'imagetaken',getFormattedDate2($image->imagetaken)).'<br>';
			if (count($s['realname']) > 1)
                                print '<span style="color:gray">By:</span> '.ooo($image,'realname',"<a href=\"/profile/{$image->user_id}\">".htmlentities2($image->realname)."</a>").'<br>';
		    ?>
	          </div>
		<?
		}
		print "<br style=clear:both></div>";

		if (!empty($q)) {
			$q = str_replace("'"," ",$q); //tofix, single quotes are special syntax in browser, and doesnt work in quoted strings, currently. Awkward!

			if (!empty($imagelist->meta) && !empty($imagelist->meta['total_found']) && $imagelist->meta['total_found'] > 100) {
				print "<p>Showing sample of 100 of roughly {$imagelist->meta['total_found']} matching images, <a href=\"/browser/#!/q=".urlencode($q)."/display=plus\">explore them more in the Browser</a>";
			} else {
				print "<p><a href=\"/browser/#!/q=".urlencode($q)."/display=plus\">Explore these images in the Browser</a>";
			}

			if (!empty($_GET['gridref'])) {
		                $q2 = trim(str_replace(" @grid_reference {$_GET['gridref']}",'',$q));
				print " or <a href=\"/browser/#!/q=".urlencode($q2)."/loc=".urlencode($_GET['gridref'])."/dist=2000/display=plus\">Explore matching images including in surrounding squares</a> (if any!)";
			}

		}

		if (count($s['realname']) == 1 && reset($s['realname']) && ($value = key($s['realname'])) )
			print "<hr><p>All images <img src=\"{$CONF['STATIC_HOST']}/img/80x15.png\"> <b>&copy; ".htmlentities2($value)."</b> and licensed for reuse under this <a href=\"http://creativecommons.org/licenses/by-sa/2.0/\" target=\"_blank\">Creative Commons Licence</a></p>";


	} else {
		print "nothing to display at this time.";

		if (!empty($_GET['label'])) {
			print " Perhaps the images have since been reclustered differently. ";

			$url = "/stuff/list.php?q=".urlencode($_GET['label'])."&gridref=".urlencode(@$_GET['gridref']);
			print "<p>For now can try a <a href=\"".htmlentities2($url)."\">keyword search in this square</a></p>";
		} elseif (!empty($_GET['title'])) {
			print " Perhaps the images been retitled. ";

			$url = "/stuff/list.php?q=".urlencode($_GET['title'])."&gridref=".urlencode(@$_GET['gridref']);
			print "<p>For now can try a <a href=\"".htmlentities2($url)."\">keyword search in this square</a></p>";
		}
	}

	if (!empty($_GET['label']) && !empty($_GET['gridref'])) {
		$label = $db->Quote($_GET['label']);
		$gr = $db->Quote($_GET['gridref']);

		//could just use gridimage_group_stat, but doing a full join like this can get number of 'overlapping' images.
		/*$others = $db->getAll("select g.label,count(g.gridimage_id) count,count(s.gridimage_id) as matched
					from gridimage_group g inner join gridimage_search i using (gridimage_id)
					 left join (select gridimage_id from gridimage_group inner join gridimage_search using (gridimage_id)
					 		where grid_reference = $gr and label = $label) s using (gridimage_id)
					where grid_reference = $gr
					group by g.label order by matched desc, count desc"); */

		//... actully for now, lets do the simpler query, as the above query is using lots of IO
		$others = $db->getAll("SELECT label,images AS count FROM gridimage_group_stat WHERE grid_reference = $gr ORDER BY images DESC LIMIT 100");
		if (!empty($others)) {
			print "<hr>";
			print "<p>Other Automatic clusters in ".htmlentities($_GET['gridref'])."</p>";
			print "<ol>";
			$gr = urlencode($_GET['gridref']);
			foreach ($others as $row) {
				if ($row['label'] == 'Other Topics')
					continue;
				print "<li value={$row['count']}>";
				if ($row['label'] == $_GET['label']) {
					print "<b>".htmlentities2($row['label'])."</b>";
				} else {
					$url = "/stuff/list.php?label=".urlencode($row['label'])."&gridref=$gr";
					print "<a href=\"".htmlentities($url)."\">".htmlentities2($row['label'])."</a>";
					if (!empty($row['matched'])) {
						print " <small>(of which {$row['matched']} shown above)</small>";
					}
				}
				print "</li>";
			}
			print "</ol>";

			if (!empty($USER->user_id)) {
				print "<hr>&middot; There is also an alternate view: <a href=\"/finder/groups.php?q=%5E$gr&group=group_ids\">View images Grouped by Automatic Cluster</a>";
			}
		}

	} elseif (!empty($_GET['title']) && !empty($_GET['gridref'])) {
		//$labeled = $db->getOne("select count(*) from gridimage_group inner join gridimage_search using (gridimage_id)
		$labeled = $db->getOne("select images from gridimage_group_stat
				where grid_reference = ".$db->Quote($_GET['gridref'])." and label = ".$db->Quote($_GET['title']));
		if ($labeled) {
			$url = "/stuff/list.php?label=".urlencode($_GET['title'])."&gridref=".urlencode($_GET['gridref']);
			print "<p>Can also view <a href=\"".htmlentities2($url)."\"><b>$labeled</b> images with the cluster ".htmlentities2($_GET['title'])." in this square</a></p>";
		}


		if (!empty($_GET['descriptions']) && count($imagelist->images)) {
			print '<div class="interestBox">';
			print "<h3>Combined descriptions from these images</h2>";
			print "<p>Click a paragraph to view one of the images with that paragraph</p>";
			print "</div>";

			//this is annoying, sphinx doesnt have descriptiosn so look them up again!
			$comments = $db->getAll($sql = "select gridimage_id,comment,realname from gridimage_search
                                where comment != '' AND grid_reference = ".$db->Quote($_GET['gridref'])." and title = ".$db->Quote($_GET['title']));

			$d = array();
			$r = array();
			foreach ($comments as $row) {
				$bits = preg_split('/\n\n/',str_replace("\r",'',$row['comment']));
				foreach ($bits as $idx=>$bit) {
					$md = md5($bit);
					if (isset($d[$md])) {
						$d[$md]['ids'][] = $row['gridimage_id'];
						$d[$md]['pos'][] = $idx+1;
					} else {
						$d[$md] = array(
							'text' => $bit,
							'ids' => array($row['gridimage_id']),
							'pos' => array($idx+1),
						);
					}
				}
				@$r[$row['realname']]++;
			}

			foreach ($d as $md => $row) {
				$d[$md]['avg'] = array_sum($row['pos'])/count($row['pos']);
			}

function cmp($a, $b) {
    if ($a['avg'] == $b['avg']) {
        return 0;
    }
    return ($a['avg'] < $b['avg']) ? -1 : 1;
}

			uasort($d, 'cmp');

			foreach ($d as $row) {
				$style = array('text-decoration:none');
				$style[] = "font-size: ".(1.2*log(1+sqrt(count($row['ids']))))."em";
				if (count($row['ids']) == 1)
					$style[] = "color:#222";
				else
					$style[] = "color:black";
				$id = array_pop($row['ids']);
				print "<p><a href=\"/photo/$id\" style=\"".implode(';',$style)."\">".GeographLinks(htmlentities2($row['text']))."</a></p>\n";
			}

			print "<hr>";
			print "<p>Text above produced by combining descriptions from images provided by ".implode(', ',array_keys($r))."</p>";
		}

	}

?>
<script>
function highlightImage() {
	if (document.referrer && (m=document.referrer.match(/photo\/(\d+)\b/))) {
		var id = "img"+m[1];
		if (document.getElementById && document.getElementById(id)) {
			var ele = document.getElementById(id);
			ele.style.backgroundColor = '#eee';
			ele.style.border = '2px solid silver';
		}
	}
}
AttachEvent(window,'load',highlightImage,false);
</script>
<?


	$smarty->display('_std_end.tpl');
	exit;


function highlight_changes($str) {
	static $prev = '';
        $c = common_prefix($str,$prev);
	$prev = $str;
        if ($c > 0)
                return substr($str,0,$c)."<b>".substr($str,$c)."</b>";
        else
                return "<b>$str</b>";
}

function common_prefix($one,$two) {
        $limit = min(strlen($one),strlen($two));
        $i=1;
        while(substr($one,0,$i)==substr($two,0,$i) && $i <= $limit) //case sensitive!
                $i++;
        return $i-1;
}

