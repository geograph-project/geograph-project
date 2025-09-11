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

$smarty->display('_std_begin.tpl');



if (empty($_GET['query'])) { ?>
	<h2>Part 1 - Find Place</h2>
	<p>Start by entering the name, so we can try to locate in various databases...
	<form method=get>
		Location: <label><input type=radio name=ri value=1 checked>Great Britain</label> &middot;
		<label><input type=radio name=ri value=2>Ireland</label>
		<hr>
		Search: <input type=search name=query value="">
		<button type=submit>Continue...</button>
	</form>
	<p>Note, it genertally best to enter the placename WITHOUT the county. if there are mulitple places can select the right one one from the list on next screen.</p>
	<?
} else { 
	?>
	<h2>Part 2 - Select the place from various lists</h2>
	<p>Please select any of these that match the your specific place exactly. There may be false matches, just select the most specific result for the place.</p>
	<p>Note: it is quite possible your place isnt in every gazetteer/list</p>

	<form action="place-images.php" method=get>
	<style>
	#maincontent form label {
	    font-size: 1.05em;
	    line-height:1.2em;
	}</style>
	<input type=hidden name=query value="<? echo htmlentities($_GET['query']); ?>">
	<hr>
	<?
	$db = GeographDatabaseConnection(true);
	$sph = GeographSphinxConnection('sphinxql',true);
	$sprt = GeographSphinxConnection('manticorert',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$conv = new Conversions;

	$ri = intval($_GET['ri']);
	$query = $sprt->Quote(trim($_GET['query']));

//(The indexes in the RT server are the 'raw' gazetteers, rather than derived gazetters!)

	if ($ri == 1) {
	//1. os_gaz (sphinx_placenames)
		$sql = "select id,concat(def_nam,', ',km_ref,', ',full_county,' (',f_code,')') as label from os_gaz where match($query)";
		renderResults("OS 50k Gazetteer", 'os_gaz', $sprt->getAll($sql) );

	//2. os_gaz_250
		$sql = "select id,concat(def_nam,', ',km_ref,', ',full_county) as label from os_gaz_250 where match($query)";
		renderResults("OS 250k Gazetteer", 'os_gaz_250', $sprt->getAll($sql) );

	//3. ab_gaz
		$sql = "select id,full_name,gridref,hcounty from abgaz where match($query)";
		if ($rows = $sph->getAll($sql)) {
			//old manticore server doesnt support CONCAT!
			foreach($rows as &$row) {
				$row['label'] = "{$row['full_name']}, {$row['gridref']}, {$row['hcounty']}";
			} unset($row);
        	        renderResults("Historic Country Gazetteer", 'loc_abgaz', $rows );
		}
	}

	//4. loc_placenames
			//NOTE we cant get a km_ref, nor do we have county - adm1 is often missing anyway!
		$sql = "select id,full_name,e,n,reference_index from loc_placenames where match($query) and reference_index = $ri";
		if ($rows = $sprt->getAll($sql)) {
			foreach($rows as &$row) {
				list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],6,$row['reference_index']);
				$row['label'] = "{$row['full_name']}, $gridref";
			} unset($row);
			renderResults("GNS Gazetteer", 'loc_placenames', $rows );
		}

	//5. os_open_names
	if ($ri == 1) {
		//this queries the full table, so not specifically settlements
		$sql = "select id,name1,name2,geometry_x,geometry_y,county_unitary,local_type from os_open_names where match($query)
			 and local_type in ('Other Settlement','Hamlet','Village','Town','City','Suburban Area') limit 40
			option field_weights=(name1=10,name2=9)";
		if ($rows = $sph->getAll($sql)) {
			foreach($rows as &$row) {
				list ($gridref,) = $conv->national_to_gridref($row['geometry_x'],$row['geometry_y'],6,$ri);
				$bits = array($row['name1'], $row['name2'], $gridref, $row['county_unitary'], $row['local_type']);
				$row['label'] = implode(', ',array_filter($bits));
			} unset($row);
			renderResults("OS Open Names", 'os_open_names', $rows );
		}
	}

	//6. ie_open_data
	if ($ri == 2) {
		$sql = "select id,name,irish,county,town_class,country, e,n from ie_open_data where match($query)";
		if ($rows = $sprt->getAll($sql)) {
			foreach($rows as &$row) {
				list ($gridref,) = $conv->national_to_gridref($row['e'],$row['n'],4,$ri);
				$bits = array($row['name'], utf8_to_latin1($row['irish']), $gridref, $row['county'], $row['town_class'], $row['country']);
				$row['label'] = implode(', ',array_filter($bits));
			} unset($row);
			renderResults("Ireland OpenData", 'ie_open_data', $rows );
		}
	}

	//7. features (Exclude placenames!) - but will cover rivers, greanspaces etc
		//4=greenspace copy, 5=os_open_names and 9=ie_open which already included above
		$sql = "select id,feature_type_id,grid_reference,name,county,category from feature_item where match($query) and feature_type_id NOT in (4,5,9) limit 40
			option field_weights=(name=10,category=9)";
		if ($rows = $sph->getAll($sql)) {
			$names= $db->getAssoc("select feature_type_id,title FROM feature_type");
			foreach($rows as &$row) {
				$bits = array($row['name'], $row['grid_reference'], $row['county'], $row['category'], @$names[$row['feature_type_id']]);
				$row['label'] = implode(', ',array_filter($bits));
			} unset($row);
			renderResults("Features Directory", 'feature_item', $rows, 'radio', 'Wont generally include placenames, so expected not to find settlements in this list');
		}

	//8. tags
		$query2 = $sprt->Quote("({$_GET['query']}) | (^{$_GET['query']}) | ({$_GET['query']}$)");
		$sql = "select id,prefix,tag FROM  tags where match($query2) limit 100";
		if ($rows = $sph->getAll($sql)) {
			foreach($rows as &$row) {
				$bits = array($row['prefix'], $row['tag']);
				$row['label'] = implode(':',array_filter($bits));
			} unset($row);
			renderResults("Tags", 'tags[]', $rows, 'checkbox', "Reminder: looking for tags that are for the specific place, not just something related, or nearby. For example wouldnt select 'Crawley Church' for 'Crawley' as it more specific tag. Also if a tag is amigious if refers the sepecific place you searching, do not select it" );
		}

	//9. content
		$sql = "select id,asource,title FROM content where match($query) and asource !=6 limit 100";
		if ($rows = $sph->getAll($sql)) {
			//seems bad way to do it, but runs quickly!
			$names= $db->getAssoc("select source+0 as asource, source from content group by source order by null");
			$names[9] = "shared description";
			foreach($rows as &$row) {
				$bits = array($row['title'], @$names[$row['asource']]);
				$row['label'] = implode(", ",array_filter($bits))."\n";
			} unset($row);
			renderResults("Collections", 'contents[]', $rows, 'checkbox', "There is a chance that someone has created a collection for this place. Only select if appears the collection is specifially 'for' this place");
		}

	print "<button type=submit>Continue..</button>";
	print "</form>";
}

function renderResults($title,$name,$rows, $type='radio', $comment = false) {
	if (empty($rows))
		return;
	print "<h3>$title</h3>";
	if (!empty($comment))
		print "<p>$comment.</p>";
	foreach($rows as $row) {
		$id    = htmlentities($row['id']);
		$label = htmlentities($row['label']);
		$style = preg_match('/\bnear\b/i',$row['label'])?' style=color:gray':'';
		print "&nbsp;&nbsp;&nbsp;<label$style><input type=$type name=$name value=\"$id\"> $label</label> <br>";
	}
	print "&nbsp;&nbsp;&nbsp;<label><input type=$type name=$name value=0> <i>None of these fit</i></label>";
	print "<hr>";
}







$smarty->display('_std_end.tpl');



