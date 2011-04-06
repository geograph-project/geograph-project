<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

if (!isLocalIPAddress())
{
	init_session();
        $USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

set_time_limit(3600*24);


#####################

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;



$words = $db->getCol("SELECT include FROM typo WHERE exclude='' AND enabled = 1");
$list = array();

if (file_exists('../../profanity-list.txt')) {
	$words += explode(',',file_get_contents('../../profanity-list.txt'));
}

foreach ($words as $word) {
	if (preg_match('/^[\w \']+$/',$word)) {
		$word = str_replace("'",' ',$word);
		if (strpos($word,' ') !== FALSE) {
			$list[] = '"'.$word.'"';			
		} else {
			$list[] = $word;
		}
	}
}

$q = strtolower(implode(' | ',$list));

print $q;
$limit = 100000;

if ($q) {
	//use cursors to loop, rather than traditional paging. Using SetIDRange so need to order by doc_id
	$more = true;
	$min_id = 1;
	$counter =0;

	while ($more && $counter < 10) {
		$more = false; //so we have to explicitly enable it if want to go again...

		$sphinx = new sphinxwrapper($q);
		$sphinx->qoutput = '--query--';
		$q = $sphinx->q;

		$pg = 1;

		$sphinx->pageSize = $pgsize = min(1000,$limit); //max_matches

		$offset = (($pg -1)* $sphinx->pageSize);


		if ($offset <= (1000-$pgsize) ) {
			#$sphinx->processQuery();

			$cl = $sphinx->_getClient();

			if ($min_id > 1)  {
				$cl->SetIDRange($min_id,999999999);
			}

			$sphinx->sort = "@id ASC";

			$ids = $sphinx->returnIds($pg,'_images');
			if (isset($_GET['d'])) {
				print_r($q);
				print_r($cl);
			}

			if (!empty($ids) && count($ids)) {
				print "<p>{$sphinx->query_info}</p>";


				$imgs = $db->getAssoc("SELECT gridimage_id,title,comment,imageclass FROM gridimage_search WHERE gridimage_id IN (".implode(',',$ids).")");

				$docs = array();
				foreach ($ids as $idx => $id) {
					$docs[$idx] = ($imgs[$id]['title']).' '.strip_tags($imgs[$id]['comment']).' '.($imgs[$id]['imageclass']);
				}
				$reply = $sphinx->BuildExcerpts($docs, 'gi_stemmed', $q, array('around'=>0,'limit'=>10,'before_match'=>'','after_match'=>''));
				
				
				foreach ($ids as $idx => $id) {
					$word = $db->Quote($reply[$idx]);
					$sql = "INSERT INTO gridimage_typo SET gridimage_id = $id,created=NOW(),`word` = $word ON DUPLICATE KEY UPDATE updated = NOW(),`word` = $word";
					$db->Execute($sql);
				}

				$delta += count($ids);

				if ($limit > 1000 && count($ids) == 1000) { //we want, and there could be more
					$more = true;
					$min_id = array_pop($ids); //use the last one (we have $sphinx->sort = "@id ASC" :)
				}
			}

		} else {
			$delta = -1;
			print "<p>All Done</p>";
		}
		$counter++;
	}
}




print "<hr>";
