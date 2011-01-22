<?php
/**
 * $Project: GeoGraph $
 * $Id: ecard.php 3886 2007-11-02 20:14:19Z barry $
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
$template='featured.tpl';
$cacheid = $USER->registered;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/pictureoftheday.class.php');
	$potd=new PictureOfTheDay;
	$potd->assignToSmarty($smarty); 

	$db = GeographDatabaseConnection(false);
	$db2 = GeographDatabaseConnection(true);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	//get a list of features
	$where = ($USER->registered)?"enabled IN('Y','R')":"enabled='Y'";
	$features = $db->getAssoc("SELECT * FROM daily_feature WHERE $where");
	
	$latest_ids = $db->getAssoc("SELECT feature_id,MAX(daily_id) FROM daily_item GROUP BY feature_id");
	
	if ($latest_ids) {
		foreach ($latest_ids as $feature_id => $daily_id) {
			if (empty($features[$feature_id])) {
				//not enabled
				unset($latest_ids[$feature_id]);
			}
		}
		$ids = implode(',',array_values($latest_ids));

		$latest = $db->getAssoc("SELECT feature_id,unique_id,url,title,validfor,created < DATE_SUB(NOW(),INTERVAL validfor HOUR) AS expired FROM daily_item WHERE daily_id IN ($ids) ORDER BY daily_id DESC");
	
	} else {
		$latest = array();
	}

	//check if any need a new item selecting... 
	foreach ($features as $feature_id => $row) {
	
		if (empty($latest[$feature_id]) || $latest[$feature_id]['expired']) {
			$rows = array();
			//create new!

			if (!empty($row['sql_statement'])) {
				
				if (strpos($row['sql_statement'],'$previous')) {
				
					$prev = $db->getCol("SELECT unique_id FROM daily_item WHERE feature_id = $feature_id");
					$values = array();
					if ($prev) {
						foreach ($prev as $value) {
							$values[] = $db->Quote($value);
						}
					} else {
						$values[] = -1; //dummy value (0 is buggy)
					}
					$row['sql_statement'] = str_replace('$previous',implode(',',$values),$row['sql_statement']);
				}
				//use db2/readonly as a safety precaution...
				$rows = $db2->getAll($row['sql_statement']);
				sendNotification($row['sql_statement'],"Notice SQL for $feature_id");
			} elseif (!empty($row['custom_function'])) {
				if (strpos($row['custom_function'],'$previous')) {
								
					$prev = $db->getCol("SELECT unique_id FROM daily_item WHERE feature_id = $feature_id");
					$values = array();
					if ($prev) {
						foreach ($prev as $value) {
							$values[] = $db->Quote($value);
						}
					} else {
						$values[] = -1; //dummy value (0 is buggy)
					}
					$row['custom_function'] = str_replace('$previous',implode(',',$values),$row['custom_function']);
				}
				eval($row['custom_function']); //TODO -- eek -- omg!
				sendNotification($rows,"Notice E for $feature_id");
			}
			
			if ($rows) {
				
				if (count($rows) < 10) {
					sendNotification("Rows Left: ".count($rows),"Running Low! {$row['title']}");
				} 
				
				$updates = $rows[0];
				
				$updates['feature_id'] = $feature_id;
				$updates['validfor'] = $row['validfor'];
				
				$db->Execute('INSERT INTO daily_item SET created=NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
							
				$latest[$feature_id] = $updates;
			} else {
				sendNotification('',"unable to load {$row['title']}");
			}
			
		}
		
		if (!empty($latest[$feature_id])) {
			$latest[$feature_id]['feature'] = $row['title'];
		}
		
		
	}
	
	
	//load specific information for each feature....
	if (empty($latest)) {
		sendNotification('eeek',"unable to load items");
		die("unable to load items");
	} else {
		foreach ($latest as $feature_id => $row) {
			$sql = '';
			$pgsize = 2;
			
			switch ($features[$feature_id]['feature']) {
				case 'GridSquare': 
					//$latest[$feature_id]['thread_id'] = 9224;
					//TODO - find the right post, so can direct to the end of the thread..
					
					$sql = "SELECT * FROM gridimage_search WHERE grid_reference = '{$row['title']}' ORDER BY moderation_status+0 DESC,seq_no LIMIT $pgsize";
					break;
					
				case 'Interesting': 
					
					$sql = "SELECT * FROM gridimage_search WHERE gridimage_id = ".$db->Quote($row['unique_id'])." LIMIT 1";
					break;
					
				case 'Hectad': 
				case 'Myriad': 
					if ($CONF['sphinx_host']) {
						$sphinx = new sphinxwrapper();
						$sphinx->pageSize = $pgsize;
						$pg = 1;

						$sphinx->prepareQuery("@".strtolower($features[$feature_id]['feature'])." {$row['unique_id']}");
						$sphinx->sort = "@random";
						$ids = $sphinx->returnIds($pg,'_images');

						if (!empty($ids) && count($ids) > 0) {
							$where = array();

							$id_list = implode(',',$ids);
							
							$sql = "SELECT * FROM gridimage_search WHERE gridimage_id IN ($id_list) ORDER BY moderation_status+0 DESC,seq_no LIMIT $pgsize";
						} else {
							 sendNotification("Unable to load load ",$sphinx->q);
						}
					} else {
						if ($features[$feature_id]['feature'] == 'Hectad') {
							preg_match('/([A-Z]+\d)(\d)$/',$row['unique_id'],$m);
							$gridref = $m[1].'_'.$m[2].'_';
						} else {
							$gridref = $row['unique_id']."____";
						}
						$sql = "SELECT * FROM gridimage_search WHERE grid_reference LIKE '$gridref' ORDER BY RAND() LIMIT $pgsize";
						
					}
					break;
					
				case 'Article':
					
					$content_id = $db->getOne("SELECT content_id FROM content WHERE foreign_id = {$row['unique_id']} AND source = 'article'");
					
					if ($content_id)
						$sql = "SELECT gi.* FROM gridimage_search gi INNER JOIN gridimage_content USING (gridimage_id) WHERE content_id = $content_id LIMIT $pgsize";
					
					break;
			}
			
			if ($sql) {
				$images = new ImageList();
				$images->_getImagesBySql($sql);

				$latest[$feature_id]['images'] = $images->images;
			}
			
		}

		//send it all to smarty...
		$smarty->assign_by_ref("latest",$latest);
	}
}

$smarty->display($template, $cacheid);


function sendNotification($message,$subject = 'Message') {
	ob_start();
	print "\n\nHost: ".`hostname`."\n\n";
	print_r($_SERVER);
	debug_print_backtrace();
	$con = ob_get_clean();
	mail('geograph@barryhunter.co.uk','[Geograph] Featured - '.$subject,$message."\n\n".$con);
}

function getLinks($extra,$direct=true) {

	$url = "http://www.geographs.org/links/api.php?function=featured&limit=10&experimental=N&internal=Y&site=www.geograph.org.uk&$extra"; //TODO hardcoded.
	
	$str = file_get_contents($url);
	
	$rows = array();
	if (strlen($str) > 5) {
		require_once '3rdparty/JSON.php';
		$json = new Services_JSON();

		$data = $json->decode($str);

		if ($data != 'empty') {
			foreach ($data as $item) {
				$row = array();
				$row['unique_id'] = $item->link_id; 
				$row['url'] = $direct?$item->url:"http://www.geographs.org/links/link.php?id=".$item->link_id; 
				$row['title'] = $item->title; 
				$rows[] = $row;
			}
		}
	}
	return $rows;
}
