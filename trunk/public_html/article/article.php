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


$template = 'article_article.tpl';
$cacheid = $_GET['page'];


function smarty_function_articletext($input) {
	$output = preg_replace('/\{image id=(\d+) text=([^\}]+)\}/e',"smarty_function_gridimage(array(id => '\$1',extra => '\$2'))",str_replace("\r",'',$input));

	$output = str_replace(
		array('[b]','[/b]','[big]','[/big]','[i]','[/i]','[h2]','[/h2]','[h3]','[/h3]'),
		array('<b>','</b>','<big>','</big>','<i>','</i>','<h3>','</h3>','<h4>','</h4>'),
		$output);
		
	$output = preg_replace('/\n\* ([^\n]+)/','<ul style="margin-bottom:0px;margin-top:0px"><li>$1</li></ul>',$output);
		
	$output = preg_replace("/\n\n/",'<br/><br/>',$output);
	
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
	where licence != 'none' 
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
