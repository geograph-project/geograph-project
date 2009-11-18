<?php
/**
 * $Project: GeoGraph $
 * $Id: swarm.php 6077 2009-11-12 22:38:51Z barry $
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
$template='swarm.tpl';	

$swarm_id = intval($_REQUEST['id']);

$cacheid = $swarm_id;


if (!$smarty->is_cached($template, $cacheid)) {

	$db = GeographDatabaseConnection(false);


	$data = $db->getRow("SELECT s.*,realname FROM swarm s INNER JOIN user USING (user_id) WHERE swarm_id = $swarm_id AND enabled = 1");
	
	if ($data['swarm_id']) {
	
		if ($data['images']) {
			
			$imagelist = new ImageList();

			if (!empty($data['query_id'])) {
				
				$sql = "SELECT gridimage_id,gi.user_id,realname,credit_realname,gi.title,imageclass,grid_reference FROM gridimage_query gq INNER JOIN gridimage_search gi USING (gridimage_id) WHERE query_id = {$data['query_id']} LIMIT 10";
			} else {
				$sql = "SELECT gridimage_id,gi.user_id,realname,credit_realname,gi.title,imageclass,grid_reference FROM gridimage_swarm gs INNER JOIN gridimage_search gi USING (gridimage_id) WHERE swarm_id = $swarm_id LIMIT 10";
			}
			
			$imagelist->_getImagesBySql($sql);
			$smarty->assign_by_ref('results', $imagelist->images);
		} 





		$smarty->assign($data);
		if ($data['images']) {
			$smarty->assign('page_title',$data['title']." [{$data['images']} photos]");
		} else {
			$smarty->assign('page_title',$data['title']);
		}
	} else {
		$template = 'static_404.tpl';
	}
} else {
	$smarty->assign('swarm_id',$swarm_id);
}
 






$smarty->display($template, $cacheid);

?>
