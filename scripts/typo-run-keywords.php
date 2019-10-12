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

        $param = array('type'=>'typo', 'size'=>0, 'debug'=>false, 'index'=>'gi_stemmed_delta', 'execute'=>true);

        chdir(__DIR__);
        require "./_scripts.inc.php";

#####################

set_time_limit(3600*24);

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


if ($param['type'] == 'typo') {
	$type= 'typo';

	$words = $db->getAssoc("SELECT include,profile FROM typo WHERE include!='' AND exclude='' AND enabled = 1 AND profile in ('keywords','either') AND updated < date_sub(now(),interval 3 hour)");
	$list = array();

	foreach ($words as $word => $profile) {
		if (preg_match('/^[\w \'"=]+$/',$word)) {
			$quotes = 0;
			$spaces = (strpos($word,' ') !== FALSE);
			$word = str_replace("'",' ',$word,$quotes);

			if ($spaces) {
				if ($profile == 'keywords') {
					if ($quotes) {
						$list[] = '"'.$word.'"';
					} else {
						$list[] = '('.$word.')';
					}
				} else {
					$list[] = '"'.preg_replace('/\b(\w)/','=$1',$word).'"';
				}
			} elseif ($quotes) {
				$list[] = '(='.str_replace(" ",'',$word).') | "'.$word.'"';
			} else {
				$list[] = $word;
			}
		}
	}

} elseif (file_exists('../../profanity-list.txt')) {

	$type = 'profanity';
	$words = explode(',',file_get_contents('../../profanity-list.txt'));

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
}

if (empty($list))
	die("no words!\n");

$q = strtolower(implode(' | ',$list));

if ($param['debug'])
	print "\n\n$q\n\n";

$limit = 100000;

if ($q) {
	//$q2 = preg_replace('/\b(the|and)\b/','',$q); //a more basic one for snippets
	$q2 = $q; //now using query_mode!

	//use cursors to loop, rather than traditional paging. Using SetIDRange so need to order by doc_id
	$more = true;
	$counter =0;

	if ($param['size']) {
		$last_id = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
		$min_id = max(1,$last_id-$param['size']);
	} else {
		$min_id = 1;
	}

	while ($more && $counter < 10) {
		$more = false; //so we have to explicitly enable it if want to go again...

		$sphinx = new sphinxwrapper();
		$sphinx->q = "@(title,comment,imageclass) ".$q;
		$sphinx->qoutput = '--query--';

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

			$ids = $sphinx->returnIds($pg,$param['index']);
			if ($param['debug'] == 2) {
				print "Cnt: ".count($ids)."   {$sphinx->query_info}\n";
				print_r($q);
				print_r($cl);
				exit;
			}

			if ($param['debug'])
				print "$min_id: {$sphinx->query_info}\n";

			if (!empty($ids) && count($ids)) {

				$imgs = $db->getAssoc("SELECT gridimage_id,title,comment,imageclass FROM gridimage_search WHERE gridimage_id IN (".implode(',',$ids).")");

				$docs = array();
				foreach ($ids as $idx => $id) {
					$docs[$idx] = ($imgs[$id]['title']).' '.strip_tags($imgs[$id]['comment']).' '.($imgs[$id]['imageclass']);
				}
				$reply = $sphinx->BuildExcerpts($docs, 'gi_stemmed', $q2, array('query_mode'=>1,'around'=>0,'limit'=>10,'before_match'=>'','after_match'=>'','allow_empty'=>1));

				foreach ($ids as $idx => $id) {
					$word = $db->Quote($reply[$idx]);

					if ($param['execute']) {
						$sql = "INSERT INTO gridimage_typo SET gridimage_id = $id,created=NOW(),`word` = $word,type='$type' ON DUPLICATE KEY UPDATE updated = NOW(),`word` = $word";
						$db->Execute($sql) or die("$sql;\n".mysql_error()."\n\n");
					} else {
						print "$id: $word\n";
					}
				}

				if ($limit > 1000 && count($ids) == 1000) { //we want, and there could be more
					$more = true;
					$min_id = array_pop($ids); //use the last one (we have $sphinx->sort = "@id ASC" :)
				}
			}

		} elseif ($param['debug']) {
			print "All Done\n";
		}
		$counter++;
	}
}


if ($param['debug'])
	print "fin.\n";
