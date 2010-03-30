<?php
ini_set("display_errors",1);
/**
 * $Project: GeoGraph $
 * $Id: cities.php 5785 2009-09-12 10:06:29Z barry $
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
require_once('geograph/mapmosaic.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$smarty = new GeographPage;
	

$template='explore_rivers.tpl';
$cacheid='';

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

	
	$smarty->assign("page_title", "Prime Rivers of Great Britain");
		
	
	$raw = $db->GetAll("SELECT poster_id,post_text FROM geobb_posts WHERE topic_id = 11785 ORDER BY post_id");
	$results = $users = array();
	foreach ($raw as $i => $row) {
	
		if (preg_match_all('/\[\[\[(\d+)\]\]\]/',$row['post_text'],$g_matches)) {
			
			preg_match('/<b>([\w ,-]+)<\/b>/',$row['post_text'],$name) ;
			
			if (empty($name)) {
				preg_match('/^(River [\w ,-]+)<br/',$row['post_text'],$name) ;
						
			}
			
			if (($ids = $g_matches[1]) && !empty($name)) {
			
			
				$result = array();

				list($result['name'],$result['county']) = explode(', ',$name[1],2);
				$result['hash'] = str_replace(' ','-',trim(preg_replace('/[^\w \-]+/','',strtolower($result['name']))));
				
				if (!empty($result['county'])) {
					$result['q'] = "({$result['name']}) | ({$result['county']} {$result['name']})";
				} else {
					$result['q'] = $result['name'];
				}
				
				$sql = "select gridimage_id,user_id,realname,title,grid_reference from gridimage_search where gridimage_id in (".implode(',',$ids).")";

				$images = $db->getAll($sql);
				
				foreach ($images as $idx => $image) {
					$gridimage=new GridImage;
					$gridimage->fastInit($image);

					$result['images'][] = $gridimage;
				}
				$results[] = $result;
				$users[] = $row['poster_id'];
			}
			
		}
	}
		
	$sql = "select user_id,realname,nickname from user where user_id in (".implode(',',$users).")";
	$user_rows = $db->getAll($sql);
	$bits = array();
	foreach ($user_rows as $row) {
		$bits[] = "<a href=\"/profile/{$row['user_id']}\">".htmlentities($row['realname'])."</a>";
	}
	$extra_info = "Compiled by ".implode(', ',array_slice($bits,0,count($bits)-1))." and ".array_pop($bits);			

	$smarty->assign_by_ref("results", $results);	
	$smarty->assign_by_ref("extra_info", $extra_info);	
}


$smarty->display($template, $cacheid);

