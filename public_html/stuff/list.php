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


	if (!empty($_GET['title'])) {
		if (substr($_GET['title'],-2) == ' #') {
			$prefix = preg_replace('/ #$/','',$_GET['title']);
			$title = "Images titled: ".$prefix;
			$q = '@title "^'.$prefix.'"';
		} else {
			$title = "Images titled: ".$_GET['title'];
			$q = '@title "^'.$_GET['title'].'$"';
		}
	} elseif (!empty($_GET['label'])) {
		$title = "images in cluster ".$_GET['label'];
		$q = '@groups "_SEP_ '.$_GET['label'].' _SEP_"';

	} elseif (!empty($_GET['q'])) {
		$title = "Images matching: ".$_GET['q'];
		$q = $_GET['q']; //todo, run this via sphinxClient?

	} else {
		die("unknown query");
	}

	if (!empty($_GET['gridref']))
		$q .= " @grid_reference {$_GET['gridref']}";

	$sql = "SELECT id,title,realname,user_id,grid_reference,takenday,place,county,country FROM sample8
		WHERE MATCH(".$db->Quote($q).") ORDER BY grid_reference ASC, takenday DESC, realname ASC, id DESC LIMIT 100";
       	$imagelist->getImagesBySphinxQL($sql);

	if (!empty($imagelist->meta) && !empty($imagelist->meta['total_found']) && $imagelist->meta['total_found'] > 1 && preg_match('/^image/i',$title)) {
		$title = $imagelist->meta['total_found']." ".$title;
	}


	$smarty->assign('page_title', $title); //is automatically escaped
	$smarty->display('_std_begin.tpl',md5($title));
	print "<div style=float:right;color:gray>Showing most recent first, in descending order</div>";
	print "<h2>".htmlentities2($title)."</h2>";
	if (!empty($_GET['label'])) {
		print "<p>Image clustering - assigning images labels - is an automated process, based on the image title/description. It's not totally accurate, and can sometimes assign images to odd clusters</p>";
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
				if ($l[$attribute] == $image->{$attribute})
					return $value;
				$l[$attribute] = $image->{$attribute};
				return "<b>$value</b>";
			}
		$thumbw = $thumbh = 120;

		$thumbh = 160;
        	$thumbw = 213;

		foreach ($imagelist->images as $image) {
		?>
        	  <div style="float:left;position:relative; width:<? echo ($thumbw+10); ?>px; height:<? echo ($thumbh+120); ?>px">
	          <div align="center" class="shadow">
        	    <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities2($image->title) ?> by <? echo htmlentities2($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a></div>
		    <?
			if (count($s['title']) > 1)
				print htmlentities2($image->title).'<br>';
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
		print "<br style=clear:both>";

		$q = str_replace("'"," ",$q); //tofix, single quotes are special syntax in browser, and doesnt work in quoted strings, currently. Awkward!

		if (!empty($imagelist->meta) && !empty($imagelist->meta['total_found']) && $imagelist->meta['total_found'] > 100) {
			print "<p>Showing sample of 100 of roughly {$imagelist->meta['total_found']} matching images, <a href=\"/browser/#!/q=".urlencode($q)."/display=plus\">explore them more in the Browser</a></p>";
		} else {
			print "<p><a href=\"/browser/#!/q=".urlencode($q)."/display=plus\">Explore these images in the Browser</a></p>";
		}

	} else {
		print "nothing to display at this time.";

		if (!empty($_GET['label'])) {
			print " Perhaps the images have since been reclustered differently. ";

			$url = "/stuff/list.php?q=".urlencode($_GET['label'])."&gridref=".urlencode($_GET['gridref']);
			print "<p>For now can try a <a href=\"".htmlentities2($url)."\">keyword search in this square</a></p>";
		} elseif (!empty($_GET['title'])) {
			print " Perhaps the images been retitled. ";

			$url = "/stuff/list.php?q=".urlencode($_GET['title'])."&gridref=".urlencode($_GET['gridref']);
			print "<p>For now can try a <a href=\"".htmlentities2($url)."\">keyword search in this square</a></p>";
		}
	}

	if (!empty($_GET['title']) && !empty($_GET['gridref'])) {
		$labeled = $db->getOne("select count(*) from gridimage_group inner join gridimage_search using (gridimage_id)
				where grid_reference = ".$db->Quote($_GET['gridref'])." and label = ".$db->Quote($_GET['title']));
		if ($labeled) {
			$url = "/stuff/list.php?label=".urlencode($_GET['title'])."&gridref=".urlencode($_GET['gridref']);
			print "<p>Can also view <a href=\"".htmlentities2($url)."\"><b>$labeled</b> images with the cluster ".htmlentities2($_GET['title'])." in this square</a></p>";
		}
	}

	$smarty->display('_std_end.tpl');
	exit;

