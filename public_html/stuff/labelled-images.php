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

$_GET['ddev'] =1;
$_GET['live'] = 1;

require_once('geograph/global.inc.php');


init_session();


$smarty = new GeographPage;

//customExpiresHeader(3600,false,true);

	$smarty->assign('page_title','Labelled Images');
	$smarty->display('_std_begin.tpl',$_SERVER['PHP_SELF']);

?>
<style>
table.examples span {
	border:1px solid silver;
	padding:3px;
	border-radius:3px;
	white-space: nowrap;
}
td.examples img {
	max-width:100px;
	max-height:100px;
}
</style>
<?


	$db = GeographDatabaseConnection(false);
	$sph = GeographSphinxConnection('sphinxql',true);

$names = array(
	'top' => 'Contexts',
	'type' => 'Types',
	'subject' => 'Subjects',
	'curated' => 'Curated',
	'snippet' => 'Shared Descriptions',
	'tag' => 'General Tags',
	'category' => 'Categories',
);
	//$prefix = nominally the tag prefix - but there are exceptions
	//$column = the column in the sample8 index, but again are exceptions

	if (empty($_GET['prefix']) || $_GET['prefix'] == 'top') {
		$_GET['prefix'] = 'top';

		$prefix = 'top';
		$column = 'contexts'; //different in Sphinx!

	} elseif ($_GET['prefix'] == 'subject') {
		$prefix = 'subject';
		$column = 'subjects';

	} elseif ($_GET['prefix'] == 'category') {
		$prefix = 'category'; //it wont be processed as prefix!
		$column = 'imageclass';

	} elseif ($_GET['prefix'] == 'type') {
		$prefix = 'type';
		$column = 'types';

	} elseif ($_GET['prefix'] == 'curated') {
		$prefix = 'curated';
		$column = 'label'; //on the curated1 index insread!

	} elseif ($_GET['prefix'] == 'snippet') {
		$prefix = 'snippet';
		$column = 'snippets';

	} elseif ($_GET['prefix'] == 'tag') {

		$prefix = 'tag'; //doesnt match anything!
		$column = 'tags';
		if (!empty($_GET['q'])) {
			//currently only filters the tag list at the top, the example images after the table
			 $where = "tagtext LIKE ".$db->Quote($_GET['q']);
		} else {
			//others, include 'county', 'canal', 'postcode area', 'postcode district' - but perhaps just leave them in?
			//'at', 'of', 'near' ?? (not many with >500 images away!
			$where = "tagtext not like 'top:%' AND tagtext not like 'subject:%' AND tagtext not like 'type:%' AND tagtext not like 'place:%' AND tagtext not like 'camera:%' ";
		}
	}

	print "<h2>Labelled Data</h2>";
	print "<p>We have ".count($names)." main tag namespaces with partial labeled data: <br><big>";
	foreach($names as $key => $name)
		if ($_GET['prefix'] == $key)
			print " &middot; <b>$name</b>";
		else
			print " &middot; <a href=?prefix=$key>$name</a>";
	print "</big><br><br>There are more, but these are the main ones. The first three are the most important.</p>";

	print "<p>We expect it might be best to make a seperate classification model for each of these namespaces, due to the size of the training data";
	print "<p>But it could also be a single combined model that can predict tags from any of these namespaces!";

	print "<hr>";

#####################################################################
//label stats

	if ($prefix == 'curated') {
		$data = $sph->getAll("SELECT label as tagtext, count(*) as count from curated1 group by label having count>100 limit 1000"); //sph has default limit of 20, 1000 is nominal max!

	} elseif ($prefix == 'snippet') {
		$data = $db->getAll("select title as tagtext,count(gridimage_id) as count from snippet inner join gridimage_snippet using (snippet_id) group by snippet_id having count > 100");

	} elseif ($prefix == 'category') {
		$data = $db->getAll("SELECT imageclass as tagtext, c as count FROM category_stat WHERE c > 100 ORDER BY imageclass");

	} elseif ($prefix == 'tag') {
		$data = $db->getAll("SELECT tagtext,count FROM tag_stat WHERE $where AND final_id = tag_id AND count >=500 ORDER by tagtext");

	} else {
		$data = $db->getAll("SELECT * FROM tag_stat WHERE tagtext like '$prefix:%' AND final_id = tag_id AND count >=100");
		if ($prefix == 'type') {
			//should consider a from:drone as a type?
			$data2 =  $db->getAll("SELECT * FROM tag_stat WHERE tagtext = 'from:drone'");
			$data = array_merge($data,$data2);
		}

	}

	print "<h3>Label Stats - shows how many images we have for each label</h3>";
	if ($prefix == 'tag' || $prefix == 'category' || $prefix == 'snippet') {
		print "<p>This is an uncontrolled namespace (ie users create as freeform tag), there might be some tags in this list that is not suitable for vision based classification, and/or there can are likely some duplicates, and overlap";
	}

	print "<table>";
	print "<tr>";
	print "<th>Tag</th>";
	print "<th>images</th>";
	print "<th></th>";
	if (!empty($_GET['sample'])) {
		print "<th>examples...</th>";
	}

	$t = 0;
	$t2 = 0;
	$c = 0;
	foreach ($data as $row) {
		print "<tr><td>{$row['tagtext']}</td>";
		print "<td align=right>".number_format($row['count'],0);
		$t+=$row['count'];
		$t2+=min(50000,$row['count']);
		$c++;

		if ($prefix == 'curated')
			continue; //todo, would need to figure a different way to get link and sample for curate!

		//... this works even for category!
		//https://www.geograph.org.uk/browser/#!/contexts+%22Housing%2C+Dwellings%22
		$str = urlencode('"'.str_replace($prefix.':','',$row['tagtext']).'"');
		$url = "https://www.geograph.org.uk/browser/#!/$column+$str";
		print "<td><a href=\"$url\">View</a>";

		if (!empty($_GET['sample']) && $c < 75) {
			if ($row['tagtext'] == 'from:Drone') { //this tag is added to the type list, but not a real type tag!
				$query = "@tags \"from Drone\"";
			} else {
				$query = "@$column ".str_replace($prefix.':','',$row['tagtext']);
				$query = "@$column \"".preg_replace('/(\w+)/','=$1',str_replace($prefix.':','',$row['tagtext'])).'"';
				//todo, perhaps should do this with tag_id instead? it should be possible with the MVAs!
			}
			$data2 = $sph->getAll("select id,user_id,realname,title FROM sample8D WHERE match(".$sph->Quote($query).") LIMIT 5");

			print "<td class=examples>";
			foreach($data2 as $row) {

				$image = new GridImage();
				$row['gridimage_id'] = $row['id'];
		                $image->fastInit($row);

				print $image->getSquareThumbnail(224,224);
			}
		}
	}
	print "<tr><td colspan=2><hr>";

	print "<tr><td>$c Tags/Classes";
	print "<tr><td>Total Image-Tag Pairs<td align=right>".number_format($t,0);
	if ($t2 != $t)
		print "<td><td>".number_format($t2,0)." if limit to 50000 per class</td>";

	if ($prefix == 'top') { //this doesnt work for prefixes, eg,
			// subjects - lots of subjects with <100 images. (and sample8 includes subject inferred from imageclass!)
			// type - sample8 includes type infered from moderation_status, so NOT tagged
		$c = $sph->getOne("SELECT COUNT(*) FROM sample8 where match('@$column _SEP_')");
		print "<tr><td>Unique Images<td align=right>".number_format($c,0);
		if ($prefix == 'top') {
			$u = 745313; //from the kaggle dataset!
			 print "<td><td>~$u unique images";
		}

		print "<tr><td>Average tags per image<td align=right>".sprintf('%.1f',$t/$c);

	} elseif ($prefix == 'tag') {
		print "<tr><td colspan=2><hr>";
		print "<b>The above is only tags with over 500 images.</b> Counting ALL tags:";
		$row = $db->getRow("select sum(count),count(*),avg(users) from tag_stat where tagtext not like 'top:%' AND tagtext not like 'subject:%' AND tagtext not like 'type:%' AND tagtext not like 'camera:%'");
		$c = $row['count(*)'];
		print "<tr><td>$c Tags/Classes";
		$t = $row['sum(count)'];
		print "<tr><td>Total Image-Tag Pairs<td align=right>".number_format($t,0);
		print "<tr><td colspan=2>Note: A lot of the tags are duplicate (mutliple people created similar tag for same concept) which if consonsolidated first, give more images in combined listing";
	}

	print "</table>";

#####################################################################
//sample images (small random list), GET['sample'] shows list of images per class instead!

	if (empty($_GET['sample'])) {

		//sphinx has defauilt limit of 20 on all queries!
		if ($prefix == 'curated') {
			//curated index, uses `id` as curated_id, not image id!
			$data = $sph->getAll("select gridimage_id,user_id,realname,title,$column FROM curated1");

		} elseif ($prefix == 'category') {
			$data = $sph->getAll("select id,user_id,realname,title,$column FROM sample8D WHERE $column != ''");
		} else {
			//todo, for subjects, maybe dont want to look up multi-labels
			$column2 = preg_replace('/s$/','_ids',$column);

			//n<5, because there are small number of images with silly amounts of tags!
			$data = $sph->getAll("select id,user_id,realname,title,$column,LENGTH($column2) as n FROM sample8D WHERE match('@$column _SEP_') AND n < 5 ORDER BY n DESC");
			$data2 = $sph->getAll("select id,user_id,realname,title,$column FROM sample8D WHERE match('@$column _SEP_')");

			$data = array_merge($data,$data2);
		}

		print "<hr><h3>Example of already labelled Images - to be used for Training</h3>";
		print "<p>Some images have multiple labels, looking for a model to predict a list of labels for an arbitary input image</p>";

		print "<table class=examples>";
		foreach($data as $row) {
			print "<tr>";

			$image = new GridImage();
			if (empty($row['gridimage_id']))
				$row['gridimage_id'] = $row['id'];
	                $image->fastInit($row);

			print "<td>".($html = $image->getSquareThumbnail(224,224))."</td>";

			$list = explode(' _SEP_ ',preg_replace('/(top|subject):/','',preg_replace('/(^\s*_SEP_\s*|\s*_SEP_\s*$)/','', $row[$column])));
			$list = array_map('htmlentities',$list);
			print "<td><span>".implode('</span>; <span>',$list)."</span>";
		}
		print "</table>";
	}

#####################################################################

	$smarty->display('_std_end.tpl');

