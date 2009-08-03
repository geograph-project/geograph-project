<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php,v 1.2 2006/04/19 10:00:00 barryhunter Exp $
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

$template = "auth.tpl";


$token=new Token;

if (isset($_GET['a']) && $token->parse($_GET['a']) && $token->hasValue('i')) {
	$id = $token->getValue('i');
	
	$db = NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	
	if (!($apikey = $db->GetOne("select apikey from apikeys where enabled = 1 and id = ".$db->Quote($id)))) {
		die("invalid 'API Key', if you are not the developer you should contact them to correct this");
	}	
} else {
	die("invalid 'Access Key', if you are not the developer you should contact them to correct this");
}

$token=new Token;
$token->magic = md5($CONF['token_secret'].$apikey);

if (isset($_GET['t']) && $token->parse($_GET['t']) && $token->hasValue('callback')) {
   	$callback = $token->getValue('callback');
   	$action = $token->getValue('action');
   	$smarty->assign('callback',$callback);
   	$smarty->assign('action',$action);
   	   	
   	$token=new Token;
	$token->magic = md5($CONF['token_secret'].$apikey);
   	$token->setValue("k", $apikey); //just to prove to THEM we know who they are
  	$token->setValue("user_id", $USER->user_id);
  	$token->setValue("realname", $USER->realname);
  	if (!empty($USER->nickname)) {
  		$token->setValue("nickname", $USER->nickname);
  	}
   	$final_url = "$callback?t=".$token->getToken();
   	$smarty->assign('final_url',$final_url);
} else {
	die("invalid request, if you are not the developer you should contact them to correct this");
}


$smarty->display($template);


?>
