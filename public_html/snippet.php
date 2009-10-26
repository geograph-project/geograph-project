<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
$template='snippet.tpl';	

$snippet_id = intval($_REQUEST['id']);

$cacheid = $snippet_id;


if (!$smarty->is_cached($template, $cacheid)) {

	$db = GeographDatabaseConnection(false);


	$data = $db->getRow("SELECT s.*,realname FROM snippet s INNER JOIN user USING (user_id) WHERE snippet_id = $snippet_id AND enabled = 1");
	
	if ($data['snippet_id']) {
	

		$data['images'] = $db->getOne("SELECT COUNT(*) FROM gridimage_snippet gs WHERE snippet_id = $snippet_id AND gridimage_id < 4294967296");

		if ($data['images']) {
			$imagelist = new ImageList();

			$sql = "SELECT gridimage_id,gi.user_id,realname,credit_realname,gi.title,imageclass,grid_reference FROM gridimage_snippet gs INNER JOIN gridimage_search gi USING (gridimage_id) WHERE snippet_id = $snippet_id AND gridimage_id < 4294967296 LIMIT 10";

			$imagelist->_getImagesBySql($sql);
			$smarty->assign_by_ref('results', $imagelist->images);
		} 


		if ($data['nateastings']) {
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			list($gr,$len) = $conv->national_to_gridref(
				$data['nateastings'],
				$data['natnorthings'],
				max(4,$data['natgrlen']),
				$data['reference_index'],false);

			$data['grid_reference'] = $gr;
		}
		
		//we do this here first, rather than in smarty - so we can attach html. 
		//todo call geographlinks?
		$data['comment'] = htmlentities2($data['comment']);
		
		if ($CONF['sphinx_host']) {

			$sphinx = new sphinxwrapper();
			$sphinx->pageSize = $pgsize = 25;
			$pg = 1;
			
			if ($data['nateastings']) {
				$geodata = array();

				list($geodata['x'],$geodata['y']) = $conv->national_to_internal($data['nateastings'],$data['natnorthings'],$data['reference_index']);

				if ($data['natgrlen'] > 4) {
					list($geodata['lat'],$geodata['long']) = array($data['wgs84_lat'],$data['wgs84_long']);
				}
				$geodata['d'] = 2;
				$geodata['sort'] = "@geodist ASC, @relevance DESC, @id DESC";

				$sphinx->setSort($geodata['sort']);
				$sphinx->setSpatial($geodata);
			} else {
				$sphinx->prepareQuery($data['grid_reference']);
			}
			
			$ids = $sphinx->returnIds($pg,'snippet');

			if (!empty($ids) && count($ids) > 1) {
				$where = array();

				$id_list = implode(',',$ids);
				$where[] = "s.snippet_id IN($id_list)";
				$orderby = "ORDER BY FIELD(s.snippet_id,$id_list)";

				$where[] = "enabled = 1"; 
				$where[] = "s.snippet_id != {$data['snippet_id']}";

				$where= implode(' AND ',$where);

				$others = $db->getAll($sql="SELECT snippet_id,title,comment FROM snippet s WHERE $where $orderby"); 

				$smarty->assign_by_ref('others',$others);
				
				
				foreach ($others as $id => $row) {
					if (strlen($row['title']) > 3)
						$data['comment'] = preg_replace("/\b(".preg_quote($row['title'],'/').")\b/i",'<a href="/snippet.php?id='.$row['snippet_id'].'">$1</a>',$data['comment']);
				}
			} 
		}

		$smarty->assign($data);
		$smarty->assign('page_title',$data['title']);
	} else {
		$template = 'static_404.tpl';
	}
} else {
	$smarty->assign('snippet_id',$snippet_id);
}
 






$smarty->display($template, $cacheid);

?>
