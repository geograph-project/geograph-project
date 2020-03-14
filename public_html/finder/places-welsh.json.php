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

	if ((
	preg_match("/^[^:]*\b([A-Z]{1,2})([0-9]{1,2}[A-Z]*) +([0-9])([A-Z]{0,2})\b/i",strtoupper($q),$pc)
	|| preg_match("/^[^:]*\b([A-Z]{1,2})([0-9]{1,2}[A-Z]*) *([0-9])([A-Z]{2})\b/i",strtoupper($q),$pc)
	|| preg_match("/^[^:]*\b([A-Z]{1,2})([0-9]{1,2}[A-Z]?)\b/i",strtoupper($q),$pc) )
	&& !in_array(strtoupper($pc[1]),array('SV','SX','SZ','TV','SU','TL','TM','SH','SJ','TG','SC','SD','NX','NY','NZ','OV','NS','NT','NU','NL','NM','NO','NF','NH','NJ','NK','NA','NB','NC','ND','HW','HY','HZ','HT','Q','D','C','J','H','F','O','T','R','X','V')) ) {
		//these prefixs are not postcodes but are valid gridsquares

		if ($pc[1] != 'BT' && $pc[4]) { //GB can do full postcodes now!
			$code = strtoupper($pc[1].$pc[2]." ".$pc[3].$pc[4]);
		} else {
			$code = strtoupper($pc[1].$pc[2].($pc[3]?" ".$pc[3]:''));
		}

		if (empty($db))
			$db = GeographDatabaseConnection(true);

	//outcode only
		if (strpos($code,' ') === FALSE) {
			$postcodes = $db->GetAll('select code,e,n,reference_index from loc_postcodes where code like '.$db->Quote("$code _").'');

	//full unit postcode
		} elseif ($pc[1] != 'BT' && preg_match("/([0-9])([A-Z]{2})$/i",strtoupper($code)) ) { //GB can do full postcodes now!
			//codepoint open encodes it as a 7char string. rather than being always with/without a space.
			if (strlen($code) == 8) {
				$code = str_replace(' ','',$code);
                        } elseif (strlen($code) == 6) {
                                $code = str_replace(' ','  ',$code);
			}
			$postcodes = $db->GetAll('select code,e,n,1 as reference_index from postcode_codeopen where code='.$db->Quote($code).' limit 1');

	//1 digit missing
		} elseif ($pc[1] != 'BT' && preg_match("/([0-9])([A-Z]{1})$/i",strtoupper($code)) ) {
                        //codepoint open encodes it as a 7char string. rather than being always with/without a space.
			if (strlen($code) == 7) {
                                $code = str_replace(' ','',$code);
                        } elseif (strlen($code) == 5) {
                                $code = str_replace(' ','  ',$code);
			}
                        $postcodes = $db->GetAll('select code,e,n,1 as reference_index from postcode_codeopen where code like '.$db->Quote($code."_").' limit 40');
	//sector
		} else {
			$postcodes = $db->GetAll('select code,e,n,reference_index from loc_postcodes where code='.$db->Quote($code).' limit 1');

			if ($pc[1] != 'BT') {
				//codepoint open encodes it as a 7char string. rather than being always with/without a space.
				if (strlen($code) == 6) {
					$code = str_replace(' ','',$code);
	                        } elseif (strlen($code) == 4) {
        	                        $code = str_replace(' ','  ',$code);
				}
				if ($postcodes2 = $db->GetAll('select code,e,n,1 as reference_index from postcode_codeopen where code like '.$db->Quote($code."__").' limit 40')) {
					$postcodes = array_merge($postcodes,$postcodes2);
				}
			}
		}

		if (!empty($postcodes)) {
			$conv = new Conversions();

			$results['items'] = array();
			foreach ($postcodes as $row) {
				if (strlen($row['code']) == 7 && strpos($row['code'],' ') === false) {
					$row['code'] = substr($row['code'],0,4).' '.substr($row['code'],4,3);
				}
				list($gr,$len) = $conv->national_to_gridref($row['e'],$row['n'],8,$row['reference_index']);
				$output = array(
					'name' => "God post ".$row['code'],
					'gr' => $gr,
					'localities'=>''
				);

				$results['items'][] = $output;
			}
			$results['total_found'] = count($postcodes);
			$results['query_info'] = '';
			$results['copyright'] = "Yn cynnwys data'r OS (c) Hawlfraint y Goron [a hawl cronfa ddata] 2018";
		}
	} elseif (preg_match("/^\s*([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\s*$/",$q,$gr)) {
                require_once('geograph/gridsquare.class.php');
                $square=new GridSquare;
                $grid_ok=$square->setByFullGridRef($gr[1].$gr[2].$gr[3],false,true);
                if ($grid_ok || $square->x && $square->y) {
			$results['items'] = array();
			$output = array(
                                'name' => "Gyfeirnod Grid",
                                'gr' => strtoupper($gr[1].$gr[2].$gr[3]),
                                'localities'=>$gr[1]
                        );
			$results['items'][] = $output;
			$results['total_found'] = count($results['items']);
        	        $results['query_info'] = '';
		}
	}

	if (empty($results)) {

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
			$rows = $sph->getAll($sql = "SELECT *,WEIGHT() AS w FROM gaz_welsh WHERE MATCH($quote) ORDER BY w DESC, scale DESC, english ASC LIMIT $offset,$pgsize $option");
			$data = $sph->getAssoc("SHOW META");

//$results['query'] = $sql;

			if (!empty($rows) && count($rows)) {

		                require_once('geograph/conversions.class.php');
                		$conv = new Conversions;

				foreach ($rows as $idx => $row) {
					$rows[$idx]['english'] = utf8_encode($row['english']); //json always want utf8. even sphinx_placnames now contain latin1 data.
					list($rows[$idx]['gridref'],$len) = $conv->national_to_gridref($row['e'],$row['n'],4,1,false);
				}

				$results['items'] = $rows;
				$results['total_found'] = $data['total_found'];
				$results['query_info'] = "Wedi canfod {$data['total_found']} canlyniad mewn {$data['time']} eiliad";
				$results['copyright'] = "Yn cynnwys data'r OS (c) Hawlfraint y Goron [a hawl cronfa ddata] 2018";
			}
		} else {
			$results = "Ni fydd y peiriant chwilio ond yn dangos y 1000 canlyniad cyntaf - rhaid mireinio eich chwilio i gael canlyniadau gyda ffocws mwy pendant";
		}
	}
} else {
	$results = "No query!";
}


if (!empty($_SERVER['HTTP_ORIGIN'])
	&& preg_match('/^https?:\/\/(m|www|schools)\.geograph\.(org\.uk|ie)\.?$/',$_SERVER['HTTP_ORIGIN'])) { //can be spoofed, but SOME protection!

	header('Access-Control-Allow-Origin: *'); //although now this allows everyone to access it!
}

outputJSON($results);
