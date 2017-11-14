<?php
/**
 * $Project: GeoGraph $
 * $Id: apikeys.php 939 2005-06-29 22:22:57Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
$USER->mustHavePerm("basic");

$template = 'admin_mykey.tpl';
$cacheid = '';


function smarty_block_highlight($params, $content, &$smarty, &$repeat) 
{ 
  return highlight_string(str_replace("\r",'',$content),true);
} 


$smarty->register_block('highlight', 'smarty_block_highlight');


	$db = NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	if (!empty($_GET['apikey'])) {
		//load the info for editing the record
		if ($_GET['apikey'] != '-new-') {
			$arr = $db->GetRow("select *,INET_NTOA(ip) as ip_text from apikeys where enabled = 1 and apikey = ".$db->Quote($_GET['apikey']));
			$smarty->assign($arr);
			
			
			$token=new Token;
			$token->setValue("i", $arr['id']);
			$smarty->assign('access',$token->getToken());
			
			$smarty->assign('shared',md5($CONF['token_secret'].$arr['apikey']));
			
		}
	} 



$smarty->display($template, $cacheid);
	
?>
