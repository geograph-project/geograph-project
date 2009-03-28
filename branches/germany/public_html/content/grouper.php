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

//you must be logged in to request changes
$USER->mustHavePerm("basic");


$template = 'content_grouper.tpl';

$db=NewADOConnection($GLOBALS['DSN']);

if (isset($_POST['save'])) {
	$updates= array();
	$updates['content_id'] = intval($_POST['content_id']);
	$updates['source'] = "user{$USER->user_id}";
	$updates['score'] = 1;
	$updates['sort_order'] = 0;
	
	foreach (explode("\n",$_POST['groups']) as $line) {
		$updates['label'] = preg_replace('/[^\w -]+/','',$line);
		
		if (!empty($updates['label'])) {
			$db->Execute('INSERT INTO content_group SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		}
	}
}

if (isset($_POST['save']) || isset($_GET['start'])) {
	$page = $db->getRow("
	select c.*
	from content c
		left join content_group cg on (c.content_id=cg.content_id and cg.source = 'user{$USER->user_id}')
	where cg.content_id IS NULL
	limit 1");

	$smarty->assign($page);
	
	
	$groups = array();
	$recordSet = &$db->Execute("SELECT label FROM content_group WHERE source like 'user%' GROUP BY label");
	while (!$recordSet->EOF)
	{
		$groups[$recordSet->fields[0]] = $recordSet->fields[0];
		$recordSet->MoveNext();
	}
	$recordSet->Close();
	$smarty->assign_by_ref('groups', $groups);
	
}

$smarty->display($template, $cacheid);

?>
