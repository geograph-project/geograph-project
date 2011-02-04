<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;
$template='tags_tagger.tpl';


$USER->mustHavePerm("basic");

$gid = 0;

if (!empty($_GET['upload_id'])) {

	$gid = crc32($_GET['upload_id'])+4294967296;
	$gid += $USER->user_id * 4294967296;

	$smarty->assign('upload_id',$_GET['upload_id']);
	$smarty->assign('gridimage_id',$gid);
} elseif (!empty($_REQUEST['gridimage_id'])) {

	$gid = intval($_REQUEST['gridimage_id']);
	
	$image=new GridImage();
	$ok = $image->loadFromId($gid);
		
	if (!$ok) {
		die("invalid image");
	}
	
	if ($image->user_id == $USER->user_id) {
		$smarty->assign('is_owner',1);
	}
	
	$smarty->assign('gridimage_id',$gid);
}

$db = GeographDatabaseConnection(false);

if (!empty($_POST['submit']) && $gid) {

	//tags precheck
	if (!empty($_POST['tag_id'])) {
		$found = 0;
		if ($gid) {
			$tags = $db->getAssoc("SELECT tag.*,gs.status FROM tag INNER JOIN gridimage_tag gs USING (tag_id) WHERE gridimage_id = $gid AND gs.user_id = {$USER->user_id}");
			if ($tags) {
				foreach ($tags as $tid => $row2) {
					if (in_array("id:$tid",$_POST['tag_id'])) {
						$found++;
						
						$idx = array_search("id:$tid",$_POST['tag_id']);
						
						$status = 1;
						if ($_POST['mode'][$idx] == 'Public' || $_POST['mode'][$idx] == 2) { //TODO check allowed to make public!
							$status = 2;
						}
						
						if ($row['status'] != $status) {
							$sql = "UPDATE gridimage_tag SET status = $status WHERE gridimage_id = $gid AND tag_id = $tid AND user_id = {$USER->user_id}";
							$db->Execute($sql);
						}
					}
				}
			}
		}
		if (count($_POST['tag_id']) != $found) {
			$tagsDontMatch = 1;
		}

	}

	if (!empty($tagsDontMatch)) {
		
		//$tags array set by the precheck :)

		if (!empty($tags)) {//see if any need deleting
			$found = 0 ;
			foreach ($tags as $tid => $row2) {
				if (in_array("id:$tid",$_POST['tag_id'])) {
					$found++;
				} else {
					$sql = "DELETE FROM gridimage_tag WHERE gridimage_id = $gid AND tag_id = $tid AND user_id = {$USER->user_id}";
					$db->Execute($sql);
				}
			}
		}

		foreach ($_POST['tag_id'] as $idx => $text) {
			if ($text == '-deleted-') {
				//its either a tag created and deleted (so we can ignore) or its been deleted above!
				
			} elseif (preg_match('/^id:(\d+)$/',$text,$m) && isset($tags[$m[1]])) {
				//its matches - nothing to do
				
			} else {
				//its a new tag for this link!
				
				$u = array();
				$u['tag'] = $text;
				$bits = explode(':',$u['tag']);
				if (count($bits) > 1) {
					$u['prefix'] = trim($bits[0]);
					$u['tag'] = $bits[1];
				}
				$u['tag'] = trim(preg_replace('/[ _]+/',' ',$u['tag']));
				
				if (preg_match('/^id:(\d+)$/',$text,$m)) {
					$tag_id = $m[1];
				} else {
					$tag_id = $db->getOne("SELECT tag_id FROM `tag` WHERE `tag` = ".$db->Quote($u['tag'])." AND `prefix` = ".$db->Quote($u['prefix']));
				}

				if (empty($tag_id)) {
					//need to create it!

					$u['user_id'] = $USER->user_id;

					$db->Execute('INSERT INTO tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
					
					$tag_id = mysql_insert_id();
				}

				$u = array();

				$u['gridimage_id'] = $gid;
				$u['tag_id'] = $tag_id;
				$u['user_id'] = $USER->user_id;
				
				if ($_POST['mode'][$idx] == 'Public' || $_POST['mode'][$idx] == 2) { //TODO check allowed to make public!
					$u['status'] = 2;
				}
				
				$db->Execute('INSERT IGNORE INTO gridimage_tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
			}
		}
	
	}

} elseif ($gid && !empty($_POST['remove'])) {
	
split_timer('tags'); //starts the timer

	$criteria = array();
	$criteria['gridimage_id'] = $gid;
	
	foreach ($_POST['remove'] as $id => $text) {
		
		$criteria['tag_id'] = $id;
		
		$db->Execute('DELETE FROM gridimage_tag WHERE `'.implode('` = ? AND `',array_keys($criteria)).'` = ?',array_values($criteria));
	}

	if ($gid < 4294967296) {
		//clear any caches involving this photo
		$ab=floor($gid/10000);
		$smarty->clear_cache(null, "img$ab|{$gid}");
		
		$smarty->clear_cache("tags.tpl", $criteria['tag_id']);
		
		$memcache->name_delete('sd', $gid);
	}

split_timer('tags','remove',$gid); //logs the wall time


} elseif ($gid && !empty($_POST['add'])) {
	
split_timer('add'); //starts the timer

	$updates = array();
	$updates['gridimage_id'] = $gid;
	$updates['user_id'] = $USER->user_id;
	
	foreach ($_POST['add'] as $id => $text) {
		
		$updates['tag_id'] = $id;
		
		$db->Execute('INSERT IGNORE INTO gridimage_tag SET `'.implode('` = ?, `',array_keys($updates)).'` = ?',array_values($updates));
	}
	
	if ($gid < 4294967296) {
		//clear any caches involving this photo
		$ab=floor($gid/10000);
		$smarty->clear_cache(null, "img$ab|{$gid}");
		
		$smarty->clear_cache("tag.tpl", $updates['tag_id']);
		
		$memcache->name_delete('sd', $gid);
	}

split_timer('tags','remove',$gid); //logs the wall time

	
}



if ($gid) {
	
	$used = $db->getAll("SELECT *,gs.status,(gs.user_id = {$USER->user_id}) AS is_owner FROM gridimage_tag gs INNER JOIN tag s USING (tag_id) WHERE gridimage_id = $gid AND (gs.user_id = {$USER->user_id} OR gs.status = 2) AND gs.status > 0 ORDER BY gs.created");

	$smarty->assign_by_ref('used',$used);
	
	$db2 = GeographDatabaseConnection(true);	
	$suggestions = $db2->getAll("(SELECT label AS tag,'cluster' AS `prefix` FROM gridimage_group WHERE gridimage_id = $gid ORDER BY score DESC,sort_order) UNION (SELECT result AS tag,'term' AS `prefix` FROM at_home_result WHERE gridimage_id = $gid ORDER BY at_home_result_id) UNION  (SELECT result AS tag,'term' AS `prefix` FROM at_home_result_archive WHERE gridimage_id = $gid ORDER BY at_home_result_id)");
	if (count($used) && count($suggestions)) {
		$list = array();
		foreach ($used as $row) $list[$row['tag']]=1;
		
		foreach ($suggestions as $idx => $row) {
			if (isset($list[$row['tag']]))
				unset($suggestions[$idx]);
		}
	}
	$smarty->assign_by_ref('suggestions',$suggestions);
}


if (!empty($_REQUEST['q']) || !empty($_REQUEST['tab'])) {
	

	if (!empty($_REQUEST['tab']) && $_REQUEST['tab'] == 'recent') {  
		
		split_timer('tags'); //starts the timer

		$results = $db->getAll($sql="SELECT s.* $fields FROM tag s INNER JOIN gridimage_tag gs USING (tag_id) WHERE gs.user_id = {$USER->user_id} AND gridimage_id != $gid GROUP BY s.tag_id ORDER BY gs.created DESC LIMIT 50"); 
		
		
		$smarty->assign('tab',$_REQUEST['tab']);
		
		split_timer('tags','recent',$USER->user_id); //logs the wall time

		
	} else {
		$where = array();
		$orderby = "ORDER BY s.tag_id";
	
		if (!empty($_REQUEST['tab']) && $_REQUEST['tab'] == 'suggestions') {  
			
			//TODO - this needs customising...
			
			split_timer('tags'); //starts the timer
			
			$pg = 1;
			
			$q=preg_replace("/[^\w ]+/",' ',$_REQUEST['corpus']);
			$q=trim(preg_replace('/\b(or|and|the|geograph|amp|quot|pound|a|about|above|according|across|actually|adj|after|afterwards|again|against|all|almost|alone|along|already|also|although|always|among|amongst|an|another|any|anyhow|anyone|anything|anywhere|are|arent|around|as|at|b|be|became|because|become|becomes|becoming|been|before|beforehand|begin|beginning|behind|being|below|beside|besides|between|beyond|billion|both|but|by|c|can|cant|cannot|caption|co|co.|could|couldnt|d|did|didnt|do|does|doesnt|dont|down|during|e|each|eg|e.g.|eight|eighty|either|else|elsewhere|end|ending|enough|etc|etc.|even|ever|every|everyone|everything|everywhere|except|f|few|fifty|first|five|for|former|formerly|forty|found|four|from|further|g|h|had|has|hasnt|have|havent|he|hed|hell|hes|hence|her|here|heres|hereafter|hereby|herein|hereupon|hers|herself|him|himself|his|how|however|hundred|i|id|ill|im|ive|ie|if|in|inc|inc.|indeed|instead|into|is|isnt|it|its|its|itself|j|k|l|last|later|latter|latterly|least|less|let|lets|like|likely|ltd|m|made|make|makes|many|maybe|me|meantime|meanwhile|might|million|miss|more|moreover|most|mostly|mr|mrs|much|must|my|myself|n|namely|neither|never|nevertheless|next|nine|ninety|no|nobody|none|nonetheless|noone|nor|not|nothing|now|nowhere|o|of|off|often|on|once|one|ones|only|onto|other|others|otherwise|our|ours|ourselves|out|over|overall|own|p|per|perhaps|q|r|rather|recent|recently|s|same|seem|seemed|seeming|seems|seven|seventy|several|she|shed|shell|shes|should|shouldnt|since|six|sixty|so|some|somehow|someone|something|sometime|sometimes|somewhere|still|stop|such|t|taking|ten|than|that|thatll|thats|thatve|their|them|themselves|then|thence|there|thered|therell|therere|theres|thereve|thereafter|thereby|therefore|therein|thereupon|these|they|theyd|theyll|theyre|theyve|thirty|this|those|though|thousand|three|through|throughout|thru|thus|to|together|too|toward|towards|trillion|twenty|two|u|under|unless|unlike|unlikely|until|up|upon|us|used|using|v|very|via|w|was|wasnt|we|wed|well|were|weve|well|were|werent|what|whatll|whats|whatve|whatever|when|whence|whenever|where|wheres|whereafter|whereas|whereby|wherein|whereupon|wherever|whether|which|while|whither|who|whod|wholl|whos|whoever|whole|whom|whomever|whose|why|will|with|within|without|wont|would|wouldnt|x|y|yes|yet|you|youd|youll|youre|youve|your|yours|yourself|yourselves|z)\b/','',$q));
			
			if ($grid_given && $grid_ok) {
				$q .= ' '.$square->grid_reference.' '.$square->gridsquare;
			}
			
			if (strlen($q) > 40) {
				$q = '"'.$q.'"/4';
			} else {
				$q = '~'.$q;
			}
			
			$sphinx = new sphinxwrapper($q);
			$sphinx->pageSize = $pgsize = 40;

			$filters = array();
			if (!empty($_REQUEST['onlymine'])) {
				$filters['user_id'] = array($USER->user_id);
				$smarty->assign("onlymine",1);
			}
			if (!empty($_REQUEST['gr']) && $_REQUEST['gr'] == '-') {
				$filters['grid_reference'] = "none";
			}
			if (!empty($filters)) {
				$sphinx->addFilters($filters);
			}

			$ids = $sphinx->returnIds($pg,'tag');

			if (!empty($ids) && count($ids)) {
				$id_list = implode(',',$ids);
				$where[] = "s.tag_id IN($id_list)";
				$orderby = "ORDER BY FIELD(s.tag_id,$id_list)";
			} else {
				$where[] = '0';
			}			
			$smarty->assign('tab',$_REQUEST['tab']);
			
			split_timer('tags','suggestions',$USER->user_id); //logs the wall time
			
		} elseif (!empty($_REQUEST['q']) && is_numeric($_REQUEST['q'])) {  

			split_timer('tags'); //starts the timer
			
			$ids = $db->getCol("SELECT tag_id FROM gridimage_tag WHERE gridimage_id = ".intval($_REQUEST['q']));

			$ids[] = intval($_REQUEST['q']); //incase it's a tag ID

			$where[] = "s.tag_id IN (".implode(',',$ids).")";

				
			split_timer('tags','nemeric',intval($_REQUEST['q'])); //logs the wall time

			
		} elseif ($CONF['sphinx_host'] && !empty($_REQUEST['q'])) {  //todo - for the moment we only use sphinx for full text searches- because of the indexing delay 
			
			split_timer('tags'); //starts the timer
			
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			if (!empty($_REQUEST['page'])) {
				$pg = intval($_REQUEST['page']);
			} else {
				$pg = 1;
			}

			$q=trim($_REQUEST['q']);

			$sphinx = new sphinxwrapper($q);
			$sphinx->pageSize = $pgsize = 25;

			if (preg_match('/\bp(age|)(\d+)\s*$/',$q,$m)) {
				$pg = intval($m[2]);
				$sphinx->q = preg_replace('/\bp(age|)\d+\s*$/','',$sphinx->q);
			}

			$smarty->assign('q', $sphinx->qclean);
			if ($q) {
				$title = "Matching word search [ ".htmlentities($sphinx->qclean)." ]";
			}

			$filters = array();
			if (!empty($_REQUEST['onlymine'])) {
				$filters['user_id'] = array($USER->user_id);
				$smarty->assign("onlymine",1);
			}

			if (!empty($filters)) {
				$sphinx->addFilters($filters);
			}

			$ids = $sphinx->returnIds($pg,'tag');

			$smarty->assign("query_info",$sphinx->query_info);

			if (!empty($ids) && count($ids)) {
				$id_list = implode(',',$ids);
				$where[] = "s.tag_id IN($id_list)";
				$orderby = "ORDER BY FIELD(s.tag_id,$id_list)";
				
				split_timer('tags','q',$sphinx->qo); //logs the wall time
				
			} else {
				$where[] = '0';
				
				split_timer('tags','q-zero',$sphinx->qo); //logs the wall time
			}
		} else {
			split_timer('tags'); //starts the timer
			
			if (!empty($_REQUEST['onlymine'])) {
				$where[] = "s.user_id = {$USER->user_id}";
				$smarty->assign("onlymine",1);
			}

			if (!empty($_REQUEST['q'])) {
				$q=mysql_real_escape_string(trim($_REQUEST['q']));

				$where[] = "(title LIKE '%$q%' OR comment LIKE '%$q%')";
				$smarty->assign('q',trim($_POST['q']));
			}

			if (count($where) == 0) {
				$where[] = "0";
				$smarty->assign('empty',1);
			}

			$where[] = "enabled = 1"; 
			
			split_timer('tags','general'); //logs the wall time
		}

		$smarty->assign_by_ref('radius',$_POST['radius']);
		
		split_timer('tags'); //starts the timer
		
		$where[] = 'ge.gridimage_id IS NULL';
		$where= implode(' AND ',$where);

		$results = $db->getAll($sql="SELECT s.*,realname,COUNT(gs.tag_id) AS images,SUM(gs.user_id = {$USER->user_id}) AS yours $fields FROM tag s LEFT JOIN user u USING (user_id) LEFT JOIN gridimage_tag gs ON (s.tag_id = gs.tag_id AND gs.gridimage_id < 4294967296) LEFT JOIN gridimage_tag ge ON (s.tag_id = ge.tag_id AND ge.gridimage_id = $gid) WHERE $where GROUP BY s.tag_id $orderby LIMIT 200"); 
		#print $sql;
		
		split_timer('tags','query',$where); //logs the wall time
	}
	
	$smarty->assign_by_ref('grid_reference',$square->grid_reference);
	$smarty->assign_by_ref('results',$results);
} 

if (!empty($CONF['sphinx_host'])) {
	$smarty->assign('sphinx',1);
}
if (!empty($_GET['create'])) {
	$smarty->assign('create',1);
}






$smarty->display($template);


