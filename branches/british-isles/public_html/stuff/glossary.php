<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

$template=((isset($_GET['add']) && $USER->hasPerm("basic"))?'stuff_glossary_add':((isset($_GET['test']))?'stuff_glossary_test':'statistics_table')).'.tpl';
$cacheid='glossary';
function print_rp(&$in,$exit = false) {
	print "<pre>";
	print_r($in);
	print "</pre>";
	if ($exit)
		exit;
}
if ($_POST['add']) {
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;
	

	$USER->mustHavePerm("basic");
	$updates = array();
	foreach (explode(' ','source_lang source_dialect source_word tran_lang tran_dialect tran_word tran_definition') as $field) {
		$updates[$field] = $db->Quote(trim(stripslashes($_POST[$field])));
	}
	$updates['created_by'] = $USER->user_id;
	

	$sql = "INSERT INTO `glossary` (`".
	implode('`,`',array_keys($updates)).
	"`) VALUES (".
	implode(',',array_values($updates)).
	");";
	

	$db->Execute($sql);
	
	$smarty->clear_cache($template, $cacheid);
}

function smarty_modifier_glossary($input) {
	$plain = strtolower(preg_replace('/[^a-zA-Z0-9]+/',' ',str_replace("'",'',strip_tags($input))));
	$words = preg_split('/ +/',$plain);
	
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');
	
	print "<pre>$plain</pre>";
	
	
	
	$all = $db->getAssoc("select 
	source_word,
	group_concat(tran_word) as tran_word,
	group_concat(tran_lang) as tran_lang,
	group_concat(tran_dialect) as tran_dialect,
	group_concat(tran_definition) as tran_definition,
	group_concat(source_lang) as source_lang,
	group_concat(source_dialect) as source_dialect
	from glossary
	group by source_word");

	foreach ($all as $word => $row) {
		$wordregex = str_replace(')(*','\w*)(',str_replace('*)(',')(\w*','(\b)('.$word.')(\b)'));
		if (strpos($row['tran_word'],',') !== FALSE) {
			$tran_words = explode(',',$row['tran_word']);
			$tran_langs = explode(',',$row['tran_lang']);
			$tran_dialects = explode(',',$row['tran_dialect']);
			$tran_definitions = explode(',',$row['tran_definition']);
			$source_langs = explode(',',$row['source_lang']);
			$source_dialects = explode(',',$row['source_dialect']);
			
			$text = '';
			foreach ($tran_words as $i => $tran_word) {
				if ($text)
					$text .="\n-------------------------------\n";
				$text .= "Meaning: {$tran_word} ({$tran_langs[$i]} {$tran_dialects[$i]})\n{$tran_definitions[$i]}\n Source: {$source_langs[$i]} {$source_dialects[$i]}";
			}
		} else {
			$text = "Meaning: {$row['tran_word']} ({$row['tran_lang']} {$row['tran_dialect']})\n{$row['tran_definition']}\n Source: {$row['source_lang']} {$row['source_dialect']}";
		}
	
		$input = preg_replace('/'.$wordregex.'/i',"$1<acronym title=\"$text\">\$2</acronym>$3",$input);
	}
	
	return $input;
}

$smarty->register_modifier("glossary", "smarty_modifier_glossary");


if (!$smarty->is_cached($template, $cacheid))
{
	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  
		#$db->debug = true;
	}
	if (isset($_GET['add']) && $USER->hasPerm("basic")) {

	} elseif (!isset($_GET['test'])) {
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


		$sql = "SELECT 
		CONCAT('<b>',`source_word`,'</b>') as 'Word'
		,`source_lang` as 'Word Language'
		,`source_dialect` as 'Word Dialect'
		,CONCAT('<b>',`tran_word`,'</b>') as 'General Meaning'
		,`tran_lang` as 'Language'
		,`tran_dialect` as 'Dialect'
		,CONCAT('<small>',`tran_definition`,'</small>') as 'Definition'
		FROM glossary 
		ORDER BY source_word";

		$table = $db->getAll($sql);	

		$smarty->assign_by_ref('table', $table);
		$smarty->assign("h2title","Regional Glossary");
		$smarty->assign("total",count($table));
	
	}

} 


$smarty->display($template, $cacheid);

	
?>
