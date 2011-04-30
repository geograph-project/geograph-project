<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7071 2011-02-04 00:39:05Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

if (!empty($_GET['callback'])) {
	header('Content-type: text/javascript');
} else {
	header('Content-type: application/json');
}

customExpiresHeader(3600);

$data = '';

if (!empty($_GET['tag'])) {
	
	if (isset($_GET['prefix'])) {
		$_GET['tag'] = "{$_GET['prefix']}:{$_GET['tag']}";
	} 
	
	$q = 'tags:"'.str_replace(':',' ',$_GET['tag']).'"';
	
	$sphinx = new sphinxwrapper($q);

	$sphinx->pageSize = $pgsize = 10;

	
	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	//centered results!
	if (!empty($_GET['gid'])) { 
	
		$db = GeographDatabaseConnection(true);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
		$data = $db->getRow("SELECT x,y,wgs84_lat AS `lat`,wgs84_long AS `long` FROM gridimage_search WHERE gridimage_id = ".intval($_GET['gid']));
		
	
		$data['d'] = !empty($_REQUEST['radius'])?floatval($_REQUEST['radius']):100; 
		$data['sort'] = "@geodist ASC, @relevance DESC, @id DESC"; 

		$sphinx->setSort($data['sort']); 
		$sphinx->setSpatial($data); 
	} 


	//lookup images
	$imageids = $sphinx->returnIds($pg,'tagsoup');	

	if (!empty($imageids)) {
		
		$imagelist = new ImageList();
		$imagelist->getImagesByIdList($imageids,"gridimage_id,title,realname,user_id,grid_reference,credit_realname,seq_no,moderation_status,ftf,submitted,imagetaken");
		
		//get tag id list
		$tagids = array();
		foreach ($sphinx->res['matches'] as $row) {
			foreach ($row['attrs']['all_tag_id'] as $t) {
				@$tagids[$t]++;
			}
		}
		arsort($tagids);

		//build a mappping table
		$id2idx = array();
		foreach ($imagelist->images as $idx => $image) {
			$id2idx[$image->gridimage_id]=$idx;
			$imagelist->images[$idx]->tags = array();
			$details = $imagelist->images[$idx]->getThumbnail(120,120,2);
			$imagelist->images[$idx]->imgserver = $details['server'];
			$imagelist->images[$idx]->thumbnail = $details['url'];
		}

		//add the tags to images list!
		if ($taglist = implode(',',array_keys($tagids))) {
			if (empty($db))
				$db = $imagelist->_getDB(true); //to reuse the same connection
		
			$sql = "SELECT gridimage_id,tag,prefix FROM tag INNER JOIN gridimage_tag gt USING (tag_id) WHERE gt.status = 2 AND tag_id IN ($taglist) ORDER BY tag";			

		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			
			$tags = $db->getAll($sql);
			if ($tags) {

				foreach ($tags as $row) {
					if ($idx = $id2idx[$row['gridimage_id']]) {
						unset($row['gridimage_id']);
						if (empty($row['prefix']))
							unset($row['prefix']);
						$imagelist->images[$idx]->tags[] = $row;
					}
				}
			}
		}
		
		
		$data = $imagelist->images;
	}
}



if (!empty($_GET['callback'])) {
        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($data);

if (!empty($_GET['callback'])) {
        echo ");";
}



