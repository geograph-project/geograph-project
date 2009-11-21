<?php
/**
 * $Project: GeoGraph $
 * $Id: swarms.php 6075 2009-11-12 22:03:51Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
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
$template='swarms.tpl';	
$cacheid=$USER->user_id;

if (!empty($_GET['oid'])) {
	$cacheid=intval($_GET['oid']);
}

$USER->mustHavePerm("basic");


if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(false);




	$where = array();
	$fields = '';
	
	
	$where[] = "s.user_id = ".$cacheid; 
	
	$where[] = "enabled = 1"; 
	$where= implode(' AND ',$where);
	
	$results = $db->getAll($sql="SELECT s.*,GROUP_CONCAT(gridimage_id) AS ids $fields FROM swarm s LEFT JOIN gridimage_swarm gs USING (swarm_id) WHERE $where GROUP BY s.swarm_id DESC LIMIT 100"); 
	
	$smarty->assign_by_ref('results',$results);
}



$smarty->display($template, $cacheid);

?>
