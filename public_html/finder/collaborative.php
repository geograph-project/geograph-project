<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 5068 2008-12-02 02:24:19Z barry $
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



if (empty($CONF['forums'])) {
	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;	
}

init_session();

$smarty = new GeographPage;

if (isset($_GET['login'])) {

	$USER->mustHavePerm("basic");

}


$template = 'finder_human.tpl';

if (isset($_GET['create'])) {
	$template = 'finder_human_create.tpl';
}


if (!empty($_POST['create'])) {

	$USER->mustHavePerm("basic");

	
	$errors = array();

	$square=new GridSquare();
	if (!empty($_POST['grid_reference'])) {
		if ($square->setByFullGridRef($_POST['grid_reference'])) {
			$_POST['grid_reference'] = $square->grid_reference;
		} else {
			$ok=false;
			$errors['grid_reference']=$square->errormsg;
		}
	}

	if (empty($errors) && !empty($_POST['q']) && strlen($_POST['q']) > 4) {
	
		//create a new search
		$db = GeographDatabaseConnection(false);
	
		$ins = "INSERT INTO humansearch SET
			title = ".$db->Quote(@$_POST['title']).",
			q = ".$db->Quote(@$_POST['q']).",
			location = ".$db->Quote(@$_POST['location']).",
			comment = ".$db->Quote(@$_POST['comment']).",
			grid_reference = ".$db->Quote(@$_POST['grid_reference']).",
			notify = ".intval(@$_POST['notify']).",
			ipaddr = INET_ATON('".getRemoteIP()."'),
			user_id = ".intval($USER->user_id).",
			created = NOW(),
			updated = NOW()";

		$db->Execute($ins);
		$smarty->assign("message","Thank you! Your search has been saved.");
		
		$template = 'finder_human.tpl';
	} else {
		$errors["q"] = "Please enter something to search for";
		
		$smarty->assign_by_ref("errors",$errors);
		$smarty->assign_by_ref("item",$_POST);
		
		$smarty->assign("message","Error, please check below...");
	}
	
} elseif (!empty($_GET['gid'])) {
	
	$USER->mustHavePerm("basic");
	
	$db = GeographDatabaseConnection(false);
	
	//report an actual result
	switch($_GET['mode']) {
		case 'report': 
			$id = intval($_GET['id']);
			$gridimage_id = intval($_GET['gid']);
	
			$upd = "UPDATE humansearch_result SET
			status = 'reviewing'
			WHERE search_id = $id
			AND gridimage_id = $gridimage_id
			AND status IN ('ok')";

			if ($db->Execute($upd)) {
				header('HTTP/1.0 204 No Content');
				//no error - so no content!
				
				$smarty->clear_cache('finder_human_results.tpl', $id);
				
				exit;
			}
			
			print "<p>Unable to record your suggestion at this time, sorry.</p>";
			print "<a href=\"{$_SERVER['PHP_SELF']}\" target=\"_top\">Return to search list</a>";
			die();
	
			break;
	}
				
	//add a suggestion to a search	
				
	if (empty($_SESSION['human_id'])) {
		print "<p>Your session seems to expired - we don't know which human search you are answering.</p>";
		print "<a href=\"{$_SERVER['PHP_SELF']}\" target=\"_top\">Return to search list</a>";
		die();
	}
	
	$id = intval($_SESSION['human_id']);
	$query_id = intval($_GET['i']);
	$gridimage_id = intval($_GET['gid']);
	
	$ins = "INSERT INTO humansearch_result SET
		search_id = $id,
		query_id = $query_id,
		page = ".intval(@$_GET['page']).",
		gridimage_id = $gridimage_id,
		ipaddr = INET_ATON('".getRemoteIP()."'),
		user_id = ".intval($USER->user_id).",
		created = NOW()";

	if ($db->Execute($ins)) {
		header('HTTP/1.0 204 No Content');
		//no error - so no content!
		
		$upd = "UPDATE humansearch SET
			images = images + 1,
			status = 'answered',
			last_image = NOW()
			WHERE search_id = $id
			AND status IN ('new','answered')";
						
		$db->Execute($upd);
		
		$smarty->clear_cache('finder_human_results.tpl', $id);
		
		
		exit;
	}

	print "<p>Unable to record your suggestion at this time, sorry.</p>";
	print "<a href=\"{$_SERVER['PHP_SELF']}\" target=\"_top\">Return to search list</a>";
	die();


} elseif (!empty($_GET['id'])) {
	//perform an action on a search
	$db = GeographDatabaseConnection(false);
	$id = intval($_GET['id']);
	
	if (!empty($_GET['mode'])) {
		$smarty->assign("search_id",$id);
		switch($_GET['mode']) {
			case 'report': 
				$upd = "UPDATE humansearch SET
				status = 'reviewing'
				WHERE search_id = $id
				AND status IN ('new','answered')";
				
				$db->Execute($upd);
				$smarty->assign("message","Thank you! Your feedback is appreciated.");
				
				$smarty->clear_cache('finder_human_results.tpl', $id);
				
				break;
			
			case 'answer':
				
				$USER->mustHavePerm("basic");
			
				$_SESSION['human_id'] = $id;
				
				$smarty->display('finder_human_frameset.tpl');
				exit;
				break;
			
			case 'top':
				
				$USER->mustHavePerm("basic");
				
				$row = $db->getRow("
					select hs.*,realname
					from humansearch hs
					left join user using(user_id)
				
					where status in ('new','answered')
					and search_id = $id
					");
					
				$smarty->assign($row);
				
				$smarty->display('finder_human_top.tpl');
				exit;
				break;
				
			case 'export':
				$_SESSION['human_id'] = 0; //otherwise they will continue to use it...
				
				$sql = "select hr.gridimage_id
					from humansearch_result hr
					where status in ('ok')
					and search_id = $id
					order by result_id desc 
					limit 500";
				
				$ids = $db->getCol($sql);
				
				if (!empty($ids)) {
					$ids = implode(',',$ids);
					header("Location: /search.php?markedImages=$ids");
					exit;
				}
				
				break;
	
			case 'results':
			default:
				$_SESSION['human_id'] = 0; //otherwise they will continue to use it...
				
				$template = 'finder_human_results.tpl';
				$cacheid = $id;
				
				if (!$smarty->is_cached($template, $cacheid)) {
					$row = $db->getRow("
						select hs.*,realname
						from humansearch hs
						left join user using(user_id)

						where status in ('new','answered')
						and search_id = $id
						");
										
					$smarty->assign($row);
					
					if (!empty($row)) {
						$sql = "select hr.*,gi.*,user.realname as finder,hr.user_id as finder_id
							from humansearch_result hr
								left join user on(hr.user_id = user.user_id)
								inner join gridimage_search gi using(gridimage_id)
							where status in ('ok')
							and search_id = $id
							order by result_id desc 
							limit 50";

						$imagelist = new ImageList();

						$imagelist->_getImagesBySql($sql);
						$smarty->assign_by_ref('results', $imagelist->images);

					}
				}
				
				$smarty->display($template, $cacheid);
				exit;
				
		
		}
	} 
	
	
	
}

if (empty($db)) {
	$db = GeographDatabaseConnection(true);
}



if ($template == 'finder_human.tpl') {

	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("
	select hs.*,realname
	from humansearch hs
	left join user using(user_id)

	where status in ('new','answered')

	order by updated desc");
	
	$ADODB_FETCH_MODE = $prev_fetch_mode;

	$answered = array();
	$pending = array();
	foreach ($list as $row) {
		if ($row['status'] == 'new') {
			$pending[] = $row;
		} else {
			$answered[] = $row;
		}
	}
	$smarty->assign_by_ref('answered', $answered);
	$smarty->assign_by_ref('pending', $pending);
}


$smarty->display($template);

	

