<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 6407 2010-03-03 20:44:37Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

$results = array();


if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);


		if (mb_detect_encoding($q, 'UTF-8, ISO-8859-1') == "UTF-8") {
			$q = utf8_to_latin1($q); //even though we nominially latin1, browsers can still send us UTF8 queries
		}

		$sphinx = new sphinxwrapper();
		$sphinx->prepareQuery($q,true);

		$sphinx->pageSize = $pgsize = 15;
                if (!empty($_GET['more']))
                        $sphinx->pageSize = $pgsize = 150;


		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}



		$offset = (($pg -1)* $sphinx->pageSize);

		if ($offset < (1000-$pgsize) ) {
			//$sphinx->processQuery(); //dont call this, we deal with this ourselfs

			$option = '';
			if (preg_match('/^[\w ]+$/',$sphinx->q)) {
				$option = "OPTION ranker=wordcount";
				$sphinx->q = "({$sphinx->q}*) | \"{$sphinx->q}*\" | @welsh \"^{$sphinx->q}*\" | @welsh \"^{$sphinx->q}*\" | @english \"^{$sphinx->q}*\$\" | @english (^{$sphinx->q}*)";
			}

			$sph = GeographSphinxConnection('sphinxql',true);
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

			$quote = $sph->Quote($sphinx->q);
			$rows = $sph->getAll($sql = "SELECT *,WEIGHT() AS w FROM curated_welsh WHERE MATCH($quote) ORDER BY w DESC, welsh ASC LIMIT $offset,$pgsize $option");
			$data = $sph->getAssoc("SHOW META");

//$results['query'] = $sql;
			if (empty($rows)) {
				//this is tricky and we ONLY at the moment do if no word matches, instead (try) translate via gazetter!
				$quote = $sph->Quote("@welsh $q"); //use original query, not the fixed one!
	                        $rows = $sph->getAll($sql = "SELECT *,WEIGHT() AS w FROM gaz_welsh WHERE MATCH($quote) ORDER BY w DESC, welsh ASC LIMIT $offset,$pgsize");
        	                $data = $sph->getAssoc("SHOW META");
			}

			if (!empty($rows) && count($rows)) {
				$results['items'] = $rows;
				$results['total_found'] = $data['total_found'];
				$results['query_info'] = "Wedi canfod {$data['total_found']} canlyniad mewn {$data['time']} eiliad";
			}
		} else {
			$results = "Ni fydd y peiriant chwilio ond yn dangos y 1000 canlyniad cyntaf - rhaid mireinio eich chwilio i gael canlyniadau gyda ffocws mwy pendant";
		}

} else {
	$results = "Rhowch ymholiad!";
}


if (!empty($_SERVER['HTTP_ORIGIN'])
	&& preg_match('/^https?:\/\/(m|www|schools)\.geograph\.(org\.uk|ie)\.?$/',$_SERVER['HTTP_ORIGIN'])) { //can be spoofed, but SOME protection!

	header('Access-Control-Allow-Origin: *'); //although now this allows everyone to access it!
}

outputJSON($results);
