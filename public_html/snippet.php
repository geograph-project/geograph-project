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

if ((strpos($_SERVER["REQUEST_URI"],'/snippet/') === FALSE && isset($_GET['id'])) || strlen($_GET['id']) !== strlen(intval($_GET['id']))) {
	//keep urls nice and clean - esp. for search engines!
	header("HTTP/1.0 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	header("Location: /snippet/".intval($_GET['id']));
	print "<a href=\"http://{$_SERVER['HTTP_HOST']}/snippet/".intval($_GET['id'])."\">View shared description page</a>";
	exit;
}


require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;
$template='snippet.tpl';	

$snippet_id = intval($_REQUEST['id']);

$cacheid = $snippet_id;

//what style should we use?
$style = $USER->getStyle();
	
$smarty->assign('maincontentclass', 'content_photo'.$style.'" style="padding:10px');


if (!$smarty->is_cached($template, $cacheid)) {

	$db = GeographDatabaseConnection(true);


	$data = $db->getRow("SELECT s.*,realname FROM snippet s LEFT JOIN user USING (user_id) WHERE snippet_id = $snippet_id AND enabled = 1");
	
	if ($data['snippet_id']) {
	

		$data['images'] = $db->getOne("SELECT COUNT(*) FROM gridimage_snippet gs WHERE snippet_id = $snippet_id AND gridimage_id < 4294967296");

		if ($data['images']) {
			$imagelist = new ImageList();

			$sql = "SELECT gridimage_id,gi.user_id,realname,credit_realname,gi.title,imageclass,grid_reference FROM gridimage_snippet gs INNER JOIN gridimage_search gi USING (gridimage_id) WHERE snippet_id = $snippet_id AND gridimage_id < 4294967296 LIMIT 25";

			$imagelist->_getImagesBySql($sql);
			$smarty->assign_by_ref('results', $imagelist->images);
		} 


		if ($data['nateastings'] && intval($data['natgrlen']) > 4) {
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			list($gr,$len) = $conv->national_to_gridref(
				$data['nateastings'],
				$data['natnorthings'],
				max(4,$data['natgrlen']),
				$data['reference_index'],false);

			$data['grid_reference'] = $gr;
		}
		
		if (!empty($data['comment'])) {
			require_once("smarty/libs/plugins/modifier.truncate.php");
			$smarty->assign('meta_description', smarty_modifier_truncate(preg_replace('/[\s\n]+/',' ',$data['comment']), 255) );

			//we do this here first, rather than in smarty - so we can attach html. 
			$data['comment'] = htmlentities2($data['comment']);
			$data['comment'] = GeographLinks(nl2br($data['comment']));
			$data['comment'] = preg_replace('/(^|[\n\r\s]+)(Keywords?[\s:][^\n\r>]+)$/i','<span class="keywords">$2</span>',$data['comment']);
		} else {
			$smarty->assign('meta_description', "Shared description for ".$data['title'].', featuring '.$data['images'].' images');
		}
		
		if ($CONF['sphinx_host'] && $data['grid_reference']) {

			$sphinx = new sphinxwrapper();
			$sphinx->pageSize = $pgsize = 25;
			$pg = 1;
			
			if ($data['nateastings']) {
				require_once('geograph/conversions.class.php');
				$conv = new Conversions;

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

			if (!empty($ids) && count($ids) > 0) {
				$where = array();

				$id_list = implode(',',$ids);
				$where[] = "s.snippet_id IN($id_list)";
				$orderby = "ORDER BY FIELD(s.snippet_id,$id_list)";

				$where[] = "enabled = 1"; 
				$where[] = "s.snippet_id != {$data['snippet_id']}";

				$where= implode(' AND ',$where);

				$others = $db->getAll($sql="SELECT snippet_id,title,comment FROM snippet s WHERE $where $orderby"); 

				$smarty->assign_by_ref('others',$others);
				
				
				//we only replace links, if they appears to be in bits not affected by markup - should help prevent replaces in what is already links, or titles of images etc
				$nohtml = strip_tags(preg_replace('/<a\s.+?>.*?<\/a>/','', $data['comment']));
				foreach ($others as $id => $row) {
					if (strlen($row['title']) > 3 && stripos($nohtml,$row['title']) !== FALSE)
						$data['comment'] = preg_replace("/\b(".preg_quote($row['title'],'/').")\b/i",'<a href="/snippet/'.$row['snippet_id'].'">$1</a>',$data['comment']);
				}
			} 
		} elseif (!empty($data['comment']) && $CONF['sphinx_host']) {
			$sphinx = new sphinxwrapper();
			$sphinx->pageSize = $pgsize = 25;
			$pg = 1;
			
			$crit = '';
			if (strlen($data['comment']) > 100) {
			
				preg_match_all('/\b([A-Z]\w{3,})\b/',str_replace('Link','',$data['comment']),$m);
				
				if (count($m[1]) > 3) {
					$words = array_unique($m[1]);
					
					$quorum = max(3,count($words) -3);
					
					$crit = ' | (@(title,comment) "'.implode(' ',$words).'"/'.$quorum.')';
					
				}
			}
			
			$sphinx->prepareQuery("(@title {$data['title']}) | (@comment \"{$data['title']}\") ".$crit);
			
			$ids = $sphinx->returnIds($pg,'snippet');
			
			if (!empty($ids) && count($ids) > 0) {
				$where = array();

				$id_list = implode(',',$ids);
				$where[] = "s.snippet_id IN($id_list)";
				$orderby = "ORDER BY FIELD(s.snippet_id,$id_list)";

				$where[] = "enabled = 1"; 
				$where[] = "s.snippet_id != {$data['snippet_id']}";

				$where= implode(' AND ',$where);

				$related = $db->getAll($sql="SELECT s.snippet_id,title,comment,realname,COUNT(gs.snippet_id) AS images FROM snippet s LEFT JOIN user u USING (user_id) LEFT JOIN gridimage_snippet gs ON (s.snippet_id = gs.snippet_id AND gridimage_id < 4294967296)  WHERE $where  GROUP BY s.snippet_id $orderby"); 
	
				$smarty->assign_by_ref('related',$related);
				
				//we only replace links, if they appears to be in bits not affected by markup - should help prevent replaces in what is already links, or titles of images etc
				$nohtml = strip_tags(preg_replace('/<a\s.+?>.*?<\/a>/','', $data['comment']));
				foreach ($related as $id => $row) {
					if (strlen($row['title']) > 3 && stripos($nohtml,$row['title']) !== FALSE)
						$data['comment'] = preg_replace("/\b(".preg_quote($row['title'],'/').")\b/i",'<a href="/snippet/'.$row['snippet_id'].'">$1</a>',$data['comment']);
				}
			} 
			
		}

		$smarty->assign($data);
		$t2 = ($data['grid_reference'])?" in {$data['grid_reference']}":'';
		if ($data['images']) {
			$smarty->assign('page_title',"{$data['title']} [{$data['images']} photos]$t2");
		} else {
			$smarty->assign('page_title',$data['title'].$t2);
		}
	} else {
		$template = 'static_404.tpl';
	}
} else {
	$smarty->assign('snippet_id',$snippet_id);
}
 






$smarty->display($template, $cacheid);

?>
