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

$template=((isset($_GET['add']) && $USER->hasPerm("basic"))?'glossery_add':'statistics_table').'.tpl';
$cacheid='glossery';

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
	
	$sql = "INSERT INTO `glossery` (`".
	implode('`,`',array_keys($updates)).
	"`) VALUES (".
	implode(',',array_values($updates)).
	");";
	
	$db->Execute($sql);
	
	$smarty->clear_cache($template, $cacheid);
}

if (!$smarty->is_cached($template, $cacheid))
{
	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  
		#$db->debug = true;
	}
	if (isset($_GET['add']) && $USER->hasPerm("basic")) {

	} else {
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


		$sql = "SELECT 
		CONCAT('<b>',`source_word`,'</b>') as 'Word'
		,`source_lang` as 'Language'
		,`source_dialect` as 'Dialect'
		,CONCAT('<b>',`tran_word`,'</b>') as 'General Meaning'
		,`tran_lang` as 'Language'
		,`tran_dialect` as 'Dialect'
		,CONCAT('<small>',`tran_definition`,'</small>') as 'Definition'
		FROM glossery 
		ORDER BY source_word";

		$table = $db->getAll($sql);	

		$smarty->assign_by_ref('table', $table);
		$smarty->assign("h2title","Regional Glossery");
		$smarty->assign("total",count($table));
	
	}

} 


$smarty->display($template, $cacheid);

	
?>
