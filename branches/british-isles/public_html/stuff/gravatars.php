<?php
/**
 * $Project: GeoGraph $
 * $Id: glossary.php 2960 2007-01-15 14:33:27Z barry $
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

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);

	$list = $db->getAll("SELECT user.user_id,realname,MD5(LOWER(email)) AS md5_email FROM user INNER JOIN user_stat s USING(user_id) WHERE rights IS NOT NULL AND s.images > 0 ORDER by user_id");
	$smarty->assign_by_ref('list', $list);
}

$smarty->display("stuff_gravatars.tpl");
	
?>
