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
init_session();

$smarty = new GeographPage;

if (empty($_GET['page']) || preg_match('/[^\w-\.]/',$_GET['page'])) {
	$smarty->display('static_404.tpl');
	exit;
}

$isadmin=$USER->hasPerm('moderator')?1:0;

$template = 'article_article.tpl';
$cacheid = $_GET['page'];
$cacheid .= "|".$USER->hasPerm('moderator')?1:0;
$cacheid .= "--".(isset($_SESSION['article_urls']) && in_array($_GET['page'],$_SESSION['article_urls'])?1:0);


function article_make_table($input) {
	$rows = explode("\n",$input);
	$output = "<table class=\"report\">";
	$c = 1;
	foreach ($rows as $row) {
		$head = 0;
		if (strpos($row,'*') === 0) {
			$row = preg_replace('/^\*/','',$row);
			$output .= "<thead>";
			$head = 1;
		} elseif ($c ==1) {
			$output .= "<tbody>";
		}
		$output .= "<tr>";
		
		$row = preg_replace('/^\| | \|$/','',$row);
		$cells = explode(' | ',$row);
		
		foreach ($cells as $cell) {
			$output .= "<td>$cell</td>";
		}
		
		$output .= "</tr>";
		if ($head) {
			$output .= "</thead>";
			$output .= "<tbody>";
		}
		$c++;
	}
	return $output."</tbody></table>";
}

function smarty_function_articletext($input) {
	$output = preg_replace('/\{image id=(\d+) text=([^\}]+)\}/e',"smarty_function_gridimage(array(id => '\$1',extra => '\$2'))",str_replace("\r",'',$input));

	$output = preg_replace('/(-{7,})\n(.*?)(-{7,})/es',"article_make_table('\$2')",$output);

	$output = str_replace(
		array('[b]','[/b]','[big]','[/big]','[i]','[/i]','[h2]','[/h2]','[h3]','[/h3]','[h4]','[/h4]'),
		array('<b>','</b>','<big>','</big>','<i>','</i>','<h2>','</h2>','<h3>','</h3>','<h4>','</h4>'),
		$output);

	$pattern=array(); $replacement=array();

	$pattern[]="/\[url[=]?\](.+?)\[\/url\]/i";
	$replacement[]="<a href=\"\\1\" target=\"_blank\" ref=\"nofollow\">\\1</a>";

	$pattern[]="/\[url=((f|ht)tp[s]?:\/\/[^<> \n]+?)\](.+?)\[\/url\]/i";
	$replacement[]="<a href=\"\\1\" target=\"_blank\">\\3</a>";

	$pattern[]='/\[img=([^\] ]+)(| [^\]]+)\]/';
	$replacement[]="<img src=\"\$1\" alt=\"$2\" title=\"$2\"/>";

	$pattern[]='/\n\* ([^\n]+)/';
	$replacement[]='<ul style="margin-bottom:0px;margin-top:0px"><li>$1</li></ul>';

	$pattern[]="/\n\n/";
	$replacement[]='<br/><br/>';

	$output=preg_replace($pattern, $replacement, $output);

	return GeographLinks($output,true);
}

$smarty->register_modifier("articletext", "smarty_function_articletext");

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);

	$page = $db->getRow("
	select article.*,realname
	from article 
		left join user using (user_id)
	where ( (licence != 'none' and approved = 1) 
		or user.user_id = {$USER->user_id}
		or $isadmin )
		and url = ".$db->Quote($_GET['page']).'
	limit 1');
	if (count($page)) {
		foreach ($page as $key => $value) {
			$smarty->assign($key, $value);
		}
	} else {
		$template = 'static_404.tpl';
	}
}




$smarty->display($template, $cacheid);

	
?>
