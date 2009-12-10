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


$USER->mustHavePerm("basic");

if (!empty($_GET['hide'])) {
	$db = GeographDatabaseConnection(false);
	$db->Execute("update typo set quieted = NOW() where typo_id = ".intval($_GET['hide']));
} elseif (!empty($_GET['delete'])) {
	$db = GeographDatabaseConnection(false);
	$db->Execute("update typo set enabled = 0 where typo_id = ".intval($_GET['delete']));
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

	
?>
