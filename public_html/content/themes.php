<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 4866 2008-10-19 21:06:25Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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


$db = GeographDatabaseConnection(true);

$data = $db->getRow("show table status like 'content_group'");

//when this table was modified
$mtime = strtotime($data['Update_time']);
	
//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
customCacheControl($mtime,$cacheid,($USER->user_id == 0));

$template = 'content_themes.tpl';

if (!empty($_GET['v'])) {
	switch ($_GET['v']) {
		case '1': $source = 'words'; $template = 'content_words.tpl'; break;
		case '2': $source = 'sphinx'; break;
		case '3': $source = 'user%'; break;
		default:  $source = 'carrot2'; break;
	}
} else {
	$source = 'carrot2';
}
$cacheid = $source.'.'.$USER->registered.'.'.$CONF['forums'];


if (!$smarty->is_cached($template, $cacheid))
{
	$where  = '';
	if ((isset($CONF['forums']) && empty($CONF['forums'])) || $USER->user_id == 0 ) {
		$where .= " AND content.`source` != 'themed'";
	}
	if ($source == 'words') {
		$listall = $db->getAll("select title from content WHERE `source` NOT IN ('user','category','snippet') $where");
	
		$a = array();
		foreach ($listall as $i => $row) {
			$alltext = preg_replace('/[^a-zA-Z0-9]+/',' ',str_replace("'",'',$row['title']));
	
			$words = preg_split('/ +/',trim($alltext));
	
			foreach ($words as $c => $w) {
				if (preg_match('/^(geograph|amp|quot|pound|a|about|above|according|across|actually|adj|after|afterwards|again|against|all|almost|alone|along|already|also|although|always|among|amongst|an|and|another|any|anyhow|anyone|anything|anywhere|are|arent|around|as|at|b|be|became|because|become|becomes|becoming|been|before|beforehand|begin|beginning|behind|being|below|beside|besides|between|beyond|billion|both|but|by|c|can|cant|cannot|caption|co|co.|could|couldnt|d|did|didnt|do|does|doesnt|dont|down|during|e|each|eg|e.g.|eight|eighty|either|else|elsewhere|end|ending|enough|etc|etc.|even|ever|every|everyone|everything|everywhere|except|f|few|fifty|first|five|for|former|formerly|forty|found|four|from|further|g|h|had|has|hasnt|have|havent|he|hed|hell|hes|hence|her|here|heres|hereafter|hereby|herein|hereupon|hers|herself|him|himself|his|how|however|hundred|i|id|ill|im|ive|ie|if|in|inc|inc.|indeed|instead|into|is|isnt|it|its|its|itself|j|k|l|last|later|latter|latterly|least|less|let|lets|like|likely|ltd|m|made|make|makes|many|maybe|me|meantime|meanwhile|might|million|miss|more|moreover|most|mostly|mr|mrs|much|must|my|myself|n|namely|neither|never|nevertheless|next|nine|ninety|no|nobody|none|nonetheless|noone|nor|not|nothing|now|nowhere|o|of|off|often|on|once|one|ones|only|onto|or|other|others|otherwise|our|ours|ourselves|out|over|overall|own|p|per|perhaps|q|r|rather|recent|recently|s|same|seem|seemed|seeming|seems|seven|seventy|several|she|shed|shell|shes|should|shouldnt|since|six|sixty|so|some|somehow|someone|something|sometime|sometimes|somewhere|still|stop|such|t|taking|ten|than|that|thatll|thats|thatve|the|their|them|themselves|then|thence|there|thered|therell|therere|theres|thereve|thereafter|thereby|therefore|therein|thereupon|these|they|theyd|theyll|theyre|theyve|thirty|this|those|though|thousand|three|through|throughout|thru|thus|to|together|too|toward|towards|trillion|twenty|two|u|under|unless|unlike|unlikely|until|up|upon|us|used|using|v|very|via|w|was|wasnt|we|wed|well|were|weve|well|were|werent|what|whatll|whats|whatve|whatever|when|whence|whenever|where|wheres|whereafter|whereas|whereby|wherein|whereupon|wherever|whether|which|while|whither|who|whod|wholl|whos|whoever|whole|whom|whomever|whose|why|will|with|within|without|wont|would|wouldnt|x|y|yes|yet|you|youd|youll|youre|youve|your|yours|yourself|yourselves|z)$/i',$w)) {
					//skip...
				} elseif (preg_match('/^[A-Z]/',$w)) {
					//give promience to uppercased words
					$a[strtolower($w)]+=2;
				} elseif (!ctype_digit($w)) {
					$a[$w]++;
				}
			}
		}
		$ADODB_FETCH_MODE = $prev_fetch_mode;
	
		arsort($a);
		$smarty->assign('words', array_slice($a,0,100));
	} else {
		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$list = $db->getAll($sql = "
		select content.content_id,content.user_id,url,title,extract,content.updated,content.created,realname,label,score
		from content_group
			inner join content using (content_id)
			left join user using (user_id)
		where content_group.`source` like '$source' and `type` = 'info' $where
		group by content_id,label
		order by label = '(Other)',content_group.label,content_group.score desc,content_group.sort_order
		");

		#print "<pre>";
		#print_r($sql);
		#exit;
	
		$smarty->assign_by_ref('list', $list);
	}
	if (!empty($_GET['v'])) {
		$smarty->assign('v', intval($_GET['v']));
	}
}

$smarty->display($template, $cacheid);


