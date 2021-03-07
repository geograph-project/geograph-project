<?php
/**
 * $Project: GeoGraph $
 * $Id: snippets.json.php barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2021 Barry Hunter (geo@barryhunter.co.uk)
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

if (!empty($_GET['mode']) && $_GET['mode'] == 'selfrecent' && !empty($_SESSION['last_grid_reference'])) { //appears have been uploading recently!
	$db = GeographDatabaseConnection(10); //very little lag
} elseif (!empty($_GET['gridimage_id'])) {
	$db = GeographDatabaseConnection(false); //no lag!
} else {
	$db = GeographDatabaseConnection(true); //allows even large lag!
}

$sql = array();

$sql['tables'] = array();
$sql['tables']['s'] = 'snippet s';
$sql['tables']['u'] = 'LEFT JOIN user u USING (user_id)';

$sql['wheres'] = array();
$sql['wheres'][] = "s.enabled = 1";

if (isset($_GET['term'])) {
	$_REQUEST['q'] = $_GET['q'] = $_GET['term'];
	if (empty($_GET['term']) && !empty($CONF['sphinx_host'])) {
		$_REQUEST['q'] = $_GET['q'] = '..'; //falls though as an empty to query, which sphinx now orders by images desc - so gives most popular snippets!
	}
	$sql['columns'] = "snippet_id,title,comment,s.grid_reference,s.user_id,u.realname";
} else {
	$sql['columns'] = "snippet_id,title,comment,s.grid_reference,s.user_id,u.realname";
}

if (!empty($_GET['mode']) && $_GET['mode'] == 'selfrecent' && empty($_GET['term'])) {
	init_session();
	customExpiresHeader(30,false,true);

	if ($USER->registered) {

		$sql['tables']['gs'] = 'INNER JOIN gridimage_snippet gs USING (snippet_id)';

		$sql['wheres'][] = "gs.user_id = {$USER->user_id}";
		$sql['wheres'][] = "gridimage_id < 4294967296";

		$sql['columns'] .= ",MAX(gs.created) AS last_used";

		$sql['group'] = 'snippet_id';
		$sql['order'] = 'last_used DESC';

		$sql['limit'] = 59;
	}

} elseif (!empty($_GET['gridimage_id'])) {
	init_session();
	if (!$USER->registered) {
		die("{error: 'not logged in'}");
	}
	customExpiresHeader(180,false,true);

	$sql['tables']['gs'] = 'INNER JOIN gridimage_snippet gs USING (snippet_id)';

	$sql['wheres'][] = "gs.user_id = ".$USER->user_id;
	$sql['wheres'][] = "gs.gridimage_id = ".intval($_GET['gridimage_id']);

	$sql['order'] = 'gs.created';

} elseif (!empty($_GET['snippet_ids']) && preg_match('/\d+(,\d+)*/',$_GET['snippet_ids'])) {
        customExpiresHeader(3600*24);

        $sql['wheres'][] = "snippet_id IN (".($_GET['snippet_ids']).")";

} elseif (!empty($_GET['gridref'])) {
        customExpiresHeader(180);
        $sql['columns'] = "gridimage_id,".$sql['columns'];

	$sql['tables']['gs'] = 'INNER JOIN gridimage_snippet gs USING (snippet_id)';
        $sql['tables']['gi'] = 'INNER JOIN gridimage_search gi USING (gridimage_id)';

        $sql['wheres'][] = "gi.grid_reference = ".$db->Quote($_GET['gridref']);

} elseif (!empty($_GET['q'])) {

	if (!empty($CONF['sphinx_host'])) {
		if (strpos($_REQUEST['q'],':') !== FALSE) {
			list($prefix,$_REQUEST['q']) = explode(':',$_REQUEST['q'],2);
		}

                $q = trim(preg_replace('/[^\w@!|-]+/',' ',str_replace("'",'',$_REQUEST['q'])));

		$pgsize = 60;
		if (!empty($_GET['limit']) && $_GET['limit']<=1000)
			$pgsize = intval($_GET['limit']);
		$sphinx = new sphinxwrapper($q);
		$sphinx->pageSize = $pgsize;

		$pg = (!empty($_REQUEST['page']))?intval(str_replace('/','',$_REQUEST['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}

		$offset = (($pg -1)* $sphinx->pageSize);

		if ($offset <= (1000-$pgsize) ) {
			$client = $sphinx->_getClient();
			if (!empty($_GET['counts'])) {
                                $client->SetSelect('images /* OPTION expand_keywords=1 */'); //in the API, always returns ID anyway!
                        } else {
	                        $client->SetSelect('id /* OPTION expand_keywords=1 */');
			}

			if (!empty($sphinx->q)) {
				$before = $sphinx->q;

				if (!preg_match('/[@"|-]/',$sphinx->q) && //this doesnt work, if already operatores in the search
						!preg_match('/(images|alpha)$/',$_GET['mode'])) { //no point doing all this, ebcause going to ignore WEIGHT()

					$sphinx->q = "\"^{$sphinx->q}$\" | \"=^{$sphinx->q}$\" | \"^{$sphinx->q}\" | \"{$sphinx->q}$\" | (^$sphinx->q) | (=$sphinx->q) | ($sphinx->q)";
				}

				if (preg_match('/ /',$before) && strpos($before,'@') !== 0)
					$sphinx->q .= " | ".str_replace(' ','',$before);
			}

			if (!empty($_GET['mode'])) {
				switch($_GET['mode']) {
					case 'alpha':
						$sphinx->sort = "title ASC";
						//... falls though to use exclusion for top
					case 'ranked': //the default anyway!
						break;

					case 'selfimages':
						$sphinx->sort = "images DESC";
						$_GET['mine'] = 1; //actully used below
						break;
					case 'selfalpha':
						$sphinx->sort = "title ASC";
						$_GET['mine'] = 1; //actully used below
						break;
					case 'selfrecent':
						//TODO - dont know how this going to work...
						//(for now its handled by a special caluse at top of this file)
						$_GET['mine'] = 1;
						break;

					case 'nearby':
						if (!empty($_GET['gr']) && preg_match('/^([A-Z]{1,2}) (\d{2})\d* (\d{2})\d*$/i',$_GET['gr'],$m)) {
							$_GET['gr'] = $m[1].$m[2].$m[3];
						}
						if (!empty($_GET['gr']) && preg_match('/^\w{1,2}\d{4}$/',$_GET['gr'])) {
							if (empty($sphinx->q)) {
								$sphinx->q = $_GET['gr'];
							} else {
								$sphinx->q = "{$_GET['gr']} ({$sphinx->q})";
							}
							$sphinx->processQuery();
							$sphinx->q = str_replace('@grid_reference (',"@image_square ({$_GET['gr']} | ",$sphinx->q);
						}
						break;
				}
			}

			if ($sphinx->sort == "title ASC") {
				$client->SetRankingMode(SPH_RANK_NONE);
			} elseif ($sphinx->sort == "images DESC") {
				$client->SetRankingMode(SPH_RANK_NONE);
			} else {
				//$client->SetRankingMode(SPH_RANK_SPH04);
				$client->SetRankingMode(SPH_RANK_WORDCOUNT);
				$client->setFieldWeights(array('title'=>10));
				$sphinx->sort = "@relevance DESC, images DESC, @id DESC"; //overall sort order
			}

			if (isset($_GET['mine'])) {
			        init_session();
			        if (!$USER->registered) {
			                die("{error: 'not logged in'}");
			        }
				$sphinx->addFilters(array('user_id'=>array($USER->user_id)));
			} elseif (isset($_GET['user_id'])) {
				$sphinx->addFilters(array('user_id'=>array(intval($_GET['user_id']))));
			}

			$ids = $sphinx->returnIds($pg,'snippet');

			//second chance, as a 'prefix' search :)
			if (empty($ids) && strlen($before) > 2 && strpos($before,'@') === FALSE) {
				$sphinx->q = '@title '.$before.'*';
				//todo, this wipes out the 'nearby' etc, maybe we could fix that
				$sphinx->sort = "title ASC";
				$client->SetRankingMode(SPH_RANK_NONE);
				$ids = $sphinx->returnIds($pg,'snippet');
			}

			if (!empty($ids) && count($ids)) {
				//todo, if dont need comment, could just return the sphinx resultset directly (it has the title as attribute!)
	
				$idstr = join(",",$ids);
				$where = "snippet_id IN(".join(",",$ids).")";

				$sql['wheres'] = array("`snippet_id` IN ($idstr)");
				$sql['order'] = "FIELD(`snippet_id`,$idstr)";
				$sql['limit'] = count($ids);
			} else {
				$sql['wheres'] = array(0);
			}
		} else {
			$sql['wheres'] = array(0);
		}
	} else {
		$sql['tables']['gs'] = 'INNER JOIN gridimage_snippet gs USING (snippet_id)';

		$sql['wheres'] = array("`title` LIKE ".$db->Quote($_GET['q'].'%'));

		$sql['group'] = 'snippet_id';

		$sql['order'] = '`title`';

		$sql['limit'] = 100;
	}
	//todo sort by popularity?

	if (isset($_GET['mine'])) {
		customExpiresHeader(3600);
	} else {
		customExpiresHeader(3600*3,true);
	}
} else {
	die("todo");
}

$query = sqlBitsToSelect($sql);
if (!empty($_GET['deb']))
        print_r($query);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$data = $db->getAll($query);
	if (!empty($_GET['counts']) && !empty($sphinx) && !empty($sphinx->res)) {
		foreach ($data as $idx => $row) {
			$data[$idx]['images'] = $sphinx->res['matches'][$row['snippet_id']]['attrs']['images'];
		}
	}

outputJSON($data);

