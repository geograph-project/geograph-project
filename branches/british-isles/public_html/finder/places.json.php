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
					'name' => "Postcode ".$row['code'],
					'gr' => $gr,
					'localities'=>''
				);

				$results['items'][] = $output;
			}
			$results['total_found'] = count($postcodes);
			$results['query_info'] = '';
			$results['copyright'] = "Contains Ordnance Survey data (c) Crown copyright and database right 2012";
		}
	} elseif (preg_match("/^\s*([a-zA-Z]{1,2}) ?(\d{1,5})[ \.]?(\d{1,5})\s*$/",$q,$gr)) {
                require_once('geograph/gridsquare.class.php');
                $square=new GridSquare;
                $grid_ok=$square->setByFullGridRef($gr[1].$gr[2].$gr[3],false,true);
                if ($grid_ok || $square->x && $square->y) {
			$results['items'] = array();
			$output = array(
                                'name' => "Grid Reference",
                                'gr' => strtoupper($gr[1].$gr[2].$gr[3]),
                                'localities'=>$gr[1]
                        );
			$results['items'][] = $output;
			$results['total_found'] = count($results['items']);
        	        $results['query_info'] = '';
		}
	} elseif (!empty($_GET['legacy'])) {
		 $gaz = new Gazetteer();

		$prev_fetch_mode = $ADODB_FETCH_MODE;
                $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

                $places = $gaz->findPlacename($_GET['q']);

		if (!empty($places))
			$places= array_values($places); //just to reset keys to prevent json writing an object, because keys not consectuative
		else
			$places= array();

		$results['items'] = $places;
		$results['query_info'] = '';
		$results['copyright'] = "Great Britain results (c) Crown copyright Ordnance Survey. All Rights Reserved. 100045616";

		$ADODB_FETCH_MODE = $prev_fetch_mode;
	}

	if (empty($results)) {
		$fuzzy = !empty($_GET['f']);

		$sphinx = new sphinxwrapper($q);

		$sphinx->pageSize = $pgsize = 15;


		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}



		$offset = (($pg -1)* $sphinx->pageSize)+1;

		if ($offset < (1000-$pgsize) ) {
			$sphinx->processQuery();

			$sphinx->sort = "score ASC, @relevance DESC, @id DESC";

			if ($fuzzy) {
				$sphinx->_getClient()->SetIndexWeights(array('gaz'=>10,'gaz_meta'=>1));
				$ids = $sphinx->returnIds($pg,'gaz,gaz_meta');
			} elseif (!empty($_GET['new'])) {
				$sphinx->sort = "@relevance DESC, @id ASC";
				$client = $sphinx->_getClient();
				if (preg_match('/^[\w ]+$/',$sphinx->q)) {
					$client->SetRankingMode(SPH_RANK_WORDCOUNT);
					$sphinx->q = "({$sphinx->q}) | \"{$sphinx->q}\" | @Place \"^{$sphinx->q}\$\" | @Place (^{$sphinx->q})";
				} else
					$client->SetRankingMode(SPH_RANK_SPH04);
				$ids = $sphinx->returnIds($pg,'gaznew');
			} else {
				$ids = $sphinx->returnIds($pg,'gaz');
			}

			if (!empty($ids) && count($ids)) {
				$where = "id IN(".join(",",$ids).")";

				if (empty($db))
					$db = GeographDatabaseConnection(true);

				$limit = 25;

				$prev_fetch_mode = $ADODB_FETCH_MODE;
				$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
				if (!empty($_GET['new'])) {
					$rows = $db->getAssoc("select placename_id,Place as name,km_ref as gr,concat(County,', ',Country) as localities from sphinx_placenames where placename_$where limit $limit");
				} else {
					$rows = $db->getAssoc("select id,name,gr,localities,score from placename_index where $where limit $limit");
				}

				$results['items'] = array();
				foreach ($ids as $c => $id) {
					$row = $rows[$id];
					$row['id'] = $id;
					$results['items'][] = $row;
				}
				$results['total_found'] = $sphinx->resultCount;
				$results['query_info'] = $sphinx->query_info;
				$results['copyright'] = "Great Britain results (c) Crown copyright Ordnance Survey. All Rights Reserved. 100045616";
			}
		} else {
			$results = "Search will only return 1000 results - please refine your search";
		}
	}
} else {
	$results = "No query!";
}




outputJSON($results);
