<?php
/**
 * $Project: GeoGraph $
 * $Id: article.php 6844 2010-09-17 23:20:32Z barry $
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

if (empty($_GET['page']) || preg_match('/[^\w\.\,-]/',$_GET['page'])) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$smarty->display('static_404.tpl');
	exit;
}


$isadmin=$USER->hasPerm('moderator')?1:0;


$db = GeographDatabaseConnection(false);

$page = $db->getRow("
select url,title,content,update_time
from article 

where ( (licence != 'none' and approved > 0) 
	or user_id = {$USER->user_id}
	or $isadmin )
	and url = ".$db->Quote($_GET['page']).'
limit 1');

function add_image_to_list($id,$thumb ='') {
	global $thumbs,$links;
	if (is_numeric($id)) {
		if ($thumb) {
			$thumbs[$id] = 1;
		} else {
			$links[$id] = 1;
		}
	}
	return '';
}
function count_section($output) {
	global $thumbs,$links;

	print "<div>Characters: ".strlen($output)."</div>";
	print "<div>Lines: ".count(explode("\n",$output))."</div>"; 
	print "<div>Approx word count: ".str_word_count($output)."</div>";

	$big = 0;
	if (preg_match_all('/\[image id=(\d+) text=([^\]]+)\]/',$output,$matches)) 
		$big += count($matches[1]);
	if (preg_match_all('/\[image id=(\d+)\]/',$output,$matches)) 
		$big += count($matches[1]);
	if ($big) 
		print "<div>Big Images: ".$big."</div>";

	$thumbs = $links = array();

	if (preg_replace('/\[\[(\[?)(\w{0,2} ?\d+ ?\d*)(\]?)\]\]/e',"add_image_to_list('\$2','\$1')",$output)) { 
		if (!empty($thumbs))
			print "<div>Thumbnails: ".count($thumbs)."</div>";
		if (!empty($links))
			print "<div>Image Links: ".count($links)."</div>";
	}

	if (preg_match_all('/\[h(\d)\]([^\n]+?)\[\/h(\d)\]/',$output,$matches)) {
		print "<div>Headings: ".count($matches[1])."</div>";
	}
}

if (count($page)) {
	print "<h2>".htmlentities($page['title'])."</h2>";
	print "<div>Last updated: {$page['update_time']}</div>";

	$output = $page['content'];

	
	$pages = preg_split("/\n+\s*~{7,}\s*\n+/",$output);
	
	print "<div>Pages: ".count($pages)."</div>";
	if (count($pages) > 1) {
		print "<div>Characters: ".strlen($output)."</div>"; 
		print "<div>Lines: ".count(explode("\n",$output))."</div>"; 
		print "<div>Approx word count: ".str_word_count($output)."</div>";
		
		foreach ($pages as $idx => $onepage) {

			print "<h3>Page ".($idx+1)."</h3>";
			print "<blockquote>";
			count_section($onepage);
			print "</blockquote>";
		}
		
	} else {
		count_section($output);
	}
	print "<hr/>";
	exit;

} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template = 'static_404.tpl';
}





$smarty->display($template, $cacheid);

