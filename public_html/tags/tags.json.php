<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 8036 2014-04-05 18:29:41Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($_GET['mode']))
	$_GET['mode'] = ''; //just to silence notices later on, when they test this variable.

if ($_GET['mode'] == 'suggestions' && !empty($_GET['string'])) {
	require("./topics.json.php");
	exit;
} elseif ($_GET['mode'] == 'prospective' && !empty($_GET['string'])) {
	require("./prospective.json.php");
	exit;
} elseif ($_GET['mode'] == 'automatic' && !empty($_GET['string'])) {
	$_GET['topics'] = 1;
	require("./prospective.json.php");
	exit;
} elseif ($_GET['mode'] == 'categories') {
	$_REQUEST['q'] = $_GET['q'] = $_GET['term'];
        if (empty($_GET['term'])) {
                $_REQUEST['q'] = $_GET['q'] = '..'; //falls though as an empty to query
        }
	$_GET['mine'] = 1;
	require("../finder/categories.json.php");
	exit;
}

require_once('geograph/global.inc.php');

if ($_GET['mode'] == 'selfrecent' && !empty($_SESSION['last_grid_reference'])) { //appears have been uploading recently!
	$db = GeographDatabaseConnection(60); //very little lag
} elseif (!empty($_GET['gridimage_id'])) {
	$db = GeographDatabaseConnection(false); //no lag!
} else {
	$db = GeographDatabaseConnection(true); //allows even large lag!
}

$sql = array();

$sql['tables'] = array();
$sql['tables']['t'] = 'tag';
$sql['wheres'] = array();

if (isset($_GET['term'])) {
	$_REQUEST['q'] = $_GET['q'] = $_GET['term'];
	$sql['columns'] = "if (tag.prefix != '' and not (tag.prefix='term' or tag.prefix='category' or tag.prefix='cluster' or tag.prefix='wiki'),concat(tag.prefix,':',tag.tag),tag.tag) as tag";
	if (empty($_GET['term']) && !empty($CONF['sphinx_host'])) {
		$_REQUEST['q'] = $_GET['q'] = '..'; //falls though as an empty to query, which sphinx now orders by images desc - so gives most popular tags!
	}
} elseif (!empty($_GET['synonums'])) {
	$sql['columns'] .= "tag.tag,tag.prefix,canonical,tag_id,`count` as images";
        $sql['tables']['stat'] = 'LEFT JOIN tag_stat ts USING (tag_id)';

} else {
	$sql['columns'] = "tag.tag,if (tag.prefix='term' or tag.prefix='category' or tag.prefix='cluster' or tag.prefix='wiki','',tag.prefix) as prefix";
}

if ($_GET['mode'] == 'selfrecent' && empty($_GET['term'])) {
	init_session();
	customExpiresHeader(30,false,true);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	if ($USER->registered) {

		$sql['tables']['gt'] = 'INNER JOIN gridimage_tag gt USING (tag_id)';

		$sql['wheres'][] = "gt.user_id = {$USER->user_id}";
		$sql['wheres'][] = "prefix != 'top'";
		$sql['wheres'][] = "prefix != 'type'";
		$sql['wheres'][] = "prefix != 'milestoneid'";
		$sql['wheres'][] = "gridimage_id < 4294967296";

		if ($USER->user_id == 2639) {

			//this user deletes a lot of tags
			$sql['wheres'][] = "gt.status = 2";
			$sql['order'] = 'gt.updated DESC';
			$sql['limit'] = 500;

			//if there are lots of tags, this turns out most effient way to 'group' it. A real group, groups all rows, order then limits.
			$sql = array(
				'columns' => 'DISTINCT *',
				'tables' => array("(".sqlBitsToSelect($sql).") AS t2"),
			);

		} else {
			$sql['columns'] .= ",MAX(gt.created) AS last_used";

			$sql['group'] = 'tag.tag_id';
			$sql['order'] = 'last_used DESC';
		}

		$sql['limit'] = 59;
	}

} elseif (!empty($_GET['gridimage_id'])) {
	init_session();
	if (!$USER->registered) {
		die(json_encode(array('error'=>'not logged in')));
	}
	customExpiresHeader(180,false,true);

	$sql['columns'] .= ",gt.status";

	$sql['tables']['gt'] = 'INNER JOIN gridimage_tag gt USING (tag_id)';

	$sql['wheres'][] = "tag.status = 1";
	if (isset($_GET['buckets'])) {
		$sql['wheres'][] = "( (gt.user_id = {$USER->user_id} AND gt.status = 1) OR (prefix = 'bucket' AND gt.status = 2) )";
	} else
		$sql['wheres'][] = "gt.user_id = ".$USER->user_id;
	$sql['wheres'][] = "gt.gridimage_id = ".intval($_GET['gridimage_id']);

	$sql['order'] = 'gt.created';

} elseif (!empty($_GET['tag_ids']) && preg_match('/\d+(,\d+)*/',$_GET['tag_ids'])) {
        customExpiresHeader(3600*24);

        $sql['columns'] = "tag_id,".$sql['columns'];

        $sql['wheres'][] = "status = 1";
        $sql['wheres'][] = "tag_id IN (".($_GET['tag_ids']).")";

} elseif (!empty($_GET['gridref'])) {
        customExpiresHeader(180);
        $sql['columns'] = "gridimage_id,".$sql['columns'];

	//todo, convert  tag_square_stat!
        $sql['tables']['gt'] = 'INNER JOIN gridimage_tag gt USING (tag_id)';
        $sql['tables']['gi'] = 'INNER JOIN gridimage_search gi USING (gridimage_id)';

        $sql['wheres'][] = "tag.status = 1";
        $sql['wheres'][] = "gt.status = 2";
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
				$client->SetSelect('images /* OPTION expand_keywords=1 */');
				$sql['columns'] .= ",tag_id";
			} else {
	                        $client->SetSelect('id /* OPTION expand_keywords=1 */');
			}

			if (!empty($sphinx->q)) {
				$before = $sphinx->q;

				if (!preg_match('/[@"|-]/',$sphinx->q) && //this doesnt work, if already operatores in the search
						!preg_match('/(images|alpha)$/',$_GET['mode'])) { //no point doing all this, ebcause going to ignore WEIGHT()

					$sphinx->q = "\"^{$sphinx->q}$\" | \"=^{$sphinx->q}$\" | \"^{$sphinx->q}\" | \"{$sphinx->q}$\" | (^$sphinx->q) | (=$sphinx->q) | ($sphinx->q) | @tag (^$sphinx->q) | @tag \"^{$sphinx->q}$\"";
				}

				if (preg_match('/ /',$before) && strpos($before,'@') !== 0)
					$sphinx->q .= " | ".str_replace(' ','',$before);

				if (!empty($prefix)) {
					$sphinx->q = "({$sphinx->q}) @prefix $prefix";
				}
			} elseif (!empty($prefix)) {
				$sphinx->q = "\"^{$prefix}$\" | (^$prefix) | ($prefix) | @tag (^$prefix) | @tag \"^{$prefix}$\" | @prefix \"^{$prefix}\" | @prefix \"^{$prefix}\" | @prefix \"^{$prefix}\"";
			}

			if (!empty($_GET['mode'])) {
				switch($_GET['mode']) {
					case 'alpha':
						$sphinx->sort = "tag ASC";
						//... falls though to use exclusion for top
					case 'ranked': //the default anyway!
						if (empty($prefix)) {
							if (empty($sphinx->q)) {
								$sphinx->q = "_ALL_ @prefix -top -subject -bucket";
							} else {
								$sphinx->q = "({$sphinx->q}) @prefix -top -subject -bucket";
							}
						}
						break;

					case 'selfimages':
						$sphinx->sort = "images DESC";
						$_GET['mine'] = 1; //actully used below
						break;
					case 'selfalpha':
						$sphinx->sort = "tag ASC";
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
							$sphinx->q = str_replace('@grid_reference (',"@image_square ({$_GET['gr']} | ",$sphinx->q)." @prefix -top -subject";
						}
						break;
					case 'subject':
					case 'top':
					case 'bucket':
						$sphinx->sort = "tag ASC";
						if (empty($prefix)) {
							$prefix = $_GET['mode'];
							if (empty($sphinx->q)) {
								$sphinx->q = "_ALL_ @prefix $prefix";
							} else {
								$sphinx->q = "({$sphinx->q}) @prefix $prefix";
							}
						}
						break;
				}
			}

			if ($sphinx->sort == "tag ASC") {
				$client->SetRankingMode(SPH_RANK_NONE);
				$client->SetGroupBy('grouping',SPH_GROUPBY_ATTR,$sphinx->sort); //overall sort order
				$sphinx->sort = "prefered DESC, images DESC";
			} elseif ($sphinx->sort == "images DESC") {
				$client->SetRankingMode(SPH_RANK_NONE);
				$client->SetGroupBy('grouping',SPH_GROUPBY_ATTR,$sphinx->sort); //overall sort order
				$sphinx->sort = "prefered DESC, images DESC";
			} else {
				//$client->SetRankingMode(SPH_RANK_SPH04);
				$client->SetRankingMode(SPH_RANK_WORDCOUNT);
				$client->setFieldWeights(array('tag'=>10));
				$client->SetGroupBy('grouping',SPH_GROUPBY_ATTR,"@relevance DESC, images DESC, @id DESC"); //overall sort order
				$sphinx->sort = "prefered DESC, images DESC"; //within group order
			}

			if (isset($_GET['mine'])) {
			        init_session();
			        if (!$USER->registered) {
			                die(json_encode(array('error'=>'not logged in')));
			        }
				$sphinx->addFilters(array('user_id'=>array($USER->user_id)));
			} elseif (isset($_GET['user_id'])) {
				$sphinx->addFilters(array('user_id'=>array(intval($_GET['user_id']))));
			}

			$ids = $sphinx->returnIds($pg,'tags');

			if (!empty($ids) && count($ids)) {
				$idstr = join(",",$ids);
				$where = "tag_id IN(".join(",",$ids).")";

				$sql['wheres'] = array("`tag_id` IN ($idstr)");
				$sql['order'] = "FIELD(`tag_id`,$idstr)";
				$sql['limit'] = count($ids);
			} else {
				$sql['wheres'] = array(0);
			}
		} else {
			$sql['wheres'] = array(0);
		}
	} else {
		$sql['tables']['gt'] = 'INNER JOIN gridimage_tag gt USING (tag_id)';

		$sql['wheres'] = array("`tag` LIKE ".$db->Quote($_GET['q'].'%'));
		$sql['wheres'][] = "t.status = 1";
		$sql['wheres'][] = "gt.status = 2";

		$sql['group'] = 'tag_id';

		$sql['order'] = '`tag`';

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
if (isset($_GET['term'])) {
	$data = $db->getCol($query);
} else {
	$data = $db->getAll($query);
	if (!empty($_GET['counts']) && !empty($sphinx) && !empty($sphinx->res)) {
		foreach ($data as $idx => $row) {
			$data[$idx]['images'] = $sphinx->res['matches'][$row['tag_id']]['attrs']['images'];
		}
	}

        if (!empty($_GET['synonums'])) {
		$have = array();
		$need = array();
		foreach ($data as $idx => $row) {
			$have[$row['tag_id']] = $row['tag_id'];
			$need[$row['tag_id']] = $row['tag_id']; //find any tags using this tag as its canonical
			if ($row['canonical'])
				$need[$row['canonical']] = $row['canonical']; //find any tags with the same canonical, AND the tag itself
		}
		if ($more = $db->getAll($sql = "SELECT {$sql['columns']},1 AS added,`count` AS images FROM tag LEFT JOIN tag_stat USING (tag_id)
				WHERE (tag_id IN (".implode(',',$need).") OR canonical IN (".implode(',',$need).")) AND tag_id NOT IN (".implode(',',$have).")"))
			$data = array_merge($data,$more);

if (!empty($_GET['dd']))
	print $sql;

	}
}

outputJSON($data);

