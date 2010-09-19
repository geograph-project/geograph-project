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

function showmessage($count,$points,$soft,$hard) {
	global $total;
	$total += ($count*$points);
	
	if ($count > $hard) {
		return " <span class=hard>WARNING: This number is too high - you should split this page into multiple</span>";
	} elseif ($count > $soft) {
		return " <span class=soft>NOTICE: This number is rather high - you should consider splitting this page into multiple pages</span>";
	}
}

function count_section($output) {
	global $thumbs,$links,$total;
	$total = 0;

	print "<div>Characters: ".strlen($output)."</div>";
	print "<div>Lines: ".count(explode("\n",$output))."</div>"; 
	print "<div>Approx word count: ".str_word_count($output)."</div>";

	$big = 0;
	if (preg_match_all('/\[image id=(\d+) text=([^\]]+)\]/',$output,$matches)) 
		$big += count($matches[1]);
	if (preg_match_all('/\[image id=(\d+)\]/',$output,$matches)) 
		$big += count($matches[1]);
	if ($big) 
		print "<div>Big Images: ".$big.showmessage($big,3,50,75)."</div>";

	$thumbs = $links = array();

	if (preg_replace('/\[\[(\[?)(\w{0,2} ?\d+ ?\d*)(\]?)\]\]/e',"add_image_to_list('\$2','\$1')",$output)) { 
		if (!empty($thumbs))
			print "<div>Thumbnails: ".count($thumbs).showmessage(count($thumbs),1,100,200)."</div>";
		if (!empty($links))
			print "<div>Image Links: ".count($links).showmessage(count($links),0.3,200,400)."</div>";
	}

	if (preg_match_all('/\[map *([STNH]?[A-Z]{1}[ \.]*\d{2,5}[ \.]*\d{2,5})( \w+|)\]/',$output,$matches))
		print "<div>Big Maps: ".count($matches[1]).showmessage(count($matches[1]),3,25,50)."</div>";

	if (preg_match_all('/\[smallmap *([STNH]?[A-Z]{1}[ \.]*\d{2,5}[ \.]*\d{2,5})( \w+|)\]/',$output,$matches))
		print "<div>Small Maps: ".count($matches[1]).showmessage(count($matches[1]),1,50,100)."</div>";

	if (preg_match_all('/\[youtube=(\w+)\]/',$output,$matches))
		print "<div>YouTube Videos: ".count($matches[1]).showmessage(count($matches[1]),30,5,10)."</div>";

	if (preg_match_all('/\[mooflow=(\w+)\]/',$output,$matches))
		print "<div>MooFlow Embeds: ".count($matches[1]).showmessage(count($matches[1]),50,1,4)."</div>";

	if (preg_match_all('/\[img=([^\] ]+)(| [^\]]+)\]/',$output,$matches))
		print "<div>External Images: ".count($matches[1]).showmessage(count($matches[1]),2,20,40)."</div>";

	if (preg_match_all('/\[h(\d)\]([^\n]+?)\[\/h(\d)\]/',$output,$matches))
		print "<div>Headings: ".count($matches[1])."</div>";
	
	print "<!--div>Points: ".$total."</div-->";
	if ($total > 400) {
		print "<div class=hard>WARNING: The number of objects on this page is too high, you should split into multiple pages</div>";
	} elseif ($total > 200) {
		print "<div class=soft>NOTICE: The number of objects on this page is rather high - you should consider splitting this page into multiple pages</div>";
	}
}
?>
<style type="text/css">
.hard {
	font-weight: bold;
	background-color:red;
	color:white;
	padding:2px;
}
.soft {
	font-weight: bold;
	background-color:pink;
	padding:2px;
}
</style>
<?
if (count($page)) {
	print "<h2>".htmlentities($page['title'])."</h2>";
	print "<div>Last updated: {$page['update_time']}</div>";

	$output = $page['content'];

	//break counting of demos
	$output = preg_replace('/\!(\[+)/e','str_repeat("¬",strlen("$1"))',$output);

	
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

