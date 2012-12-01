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

if (empty($CONF['forums'])) {
	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;	
}

/*
if ((!preg_match('/\/project\/\d+/',$_SERVER["REQUEST_URI"]) && isset($_GET['id'])) || strlen($_GET['id']) !== strlen(intval($_GET['id']))) {
        //keep urls nice and clean - esp. for search engines!
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");
        header("Location: /project/".intval($_GET['id']));
        print "<a href=\"http://{$_SERVER['HTTP_HOST']}/project/".intval($_GET['id'])."\">View project entry</a>";
        exit;
}
*/

init_session();

$smarty = new GeographPage;

if (empty($_GET['id']) || preg_match('/[^\d]/',$_GET['id'])) {
	$smarty->display('static_404.tpl');
	exit;
}

$project_id = intval($_GET['id']);

$db=NewADOConnection($GLOBALS['DSN']);

$USER->mustHavePerm('basic');
$isadmin=$USER->hasPerm('moderator')?1:0;




$template = 'project_entry.tpl';
$cacheid = $project_id;



$sql_where = " project_id = ".$db->Quote($project_id);

$page = $db->getRow("
select project.*,realname
from project 
	left join user using (user_id)
where $sql_where
limit 1");

if (count($page)) {
	
	if ($page['approved'] == -1 && !$USER->hasPerm('moderator')) {
		header("HTTP/1.0 403 Forbidden");
		header("Status: 403 Forbidden");
		$template = "static_404.tpl";
	}

	if ($page['user_id'] == $USER->user_id) {
		$cacheid .= '|'.$USER->user_id;
	}
	
	//when this page was modified
	$mtime = strtotime($page['updated']);
		
	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$cacheid,($USER->user_id == 0));

                if (!isset($_GET['dontcount']) && $CONF['template']!='archive'
                        && (stripos($_SERVER['HTTP_USER_AGENT'], 'http')===FALSE)
                        && (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')===FALSE)
                        && (strpos($_SERVER['HTTP_USER_AGENT'], 'Web Preview')===FALSE)
                        ) {
                        $db->Execute("UPDATE LOW_PRIORITY project SET views=views+1,updated=updated WHERE project_id = ".$page['project_id']);
                }

}

if (!$smarty->is_cached($template, $cacheid))
{
	if (count($page)) {
		$smarty->assign($page);
		if (!empty($page['extract'])) {
			$smarty->assign('meta_description', $page['description']);
		}
		
		$links = $db->getAll("SELECT * FROM project_link WHERE project_id = {$page['project_id']} ORDER BY project_link_id");
		$smarty->assign_by_ref('links', $links);
		
		$registers = $db->getAll("SELECT r.*,realname FROM project_register r INNER JOIN user USING (user_id) WHERE project_id = {$page['project_id']} ORDER BY project_register_id");
		$smarty->assign_by_ref('registers', $registers);
		
	} else {
		$template = 'static_404.tpl';
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
	}
} else {
	$smarty->assign('user_id', $page['user_id']);
}


$smarty->assign('project_id', $page['project_id']);

$smarty->display($template, $cacheid);
