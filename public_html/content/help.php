<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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

##init_session();
init_session_or_cache(3600*3, 900); //cache publically, and privately

$smarty = new GeographPage;

pageMustBeHTTPS();

$template = 'content_help.tpl';
$cacheid = '';

##$data = $db->getRow("show table status like 'content'");

//when this table was modified
##$mtime = strtotime($data['Update_time']);
	
##//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
##customCacheControl($mtime,$cacheid,($USER->user_id == 0));

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;



$cats = array();
$cats[] = 'New Contributors';
$cats[] = 'Contributors';
$cats[] = 'General Website';
$cats[] = 'Image Searching';
$cats[] = 'Project';
$cats[] = 'Technical';
//$cats[] = 'Outdated'; //dont get rendered as own tab!
//$cats[] = 'Obsolete';
$cats[] = 'All Links';
	$smarty->assign_by_ref('cats', $cats);

	$data = $db->getAll("
select content_id,source, c.url,c.title,c.extract
 ,level, answer_id
 ,group_concat(distinct category) as categories
from content c
 left join answer_answer on (source = 'faq' and answer_id = foreign_id)
 left join content_cat using (content_id)
where type = 'document'
group by content_id
having categories is not null
order by source,level,content_id desc");


	$data = array_reverse($data); //work though in reverse order, as want to make sure add some to the end!
	$list = array();
	//Obsolete always at the end
	foreach($data as $idx => $row) {
		if (strpos($row['categories'], 'Obsolete') !== FALSE) {
			array_unshift($list, $row);
			unset($data[$idx]);
		}
	}
	//then Technical is next to last!
	foreach($data as $idx => $row) {
		if (strpos($row['categories'], 'Technical') !== FALSE) {
			array_unshift($list, $row);
			unset($data[$idx]);
		}
	}
	//then links!
	foreach($data as $idx => $row) {
		if ($row['source'] == 'link') {
			array_unshift($list, $row);
			unset($data[$idx]);
		}
	}
	//then then faq
	foreach($data as $idx => $row) {
		if ($row['source'] == 'link') {
			array_unshift($list, $row);
			unset($data[$idx]);
		}
	}
	//and finally everythign else (which are the most important!)
	foreach($data as $idx => $row) {
		array_unshift($list, $row);
	}

	$smarty->assign_by_ref('list', $list);

	$faq = $db->getAssoc("select answer_id,title,content from answer_answer where status = 1");
	foreach($faq as $idx => $row)
		$faq[$idx]['content'] = latin1_to_utf8($row['content']);

	$smarty->assign('answers', json_encode($faq));
}

$smarty->display($template, $cacheid);



