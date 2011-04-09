<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/imagelist.class.php');
init_session();


$smarty = new GeographPage;

customGZipHandlerStart();

dieUnderHighLoad(0.8);

$USER->mustHavePerm("basic");

if (!empty($_GET['hide'])) {
	$db = GeographDatabaseConnection(false);
	$db->Execute("UPDATE typo SET quieted = NOW() WHERE typo_id = ".intval($_GET['hide']));

} elseif (!empty($_GET['delete'])) {
	$db = GeographDatabaseConnection(false);
	$db->Execute("UPDATE typo SET enabled = 0 WHERE typo_id = ".intval($_GET['delete']));

	if (!empty($_GET['watch'])) {
		$word = $db->getOne("SELECT include FROM typo WHERE typo_id = ".intval($_GET['delete']));
		
		$sql = "UPDATE gridimage_typo SET
			muted = NOW(),
			moderator = ".intval($USER->user_id)."
			WHERE word = ".$db->Quote($word);
		$db->Execute($sql);
		
		print "Images updated = ".mysql_affected_rows();
		
	} elseif (!empty($_GET['profile']) && $_GET['profile'] != 'keywords') {
		$word = htmlentities($db->getOne("SELECT include FROM typo WHERE typo_id = ".intval($_GET['delete'])));
		print "<tt><b>$word</b></tt> Typo entry deleted.";

		print "<p>Do you also want to remove any photos from the watchlist that match this rule <tt><b>$word</b></tt>? <a href=\"?delete=".intval($_GET['delete'])."&amp;watch=1\">Yes</a> (Useful if this rule added many false positives to the list)</p>";
		
		print "<a href=\"?\">No</a> (return to list)";
		
		exit;
	}
	
} elseif (!empty($_GET['toggle'])) {
	$db = GeographDatabaseConnection(false);
	$db->Execute("UPDATE typo SET profile = (profile MOD 4)+1,updated=updated WHERE typo_id = ".intval($_GET['toggle']));

} elseif (!empty($_POST['rows'])) {
	$rows = str_replace("\r",'',$_POST['rows']);
	$results = array();
	foreach (explode("\n",$rows) as $q) {
	
		if (preg_match("/\b(AND|OR|NOT)\b/",$q) || preg_match('/^\^.*\+$/',$q) || preg_match('/(^|\s+)-([\w^]+)/',$q)) {
			$terms = '';
			$tokens = preg_split('/\s+/',trim(preg_replace('/([\(\)])/',' $1 ',preg_replace('/(^|\s+)-([\w^]+)/e','("$1"?"$1AND ":"")."NOT $2"',$q))));
			$number = count($tokens);
			$c = 1;
			$tokens[] = 'END';
			foreach ($tokens as $token) {
				switch ($token) {
					case 'END': $token = '';
					case 'AND':
					case 'OR': 
						if ($c != 1 && $c != $number) {
							if (strpos($terms,'^') === 0) {
								$results[] = str_replace('^','',preg_replace('/\+$/','',$terms));
							} else {
								$results[] = preg_replace('/[\+~]$/','',$terms);
							}
							$terms = '';
						}
						break;
					case '(': 
					case ')': 
					case 'NOT': break;
					default: 
						if ($terms)	$terms .= " ";
						$terms .= $token;
				}
				$c++;
			}
		} elseif (strpos($q,'^') === 0 || preg_match('/\+$/',$q)) {
			$results[] = preg_replace('/[\+~]$/','',str_replace('^','',$q));
		} elseif (strpos($q,'~') === 0) {
			$q = preg_replace('/^\~/','',$q);
			
			$sphinx = new sphinxwrapper($q);
			
			foreach ($sphinx->explodeWithQuotes(" ",$q) as $token) {
				$results[] = str_replace('"','',$token);
			}
		} else {
			$results[] = $q;
		}
	}
	
	$db = GeographDatabaseConnection(false);
	foreach ($results as $result) {
		if (strlen($result) > 1) {
			$inserts = array();
			$inserts[] = "created=NOW()";
			$inserts[] = "include = ".$db->Quote(preg_replace('/^=/','',$result));
			$inserts[] = "title = ".intval($_GET['title']);
			$inserts[] = "profile = ".$db->Quote($_GET['profile']);

			$inserts[] = "user_id = ".$USER->user_id;
			
			$db->Execute('INSERT IGNORE INTO typo SET '.implode(',',$inserts));
		}
	}
}





$template='admin_typolist.tpl';

$cacheid = '';
$smarty->caching = 0; // lifetime is per cache

	
//regenerate?
if (!$smarty->is_cached($template, $cacheid) )
{
	if (!isset($db)) {
		$db = GeographDatabaseConnection(true);
	}	
	
	$sql="select * from typo where enabled = 1 and quieted < date_sub(now(), interval 48 hour) order by updated desc limit 400";
	
	$data = $db->getAll($sql);
	
	$smarty->assign_by_ref('data',$data);
}


$smarty->display($template, $cacheid);

