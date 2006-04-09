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

$template='statistics_table.tpl';

$l = (isset($_GET['l']) && strlen($_GET['l']) == 1)?$_GET['l']:'a';

$cacheid='typo.'.$l;

$smarty->caching = 2;

if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, $cacheid);

if (!$smarty->is_cached($template, $cacheid))
{

	$db = NewADOConnection($GLOBALS['DSN']);
	$table = array();
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table = $db->getAll("
	select DISTINCT 
		w.words as word1,
		x.words as word2 
	from wordnet w 
	inner join wordnet x 
		on (w.len = 1 and x.len = 1 
		and w.words LIKE '$l%' and x.words LIKE '$l%' 
		and w.words != x.words 
		and LENGTH(w.words) > 5 and LENGTH(x.words) > 5
		AND SOUNDEX(w.words) = SOUNDEX(x.words)) 
	limit 500;
	");
	foreach ($table as $i => $row) {
		if (levenshtein($row['word1'], $row['word2']) > 2) {
			unset($table[$i]);
		}
	}
	
	$smarty->assign_by_ref('table', $table);

	$smarty->assign("h2title","POSSIBLE typos beginning with '$l'");
	$smarty->assign("total",count($table));

$smarty->assign("footnote","<p>Finds words in the title that are similar, based on the premise that one might be a typo. Will return lots of false positives!</p>");
	
}

$smarty->display($template,$cacheid);

	
?>
